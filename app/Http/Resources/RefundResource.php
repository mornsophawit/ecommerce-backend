<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RefundResource extends JsonResource
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
            'branch_name' => $this->order && $this->order->branch ? $this->order->branch->name : null,
            'receipt_number' => $this->order ? $this->order->receipt_number : null,
            'status_id' => $this->status_id,
            'status_name' => $this->status ? $this->status->name : null,
            'status_name_kh' => $this->status ? $this->status->name_kh : null,
            'reason' => $this->reason,
            'request_by' => $this->request_by,
            'requester_name' => $this->requester ? $this->requester->name : null,
            'approved_by' => $this->approved_by,
            'approver_name' => $this->approver ? $this->approver->name : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'images' => $this->images->map(fn($img) => [
                'id' => $img->id,
                'image_url' => $img->image_url,
            ]),
            'items' => $this->items->map(fn($item) => [
                'id' => $item->id,
                'order_detail_id' => $item->order_detail_id,
                'product_name' => $item->orderDetail && $item->orderDetail->product ? $item->orderDetail->product->name : null,
                'product_name_kh' => $item->orderDetail && $item->orderDetail->product ? $item->orderDetail->product->name_kh : null,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ]),
            'total_refund_amount' => $this->items->sum('subtotal'),
        ];
    }
}
