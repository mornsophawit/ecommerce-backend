<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartItemController extends Controller
{
    public function index(): JsonResponse
    {
        // Enforce fallback field schema (using 'created_by' based on your previous Cart model schema)
        $cart = Cart::where('created_by', auth()->id())->first();
        if (!$cart) {
            return response()->json(['message' => 'No cart found'], 404);
        }
        
        $items = CartItem::where('cart_id', $cart->id)->with(['product', 'options', 'branch'])->get();
        return response()->json($items, 200);
    }

    public function show($id): JsonResponse
    {
        $cart = Cart::where('created_by', auth()->id())->first();
        if (!$cart) {
            return response()->json(['error' => 'No cart found'], 404);
        }

        $item = CartItem::where('id', $id)->where('cart_id', $cart->id)->with(['product', 'options', 'branch'])->first();
        if (!$item) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }
        return response()->json($item, 200);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id', // Enforces explicit location tracking
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'product_option_ids' => 'nullable|array',
            'product_option_ids.*' => 'integer|distinct|exists:product_options,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        return DB::transaction(function () use ($request) {
            $userId = auth()->id();
            $cart = Cart::firstOrCreate(
                ['created_by' => $userId],
                ['updated_by' => $userId, 'expires_at' => now()->addDays(7)]
            );

            // STAGE 1: Check Mixed-Branch Cart Validation Rules
            $firstItem = $cart->items()->first();
            if ($firstItem && (int)$firstItem->branch_id !== (int)$request->branch_id) {
                return response()->json([
                    'error' => 'Your cart contains items from another branch. Please clear your cart first.',
                ], 400);
            }

            // STAGE 2: Validate Product Branch Assignment & Physical Stock Levels
            $product = Product::findOrFail($request->product_id);
            $branchPivot = $product->branches()->where('branches.id', $request->branch_id)->first();

            if (!$branchPivot) {
                return response()->json(['error' => 'This product is not assigned to the selected branch.'], 422);
            }

            // Total target quantity calculation (existing items in cart + new quantity requested)
            $optionIds = collect($request->input('product_option_ids', []))->map(fn($id) => (int)$id)->unique()->sort()->values();
            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $request->product_id)
                ->get()
                ->first(function ($item) use ($optionIds) {
                    return $item->options->pluck('id')->sort()->values()->all() === $optionIds->all();
                });

            $currentCartQty = $existingItem ? $existingItem->quantity : 0;
            $totalTargetQty = $currentCartQty + $request->quantity;

            if ($branchPivot->pivot->stock < $totalTargetQty) {
                return response()->json([
                    'error' => "Insufficient stock. Only {$branchPivot->pivot->stock} items available at this branch location.",
                ], 422);
            }

            // STAGE 3: Build Custom Pricing Rules
            $options = ProductOption::whereIn('id', $optionIds)->get();
            foreach ($options as $option) {
                if ((int)$option->product_id !== (int)$product->id) {
                    return response()->json(['error' => 'One or more selected options do not belong to this product.'], 422);
                }
            }

            // Pull custom branch override pricing if available; fallback to catalog default price
            $basePrice = $branchPivot->pivot->price ?? $product->price;
            $unitPrice = (float)$basePrice + (float)$options->sum('price');

            // STAGE 4: Execute Record Insertion or Update Action
            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $totalTargetQty,
                    'price' => $unitPrice,
                ]);
                $existingItem->options()->sync($optionIds);
                return response()->json($existingItem->load(['product', 'options', 'branch']), 200);
            }

            $item = CartItem::create([
                'cart_id' => $cart->id,
                'branch_id' => $request->branch_id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
                'price' => $unitPrice,
            ]);

            $item->options()->sync($optionIds);
            return response()->json($item->load(['product', 'options', 'branch']), 201);
        });
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cart = Cart::where('created_by', auth()->id())->first();
        if (!$cart) {
            return response()->json(['error' => 'No cart found'], 404);
        }

        $item = CartItem::where('id', $id)->where('cart_id', $cart->id)->with(['product', 'options'])->first();
        if (!$item) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $request->validate([
            'quantity' => 'sometimes|integer|min:1',
            'product_option_ids' => 'sometimes|array',
            'product_option_ids.*' => 'integer|distinct|exists:product_options,id',
        ]);

        if (!$request->has('quantity') && !$request->has('product_option_ids')) {
            return response()->json(['error' => 'Please provide quantity or product_option_ids to update.'], 422);
        }

        return DB::transaction(function () use ($request, $item) {
            $optionIds = $request->has('product_option_ids')
                ? collect($request->input('product_option_ids', []))->map(fn($id) => (int)$id)->unique()->sort()->values()
                : $item->options->pluck('id')->map(fn($id) => (int)$id)->sort()->values();

            $options = ProductOption::whereIn('id', $optionIds)->get();
            foreach ($options as $option) {
                if ((int)$option->product_id !== (int)$item->product_id) {
                    return response()->json(['error' => 'One or more selected options do not belong to this product.'], 422);
                }
            }

            // Validate requested updates against branch-specific physical stock limits
            $targetQty = $request->input('quantity', $item->quantity);
            $branchPivot = $item->product->branches()->where('branches.id', $item->branch_id)->first();
            
            if ($branchPivot && $branchPivot->pivot->stock < $targetQty) {
                return response()->json([
                    'error' => "Cannot update quantity. Only {$branchPivot->pivot->stock} items available at this branch.",
                ], 422);
            }

            $basePrice = ($branchPivot && $branchPivot->pivot->price) ? $branchPivot->pivot->price : $item->product->price;
            $unitPrice = (float)$basePrice + (float)$options->sum('price');

            $item->update([
                'quantity' => $targetQty,
                'price' => $unitPrice,
            ]);

            if ($request->has('product_option_ids')) {
                $item->options()->sync($optionIds);
            }

            return response()->json($item->load(['product', 'options', 'branch']), 200);
        });
    }

    public function destroy($id): JsonResponse
    {
        $cart = Cart::where('created_by', auth()->id())->first();
        if (!$cart) {
            return response()->json(['error' => 'No cart found'], 404);
        }

        $item = CartItem::where('id', $id)->where('cart_id', $cart->id)->first();
        if (!$item) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $item->options()->detach();
        $item->delete();
        return response()->json(['message' => 'Item removed from cart'], 200);
    }
}
