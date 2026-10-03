<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderDetail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class OrderDetailController extends Controller
{
    /**
     * Display a listing of items belonging to an order.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Enforce an order ID constraint filter
        if (!$request->has('order_id')) {
            return response()->json(['success' => false, 'message' => 'The order_id query parameter is required.'], 422);
        }

        $orderId = $request->input('order_id');
        $order = Order::findOrFail($orderId);

        // Strict Owner Isolation: Customers can only see details of orders they created
        if ($user->isCustomer() && (int)$order->created_by !== (int)$user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this order record.'], 403);
        }

        // Managers, Cashiers, and Admins can view any order's details
        if (!$user->isCustomer() && !$user->isSuperAdmin() && !$user->isStoreAdmin() && !$user->isCashier()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized role.'], 403);
        }

        $details = OrderDetail::where('order_id', $orderId)
            ->with(['product', 'productOption'])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Order details retrieved successfully.',
            'data' => $details
        ], 200);
    }

    /**
     * Display a specific line item.
     */
    public function show($id): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $detail = OrderDetail::with(['product', 'productOption', 'order'])->findOrFail($id);

        // Check ownership if user is a customer
        if ($user->isCustomer() && (int)$detail->order->created_by !== (int)$user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access to this record.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Line item retrieved successfully.',
            'data' => $detail
        ], 200);
    }
    
    // Note: 'store', 'update', and 'destroy' methods are removed because modifications 
    // to order snapshots should be locked down to preserve billing audit integrity.
}
