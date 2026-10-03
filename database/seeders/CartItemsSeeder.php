<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;

class CartItemsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carts = Cart::all();
        $products = Product::all();

        if ($carts->isEmpty() || $products->isEmpty()) {
            return;
        }

        // Add items to first cart only
        $cart = $carts->first();
        $selectedProducts = $products->random(min(3, $products->count()));

        foreach ($selectedProducts as $product) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => rand(1, 3),
                'price' => $product->price,
            ]);
        }
    }
}