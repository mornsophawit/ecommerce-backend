<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductOption;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    public function viewCart(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Fetch or instantiate the active cart footprint shell for this user session
        $cart = Cart::firstOrCreate(
            ['created_by' => $user->id],
            ['updated_by' => $user->id, 'expires_at' => now()->addDays(7)]
        );

        // 2. Fixed: Safely load properties using your synchronized model method definitions
        $relations = ['items.product', 'items.branch'];
        
        // Checks dynamically if your model contains the clean productOption method binding
        if (method_exists(new \App\Models\CartItem(), 'productOption')) {
            $relations[] = 'items.productOption';
        }

        $cart->load($relations);

        return response()->json([
            'success' => true,
            'message' => 'Shopping cart contents retrieved successfully.',
            'data' => $cart
        ], 200);
        // $user = auth()->user();
        // $cart = Cart::with(['items.product', 'items.options', 'items.branch'])
        //     ->firstOrCreate(
        //         ['created_by' => $user->id],
        //         ['updated_by' => $user->id, 'expires_at' => now()->addDays(7)]
        //     );

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Cart details retrieved.',
        //     'data' => $cart
        // ], 200);
    }

    public function addItem(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'branch_id'         => 'required|exists:branches,id',
            'product_id'        => 'required|exists:products,id',
            'product_option_id' => 'nullable|exists:product_options,id',
            'quantity'          => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $cart = DB::transaction(function () use ($request, $user) {
                // 1. Cart for this user
                $cart = Cart::firstOrCreate(
                    ['created_by' => $user->id],
                    [
                        'updated_by' => $user->id,
                        'expires_at' => now()->addDays(7),
                    ]
                );

                // 2. Product + branch stock/price
                $product = Product::findOrFail($request->product_id);

                $branchPivot = $product->branches()
                    ->where('branch_id', $request->branch_id)
                    ->first();

                if (!$branchPivot) {
                    $branchPivot = $product->branches()
                        ->where('branches.id', $request->branch_id)
                        ->first();
                }

                if (!$branchPivot) {
                    throw new \RuntimeException(
                        'This product is not available at the selected branch location.',
                        422
                    );
                }

                // 3. Mixed-branch guard
                $firstItem = $cart->items()->first();
                if ($firstItem && (int) $firstItem->branch_id !== (int) $request->branch_id) {
                    throw new \RuntimeException(
                        'Your cart contains items from another branch location. Clear cart first.',
                        400
                    );
                }

                // 4. Existing line (same product + same option, or both null)
                $query = CartItem::where('cart_id', $cart->id)
                    ->where('branch_id', $request->branch_id)
                    ->where('product_id', $request->product_id);

                if ($request->filled('product_option_id')) {
                    $query->where('product_option_id', $request->product_option_id);
                } else {
                    $query->whereNull('product_option_id');
                }

                $cartItem = $query->first();
                $currentQty = $cartItem ? (int) $cartItem->quantity : 0;
                $totalQty = $currentQty + (int) $request->quantity;

                $stock = (int) ($branchPivot->pivot->stock ?? 0);
                if ($stock < $totalQty) {
                    throw new \RuntimeException(
                        "Insufficient stock. Only {$stock} items available at this branch.",
                        422
                    );
                }

                // 5. Price
                $basePrice = $branchPivot->pivot->price ?? $product->price;
                $unitPrice = (float) $basePrice;

                if ($request->filled('product_option_id')) {
                    $option = ProductOption::where('id', $request->product_option_id)
                        ->where('product_id', $product->id)
                        ->first();

                    if (!$option) {
                        throw new \RuntimeException(
                            "The selected option does not exist for product [{$product->name}].",
                            422
                        );
                    }

                    $unitPrice += (float) $option->price;
                }

                // 6. Insert or update
                if ($cartItem) {
                    $cartItem->update([
                        'quantity'   => $totalQty,
                        'price'      => $unitPrice,
                        'updated_by' => $user->id,
                    ]);
                } else {
                    $data = [
                        'cart_id'    => $cart->id,
                        'branch_id'  => $request->branch_id,
                        'product_id' => $request->product_id,
                        'quantity'   => (int) $request->quantity,
                        'price'      => $unitPrice,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ];

                    // Only set when present (column is nullable)
                    if ($request->filled('product_option_id')) {
                        $data['product_option_id'] = $request->product_option_id;
                    }

                    CartItem::create($data);
                }

                return $cart;
            });

            // Load relations after commit
            $relations = ['items.product', 'items.branch'];
            if (method_exists(new CartItem(), 'productOption')) {
                $relations[] = 'items.productOption';
            }
            $cart->load($relations);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully.',
                'data'    => $cart,
            ], 200);

        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            if (!in_array($code, [400, 422], true)) {
                $code = 422;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add item to cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    // public function addItem(Request $request): JsonResponse
    // {
    //     $user = auth()->user();
    //     $validator = Validator::make($request->all(), [
    //         'branch_id' => 'required|exists:branches,id',
    //         'product_id' => 'required|exists:products,id',
    //         'product_option_id' => 'nullable|exists:product_options,id',
    //         'quantity' => 'required|integer|min:1',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    //     }

    //     return DB::transaction(function () use ($request, $user) {
    //         $cart = Cart::firstOrCreate(
    //             ['created_by' => $user->id],
    //             ['updated_by' => $user->id, 'expires_at' => now()->addDays(7)]
    //         );

    //         // STAGE 1: Check branch stock limits via the relation pivot
    //         $product = Product::findOrFail($request->product_id);
    //         $branchPivot = $product->branches()->where('branch_id', $request->branch_id)->first();

    //         if (!$branchPivot) {
    //             return response()->json([
    //                 'success' => false, 
    //                 'message' => 'This product is not available at the selected branch location.'
    //             ], 422);
    //         }

    //         // STAGE 2: Handle mixed-branch validation
    //         $firstItem = $cart->items()->first();
    //         if ($firstItem && (int)$firstItem->branch_id !== (int)$request->branch_id) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Your cart contains items from another branch location. Clear cart first.'
    //             ], 400);
    //         }

    //         // Calculate total target quantity including items already present in the cart
    //         $query = CartItem::where('cart_id', $cart->id)
    //             ->where('branch_id', $request->branch_id)
    //             ->where('product_id', $request->product_id);

    //         if ($request->filled('product_option_id')) {
    //             $query->where('product_option_id', $request->product_option_id);
    //         } else {
    //             $query->whereNull('product_option_id');
    //         }

    //         $cartItem = $query->first();

    //         $currentCartQty = $cartItem ? $cartItem->quantity : 0;
    //         $totalTargetQty = $currentCartQty + $request->quantity;

    //         if ($branchPivot->pivot->stock < $totalTargetQty) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => "Insufficient stock. Only {$branchPivot->pivot->stock} items available at this branch."
    //             ], 422);
    //         }

    //         // STAGE 3: Determine base pricing rule
    //         $basePrice = $branchPivot->pivot->price ?? $product->price;

    //         if ($request->filled('product_option_id')) {
    //             $option = ProductOption::where('id', $request->product_option_id)
    //                 ->where('product_id', $product->id)
    //                 ->first();

    //             if (!$option) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => "The selected option variant does not exist for the product [{$product->name}]."
    //                 ], 422);
    //             }

    //             $unitPrice = (float) $basePrice + (float) $option->price;
    //         } else {
    //             $unitPrice = (float) $basePrice;
    //         }

    //         // STAGE 4: Insert or update
    //         if ($cartItem) {
    //             $cartItem->update([
    //                 'quantity'   => $totalTargetQty,
    //                 'price'      => $unitPrice,
    //                 'updated_by' => $user->id,
    //             ]);
    //         } else {
    //             $cartItem = CartItem::create([
    //                 'cart_id'           => $cart->id,
    //                 'branch_id'         => $request->branch_id,
    //                 'product_id'        => $request->product_id,
    //                 'product_option_id' => $request->input('product_option_id'), // null is OK after migration
    //                 'quantity'          => $request->quantity,
    //                 'price'             => $unitPrice,
    //                 'created_by'        => $user->id,
    //                 'updated_by'        => $user->id,
    //             ]);
    //         }

            // // Calculate total target quantity including items already present in the cart
            // $cartItem = CartItem::where('cart_id', $cart->id)
            //     ->where('branch_id', $request->branch_id)
            //     ->where('product_id', $request->product_id)
            //     ->where('product_option_id', $request->product_option_id) // Matches single-option column if applicable
            //     ->first();

            // $currentCartQty = $cartItem ? $cartItem->quantity : 0;
            // $totalTargetQty = $currentCartQty + $request->quantity;

            // if ($branchPivot->pivot->stock < $totalTargetQty) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => "Insufficient stock. Only {$branchPivot->pivot->stock} items available at this branch."
            //     ], 422);
            // }

            // // STAGE 3: Determine base pricing rule
            // $basePrice = $branchPivot->pivot->price ?? $product->price;

            // if ($request->filled('product_option_id')) {
            //     // Look for the option ensuring it explicitly belongs to this product id right in the query
            //     $option = ProductOption::where('id', $request->product_option_id)
            //                         ->where('product_id', $product->id)
            //                         ->first();

            //     if (!$option) {
            //         return response()->json([
            //             'success' => false, 
            //             'message' => "The selected option variant does not exist for the product [{$product->name}]."
            //         ], 422);
            //     }
                
            //     $unitPrice = (float)$basePrice + (float)$option->price;
            // } else {
            //     $unitPrice = (float)$basePrice;
            // }

            // // STAGE 4: Process processing row state entries
            // if ($cartItem) {
            //     $cartItem->update([
            //         'quantity' => $totalTargetQty,
            //         'price' => $unitPrice,
            //         'updated_by' => $user->id
            //     ]);
            // } else {
            //     $cartItem = CartItem::create([
            //         'cart_id'           => $cart->id,
            //         'branch_id'         => $request->branch_id,
            //         'product_id'        => $request->product_id,
            //         'product_option_id' => $request->product_option_id,
            //         'quantity'          => $request->quantity,
            //         'price'             => $unitPrice,
            //         'created_by'        => $user->id,
            //         'updated_by'        => $user->id,
            //     ]);
            // }

            // // Fixed: Safely load relationships matching your single-column architecture
            // $relations = ['items.product', 'items.branch'];
            
            // // Checks if your CartItem model has the productOption relationship method defined
            // if (method_exists(new \App\Models\CartItem(), 'productOption')) {
            //     $relations[] = 'items.productOption';
            // }

            // $cart->load($relations);

            // return response()->json([
            //     'success' => true,
            //     'message' => 'Item added to cart successfully.',
            //     'data' => $cart
            // ], 200);
    //     });
    // }

        /**
     * Remove an explicit row entry from the shopping cart.
     * DELETE /api/cart/item/{itemId}
     */
    /**
     * Remove an explicit single item row from the shopping cart.
     * DELETE /api/cart/item/{itemId}
     */
    public function removeItem($itemId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $cartItem = CartItem::where('id', $itemId)
            ->whereHas('cart', function ($query) use ($user) {
                $query->where('created_by', $user->id);
            })->first();

        if (!$cartItem) {
            return response()->json(['success' => false, 'message' => 'Item not found in your cart.'], 404);
        }

        $cartItem->delete(); // Only drops this row!

        $cart = Cart::where('created_by', $user->id)->first();
        if ($cart) { $cart->load(['items.product', 'items.branch']); }

        return response()->json(['success' => true, 'message' => 'Specific product removed.', 'data' => $cart]);
    }

    /**
     * Completely empty all contents inside the authenticated user's cart.
     * DELETE /api/cart/clear
     */
    public function clearCart(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Locate the user's root cart footprint profile
        $cart = Cart::where('created_by', $user->id)->first();

        if ($cart) {
            // 2. Erase ALL nested child rows linked to this cart simultaneously
            $cart->items()->delete(); 
        }

        return response()->json([
            'success' => true,
            'message' => 'Your shopping cart has been cleared completely.',
            'data' => $cart ? $cart->load('items') : null
        ], 200);
    }


    // public function removeItem($itemId): JsonResponse
    // {
    //     /** @var \App\Models\User $user */
    //     $user = auth()->user();

    //     // 1. Explicitly locate the target line item ensuring it maps to the active user's cart
    //     $cartItem = CartItem::where('id', $itemId)
    //         ->whereHas('cart', function ($query) use ($user) {
    //             $query->where('created_by', $user->id);
    //         })->first();

    //     if (!$cartItem) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Cart item not found or does not belong to your session.'
    //         ], 444); // Distinct response flag mapping
    //     }

    //     // 2. Erase the database record line entry
    //     $cartItem->delete();

    //     // 3. Fetch the updated cart state layout summary
    //     $cart = Cart::where('created_by', $user->id)->first();
    //     if ($cart) {
    //         $cart->load(['items.product', 'items.branch', 'items.productOption']);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Item removed from cart successfully.',
    //         'data' => $cart
    //     ], 200);
    // }

    // public function removeItem($itemId): JsonResponse
    // {
    //     $user = auth()->user();
    //     $cartItem = CartItem::where('id', $itemId)->where('created_by', $user->id)->firstOrFail();
        
    //     $cartItem->options()->detach();
    //     $cartItem->delete();

    //     $cart = Cart::where('created_by', $user->id)->with(['items.product', 'items.options', 'items.branch'])->first();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Item removed from cart.',
    //         'data' => $cart
    //     ], 200);
    // }
}
