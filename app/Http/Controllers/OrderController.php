<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use ScopesToOwner;

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $query = Order::query();

        if ($user->isCustomer()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isStoreAdmin() || $user->isCashier()) {
            // Store Admins only see orders fulfilled through branches under
            // their own stores; Cashiers inherit their assigned branch's
            // store_admin scope.
            $this->scopeToStoreOwnerVia($query, $user, 'branch.store');
        } elseif (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
        }

        if ($request->has('branch_id')) {
            $query->where('branch_id', $request->input('branch_id'));
        }

        if ($request->has('search')) {
            $query->where('receipt_number', 'LIKE', "%{$request->input('search')}%");
        }

        if ($request->has('status_id')) {
            $query->where('status_id', $request->input('status_id'));
        }

        $query->orderBy('created_at', 'desc');
        $perPage = $request->input('per_page', 10);
        $orders = $query->with(['details.product', 'details.productOption', 'address', 'branch', 'status', 'creator'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Orders retrieved successfully.',
            'data' => OrderResource::collection($orders->items()),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Structural multi-fulfillment verification parameters
        $validator = Validator::make($request->all(), [
            'fulfillment_type' => 'required|in:shipping,pos',
            'payment_method'   => 'required|in:cash,khqr,aba,stripe',
            // Delivery address is mandatory for online dispatch but bypassed for POS physical registers
            'address_id'       => 'required_if:fulfillment_type,shipping|nullable|exists:addresses,id',
            // Direct branch targeting is required for real-time cashier register desks
            'branch_id'        => 'required_if:fulfillment_type,pos|nullable|exists:branches,id',
            // Direct raw sales arrays are required only for physical POS registers
            'items'            => 'required_if:fulfillment_type,pos|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.product_option_id' => 'nullable|exists:product_options,id',
            'items.*.price'    => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $fulfillmentType = $request->input('fulfillment_type');
        
        // ✅ FIX: Define $cart as null initially so it safely passes into the closure scope
        $cart = null; 

        // 2. Data Sourcing Router Switch
        if ($fulfillmentType === 'shipping') {
            // ONLINE FLOW: Reclaim active items from the customer's cart
            $cart = Cart::where('created_by', $user->id)->with('items.product')->first();
            if (!$cart || $cart->items->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Your shopping cart is currently empty.'], 400);
            }
            $branchId = $cart->items->first()->branch_id;
            $orderItems = $cart->items;
            $totalAmount = $cart->items->sum(fn($item) => $item->quantity * $item->price);
        } else {
            // POS FLOW: Use direct scanner item lists submitted by the cashier
            $branchId = $request->input('branch_id');
            $orderItems = json_decode(json_encode($request->input('items'))); // Standardize array layout
            $totalAmount = collect($orderItems)->sum(fn($item) => $item->quantity * $item->price);
        }

        try {
            // Pass $cart inside safely now since it is guaranteed to exist as an absolute reference variable
            return DB::transaction(function () use ($request, $user, $fulfillmentType, $branchId, $orderItems, $totalAmount, $cart) {
                
                // Real-time POS checkout matches 'completed' state instantly; Online defaults to 'pending'
                $statusValue = ($fulfillmentType === 'pos') ? 'completed' : 'pending';
                $initialStatus = Status::where('type', 'order')->where('value', $statusValue)->first();

                $order = Order::create([
                    'address_id' => $request->address_id ?? null,
                    'branch_id' => $branchId,
                    'status_id' => $initialStatus ? $initialStatus->id : null,
                    'total_amount' => $totalAmount,
                    'receipt_number' => 'REC-' . strtoupper(Str::random(4)) . '-' . time(),
                    'date' => now()->format('Y-m-d'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                foreach ($orderItems as $item) {
                    // Force row locks to prevent duplicate stock updates under high traffic load
                    $pivotRow = DB::table('branch_product')
                        ->where('branch_id', $branchId)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$pivotRow || $pivotRow->stock < $item->quantity) {
                        $pName = Product::find($item->product_id)->name ?? 'Unknown Item';
                        throw new \Exception("The item '{$pName}' is out of stock at this branch location.");
                    }

                    // Atomic stock deduction from the branch pool
                    DB::table('branch_product')
                        ->where('branch_id', $branchId)
                        ->where('product_id', $item->product_id)
                        ->decrement('stock', $item->quantity);

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_option_id' => $item->product_option_id ?? null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ]);
                }

                // If checkout is initiated by an online customer, clear out their cart
                if ($fulfillmentType === 'shipping' && !is_null($cart)) {
                    $cart->items()->delete();
                }

                $order->load(['details.product', 'details.productOption', 'address', 'branch', 'status', 'creator']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Checkout processed. Order generated successfully.',
                    'data' => new OrderResource($order)
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }


    public function cancel(Order $order): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $cancelledStatus = Status::where('type', 'order')->where('value', 'cancelled')->first();
        if ($order->status_id === ($cancelledStatus ? $cancelledStatus->id : null)) {
            return response()->json(['success' => false, 'message' => 'This order is already cancelled.'], 400);
        }

        if ($user->isCustomer() && $order->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        return DB::transaction(function () use ($order, $user, $cancelledStatus) {
            foreach ($order->details as $detail) {
                if ($detail->product_id) {
                    DB::table('branch_product')
                        ->where('branch_id', $order->branch_id)
                        ->where('product_id', $detail->product_id)
                        ->increment('stock', $detail->quantity);
                }
            }

            $order->update([
                'status_id' => $cancelledStatus ? $cancelledStatus->id : null,
                'updated_by' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully and branch inventory has been restored.',
                'data' => null
            ], 200);
        });
    }

    public function show(Order $order): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer() && $order->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        if ($user->isStoreAdmin() || $user->isCashier()) {
            $order->loadMissing('branch.store');
            if (!$order->branch || !$order->branch->store || $order->branch->store->created_by !== $user->storeOwnerId()) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        $order->load(['details.product', 'details.productOption', 'address', 'branch', 'status', 'creator']);
        return response()->json([
            'success' => true,
            'message' => 'Order metrics retrieved successfully.',
            'data' => new OrderResource($order)
        ], 200);
    }

    public function update(Request $request, Order $order): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer()) {
            return response()->json(['success' => false, 'message' => 'Customers cannot edit submitted orders.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'status_id' => 'required|exists:statuses,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }
        $order->update([
            'status_id' => $request->status_id,'updated_by' => $user->id,
        ]);
        $order->load([
            'details.product', 'details.productOption', 'address', 'branch', 'status', 'creator'
        ]);
        return response()->json([
            'success' => true,'message' => 'Order processing state updated successfully.',
            'data' => new OrderResource($order)
        ], 
        200);
    }

    /**
     * Sandbox: customer confirms they "paid" (no real QR scan).
     * POST /api/orders/{order}/confirm-paid
     */
    public function confirmPaid(Order $order): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer() && (int) $order->created_by !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        $paymentPaid = Status::where('type', 'payment')->where('value', 'paid')->first();
        // Prefer value lookup; only use Status::find(6) if that row is the order status you want
        $orderStatus = Status::where('type', 'order')->where('value', 'completed')->first()
            ?? Status::find(6);

        return DB::transaction(function () use ($order, $user, $paymentPaid, $orderStatus) {
            $payment = \App\Models\Payment::where('order_id', $order->id)->latest('id')->first();
            if ($payment && $paymentPaid) {
                $payment->update(['status_id' => $paymentPaid->id, 'updated_by' => $user->id]);
            }
            if ($orderStatus) {
                $order->update(['status_id' => $orderStatus->id, 'updated_by' => $user->id]);
            }
            $order->load(['details.product', 'details.productOption', 'address', 'branch', 'status', 'creator']);
            return response()->json([
                'success' => true,
                'message' => 'Sandbox payment confirmed.',
                'data' => new OrderResource($order),
            ], 200);
        });
    }
}

