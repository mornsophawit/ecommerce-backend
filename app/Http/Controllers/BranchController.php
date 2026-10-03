<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Store;
use App\Models\User;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    use ScopesToOwner;
    /**
     * Display a listing of branches with search and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        // This endpoint is public (no 'auth:api' middleware), so the default
        // guard stays 'web'. Resolve the 'api' (JWT) guard explicitly so an
        // optional Bearer token from a logged-in admin/cashier is honored.
        /** @var User|null $user */
        $user = auth('api')->user();
        
        $query = Branch::query();

        // 1. Role Scope Isolation & Guest Handling
        if ($user) {
            if ($user->isStoreAdmin() || $user->isCashier()) {
                // Store Admins only view branches belonging to their own stores.
                // Cashiers inherit the scope of the store_admin who owns their assigned branch.
                $this->scopeToStoreOwnerVia($query, $user, 'store');
            } elseif ($user->isCustomer() || $user->isSuperAdmin()) {
                // Super Admins and Customers see all branches (no filters added here)
            } else {
                // Catch-all block for any unhandled roles to keep the API secure
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        } else {
            // Guest users (no auth) can access to see all branches
            // No restriction clauses are added to the query builder here
        }

        // 2. Multi-Language Search (name, name_kh, address, address_kh)
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_kh', 'LIKE', "%{$search}%")
                  ->orWhere('address', 'LIKE', "%{$search}%")
                  ->orWhere('address_kh', 'LIKE', "%{$search}%");
            });
        }

        // 3. Pagination Configuration (Default: 10 items per page)
        $perPage = $request->input('per_page', 10);
        $paginatedBranches = $query->with(['store', 'creator', 'updater'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Branches retrieved successfully.',
            'data' => BranchResource::collection($paginatedBranches->items()),
            'pagination' => [
                'current_page' => $paginatedBranches->currentPage(),
                'last_page' => $paginatedBranches->lastPage(),
                'per_page' => $paginatedBranches->perPage(),
                'total' => $paginatedBranches->total(),
            ]
        ], 200);
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'address_kh' => 'nullable|string|max:255',
            'map_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        // Verify that the Store Admin actually owns the parent store
        if ($user->isStoreAdmin()) {
            $storeExists = Store::where('id', $request->store_id)->where('created_by', $user->id)->exists();
            if (!$storeExists) {
                return response()->json(['success' => false, 'message' => 'You do not own this store.'], 403);
            }
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $branch = Branch::create($validated);
        $branch->load(['store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Branch created successfully.',
            'data' => new BranchResource($branch)
        ], 201);
    }

    /**
     * Display the specified branch.
     */
    public function show(Branch $branch): JsonResponse
    {
        // Public endpoint: resolve the 'api' guard explicitly (see index()).
        /** @var User|null $user */
        $user = auth('api')->user();

        // 1. Guard against unauthenticated visitors or roles if the route is public
        if ($user && ($user->isStoreAdmin() || $user->isCashier())) {
            $branch->load('store');
            if ($branch->store && $branch->store->created_by !== $user->storeOwnerId()) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        // 2. Safe loading using optional conditional relation checks
        $relations = ['store'];
        if (method_exists($branch, 'creator')) $relations[] = 'creator';
        if (method_exists($branch, 'updater')) $relations[] = 'updater';
        
        $branch->load($relations);

        return response()->json([
            'success' => true,
            'message' => 'Branch details retrieved successfully.',
            'data' => new BranchResource($branch)
        ], 200);
    }

    // public function show(Branch $branch): JsonResponse
    // {
    //     /** @var User $user */
    //     $user = auth()->user();

    //     if ($user->isStoreAdmin()) {
    //         $branch->load('store');
    //         if ($branch->store->created_by !== $user->id) {
    //             return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
    //         }
    //     }

    //     $branch->load(['store', 'creator', 'updater']);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Branch details retrieved successfully.',
    //         'data' => new BranchResource($branch)
    //     ], 200);
    // }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ($user->isStoreAdmin()) {
            $branch->load('store');
            if ($branch->store->created_by !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'address_kh' => 'nullable|string|max:255',
            'map_url' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $branch->update($validated);
        $branch->load(['store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Branch updated successfully.',
            'data' => new BranchResource($branch)
        ], 200);
    }

    /**
     * Remove the specified branch.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
        }

        if ($user->isStoreAdmin()) {
            $branch->load('store');
            if ($branch->store->created_by !== $user->id) {
                return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
            }
        }

        $branch->delete();

        return response()->json([
            'success' => true,
            'message' => 'Branch deleted successfully.',
            'data' => null
        ], 200);
    }
}
