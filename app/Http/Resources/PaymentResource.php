<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'order_id' => $this->order_id,
            'receipt_number' => $this->order ? $this->order->receipt_number : null,
            'total_amount' => $this->order ? $this->order->total_amount : null,
            'transaction_id' => $this->transaction_id,
            'status_id' => $this->status_id,
            'status_name' => $this->status ? $this->status->name : null,
            'status_name_kh' => $this->status ? $this->status->name_kh : null,
            'fulfillment_type' => $this->fulfillment_type,
            'method' => $this->method,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
