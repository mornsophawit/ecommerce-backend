<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Fetch aggregated analytic metrics for the management overview dashboard.
     * 
     * GET /api/dashboard/stats
     */
    public function getStats(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Establish Multi-Tenant Domain Boundaries
        $isGlobalAdmin = $user->isSuperAdmin();
        $targetBranchId = $request->input('branch_id');

        // Non-super admins are strictly locked to branches belonging to their store scope
        if (!$isGlobalAdmin) {
            // Find branches managed by this Store Admin / Cashier via 'created_by' linkage
            $accessibleBranchIds = DB::table('branches')
                ->join('stores', 'branches.store_id', '=', 'stores.id')
                ->where('stores.created_by', $user->id)
                ->orWhere('branches.created_by', $user->id)
                ->pluck('branches.id')
                ->toArray();

            // If a specific branch filter is passed, make sure they own it
            if ($targetBranchId) {
                if (!in_array((int)$targetBranchId, $accessibleBranchIds)) {
                    return response()->json(['success' => false, 'message' => 'Access Denied to this branch dashboard scope.'], 403);
                }
            } else {
                // Default to all their managed branches if no explicit filter is requested
                $targetBranchIds = $accessibleBranchIds;
            }
        } else {
            // Super admin can filter by any explicit branch ID passed, or see the whole ecosystem
            $targetBranchIds = $targetBranchId ? [(int)$targetBranchId] : [];
        }

        // 2. Fetch Aggregated Gross Sales Revenue Data Matrix
        $salesQuery = DB::table('orders')
            ->join('statuses', 'orders.status_id', '=', 'statuses.id')
            ->where('statuses.type', 'order')
            ->whereIn('statuses.value', ['completed', 'processing']); // Only count successful financial rows

        if (!empty($targetBranchIds)) {
            $salesQuery->whereIn('orders.branch_id', $targetBranchIds);
        }

        $totalRevenue = $salesQuery->sum('orders.total_amount');
        $totalOrdersCount = $salesQuery->count();

        // 3. Monitor Low Inventory Warnings (Stock Alert threshold <= 15 units)
        $inventoryQuery = DB::table('branch_product')
            ->join('products', 'branch_product.product_id', '=', 'products.id')
            ->join('branches', 'branch_product.branch_id', '=', 'branches.id')
            ->select(
                'branches.name as branch_name',
                'products.name as product_name',
                'products.name_kh as product_name_kh',
                'branch_product.stock'
            )
            ->where('branch_product.stock', '<=', 15);

        if (!empty($targetBranchIds)) {
            $inventoryQuery->whereIn('branch_product.branch_id', $targetBranchIds);
        }
        $lowStockAlerts = $inventoryQuery->limit(5)->get();

        // 4. Discover Top 5 Selling Products Profile Summary
        $topProductsQuery = DB::table('order_details')
            ->join('orders', 'order_details.order_id', '=', 'orders.id')
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->join('statuses', 'orders.status_id', '=', 'statuses.id')
            ->where('statuses.type', 'order')
            ->whereIn('statuses.value', ['completed', 'processing']);

        if (!empty($targetBranchIds)) {
            $topProductsQuery->whereIn('orders.branch_id', $targetBranchIds);
        }

        $topProducts = $topProductsQuery->select(
            'products.id',
            'products.name',
            'products.name_kh',
            DB::raw('SUM(order_details.quantity) as total_units_sold'),
            DB::raw('SUM(order_details.quantity * order_details.price) as total_generated_revenue')
        )
        ->groupBy('products.id', 'products.name', 'products.name_kh')
        ->orderBy('total_units_sold', 'desc')
        ->limit(5)
        ->get();

        // 5. Track Escalated Active Disputed Customer Refund Tickets Counter
        $refundQuery = DB::table('refunds')
            ->join('statuses', 'refunds.status_id', '=', 'statuses.id')
            ->where('statuses.type', 'refund')
            ->where('statuses.value', 'pending');

        if (!empty($targetBranchIds)) {
            $refundQuery->join('orders', 'refunds.order_id', '=', 'orders.id')
                         ->whereIn('orders.branch_id', $targetBranchIds);
        }
        $pendingRefundsCount = $refundQuery->count();

        // 6. Compile Consolidated Response Matrix Package
        return response()->json([
            'success' => true,
            'message' => 'Dashboard business performance metrics calculated successfully.',
            'scope' => empty($targetBranchIds) ? 'Global Ecosystem' : 'Isolated Branch Scope',
            'data' => [
                'summary' => [
                    'total_revenue' => (float) $totalRevenue,
                    'total_orders'  => (int) $totalOrdersCount,
                    'pending_refund_disputes' => (int) $pendingRefundsCount,
                ],
                'top_selling_products' => $topProducts,
                'low_stock_alerts'     => $lowStockAlerts
            ]
        ], 200);
    }
}
