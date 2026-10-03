<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
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
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'profile_url' => $this->profile_url,
            'cover_url' => $this->cover_url,
            'description' => $this->description,
            'description_kh' => $this->description_kh,

            'branches_count' => $this->branches_count ?? 0,
            
            // Conditionally loads branches array layout if requested in query parameters
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null,
            'updated_at' => $this->updated_at,
        ];
    }
}
