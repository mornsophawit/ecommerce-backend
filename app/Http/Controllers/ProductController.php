<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return response()->json(Product::all());
    }

    public function show($id)
    {
        $product = Product::find($id);
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
            'img_url' => 'required_without:image|string|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $img_url = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $img_url = asset('storage/' . $path);
        } else {
            $img_url = $request->img_url;
        }

        $product = Product::create([
            'product_type_id' => $request->product_type_id,
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
            'img_url' => $img_url,
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
            'img_url' => 'nullable|string|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['product_type_id', 'name', 'description', 'price', 'stock']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $data['img_url'] = asset('storage/' . $path);
        } elseif ($request->has('img_url')) {
            $data['img_url'] = $request->img_url;
        }

        $product->update($data);

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
        $products = Product::where('product_type_id', $product_type_id)->get();
        
        if ($products->isEmpty()) {
            return response()->json(['error' => 'No products found for this type'], 404);
        }

        return response()->json($products);
    }

    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $name = $request->name;

        $products = Product::where('name', 'like', '%' . $name . '%')->get();

        return response()->json($products);
    }

    public function query(Request $request)
    {
        $request->validate([
            'productType' => 'nullable|integer|exists:product_types,id',
            'productTypeName' => 'nullable|string|exists:product_types,name',
            'name' => 'nullable|string',
            'userName' => 'nullable|string',
        ]);

        $query = Product::query();

        if ($request->has('productType')) {
            $query->where('product_type_id', $request->productType);
        }

        if ($request->has('productTypeName')) {
            $query->whereHas('productType', function ($q) use ($request) {
                $q->where('name', $request->productTypeName);
            });
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('userName')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->userName . '%')
                   ->where('role', 'vendor');
            });
        }

        $products = $query->get();

        return response()->json($products);
    }
}
