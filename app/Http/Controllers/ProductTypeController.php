<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductType;

class ProductTypeController extends Controller
{
    public function index()
    {
        return response()->json(ProductType::all());
    }

    public function show($id)
    {
        $type = ProductType::find($id);
        if (!$type) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        return response()->json($type);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:product_types',
            'img_url' => 'required_without:image|string|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $img_url = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('product_types', 'public');
            $img_url = asset('storage/' . $path);
        } else {
            $img_url = $request->img_url;
        }

        $type = ProductType::create([
            'name' => $request->name,
            'img_url' => $img_url,
        ]);
        return response()->json($type, 201);
    }

    public function update(Request $request, $id)
    {
        $type = ProductType::find($id);
        if (!$type) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        $request->validate([
            'name' => 'required|string|unique:product_types,name,' . $id,
            'img_url' => 'nullable|string|url',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name']);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('product_types', 'public');
            $data['img_url'] = asset('storage/' . $path);
        } elseif ($request->has('img_url')) {
            $data['img_url'] = $request->img_url;
        }

        $type->update($data);
        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = ProductType::find($id);
        if (!$type) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        $type->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
