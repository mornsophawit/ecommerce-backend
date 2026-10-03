<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'receipt_number' => $this->receipt_number,
            'date' => $this->date->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'address_id' => $this->address_id,
            'delivery_address' => $this->address ? $this->address->address : null,
            'status_id' => $this->status_id,
            'status_name' => $this->status ? $this->status->name : null,
            'status_name_kh' => $this->status ? $this->status->name_kh : null,
            'branch' => $this->whenLoaded('branch', function() {
                return [
                    'id' => $this->branch->id,
                    'name' => $this->branch->name,
                    'name_kh' => $this->branch->name_kh,
                ];
            }),
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_at' => $this->updated_at,
            'details' => $this->details->map(fn($detail) => [
                'id' => $detail->id,
                'product_id' => $detail->product_id,
                'product_name' => $detail->product ? $detail->product->name : null,
                'product_name_kh' => $detail->product ? $detail->product->name_kh : null,
                'product_option_id' => $detail->product_option_id,
                'option_name' => $detail->productOption ? $detail->productOption->option_name : null,
                'option_name_kh' => $detail->productOption ? $detail->productOption->option_name_kh : null,
                'quantity' => $detail->quantity,
                'price' => $detail->price,
                'subtotal' => $detail->quantity * $detail->price,
            ]),
        ];
    }
}
