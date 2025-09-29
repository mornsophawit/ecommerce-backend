<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderDetail;
use App\Models\Product;

class OrderDetailController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $cartItems = OrderDetail::with('product')
            ->where('user_id', $userId)
            ->whereNull('order_id') // not yet ordered
            ->get();

        return response()->json($cartItems);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        $orderDetail = OrderDetail::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'quantity' => $request->quantity,
            'price' => $product->price, // take snapshot of current product price
            'order_id' => null,         // still in cart
        ]);

        return response()->json($orderDetail, 201);
    }

    public function destroy($id)
    {
        $cartItem = OrderDetail::where('user_id', auth()->id())
            ->whereNull('order_id')
            ->find($id);

        if (!$cartItem) {
            return response()->json(['error' => 'Cart item not found'], 404);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }
}
