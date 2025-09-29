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
            'img_url' => 'nullable|string',
        ]);
        $type = ProductType::create($request->only(['name', 'img_url']));
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
            'img_url' => 'nullable|string',
        ]);
        $type->update($request->only(['name', 'img_url']));
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
