<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin');
    }

    public function index()
    {
        try {
            // Fetch orders with related data
            $orders = Order::with(['orderDetails.product', 'address', 'payment', 'user'])
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            // Calculate total revenue
            $totalRevenue = $orders->sum('total_amount');

            // Fetch users
            $users = User::select('id', 'name', 'email', 'role')
                // ->where('role', '!=', 'admin')
                ->get();

            // Calculate top products by sales
            $topProducts = Product::join('order_details', 'products.id', '=', 'order_details.product_id')
                ->select('products.id', 'products.name', DB::raw('SUM(order_details.quantity) as sales'))
                ->groupBy('products.id', 'products.name')
                ->orderBy('sales', 'desc')
                ->limit(5)
                ->get();

            return response()->json([
                'orders' => $orders,
                'total_revenue' => $totalRevenue,
                'users' => $users,
                'top_products' => $topProducts,
                'user_count' => $users->count(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load dashboard data: ' . $e->getMessage()], 500);
        }
    }
}