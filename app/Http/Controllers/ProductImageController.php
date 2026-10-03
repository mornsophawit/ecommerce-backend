<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    /**
     * Drop an individual specific picture row cleanly.
     */
    public function destroy(ProductImage $productImage): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isStoreAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized entry.'], 403);
        }

        // Drop the database log tracker entry
        $productImage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Specific gallery image erased successfully from product card.',
            'data' => null
        ], 200);
    }
}
