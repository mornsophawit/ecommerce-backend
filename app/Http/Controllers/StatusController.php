<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusResource;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Status::query();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_kh', 'LIKE', "%{$search}%")
                  ->orWhere('value', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $paginatedStatuses = $query->with(['creator', 'updater'])->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Statuses retrieved successfully.',
            'data' => StatusResource::collection($paginatedStatuses),
            'pagination' => [
                'current_page' => $paginatedStatuses->currentPage(),
                'last_page' => $paginatedStatuses->lastPage(),
                'per_page' => $paginatedStatuses->perPage(),
                'total' => $paginatedStatuses->total(),
            ]
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden configuration access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|max:255',
            'value' => 'required|string|max:255',
            'name' => 'required|string|max:255|unique:statuses,name',
            'name_kh' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['created_by'] = $user->id;
        $validated['updated_by'] = $user->id;

        $status = Status::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status state registered successfully.',
            'data' => new StatusResource($status)
        ], 201);
    }

    public function show(Status $status): JsonResponse
    {
        $status->load(['creator', 'updater']);

        return response()->json([
            'success' => true,
            'message' => 'Status parameters retrieved successfully.',
            'data' => new StatusResource($status)
        ], 200);
    }

    public function update(Request $request, Status $status): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden configuration access.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|string|max:255',
            'value' => 'sometimes|required|string|max:255',
            'name' => 'sometimes|required|string|max:255|unique:statuses,name,' . $status->id,
            'name_kh' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $validated['updated_by'] = $user->id;

        $status->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status parameters updated successfully.',
            'data' => new StatusResource($status)
        ], 200);
    }

    public function destroy(Status $status): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Forbidden configuration access.'], 403);
        }

        $status->delete();

        return response()->json([
            'success' => true,
            'message' => 'Status parameter removed permanently.',
            'data' => null
        ], 200);
    }
}
