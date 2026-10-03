<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
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
            'expires_at' => $this->expires_at,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'items' => $this->items->map(fn($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product ? $item->product->name : null,
                'product_name_kh' => $item->product ? $item->product->name_kh : null,
                'product_option_id' => $item->product_option_id,
                'option_name' => $item->productOption ? $item->productOption->option_name : null,
                'option_name_kh' => $item->productOption ? $item->productOption->option_name_kh : null,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total_item_price' => $item->quantity * $item->price,
            ]),
            'grand_total' => $this->items->sum(fn($item) => $item->quantity * $item->price),
        ];
    }
}
