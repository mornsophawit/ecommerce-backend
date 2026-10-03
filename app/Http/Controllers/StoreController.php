<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Http\Resources\StoreResource;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    use ScopesToOwner;
       /**
     * Display a listing of stores with dynamic relation inclusion and metrics.
     * 
     * GET /api/store?include=branches
     */
    public function index(Request $request): JsonResponse
    {
        // This endpoint is public (no 'auth:api' middleware), so the default
        // guard stays 'web'. Resolve the 'api' (JWT) guard explicitly so an
        // optional Bearer token from a logged-in admin/cashier is honored.
        /** @var User|null $user */
        $user = auth('api')->user();

        // 1. Initialize a clean Eloquent query string builder base
        $query = \App\Models\Store::query();

        // Store Admins only see stores they created; Cashiers inherit the
        // scope of the store_admin who owns their assigned branch.
        // Super Admins, customers, and guests browse the full network.
        $this->scopeToStoreOwner($query, $user);

        // 2. Safely parse dynamic 'include=branches' query request arrays
        $includes = explode(',', $request->input('include', ''));
        if (in_array('branches', $includes)) {
            $query->with('branches');
        }

        // Always aggregate the total branch counts cleanly to support the frontend badges
        $query->withCount('branches');

        // 3. Process Pagination Mappings matching your system defaults
        $perPage = $request->input('per_page', 10);
        $stores = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // 4. Transform collection objects securely to pass the branches_count down to the UI
        $transformedStores = collect($stores->items())->map(function ($store) {
            return [
                'id' => $store->id,
                'name' => $store->name,
                'name_kh' => $store->name_kh,
                'description' => $store->description,
                'description_kh' => $store->description_kh,
                'image_url' => $store->image_url,
                // Appends the counted relationship column metric natively compiled by Laravel
                'branches_count' => (int) $store->branches_count,
                // Pulls down the branches model structure only if explicitly requested
                'branches' => $store->relationLoaded('branches') ? $store->branches : null,
                'created_at' => $store->created_at,
                'updated_at' => $store->updated_at,
            ];
        });

        // 5. Output response bundle matching your standard format layout
        return response()->json([
            'success' => true,
            'message' => 'Stores network collections retrieved successfully.',
            'data' => $transformedStores,
            'pagination' => [
                'current_page' => $stores->currentPage(),
                'last_page' => $stores->lastPage(),
                'per_page' => $stores->perPage(),
                'total' => $stores->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created store in storage.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Only Super Admins and Store Admins can spawn store models
        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'name_kh' => 'required|string|max:255',
            'profile_url' => 'nullable|string|max:255',
            'cover_url' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'description_kh' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $store = Store::create($validated);
        $store->load(['creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Store created successfully.',
            'data' => new StoreResource($store)
        ], 201);
    }

    /**
     * Display the specified store.
     */
    public function show(Store $store): JsonResponse
    {
        // Public endpoint: resolve the 'api' guard explicitly (see index()).
        /** @var User|null $user */
        $user = auth('api')->user();

        // Enforce store ownership checks for Store Admins & Cashiers
        if ($user && ($user->isStoreAdmin() || $user->isCashier()) && $store->created_by !== $user->storeOwnerId()) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        $store->load(['creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Store details retrieved successfully.',
            'data' => new StoreResource($store)
        ], 200);
    }

    /**
     * Return store information for editing forms.
     */
    public function edit(Store $store): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isStoreAdmin() && $store->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Store data fetched for editing.',
            'data' => new StoreResource($store)
        ], 200);
    }

    /**
     * Update the specified store in storage.
     */
    public function update(Request $request, Store $store): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Validate Ownership Boundaries
        if ($user->isStoreAdmin() && $store->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You do not own this store record.'], 403);
        }

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_kh' => 'sometimes|required|string|max:255',
            'profile_url' => 'nullable|string|max:255',
            'cover_url' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:255',
            'description_kh' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $store->update($validated);
        $store->load(['creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Store updated successfully.',
            'data' => new StoreResource($store)
        ], 200);
    }

    /**
     * Remove the specified store from storage.
     */
    public function destroy(Store $store): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isStoreAdmin() && $store->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized deletion attempt.'], 403);
        }

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        $store->delete();

        return response()->json([
            'success' => true,
            'message' => 'Store deleted successfully.',
            'data' => null
        ], 200);
    }
}
