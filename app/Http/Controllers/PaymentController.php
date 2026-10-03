<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Status;
use App\Models\User;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $query = Payment::query();

        if ($user->isCustomer()) {
            $query->where('created_by', $user->id);
        } elseif (!$user->isSuperAdmin() && !$user->isStoreAdmin() && !$user->isCashier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized Access.'], 403);
        }

        if ($request->has('method')) {
            $query->where('method', $request->input('method'));
        }
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('transaction_id', 'LIKE', "%{$search}%")
                  ->orWhereHas('order', function($q) use ($search) {
                      $q->where('receipt_number', 'LIKE', "%{$search}%");
                  });
        }

        $perPage = $request->input('per_page', 10);
        $payments = $query->with(['order', 'status', 'creator'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully.',
            'data' => PaymentResource::collection($payments),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ]
        ], 200);
    }

    /**
     * Process checkout payment execution supporting all transactional streams.
     */
    public function processPayment(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'fulfillment_type' => 'required|in:shipping,pickup,pos',
            'method' => 'required|in:cash_on_delivery,stripe,khqr,pay_at_counter,aba_payway',
            'stripe_token' => 'required_if:method,stripe|string',
            'received_amount' => 'required_if:method,pay_at_counter|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $order = Order::findOrFail($request->order_id);

        if ($user->isCustomer() && (int)$order->created_by !== (int)$user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized order reference.'], 403);
        }

        return DB::transaction(function () use ($request, $user, $order) {
            $method = $request->input('method');
            $transactionId = null;

            $statusPending = Status::where('type', 'payment')->where('value', 'pending')->first();
            $statusPaid = Status::where('type', 'payment')->where('value', 'paid')->first();
            $statusFailed = Status::where('type', 'payment')->where('value', 'failed')->first();

            $assignedStatusId = $statusPending ? $statusPending->id : null;
            $additionalMeta = [];

            // ==========================================
            // PATHWAY 1: PAY AT COUNTER (Cashier Flow)
            // ==========================================
            if ($method === 'pay_at_counter') {
                $received = (float) $request->input('received_amount');
                $total = (float) $order->total_amount;

                if ($received < $total) {
                    return response()->json(['success' => false, 'message' => 'Insufficient cash received.'], 400);
                }

                $transactionId = 'counter_tx_' . time();
                $assignedStatusId = $statusPaid ? $statusPaid->id : null;

                $additionalMeta['cash_calculations'] = [
                    'cash_received' => $received,
                    'change_due' => round($received - $total, 2),
                ];
            }
            // ==========================================
            // PATHWAY 2: CASH ON DELIVERY (COD Flow — e-commerce only)
            // ==========================================
            elseif ($method === 'cash_on_delivery') {
                $transactionId = 'cod_track_' . time();
                $assignedStatusId = $statusPending ? $statusPending->id : null;
            }
            // ==========================================
            // PATHWAY 3: STRIPE GATEWAY (sandbox mock)
            // ==========================================
            elseif ($method === 'stripe') {
                try {
                    $token = $request->input('stripe_token');

                    if ($token === 'tok_chargeDeclined' || $token === 'fail') {
                        throw new \Exception("Card validation failed inside Stripe Sandbox.");
                    }

                    $transactionId = 'ch_' . Str::random(24);
                    $assignedStatusId = $statusPaid ? $statusPaid->id : null;
                } catch (\Exception $e) {
                    $assignedStatusId = $statusFailed ? $statusFailed->id : null;
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
                }
            }
            // ==========================================
            // PATHWAY 4: ABA PAYWAY GATEWAY (sandbox)
            // ==========================================
            elseif ($method === 'aba_payway') {
                $transactionId = 'ABATX' . time() . strtoupper(Str::random(4));
                // Sandbox: mark paid immediately so the order can complete.
                $assignedStatusId = $statusPaid ? $statusPaid->id : null;

                // Real ABA PayWay checkout payload shape (for a genuine hosted-page
                // redirect integration later — not used by the sandbox POS UI).
                $merchantId = env('ABA_PAYWAY_MERCHANT_ID', 'default_merchant');
                $apiKey = env('ABA_PAYWAY_API_KEY', 'default_key');
                $apiUrl = env('ABA_PAYWAY_API_URL', 'https://ababank.com');
                $amount = number_format((float) $order->total_amount, 2, '.', '');
                $hashStr = $merchantId . $transactionId . $amount;
                $hashSignature = base64_encode(hash_hmac('sha512', $hashStr, $apiKey, true));

                $additionalMeta['aba_checkout_payload'] = [
                    'api_url' => $apiUrl,
                    'merchant_id' => $merchantId,
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'hash' => $hashSignature,
                    'firstname' => $user->name,
                    'email' => $user->email,
                ];

                // Cambodia's interbank KHQR standard is what ABA's own checkout
                // renders too, so the POS shows the same standards-shaped QR here.
                $additionalMeta['sandbox_payment_helper'] = $this->buildKhqrPayload(
                    $order,
                    $transactionId,
                    'Sandbox Merchant (ABA PayWay)',
                );
            }
            // ==========================================
            // PATHWAY 5: KHQR (Bakong)
            // ==========================================
            elseif ($method === 'khqr') {
                $transactionId = 'bakong_tx_' . time() . Str::upper(Str::random(4));
                $assignedStatusId = $statusPaid ? $statusPaid->id : null;

                $additionalMeta['sandbox_payment_helper'] = $this->buildKhqrPayload(
                    $order,
                    $transactionId,
                    'Sandbox Merchant (KHQR)',
                );
            }

            // 2. Map Payment database tracking entry
            $payment = Payment::create([
                'order_id' => $order->id,
                'transaction_id' => $transactionId,
                'status_id' => $assignedStatusId,
                'fulfillment_type' => $request->fulfillment_type,
                'method' => $method,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // 3. Move the order to "completed" once payment has actually settled.
            if ($assignedStatusId === ($statusPaid ? $statusPaid->id : null)) {
                $orderPaidStatus = Status::where('type', 'order')->where('value', 'completed')->first();
                if ($orderPaidStatus) {
                    $order->update(['status_id' => $orderPaidStatus->id]);
                }
            }

            $payment->load(['order', 'status', 'creator']);

            $responsePayload = [
                'success' => true,
                'message' => 'Payment transaction processed successfully.',
                'data' => new PaymentResource($payment),
            ];

            if (!empty($additionalMeta)) {
                $responsePayload = array_merge($responsePayload, $additionalMeta);
            }

            return response()->json($responsePayload, 201);
        });
    }

    /**
     * Build a standards-shaped Bakong KHQR / EMVCo QR Code payload: correct
     * TLV tag/length/value encoding plus a real CRC-16/CCITT-FALSE checksum
     * (polynomial 0x1021, init 0xFFFF) — the part that makes a QR code
     * structurally valid to any EMVCo-compliant scanner, as opposed to just
     * a fixed placeholder string.
     *
     * This is SANDBOX ONLY: the merchant account handle below is a clearly
     * fake placeholder, not a real registered Bakong account, so nothing
     * here can move real money — it just makes the demo QR scan and parse
     * correctly like a genuine one would.
     */
    private function buildKhqrPayload(
        Order $order,
        string $transactionId,
        string $merchantName = 'Sandbox Merchant',
        string $merchantCity = 'Phnom Penh',
        string $currencyCode = '840' // 840 = USD, 116 = KHR (ISO 4217 numeric)
    ): array {
        $amount = number_format((float) $order->total_amount, 2, '.', '');

        // Merchant Account Info (tag 29): GUID sub-tag 00 identifies the
        // scheme (Bakong), sub-tag 01 carries the account handle.
        $merchantAccountInfo =
            $this->khqrTlv('00', 'kh.gov.nbc.bakong') .
            $this->khqrTlv('01', 'sandbox.thesis@bakong');

        $payload =
            $this->khqrTlv('00', '01') .                              // Payload Format Indicator
            $this->khqrTlv('01', '12') .                              // Point of Initiation: dynamic (amount present)
            $this->khqrTlv('29', $merchantAccountInfo) .              // Merchant Account Info
            $this->khqrTlv('52', '5999') .                            // Merchant Category Code (generic retail)
            $this->khqrTlv('53', $currencyCode) .                     // Transaction Currency
            $this->khqrTlv('54', $amount) .                           // Transaction Amount
            $this->khqrTlv('58', 'KH') .                              // Country Code
            $this->khqrTlv('59', substr($merchantName, 0, 25)) .      // Merchant Name
            $this->khqrTlv('60', $merchantCity) .                     // Merchant City
            $this->khqrTlv('62', $this->khqrTlv('01', substr($transactionId, 0, 25))); // Bill number

        // The CRC (tag 63) must be computed over the payload plus the "6304"
        // tag+length prefix of the CRC field itself, per the EMVCo spec.
        $withCrcPrefix = $payload . '6304';
        $crc = $this->crc16Ccitt($withCrcPrefix);
        $khqrString = $withCrcPrefix . $crc;

        return [
            'khqr_string' => $khqrString,
            'deeplink_url' => 'bakong://pay?uuid=' . $transactionId . '&amount=' . $amount,
            'merchant_name' => $merchantName,
            'merchant_city' => $merchantCity,
            'currency' => $currencyCode === '116' ? 'KHR' : 'USD',
            'amount' => $amount,
        ];
    }

    /** EMVCo TLV: 2-digit tag + 2-digit zero-padded length + value. */
    private function khqrTlv(string $tag, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /** CRC-16/CCITT-FALSE (poly 0x1021, init 0xFFFF) — the checksum EMVCo QR codes require. */
    private function crc16Ccitt(string $data): string
    {
        $crc = 0xFFFF;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if (($crc & 0x8000) !== 0) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /**
     * Display a specific payment transaction record summary layout.
     * GET /api/payments/{id}
     */
    public function show(Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $payment->load(['order']);

        if ($user->isCustomer()) {
            $isOrderOwner = (int)$payment->order->created_by === (int)$user->id;

            if (!$isOrderOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access Denied. You do not own this billing summary statement.'
                ], 403);
            }
        }

        $payment->load(['status', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Payment record details loaded successfully.',
            'data' => new PaymentResource($payment)
        ], 200);
    }

    /**
     * Administrative Settlement tool for Cashiers closing out COD tracking entries.
     */
    public function settleCODPayment(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied.'
            ], 403);
        }

        if ($payment->method !== 'cash_on_delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Target profile is not a COD entry.'
            ], 400);
        }

        $statusPaid = Status::where('type', 'payment')->where('value', 'paid')->first();

        DB::transaction(function () use ($payment, $user, $statusPaid) {
            $payment->update([
                'status_id'  => $statusPaid ? $statusPaid->id : null,
                'updated_by' => $user->id
            ]);

            $orderPaidStatus = Status::where('type', 'order')->where('value', 'completed')->first();

            if ($orderPaidStatus && $payment->order) {
                $payment->order->update([
                    'status_id'  => $orderPaidStatus->id,
                    'updated_by' => $user->id
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'COD transaction marked as successfully settled.'
        ], 200);
    }
}