<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'store_id' => $this->store_id,
            'store_name' => $this->store ? $this->store->name : null,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'address' => $this->address,
            'address_kh' => $this->address_kh,
            'map_url' => $this->map_url,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null,
            'updated_at' => $this->updated_at,
        ];
    }
}
