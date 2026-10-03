<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionStandaloneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_name' => $this->product ? $this->product->name : null,
            'option_type' => $this->option_type,
            'option_type_kh' => $this->option_type_kh,
            'option_name' => $this->option_name,
            'option_name_kh' => $this->option_name_kh,
            'price' => $this->price,
            'pricing_type' => $this->pricing_type,
            'image_url' => $this->image_url,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null,
            'updated_at' => $this->updated_at,
        ];
    }
}
