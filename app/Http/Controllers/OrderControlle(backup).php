<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use App\Http\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $query = Order::query();

        if ($user->isCustomer()) {
            $query->where('created_by', $user->id);
        } elseif (!$user->isSuperAdmin() && !$user->isStoreAdmin() && !$user->isCashier()) {
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

        $validator = Validator::make($request->all(), [
            'address_id' => 'required|exists:addresses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $cart = Cart::where('created_by', $user->id)->with('items.product')->first();
        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Your shopping cart is currently empty.'], 400);
        }

        try {
            return DB::transaction(function () use ($request, $user, $cart) {
                // Extract branch_id from the first item since cart items are restricted to a single branch
                $branchId = $cart->items->first()->branch_id;
                
                $totalAmount = $cart->items->sum(fn($item) => $item->quantity * $item->price);
                $initialStatus = Status::where('type', 'order')->where('value', 'pending')->first();

                $order = Order::create([
                    'address_id' => $request->address_id,
                    'branch_id' => $branchId,
                    'status_id' => $initialStatus ? $initialStatus->id : null,
                    'total_amount' => $totalAmount,
                    'receipt_number' => 'REC-' . strtoupper(Str::random(4)) . '-' . time(),
                    'date' => now()->format('Y-m-d'),
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                foreach ($cart->items as $item) {
                    // Lock the branch stock row for safe inventory checking
                    $pivotRow = DB::table('branch_product')
                        ->where('branch_id', $branchId)
                        ->where('product_id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$pivotRow || $pivotRow->stock < $item->quantity) {
                        throw new \Exception("The item '{$item->product->name}' is out of stock at this branch location.");
                    }

                    // Deduct stock levels on the pivot row record
                    DB::table('branch_product')
                        ->where('branch_id', $branchId)
                        ->where('product_id', $item->product_id)
                        ->decrement('stock', $item->quantity);

                    OrderDetail::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'product_option_id' => $item->product_option_id,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ]);
                }

                // Empty user cart contents upon successful checkout completion
                $cart->items()->delete();

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
            // Restore inventory counts to the correct branch pivot row location
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
            'status_id' => $request->status_id,
            'updated_by' => $user->id,
        ]);

        $order->load(['details.product', 'details.productOption', 'address', 'branch', 'status', 'creator']);
        return response()->json([
            'success' => true,
            'message' => 'Order processing state updated successfully.',
            'data' => new OrderResource($order)
        ], 200);
    }
}
