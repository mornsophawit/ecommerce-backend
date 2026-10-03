<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Branch;
use App\Models\User;
use App\Http\Controllers\Concerns\ScopesToOwner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    use ScopesToOwner;

    private const ROLES = ['super_admin', 'store_admin', 'cashier', 'customer'];

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        $query = User::query()->with(['branch.store', 'creator', 'updater']);

        // Store Admins only manage the accounts (cashiers) they personally created
        $this->scopeToStoreOwner($query, $user);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $sortable = ['name', 'role', 'status', 'created_at'];
        $sortBy = in_array($request->input('sort_by'), $sortable, true)
            ? $request->input('sort_by')
            : 'created_at';
        $sortDir = strtolower($request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->input('per_page', 15);
        $paginatedUsers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => UserResource::collection($paginatedUsers->items()),
            'pagination' => [
                'current_page' => $paginatedUsers->currentPage(),
                'last_page' => $paginatedUsers->lastPage(),
                'per_page' => $paginatedUsers->perPage(),
                'total' => $paginatedUsers->total(),
            ],
        ], 200);
    }

    public function show(User $targetUser): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        if ($user->isStoreAdmin() && $targetUser->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        $targetUser->load(['branch.store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'User retrieved successfully.',
            'data' => new UserResource($targetUser),
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', self::ROLES),
            // Only cashiers get pinned to a branch. Store admins don't need
            // one — their stores are found via stores.created_by.
            'branch_id' => 'nullable|exists:branches,id|required_if:role,cashier',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if ($user->isStoreAdmin()) {
            // Store Admins may only create cashier accounts, pinned to a branch under one of their own stores
            if ($validated['role'] !== 'cashier') {
                return response()->json(['success' => false, 'message' => 'Store Admins can only create cashier accounts.'], 403);
            }

            $ownsBranch = Branch::where('id', $validated['branch_id'] ?? null)
                ->whereHas('store', fn ($q) => $q->where('created_by', $user->id))
                ->exists();

            if (!$ownsBranch) {
                return response()->json(['success' => false, 'message' => 'You can only assign cashiers to a branch under your own store.'], 403);
            }
        }

        $validated['password'] = Hash::make($validated['password']);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['branch_id'] = $validated['role'] === 'cashier' ? ($validated['branch_id'] ?? null) : null;
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $newUser = User::create($validated);
        $newUser->load(['branch.store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => new UserResource($newUser),
        ], 201);
    }

    public function update(Request $request, User $targetUser): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        if ($user->isStoreAdmin() && $targetUser->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $targetUser->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:' . implode(',', self::ROLES),
            'branch_id' => 'nullable|exists:branches,id|required_if:role,cashier',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if ($user->isStoreAdmin()) {
            // Store Admins may only manage cashier accounts, pinned to a branch under one of their own stores
            if ($validated['role'] !== 'cashier') {
                return response()->json(['success' => false, 'message' => 'Store Admins can only manage cashier accounts.'], 403);
            }

            $ownsBranch = Branch::where('id', $validated['branch_id'] ?? null)
                ->whereHas('store', fn ($q) => $q->where('created_by', $user->id))
                ->exists();

            if (!$ownsBranch) {
                return response()->json(['success' => false, 'message' => 'You can only assign cashiers to a branch under your own store.'], 403);
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['status'] = $validated['status'] ?? $targetUser->status;
        $validated['branch_id'] = $validated['role'] === 'cashier' ? ($validated['branch_id'] ?? null) : null;
        $validated['updated_by'] = $user->id;

        $targetUser->update($validated);
        $targetUser->load(['branch.store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => new UserResource($targetUser),
        ], 200);
    }

    /**
     * Quick status-only toggle, used by the table's status switch so a
     * full edit form round-trip isn't required for a simple activate/deactivate.
     */
    // public function updateStatus(Request $request, User $targetUser): JsonResponse
    // {
    //     /** @var User $user */
    //     $user = auth()->user();

    //     if (!$user->isSuperAdmin()) {
    //         return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validation error.',
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     $targetUser->update([
    //         'status' => $request->input('status'),
    //         'updated_by' => $user->id,
    //     ]);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'User status updated successfully.',
    //         'data' => new UserResource($targetUser),
    //     ], 200);
    // }

    public function destroy(User $targetUser): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Action Unauthorized.'], 403);
        }

        if ($user->isStoreAdmin() && $targetUser->created_by !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Access Denied.'], 403);
        }

        if ($targetUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'You cannot delete your own account.'], 422);
        }

        $targetUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ], 200);
    }

    /**
     * PATCH/PUT status only — used by the admin table switch.
     * PUT/PATCH /api/users/{targetUser}/status
     */
    public function updateStatus(Request $request, User $targetUser): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Action Unauthorized.',
            ], 403);
        }

        if ($user->isStoreAdmin() && $targetUser->created_by !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access Denied.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Optional: prevent deactivating yourself
        if (
            (int) $targetUser->id === (int) $user->id
            && $request->input('status') === 'inactive'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own account.',
            ], 422);
        }

        $targetUser->update([
            'status'     => $request->input('status'),
            'updated_by' => $user->id,
        ]);

        $targetUser->load(['branch.store', 'creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data'    => new UserResource($targetUser),
        ], 200);
    }
}