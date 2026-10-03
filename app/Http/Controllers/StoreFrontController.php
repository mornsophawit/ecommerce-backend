<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductCategory; // adjust if your category model has a different name
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StoreFrontController extends Controller
{
    /**
     * Public endpoint feeding the storefront homepage shelves in ONE request.
     *
     * Route (outside the auth group — guests browse the storefront):
     *   Route::get('/storefront/home', [StorefrontController::class, 'home']);
     *
     * Query params (all optional):
     *   branch_id      only products stocked at this branch (also what the customer will buy from)
     *   per_row        products per shelf, default 10, max 20
     *   category_rows  how many category shelves, default 6, max 12
     *
     * Table/column names assumed from your OrderController / ProductController:
     *   order_details(order_id, product_id, quantity), orders(status_id, created_at),
     *   statuses(type, value), branch_product(branch_id, product_id, stock).
     */
    private const RELATIONS = ['productType.category', 'images', 'options', 'branches.store', 'creator', 'updater'];
    private const TRENDING_DAYS = 14;
 
    public function home(Request $request): JsonResponse
    {
        $perRow = min(max((int) $request->input('per_row', 10), 1), 20);
        $categoryRows = min(max((int) $request->input('category_rows', 6), 1), 12);
        $branchId = $request->filled('branch_id') ? (int) $request->input('branch_id') : null;
 
        // Best sellers: all-time units sold on completed orders (POS + online).
        // Only products that have actually sold — an empty shelf is hidden by the UI.
        $best = $this->ranked($branchId, null, onlySold: true)->limit($perRow)->get();
 
        // Hot: momentum over the last 14 days, excluding items already in Best Sellers
        // so the same product doesn't appear in two shelves back to back.
        $hot = $this->ranked($branchId, now()->subDays(self::TRENDING_DAYS), onlySold: true)
            ->whereNotIn('products.id', $best->pluck('id'))
            ->limit($perRow)
            ->get();
        $hotBasis = 'trending';
 
        // Thesis / new-store fallback: with no recent sales, show newest arrivals
        // instead of an empty shelf. `basis` lets the UI relabel the row honestly.
        if ($hot->isEmpty()) {
            $hot = $this->available($branchId)
                ->whereNotIn('products.id', $best->pluck('id'))
                ->latest('products.created_at')
                ->limit($perRow)
                ->get();
            $hotBasis = 'newest';
        }
 
        // One shelf per active category, best sellers first, newest as the tiebreaker.
        $rows = [];
        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get();
 
        foreach ($categories as $category) {
            if (count($rows) >= $categoryRows) {
                break;
            }
 
            $products = $this->ranked($branchId, null, onlySold: false)
                ->whereHas('productType', fn ($q) => $q->where('category_id', $category->id))
                ->limit($perRow)
                ->get();
 
            if ($products->isEmpty()) {
                continue; // never render an empty shelf
            }
 
            $rows[] = [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'name_kh' => $category->name_kh,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                ],
                'products' => ProductResource::collection($products),
            ];
        }
 
        return response()->json([
            'success' => true,
            'message' => 'Storefront sections retrieved successfully.',
            'data' => [
                'hot' => [
                    'basis' => $hotBasis, // 'trending' | 'newest'
                    'products' => ProductResource::collection($hot),
                ],
                'best_sellers' => ProductResource::collection($best),
                'categories' => $rows,
            ],
        ], 200);
    }
 
    /** Products a customer could actually buy: in stock at (any / the given) branch. */
    private function available(?int $branchId): Builder
    {
        return Product::query()
            ->with(self::RELATIONS)
            ->whereHas('branches', function ($q) use ($branchId) {
                $q->where('branch_product.stock', '>', 0);
                if ($branchId) {
                    $q->where('branches.id', $branchId);
                }
            });
    }
 
    /** Available products ranked by units sold (optionally since a date), newest as tiebreaker. */
    private function ranked(?int $branchId, ?Carbon $since, bool $onlySold): Builder
    {
        $sales = DB::table('order_details')
            ->join('orders', 'orders.id', '=', 'order_details.order_id')
            ->join('statuses', 'statuses.id', '=', 'orders.status_id')
            ->where('statuses.type', 'order')
            ->where('statuses.value', 'completed')
            ->when($since, fn ($q) => $q->where('orders.created_at', '>=', $since))
            ->groupBy('order_details.product_id')
            ->select('order_details.product_id', DB::raw('SUM(order_details.quantity) as units_sold'));
 
        return $this->available($branchId)
            ->leftJoinSub($sales, 'sales', 'sales.product_id', '=', 'products.id')
            ->select('products.*', DB::raw('COALESCE(sales.units_sold, 0) as units_sold'))
            ->when($onlySold, fn ($q) => $q->where('sales.units_sold', '>', 0))
            ->orderByDesc('units_sold')
            ->orderByDesc('products.created_at');
    }
}
