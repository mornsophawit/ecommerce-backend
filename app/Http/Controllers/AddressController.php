<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use Illuminate\Http\Request;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // 1. Customer: Can ONLY see addresses they created themselves
        if ($user->isCustomer()) {
            $addresses = Address::where('created_by', $user->id)->get();
        } 
        // 2. Super Admin & Store Admin: Full auditing view of all database entries
        elseif ($user->isSuperAdmin() || $user->isStoreAdmin()) {
            $addresses = Address::with(['creator', 'updater'])->get();
        } 
        // 3. Cashier: Denied general listing access
        else {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to address list.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Addresses retrieved successfully.',
            'data' => AddressResource::collection($addresses) // Self-corrects via resource mapping logic downstream
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // ONLY Customers should create delivery addresses for themselves
        if (!$user->isCustomer() && !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only customers can register personal addresses.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'      => 'required|string|max:255',
            'address'    => 'required|string',
            'address_kh' => 'nullable|string',
            'contact'    => 'required|string|max:255',
            'lat'        => 'nullable|numeric|between:-90,90',
            'long'       => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;
        $validated['is_default'] = $request->boolean('is_default');

        // Handle default reset logic atomically
        if ($validated['is_default']) {
            Address::where('created_by', $user->id)
                   ->where('is_default', true)
                   ->update(['is_default' => false]);
        }

        $address = Address::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address created successfully.',
            'data'    => new AddressResource($address)
        ], 201);
    }

    public function show(Address $address): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if ($user->isCustomer() && (int)$address->created_by !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this address profile.'
            ], 403);
        }

        if ($user->isCashier()) {
            return response()->json([
                'success' => false,
                'message' => 'Cashiers must view addresses via individual invoice logs.'
            ], 403);
        }

        $address->load(['creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Address details retrieved successfully.',
            'data'    => new AddressResource($address)
        ], 200);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        // Fixed authorization logic using type-hinted instance variables
        if (!$user->isCustomer() || (int)$address->created_by !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the direct customer owner is permitted to modify this address profile.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'      => 'sometimes|required|string|max:255',
            'address'    => 'sometimes|required|string',
            'address_kh' => 'nullable|string',
            'contact'    => 'sometimes|required|string|max:255',
            'lat'        => 'nullable|numeric|between:-90,90',
            'long'       => 'nullable|numeric|between:-180,180',
            'is_default' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        if (isset($validated['is_default'])) {
            $validated['is_default'] = $request->boolean('is_default');
            
            if ($validated['is_default']) {
                Address::where('created_by', $user->id)
                       ->where('id', '!=', $address->id)
                       ->where('is_default', true)
                       ->update(['is_default' => false]);
            }
        }

        $address->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully.',
            'data'    => new AddressResource($address)
        ], 200);
    }

    public function destroy(Address $address): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isCustomer() || (int)$address->created_by !== (int)$user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only the direct customer owner can delete this address profile.'
            ], 403);
        }

        $address->delete();

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully.',
            'data'    => null
        ], 200);
    }
}
