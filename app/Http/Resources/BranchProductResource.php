<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $product = $this->product;
        $effectivePrice = $this->override_price ?? $product?->price;

        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'store_id' => $this->branch?->store_id,
            'store_name' => $this->branch?->store?->name,

            'product_id' => $this->product_id,
            'product_name' => $product?->name,
            'product_name_kh' => $product?->name_kh,
            'description' => $product?->description,

            'category_id' => $product?->productType?->category_id,
            'category_name' => $product?->productType?->category?->name,
            'product_type_id' => $product?->product_type_id,
            'product_type_name' => $product?->productType?->name,

            'unit_value' => $product?->unit_value,
            'unit_name' => $product?->unit_name,

            'global_price' => $product?->price,
            'branch_stock' => $this->stock,
            'branch_override_price' => $this->override_price,
            // What the POS should actually charge — override if set, else the catalog price.
            'effective_price' => $effectivePrice,
            'in_stock' => $this->stock > 0,

            'images' => $product?->images?->map(fn ($image) => [
                'id' => $image->id,
                'image_url' => $image->image_url,
                'is_primary' => (bool) $image->is_primary,
            ]),
            'options' => $product?->options?->map(fn ($option) => [
                'id' => $option->id,
                'option_type' => $option->option_type,
                'option_type_kh' => $option->option_type_kh,
                'option_name' => $option->option_name,
                'option_name_kh' => $option->option_name_kh,
                'price' => $option->price,
                'pricing_type' => $option->pricing_type,
                'image_url' => $option->image_url,
            ]),
        ];
    }
}