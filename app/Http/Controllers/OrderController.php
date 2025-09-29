<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Address;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin')->only('dashboard');
    }

    public function index()
    {
        $user = auth()->user();
        $orders = Order::with(['orderDetails.product', 'address', 'payment'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    public function show($id)
    {
        $user = auth()->user();
        $order = Order::with(['orderDetails.product', 'address', 'payment'])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        return response()->json($order);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:stripe,cash',
            'address_id' => 'required|exists:addresses,id',
            'stripe_token' => 'required_if:payment_method,stripe|string',
        ]);

        $user = auth()->user();
        $cartItems = OrderDetail::where('user_id', $user->id)
            ->whereNull('order_id')
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 422);
        }

        // Calculate total
        $total = $cartItems->sum(fn($item) => $item->price * $item->quantity);

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $user->id,
                'address_id' => $request->address_id,
                'total_amount' => $total,
                'status' => 'Pending',
                'receipt_number' => 'RCPT-' . strtoupper(uniqid()),
            ]);

            // Attach cart items to order
            $cartItems->each(function ($item) use ($order) {
                $item->update(['order_id' => $order->id]);
            });

            $paymentStatus = 'Pending';
            $clientSecret = null;

            if ($request->payment_method === 'stripe') {
                Stripe::setApiKey(config('services.stripe.secret'));
                $paymentIntent = PaymentIntent::create([
                    'amount' => intval($total * 100),
                    'currency' => 'usd',
                    'payment_method_types' => ['card'],
                    'payment_method' => $request->stripe_token,
                    'confirm' => true,
                ]);
                $clientSecret = $paymentIntent->client_secret;
                $paymentStatus = $paymentIntent->status === 'succeeded' ? 'Paid' : 'Pending';
            }

            Payment::create([
                'order_id' => $order->id,
                'method' => $request->payment_method,
                'status' => $paymentStatus,
                'transaction_id' => $clientSecret ? $paymentIntent->id : null,
            ]);

            // Update order status
            $order->update(['status' => $paymentStatus]);

            // Deduct stock
            foreach ($cartItems as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->decrement('stock', $item->quantity);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order->load(['orderDetails.product', 'address', 'payment']),
                'client_secret' => $clientSecret,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Checkout failed: ' . $e->getMessage()], 500);
        }
    }
}