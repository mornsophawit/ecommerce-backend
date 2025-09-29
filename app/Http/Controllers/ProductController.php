<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::with(['user', 'productType'])->get());
    }

    public function show($id)
    {
        $product = Product::with(['user', 'productType'])->find($id);
        if (!$product) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        return response()->json($product);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_type_id' => 'required|exists:product_types,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'img_url' => 'required|string',
        ]);

        $product = Product::create([
            'product_type_id' => $request->product_type_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'img_url' => $request->img_url,
        ]);

        return response()->json($product, 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['error' => 'Not Found'], 404);

        $request->validate([
            'product_type_id' => 'exists:product_types,id',
            'name' => 'string|max:255',
            'description' => 'string',
            'price' => 'numeric|min:0',
            'stock' => 'integer|min:0',
            'img_url' => 'string',
        ]);

        $product->update($request->only([
            'product_type_id', 'name', 'description', 'price', 'stock', 'img_url'
        ]));

        return response()->json($product);
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return response()->json(['error' => 'Not Found'], 404);

        $product->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function getByType($product_type_id)
    {
        $products = Product::with(['user', 'productType'])
            ->where('product_type_id', $product_type_id)
            ->get();
        
        if ($products->isEmpty()) {
            return response()->json(['error' => 'No products found for this type'], 404);
        }

        return response()->json($products);
    }
}
