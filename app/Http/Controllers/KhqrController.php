<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\KhqrService;

class KhqrController extends Controller
{
    public function __construct(private KhqrService $khqr) {}

    public function checkStatus(int $orderId)
    {
        $user  = auth()->user();
        $order = Order::where('user_id', $user->id)->find($orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $payment = Payment::where('order_id', $order->id)
            ->where('method', 'khqr')
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'No KHQR payment record for this order'], 404);
        }

        // Already confirmed — return immediately without calling Bakong API
        if ($payment->status === 'Paid') {
            return response()->json([
                'status' => 'Paid',
                'order'  => $order->load(['orderDetails.product', 'orderDetails.options', 'address', 'payment']),
            ]);
        }

        // Ask Bakong whether the transaction has settled
        $bakong = $this->khqr->checkTransactionByMd5($payment->transaction_id);

        // errorCode 0 = transaction found and confirmed by payer's bank
        if (isset($bakong['errorCode']) && $bakong['errorCode'] === 0) {
            $payment->update(['status' => 'Paid']);
            $order->update(['status'   => 'Paid']);

            return response()->json([
                'status' => 'Paid',
                'order'  => $order->load(['orderDetails.product', 'orderDetails.options', 'address', 'payment']),
            ]);
        }

        return response()->json([
            'status'  => 'Pending',
            'message' => $bakong['message'] ?? 'Awaiting payment',
        ]);
    }
}
