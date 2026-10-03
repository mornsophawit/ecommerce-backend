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
     * Process checkout payment execution supporting 5 distinct payment pathways.
     */
    public function processPayment(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'fulfillment_type' => 'required|in:shipping,pickup',
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
                    'change_due' => $received - $total,
                ];
            } 
            // ==========================================
            // PATHWAY 2: CASH ON DELIVERY (COD Flow)
            // ==========================================
            elseif ($method === 'cash_on_delivery') {
                $transactionId = 'cod_track_' . time();
                $assignedStatusId = $statusPending ? $statusPending->id : null;
            } 
            // ==========================================
            // PATHWAY 3: STRIPE GATEWAY
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
            // PATHWAY 4: ABA PAYWAY GATEWAY
            // ==========================================
            elseif ($method === 'aba_payway') {
                $transactionId = 'ABATX' . time() . strtoupper(Str::random(4));
                $assignedStatusId = $statusPending ? $statusPending->id : null;

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

                // Sandbox QR (same shape as KHQR so the POS can show the card)
                $merchantName = 'Ecommerce Store';
                $merchantNameLen = str_pad(strlen($merchantName), 2, '0', STR_PAD_LEFT);
                $amountStr = $amount;
                $amountLen = str_pad(strlen($amountStr), 2, '0', STR_PAD_LEFT);
                // 840 = USD, 116 = KHR
                $currencyTag = '5303840'; // USD

                $additionalMeta['sandbox_payment_helper'] = [
                    'khqr_string' =>
                        '00020101021230380012KHQRMerchant12030612345652045999' .
                        $currencyTag .
                        '54' . $amountLen . $amountStr .
                        '5802KH' .
                        '59' . $merchantNameLen . $merchantName .
                        '6010Phnom Penh' .
                        '6304A1B2',
                    'deeplink_url' => 'bakong://pay?uuid=' . $transactionId . '&amount=' . $amount,
                ];
            }
            // ==========================================
            // PATHWAY 5: KHQR (Bakong Fallback Flow)
            // ==========================================
            elseif ($method === 'khqr') {
                $transactionId = 'bakong_tx_' . time() . Str::upper(Str::random(4));
                $assignedStatusId = $statusPending ? $statusPending->id : null;

                $merchantName = 'Ecommerce Store';
                $merchantNameLen = str_pad(strlen($merchantName), 2, '0', STR_PAD_LEFT);
                $amountStr = number_format((float) $order->total_amount, 2, '.', '');
                $amountLen = str_pad(strlen($amountStr), 2, '0', STR_PAD_LEFT);
                // 840 = USD  (change to 5303116 if you ever want KHR)
                $currencyTag = '5303840';

                $additionalMeta['sandbox_payment_helper'] = [
                    'khqr_string' =>
                        '00020101021230380012KHQRMerchant12030612345652045999' .
                        $currencyTag .
                        '54' . $amountLen . $amountStr .
                        '5802KH' .
                        '59' . $merchantNameLen . $merchantName .
                        '6010Phnom Penh' .
                        '6304A1B2',
                    'deeplink_url' => 'bakong://pay?uuid=' . $transactionId . '&amount=' . $amountStr,
                ];
            }
            // // ==========================================
            // // PATHWAY 4: ABA PAYWAY GATEWAY
            // // ==========================================
            // elseif ($method === 'aba_payway') {
            //     $transactionId = 'ABATX' . time() . strtoupper(Str::random(4));
            //     $assignedStatusId = $statusPending ? $statusPending->id : null;

            //     $merchantId = env('ABA_PAYWAY_MERCHANT_ID', 'default_merchant');
            //     $apiKey = env('ABA_PAYWAY_API_KEY', 'default_key');
            //     $apiUrl = env('ABA_PAYWAY_API_URL', 'https://ababank.com');

            //     $amount = number_format($order->total_amount, 2, '.', '');
                
            //     $hashStr = $merchantId . $transactionId . $amount;
            //     $hashSignature = base64_encode(hash_hmac('sha512', $hashStr, $apiKey, true));

            //     $additionalMeta['aba_checkout_payload'] = [
            //         'api_url' => $apiUrl,
            //         'merchant_id' => $merchantId,
            //         'transaction_id' => $transactionId,
            //         'amount' => $amount,
            //         'hash' => $hashSignature,
            //         'firstname' => $user->name,
            //         'email' => $user->email,
            //     ];
            // } 
            // // ==========================================
            // // PATHWAY 5: KHQR (Bakong Fallback Flow)
            // // ==========================================
            // elseif ($method === 'khqr') {
            //     $transactionId = 'bakong_tx_' . time() . Str::upper(Str::random(4));
            //     $assignedStatusId = $statusPending ? $statusPending->id : null;
                
            //     $additionalMeta['sandbox_payment_helper'] = [
            //         'khqr_string' => "00020101021230380012KHQRMerchant1203061234565204599953031165404" . $order->total_amount . "5802KH5916Ecommerce Store6010Phnom Penh6304A1B2",
            //         'deeplink_url' => "bakong://pay?uuid=" . $transactionId . "&amount=" . $order->total_amount,
            //     ];
            // }

            // // 2. Map Payment database tracking entry
            // $payment = Payment::create([
            //     'order_id' => $order->id,
            //     'transaction_id' => $transactionId,
            //     'status_id' => $assignedStatusId,
            //     'fulfillment_type' => $request->fulfillment_type,
            //     'method' => $method,
            //     'created_by' => $user->id,
            //     'updated_by' => $user->id,
            // ]);

            // // 3. Shift order state logic cleanly if checkout settled completely upfront
            // if ($assignedStatusId === ($statusPaid ? $statusPaid->id : null)) {
            //     $orderPaidStatus = Status::where('type', 'order')->where('value', 'completed')->first();
            //     if ($orderPaidStatus) {
            //         $order->update(['status_id' => $orderPaidStatus->id]);
            //     }
            // }

            // $payment->load(['order', 'status', 'creator']);
            
            // $responsePayload = [
            //     'success' => true,
            //     'message' => 'Payment transaction processed successfully.',
            //     'data' => new PaymentResource($payment)
            // ];

            // if (!empty($additionalMeta)) {
            //     $responsePayload = array_merge($responsePayload, $additionalMeta);
            // }

            // return response()->json($responsePayload, 201);
        // }
        });
    }

    public function confirmSandboxPayment(Request $request, $orderId): JsonResponse
    {
        $order = Order::findOrFail($orderId);
        $statusPaid = Status::where('type', 'payment')->where('value', 'paid')->first();
        $orderCompleted = Status::where('type', 'order')->where('value', 'completed')->first();

        $payment = Payment::where('order_id', $order->id)->latest()->first();
        if ($payment && $statusPaid) {
            $payment->update(['status_id' => $statusPaid->id]);
        }
        if ($orderCompleted) {
            $order->update(['status_id' => $orderCompleted->id]);
        }

        return response()->json(['success' => true, 'message' => 'Sandbox payment confirmed.']);
    }

    /**
     * Display a specific payment.
     */
    public function show(Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer() && (int)$payment->created_by !== (int)$user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        $payment->load(['order', 'status', 'creator']);
        return response()->json([
            'success' => true,
            'message' => 'Payment record details loaded.',
            'data' => new PaymentResource($payment)
        ], 200);
    }

        /**
     * Administrative Settlement tool for Cashiers closing out COD tracking entries.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function settleCODPayment(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Enforce administrative role protection boundaries
        if ($user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied.'
            ], 403);
        }

        // 2. Prevent illegal processing state actions on non-COD entries
        if ($payment->method !== 'cash_on_delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Target profile is not a COD entry.'
            ], 400);
        }

        // 3. Verify target transaction lookup records exist before modifications
        $statusPaid = Status::where('type', 'payment')->where('value', 'paid')->first();

        DB::transaction(function () use ($payment, $user, $statusPaid) {
            // Update the immediate tracking register entry
            $payment->update([
                'status_id'  => $statusPaid ? $statusPaid->id : null,
                'updated_by' => $user->id
            ]);

            // Shift parent order collection context properties to completed state 
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




// backup 25/9/26
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
            // Fixed: Added 'pos' to allow real-time register checkouts to settle cleanly
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
                    'change_due' => $received - $total,
                ];
            } 
            // ==========================================
            // PATHWAY 2: CASH ON DELIVERY (COD Flow)
            // ==========================================
            elseif ($method === 'cash_on_delivery') {
                $transactionId = 'cod_track_' . time();
                $assignedStatusId = $statusPending ? $statusPending->id : null;
            } 
            // ==========================================
            // PATHWAY 3: STRIPE GATEWAY
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
            // // ==========================================
            // // PATHWAY 4: ABA PAYWAY GATEWAY
            // // ==========================================
            // elseif ($method === 'aba_payway') {
            //     $transactionId = 'ABATX' . time() . strtoupper(Str::random(4));
            //     $assignedStatusId = $statusPending ? $statusPending->id : null;

            //     $merchantId = env('ABA_PAYWAY_MERCHANT_ID', 'default_merchant');
            //     $apiKey = env('ABA_PAYWAY_API_KEY', 'default_key');
            //     $apiUrl = env('ABA_PAYWAY_API_URL', 'https://ababank.com');

            //     $amount = number_format($order->total_amount, 2, '.', '');
                
            //     $hashStr = $merchantId . $transactionId . $amount;
            //     $hashSignature = base64_encode(hash_hmac('sha512', $hashStr, $apiKey, true));

            //     $additionalMeta['aba_checkout_payload'] = [
            //         'api_url' => $apiUrl,
            //         'merchant_id' => $merchantId,
            //         'transaction_id' => $transactionId,
            //         'amount' => $amount,
            //         'hash' => $hashSignature,
            //         'firstname' => $user->name,
            //         'email' => $user->email,
            //     ];
            // } 
            // // ==========================================
            // // PATHWAY 5: KHQR (Bakong Fallback Flow)
            // // ==========================================
            // elseif ($method === 'khqr') {
            //     $transactionId = 'bakong_tx_' . time() . Str::upper(Str::random(4));
            //     // Tip: If you want online digital KHQR scans to map immediately as 'paid', change this fallback assignment index pointer:
            //     $assignedStatusId = $statusPaid ? $statusPaid->id : ($statusPending ? $statusPending->id : null);
                
            //     $additionalMeta['sandbox_payment_helper'] = [
            //         'khqr_string' => "00020101021230380012KHQRMerchant1203061234565204599953031165404" . $order->total_amount . "5802KH5916Ecommerce Store6010Phnom Penh6304A1B2",
            //         'deeplink_url' => "bakong://pay?uuid=" . $transactionId . "&amount=" . $order->total_amount,
            //     ];
            // }

            // // 2. Map Payment database tracking entry
            // $payment = Payment::create([
            //     'order_id' => $order->id,
            //     'transaction_id' => $transactionId,
            //     'status_id' => $assignedStatusId,
            //     'fulfillment_type' => $request->fulfillment_type,
            //     'method' => $method,
            //     'created_by' => $user->id,
            //     'updated_by' => $user->id,
            // ]);

            // // 3. Shift order state logic cleanly if checkout settled completely upfront
            // if ($assignedStatusId === ($statusPaid ? $statusPaid->id : null)) {
            //     $orderPaidStatus = Status::where('type', 'order')->where('value', 'processing')->first();
            //     if ($orderPaidStatus) {
            //         $order->update(['status_id' => $orderPaidStatus->id]);
            //     }
            // }

            // $payment->load(['order', 'status', 'creator']);
            
            // $responsePayload = [
            //     'success' => true,
            //     'message' => 'Payment transaction processed successfully.',
            //     'data' => new PaymentResource($payment)
            // ];

            // if (!empty($additionalMeta)) {
            //     $responsePayload = array_merge($responsePayload, $additionalMeta);
            // }
            // ==========================================
            // PATHWAY 4: ABA PAYWAY GATEWAY (sandbox)
            // ==========================================
            elseif ($method === 'aba_payway') {
                $transactionId = 'ABATX' . time() . strtoupper(Str::random(4));
                // Sandbox: mark paid immediately so order can complete
                $assignedStatusId = $statusPaid ? $statusPaid->id : null;

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

                // Same sandbox QR shape as KHQR so POS shows the card
                $merchantName = 'Ecommerce Store';
                $merchantNameLen = str_pad((string) strlen($merchantName), 2, '0', STR_PAD_LEFT);
                $amountLen = str_pad((string) strlen($amount), 2, '0', STR_PAD_LEFT);

                $additionalMeta['sandbox_payment_helper'] = [
                    'khqr_string' =>
                        '00020101021230380012KHQRMerchant12030612345652045999' .
                        '5303840' .                                    // USD
                        '54' . $amountLen . $amount .
                        '5802KH' .
                        '59' . $merchantNameLen . $merchantName .
                        '6010Phnom Penh' .
                        '6304A1B2',
                    'deeplink_url' => 'bakong://pay?uuid=' . $transactionId . '&amount=' . $amount,
                ];
            }
            // ==========================================
            // PATHWAY 5: KHQR (sandbox)
            // ==========================================
            elseif ($method === 'khqr') {
                $transactionId = 'bakong_tx_' . time() . Str::upper(Str::random(4));
                // Sandbox: mark paid immediately so order can complete
                $assignedStatusId = $statusPaid ? $statusPaid->id : null;

                $merchantName = 'Ecommerce Store';
                $merchantNameLen = str_pad((string) strlen($merchantName), 2, '0', STR_PAD_LEFT);
                $amount = number_format((float) $order->total_amount, 2, '.', '');
                $amountLen = str_pad((string) strlen($amount), 2, '0', STR_PAD_LEFT);

                $additionalMeta['sandbox_payment_helper'] = [
                    'khqr_string' =>
                        '00020101021230380012KHQRMerchant12030612345652045999' .
                        '5303840' .                                    // USD (NOT 116)
                        '54' . $amountLen . $amount .
                        '5802KH' .
                        '59' . $merchantNameLen . $merchantName .
                        '6010Phnom Penh' .
                        '6304A1B2',
                    'deeplink_url' => 'bakong://pay?uuid=' . $transactionId . '&amount=' . $amount,
                ];
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

            // 3. Shift order state logic cleanly if checkout settled completely upfront
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
                'data' => new PaymentResource($payment)
            ];

            if (!empty($additionalMeta)) {
                $responsePayload = array_merge($responsePayload, $additionalMeta);
            }

            return response()->json($responsePayload, 201);
        });
    }


    /**
     * Display a specific payment transaction record summary layout.
     * GET /api/payments/{id}
     */
    public function show(Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Load the underlying order structure immediately to read ownership properties
        $payment->load(['order']);

        // 2. Fixed: Verify order ownership instead of payment creator to support POS cashiers
        if ($user->isCustomer()) {
            $isOrderOwner = (int)$payment->order->created_by === (int)$user->id;
            
            if (!$isOrderOwner) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Access Denied. You do not own this billing summary statement.'
                ], 403);
            }
        }

        // 3. Eager load the remaining display parameters for the API resource payload output map
        $payment->load(['status', 'creator']);

        return response()->json([
            'success' => true,
            'message' => 'Payment record details loaded successfully.',
            'data' => new PaymentResource($payment)
        ], 200);
    }


        /**
     * Administrative Settlement tool for Cashiers closing out COD tracking entries.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Payment  $payment
     * @return \Illuminate\Http\JsonResponse
     */
    public function settleCODPayment(Request $request, Payment $payment): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Enforce administrative role protection boundaries
        if ($user->isCustomer()) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied.'
            ], 403);
        }

        // 2. Prevent illegal processing state actions on non-COD entries
        if ($payment->method !== 'cash_on_delivery') {
            return response()->json([
                'success' => false,
                'message' => 'Target profile is not a COD entry.'
            ], 400);
        }

        // 3. Verify target transaction lookup records exist before modifications
        $statusPaid = Status::where('type', 'payment')->where('value', 'paid')->first();

        DB::transaction(function () use ($payment, $user, $statusPaid) {
            // Update the immediate tracking register entry
            $payment->update([
                'status_id'  => $statusPaid ? $statusPaid->id : null,
                'updated_by' => $user->id
            ]);

            // Shift parent order collection context properties to completed state 
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
