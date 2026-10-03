<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\User;
use App\Http\Resources\ProductResource;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use ScopesToOwner;

    public function index(Request $request): JsonResponse
    {
        // This endpoint is public (no 'auth:api' middleware), so the default
        // guard stays 'web'. Resolve the 'api' (JWT) guard explicitly so an
        // optional Bearer token from a logged-in admin/cashier is honored.
        /** @var User|null $user */
        $user = auth('api')->user();

        $query = Product::query();

        // Store Admins only see products they created; Cashiers inherit the
        // scope of the store_admin who owns their assigned branch. Super
        // Admins, customers, and guests browse the full public catalog.
        $this->scopeToStoreOwner($query, $user);

        // ---- existing: single product type ----
        if ($request->has('product_type_id')) {
            $query->where('product_type_id', $request->input('product_type_id'));
        }

        // ---- existing: branch ----
        if ($request->has('branch_id')) {
            $query->whereHas('branches', function ($q) use ($request) {
                $q->where('branches.id', $request->input('branch_id'));
            });
        }

        // ---- existing: search ----
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('name_kh', 'LIKE', "%{$search}%");
            });
        }

        // ---- NEW: price range (from ProductFilters) ----
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        // ---- NEW: multiple product types  types=1,2,3 ----
        if ($request->filled('types')) {
            $typeIds = collect(explode(',', $request->input('types')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();

            if (count($typeIds) > 0) {
                $query->whereIn('product_type_id', $typeIds);
            }
        }

        // ---- NEW: categories  categories=1,2 ----
        // Assumes product_types has category_id (via productType.category)
        if ($request->filled('categories')) {
            $categoryIds = collect(explode(',', $request->input('categories')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->values()
                ->all();

            if (count($categoryIds) > 0) {
                $query->whereHas('productType', function ($q) use ($categoryIds) {
                    $q->whereIn('category_id', $categoryIds);
                });
            }
        }

        // ---- NEW: sort (from products page select) ----
        $sort = $request->input('sort', 'featured');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'popular':
                // No sales column yet — newest as a safe fallback
                // Later: orderByDesc('sold_count') or similar
                $query->orderBy('created_at', 'desc');
                break;
            case 'featured':
            default:
                $query->orderBy('id', 'asc');
                break;
        }

        $perPage = $request->input('per_page', 10);

        $products = $query
            ->with([
                'productType.category',
                'productType',
                'images',
                'options',
                'branches.store',
                'branches',
                'creator',
                'updater',
            ])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'data' => ProductResource::collection($products),
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'product_type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'name_kh' => 'required|string|max:255',
            'description' => 'nullable|string',
            'description_kh' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'unit_value' => 'required|numeric|min:0',
            'unit_name' => 'required|string|max:20',
            'unit_name_kh' => 'required|string|max:255',
            
            // Replaced 'stock' with branch data mapping array
            'branches' => 'nullable|array',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.stock' => 'required|integer|min:0',
            'branches.*.price' => 'nullable|numeric|min:0', // Localized price override

            'images' => 'nullable|array',
            'images.*.image_url' => 'required|string|max:255',
            'images.*.is_primary' => 'nullable|boolean',
            
            'options' => 'nullable|array',
            'options.*.option_type' => 'required|string|max:255',
            'options.*.option_type_kh' => 'required|string|max:255',
            'options.*.option_name' => 'required|string|max:255',
            'options.*.option_name_kh' => 'required|string|max:255',
            'options.*.price' => 'required|numeric|min:0',
            'options.*.pricing_type' => 'required|in:addon,override',
            'options.*.image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $user) {
            $productData = $request->except(['images', 'options', 'branches']);
            $productData['created_by'] = $user->id;
            $productData['updated_by'] = $user->id;

            $product = Product::create($productData);

            // Sync stock details to specific branches
            if ($request->has('branches')) {
                foreach ($request->input('branches') as $branch) {
                    $product->branches()->attach($branch['branch_id'], [
                        'stock' => $branch['stock'],
                        'price' => $branch['price'] ?? null
                    ]);
                }
            }

            if ($request->has('images')) {
                foreach ($request->input('images') as $img) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $img['image_url'],
                        'is_primary' => $img['is_primary'] ?? false,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }

            if ($request->has('options')) {
                foreach ($request->input('options') as $opt) {
                    ProductOption::create(array_merge($opt, [
                        'product_id' => $product->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]));
                }
            }

            $product->load(['productType', 'images', 'options', 'branches', 'creator', 'updater']);
            return response()->json([
                'success' => true,
                'message' => 'Product created successfully.',
                'data' => new ProductResource($product)
            ], 201);
        });
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['productType.category', 'productType', 'images', 'options', 'branches.store', 'branches', 'creator', 'updater']);
        return response()->json([
            'success' => true,
            'message' => 'Product details retrieved successfully.',
            'data' => new ProductResource($product)
        ], 200);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
        //     return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        // }

        // 1. Super Admin bypasses all checks completely
        if (!$user->isSuperAdmin()) {
            
            // 2. If they are a Store Admin, enforce strict multi-tenant branch checks
            if ($user->isStoreAdmin()) {
                
                // Check if this product is linked to any branch belonging to this Store Admin's stores
                $hasBranchOwnership = $product->branches()
                    ->whereHas('store', function ($query) use ($user) {
                        $query->where('created_by', $user->id); // Store 1 belongs to Store Admin 1
                    })->exists();

                // Deny access if they didn't create the product AND it doesn't live in their branches
                if (!$hasBranchOwnership && $product->created_by !== $user->id) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Access Denied. This product belongs to another store manager scope.'
                    ], 403);
                }
            } else {
                // Cashiers or Customers trying to hit PUT /api/products/{id}
                return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'product_type_id' => 'sometimes|required|exists:product_types,id',
            'name' => 'sometimes|required|string|max:255',
            'name_kh' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'unit_value' => 'sometimes|required|numeric|min:0',
            'unit_name' => 'sometimes|required|string|max:20',
            'unit_name_kh' => 'sometimes|required|string|max:255',
            
            'branches' => 'nullable|array',
            'branches.*.branch_id' => 'required|exists:branches,id',
            'branches.*.stock' => 'required|integer|min:0',
            'branches.*.price' => 'nullable|numeric|min:0',

            'images' => 'nullable|array',
            'options' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request, $product, $user) {
            $productData = $request->except(['images', 'options', 'branches']);
            $productData['updated_by'] = $user->id;
            $product->update($productData);

            // Sync branches mapping values
            if ($request->has('branches')) {
                $syncData = [];
                foreach ($request->input('branches') as $branch) {
                    $syncData[$branch['branch_id']] = [
                        'stock' => $branch['stock'],
                        'price' => $branch['price'] ?? null
                    ];
                }
                $product->branches()->sync($syncData);
            }

            if ($request->has('images')) {
                ProductImage::where('product_id', $product->id)->delete();
                foreach ($request->input('images') as $img) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $img['image_url'],
                        'is_primary' => $img['is_primary'] ?? false,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                }
            }

            if ($request->has('options')) {
                ProductOption::where('product_id', $product->id)->delete();
                foreach ($request->input('options') as $opt) {
                    ProductOption::create(array_merge($opt, [
                        'product_id' => $product->id,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]));
                }
            }

            $product->load(['productType', 'images', 'options', 'branches', 'creator', 'updater']);
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully.',
                'data' => new ProductResource($product)
            ], 200);
        });
    }

    /**
     * Remove the specified product resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Super Admin bypasses all checks completely
        if (!$user->isSuperAdmin()) {
            
            if ($user->isStoreAdmin()) {
                
                // Check if this product is tied to their branches
                $hasBranchOwnership = $product->branches()
                    ->whereHas('store', function ($query) use ($user) {
                        $query->where('created_by', $user->id);
                    })->exists();

                if (!$hasBranchOwnership && $product->created_by !== $user->id) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Access Denied. You cannot delete inventory tracking items from other scopes.'
                    ], 403);
                }
            } else {
                return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
            }
        }

        // 2. Proceed with your original product deletion logic
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.'
        ], 200);
    }
}
