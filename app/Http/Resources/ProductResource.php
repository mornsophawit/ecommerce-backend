<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         // Safely extract parent category info from product type
        $category = $this->productType ? $this->productType->category : null;

         // Extract associated stores based on assigned branch networks
        $stores = $this->branches ? $this->branches->map(fn($b) => $b->store)->filter()->unique('id')->values() : collect();

        return [
            'id' => $this->id,
            'category_id' => $category ? $category->id : null,
            'category_name' => $category ? $category->name : null,
            'category_name_kh' => $category ? $category->name_kh : null,
            'product_type_id' => $this->product_type_id,
            'product_type_name' => $this->productType ? $this->productType->name : null,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'description' => $this->description,
            'description_kh' => $this->description_kh,
            'price' => $this->price,
            // 'stock' => $this->stock,
            // 3. Store Mapping (Array of stores associated with this item's branches)
            'stores' => $stores->map(fn($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'name_kh' => $s->name_kh,
            ]),
            'branches' => $this->whenLoaded('branches', function() {
                return $this->branches->map(fn($branch) => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'name_kh' => $branch->name_kh,
                    'stock' => $branch->pivot->stock, // Pulls directly from branch_product pivot
                    'branch_price_override' => $branch->pivot->price, // Optional custom price
                ]);
            }),
            
            'unit_value' => $this->unit_value,
            'unit_name' => $this->unit_name,
            'unit_name_kh' => $this->unit_name_kh,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null,
            'updated_at' => $this->updated_at,
            
            // Nested clean components array matching standard requirements
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
                'is_primary' => $img->is_primary,
            ]),
            
            'options' => $this->options->map(fn($opt) => [
                'id' => $opt->id,
                'option_type' => $opt->option_type,
                'option_type_kh' => $opt->option_type_kh,
                'option_name' => $opt->option_name,
                'option_name_kh' => $opt->option_name_kh,
                'price' => $opt->price,
                'pircing_type' => $opt->pricing_type,
                'image_url' => $opt->image_url,
            ]),
        ];
    }
}
