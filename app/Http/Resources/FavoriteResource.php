<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteResource extends JsonResource
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
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'product_name' => $this->product ? $this->product->name : null,
            'product_name_kh' => $this->product ? $this->product->name_kh : null,
            'product_price' => $this->product ? $this->product->price : null,
            'created_at' => $this->created_at,
        ];
    }
}
