<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index()
    {
        return response()->json(ProductCategory::all());
    }

    public function show($id)
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        return response()->json($category);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string|unique:product_categories',
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|url',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        $data = $request->only(['title', 'slug', 'description', 'image_url', 'icon', 'is_active', 'display_order', 'parent_id']);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        $category = ProductCategory::create($data);
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Not Found'], 404);
        }

        $request->validate([
            'title' => 'required|string',
            'slug' => 'required|string|unique:product_categories,slug,' . $id,
            'description' => 'nullable|string',
            'image_url' => 'nullable|string|url',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'parent_id' => 'nullable|exists:product_categories,id',
        ]);

        $data = $request->only(['title', 'slug', 'description', 'image_url', 'icon', 'is_active', 'display_order', 'parent_id']);
        $data['updated_by'] = auth()->id();

        $category->update($data);
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = ProductCategory::find($id);
        if (!$category) {
            return response()->json(['error' => 'Not Found'], 404);
        }
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
