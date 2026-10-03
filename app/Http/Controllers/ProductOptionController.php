<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductOptionStandaloneResource;
use Illuminate\Http\Request;
use App\Models\ProductOption;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductOptionController extends Controller
{
    /**
     * Display a paginated listing of options (Admin Panel Grid View).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProductOption::query();

        if ($request->has('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('option_name', 'LIKE', "%{$search}%")
                  ->orWhere('option_name_kh', 'LIKE', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $options = $query->with(['product', 'creator', 'updater'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Product options retrieved successfully.',
            'data' => ProductOptionStandaloneResource::collection($options->items()),
            'pagination' => [
                'current_page' => $options->currentPage(),
                'last_page' => $options->lastPage(),
                'per_page' => $options->perPage(),
                'total' => $options->total(),
            ]
        ], 200);
    }

    /**
     * Add a single new option variant directly to an existing product.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'option_type' => 'required|string|max:255',
            'option_type_kh' => 'required|string|max:255',
            'option_name' => 'required|string|max:255',
            'option_name_kh' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $option = ProductOption::create($validated);
        $option->load(['product', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'New product option variant added successfully.',
            'data' => new ProductOptionStandaloneResource($option)
        ], 201);
    }

    /**
     * Display a single product option.
     */
    public function show(ProductOption $productOption): JsonResponse
    {
        $productOption->load(['product', 'creator', 'updater']);
        return response()->json([
            'success' => true,
            'message' => 'Product option loaded successfully.',
            'data' => new ProductOptionStandaloneResource($productOption)
        ], 200);
    }

    /**
     * Inline Update: Modify a single variant parameter field.
     */
    public function update(Request $request, ProductOption $productOption): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'option_type' => 'sometimes|required|string|max:255',
            'option_type_kh' => 'sometimes|required|string|max:255',
            'option_name' => 'sometimes|required|string|max:255',
            'option_name_kh' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'image_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $productOption->update($validated);
        $productOption->load(['product', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Product option updated inline successfully.',
            'data' => new ProductOptionStandaloneResource($productOption)
        ], 200);
    }

    /**
     * Drop a single product variant row.
     */
    public function destroy(ProductOption $productOption): JsonResponse
    {
        $productOption->delete();
        return response()->json(['success' => true, 'message' => 'Product option variant dropped.', 'data' => null], 200);
    }
}