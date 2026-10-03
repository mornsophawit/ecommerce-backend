<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductCategoryResource;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of product categories with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductCategory::query();

        // 1. Filter by root components only (Optional query parameter flag)
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // 2. Global Multi-Language Search (name, name_kh, slug, or descriptions)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_kh', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        // Default sorting sequence based on your schema profile
        $query->orderBy('display_order', 'asc');

        // 3. Dynamic Server Side Pagination
        $perPage = $request->input('per_page', 10);
        $paginatedCategories = $query->with(['parent', 'creator', 'updater'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Product categories retrieved successfully.',
            'data' => ProductCategoryResource::collection($paginatedCategories->items()),
            'pagination' => [
                'current_page' => $paginatedCategories->currentPage(),
                'last_page' => $paginatedCategories->lastPage(),
                'per_page' => $paginatedCategories->perPage(),
                'total' => $paginatedCategories->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Security Guard: Check administrative operational rights
        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:product_categories,id',
            'name' => 'required|string|max:255',
            'name_kh' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug',
            'description' => 'nullable|string',
            'description_kh' => 'nullable|string',
            'image_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;
        
        // Auto convert string names to slugs if explicitly omitted from payload
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category = ProductCategory::create($validated);
        $category->load(['parent', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product category created successfully.',
            'data' => new ProductCategoryResource($category)
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(ProductCategory $productCategory): JsonResponse
    {
        $productCategory->load(['parent', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product category details retrieved successfully.',
            'data' => new ProductCategoryResource($productCategory)
        ], 200);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, ProductCategory $productCategory): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:product_categories,id',
            'name' => 'sometimes|required|string|max:255',
            'name_kh' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:product_categories,slug,' . $productCategory->id,
            'description' => 'nullable|string',
            'description_kh' => 'nullable|string',
            'image_url' => 'nullable|string|max:255',
            'icon' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
            'display_order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $productCategory->update($validated);
        $productCategory->load(['parent', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product category updated successfully.',
            'data' => new ProductCategoryResource($productCategory)
        ], 200);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(ProductCategory $productCategory): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only Super Admins can erase category nodes.'], 403);
        }

        // Note: Due to onDelete('cascade'), deleting a parent category will clear out its children.
        $productCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product category and its subcategories removed successfully.',
            'data' => null
        ], 200);
    }
}
