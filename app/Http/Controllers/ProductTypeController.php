<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductTypeResource;
use Illuminate\Http\Request;
use App\Models\ProductType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductTypeController extends Controller
{
    /**
     * Display a listing of product types with filters, search, and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductType::query();

        // 1. Filter directly by product category id
        if ($request->has('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 2. Global Multi-Language Search filter (name or name_kh)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_kh', 'LIKE', "%{$search}%");
            });
        }

        // 3. Dynamic Server-Side Pagination
        $perPage = $request->input('per_page', 10);
        $paginatedTypes = $query->with(['category', 'creator', 'updater'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Product types retrieved successfully.',
            'data' => ProductTypeResource::collection($paginatedTypes->items()),
            'pagination' => [
                'current_page' => $paginatedTypes->currentPage(),
                'last_page' => $paginatedTypes->lastPage(),
                'per_page' => $paginatedTypes->perPage(),
                'total' => $paginatedTypes->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created product type in storage.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:product_categories,id',
            'name' => 'required|string|max:255|unique:product_types,name',
            'name_kh' => 'required|string|max:255|unique:product_types,name_kh',
            'img_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $productType = ProductType::create($validated);
        $productType->load(['category', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product type created successfully.',
            'data' => new ProductTypeResource($productType)
        ], 201);
    }

    /**
     * Display the specified product type.
     */
    public function show(ProductType $productType): JsonResponse
    {
        $productType->load(['category', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product type details retrieved successfully.',
            'data' => new ProductTypeResource($productType)
        ], 200);
    }

    /**
     * Update the specified product type in storage.
     */
    public function update(Request $request, ProductType $productType): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|required|exists:product_categories,id',
            'name' => 'sometimes|required|string|max:255|unique:product_types,name,' . $productType->id,
            'name_kh' => 'sometimes|required|string|max:255|unique:product_types,name_kh,' . $productType->id,
            'img_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $productType->update($validated);
        $productType->load(['category', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product type updated successfully.',
            'data' => new ProductTypeResource($productType)
        ], 200);
    }

    /**
     * Remove the specified product type from storage.
     */
    public function destroy(ProductType $productType): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Only Super Admins can delete product types.'], 403);
        }

        $productType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product type deleted successfully.',
            'data' => null
        ], 200);
    }
}
