<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Http\Resources\FavoriteResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = Favorite::where('user_id', $user->id);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('name_kh', 'LIKE', "%{$search}%");
            });
        }

        $perPage = $request->input('per_page', 10);
        $favorites = $query->with('product')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Favorites retrieved successfully.',
            'data' => FavoriteResource::collection($favorites),
            'pagination' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ]
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Avoid duplicated favorites
        $favorite = Favorite::firstOrCreate([
            'user_id' => $user->id,
            'product_id' => $request->product_id
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to favorites successfully.',
            'data' => new FavoriteResource($favorite)
        ], 201);
    }

    public function destroy($id): JsonResponse
    {
        $user = auth()->user();
        $favorite = Favorite::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $favorite->delete();

        return response()->json(['success' => true, 'message' => 'Product removed from favorites.', 'data' => null], 200);
    }
}
