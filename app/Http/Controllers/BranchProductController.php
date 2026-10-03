<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Try to get the user if a token is present, otherwise returns null for guests
        /** @var User|null $user */
        $user = auth()->user(); 

        // 1. Core Query Builder
        $query = DB::table('branch_product')
            ->join('branches', 'branch_product.branch_id', '=', 'branches.id')
            ->join('products', 'branch_product.product_id', '=', 'products.id')
            ->leftJoin('product_types', 'products.product_type_id', '=', 'product_types.id')
            ->leftJoin('product_categories', 'product_types.category_id', '=', 'product_categories.id')
            ->select(
                'branch_product.id',
                'branch_product.branch_id',
                'branches.name as branch_name',
                'branch_product.product_id',
                'products.name as product_name',
                'products.name_kh as product_name_kh',
                'products.price as global_price',
                'products.description',
                'products.unit_value',
                'products.unit_name',
                'product_types.id as product_type_id',
                'product_types.name as product_type_name',
                'product_categories.id as category_id',
                'product_categories.name as category_name',
                'branch_product.stock as branch_stock',
                'branch_product.price as branch_override_price'
            );

        // 2. Hybrid Role-based and Marketplace Scoping rules
        if ($user) {
            // --- ADMIN / CASHIER APP BUSINESS FLOW ---
            if ($user->isCashier()) {
                if (!$user->branch_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No branch is assigned to this cashier account yet.',
                    ], 403);
                }
                // Cashier can ONLY see their assigned branch inventory
                $query->where('branch_product.branch_id', $user->branch_id);
            } elseif ($user->isStoreAdmin()) {
                // Store Admin sees only products in branches belonging to stores they created
                $ownedStoreIds = Store::where('created_by', $user->id)->pluck('id');
                $query->whereIn('branches.store_id', $ownedStoreIds);

                if ($request->filled('branch_id')) {
                    $query->where('branch_product.branch_id', $request->input('branch_id'));
                }
            } else {
                // Super Admin can browse anything
                if ($request->filled('branch_id')) {
                    $query->where('branch_product.branch_id', $request->input('branch_id'));
                }
            }
        } else {
            // --- E-COMMERCE PUBLIC APP BUSINESS FLOW ---
            // Public customers see global stock records, filtered optionally by branch selection
            if ($request->filled('branch_id')) {
                $query->where('branch_product.branch_id', $request->input('branch_id'));
            }
        }

        // 3. Keep Filters (search, category_id, product_type_id) completely the same
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'LIKE', "%{$search}%")
                ->orWhere('products.name_kh', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('product_types.category_id', $request->input('category_id'));
        }

        if ($request->filled('product_type_id')) {
            $query->where('products.product_type_id', $request->input('product_type_id'));
        }

        // 4. Pagination & Relationship Hydration (Images & Options)
        $perPage = $request->input('per_page', 1000);
        $items = $query->orderBy('branch_product.id', 'desc')->paginate($perPage);

        $records = collect($items->items())->map(function ($item) {
            $item->images = DB::table('product_images')
                ->where('product_id', $item->product_id)
                ->select('id', 'image_url', 'is_primary')
                ->get();

            $item->options = DB::table('product_options')
                ->where('product_id', $item->product_id)
                ->select('id', 'option_type', 'option_name', 'option_name_kh', 'price', 'pricing_type', 'image_url')
                ->get();

            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Inventory catalog retrieved successfully.',
            'data' => $records,
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ], 200);
    }

}





// namespace App\Http\Controllers;

// use App\Models\Branch;
// use App\Models\Product;
// use Illuminate\Http\Request;
// use Illuminate\Http\JsonResponse;
// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Validator;

// class BranchProductController extends Controller
// {

    /**
     * Display a paginated list of product inventory allocations.
     * Accepts optional branch filtering, otherwise falls back to the whole global catalog.
     * 
     * GET /api/branch-products
     * GET /api/branch-products?branch_id=1
     */
    // public function index(Request $request): JsonResponse
    // {
    //     $query = DB::table('branch_product')
    //         ->join('branches', 'branch_product.branch_id', '=', 'branches.id')
    //         ->join('products', 'branch_product.product_id', '=', 'products.id')
    //         ->select(
    //             'branch_product.id',
    //             'branch_product.branch_id',
    //             'branches.name as branch_name',
    //             'branch_product.product_id',
    //             'products.name as product_name',
    //             'products.name_kh as product_name_kh',
    //             'products.price as global_price',
    //             'branch_product.stock as branch_stock',
    //             'branch_product.price as branch_override_price'
    //         );

    //     // Explicit branch filter: If present, scope inventory directly to this location
    //     if ($request->has('branch_id') && !empty($request->branch_id)) {
    //         $query->where('branch_product.branch_id', $request->branch_id);
    //     }

    //     // Optional product tracking filter
    //     if ($request->has('product_id') && !empty($request->product_id)) {
    //         $query->where('branch_product.product_id', $request->product_id);
    //     }

    //     // Return latest allocations first
    //     $inventories = $query->orderBy('branch_product.id', 'desc')
    //         ->paginate($request->get('per_page', 15));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Branch stock inventory fetched successfully.',
    //         'data' => $inventories->items(),
    //         'pagination' => [
    //             'current_page' => $inventories->currentPage(),
    //             'last_page' => $inventories->lastPage(),
    //             'per_page' => $inventories->perPage(),
    //             'total' => $inventories->total(),
    //         ]
    //     ], 200);
    // }

    // /**
    //  * Link/Refill product stock at a specific branch.
    //  * Restricted via Middleware to admin roles, and via Controller to Store Owners.
    //  */
    // public function store(Request $request): JsonResponse
    // {
    //     $validator = Validator::make($request->all(), [
    //         'branch_id' => 'required|exists:branches,id',
    //         'product_id' => 'required|exists:products,id',
    //         'stock' => 'required|integer|min:0',
    //         'price' => 'nullable|numeric|min:0',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    //     }

    //     /** @var \App\Models\User $user */
    //     $user = auth()->user();

    //     // Enforce branch ownership checks for Store Admins
    //     if ($user->isStoreAdmin()) {
    //         // Find the branch to verify which store it belongs to
    //         $branch = Branch::findOrFail($request->branch_id);
            
    //         // Check if the branch's parent store was created by the logged-in Store Admin
    //         $isBranchOwner = DB::table('stores')
    //             ->where('id', $branch->store_id)
    //             ->where('created_by', $user->id)
    //             ->exists();

    //         if (!$isBranchOwner) {
    //             return response()->json([
    //                 'success' => false, 
    //                 'message' => 'Access Denied. You do not manage this branch location.'
    //             ], 403);
    //         }
    //     }

    //     $product = Product::findOrFail($request->product_id);

    //     DB::table('branch_product')->updateOrInsert(
    //         [
    //             'branch_id' => $request->branch_id,
    //             'product_id' => $request->product_id,
    //         ],
    //         [
    //             'stock' => $request->stock,
    //             'price' => $request->price,
    //             'updated_at' => now(),
    //             'created_at' => DB::raw('IFNULL(created_at, NOW())')
    //         ]
    //     );

    //     return response()->json([
    //         'success' => true,
    //         'message' => "Successfully synchronized product [{$product->name}] stock to Branch ID {$request->branch_id}.",
    //         'data' => [
    //             'branch_id' => (int)$request->branch_id,
    //             'product_id' => (int)$request->product_id,
    //             'stock' => (int)$request->stock,
    //             'price' => $request->price ? (float)$request->price : null
    //         ]
    //     ], 200);
    // }
// }
