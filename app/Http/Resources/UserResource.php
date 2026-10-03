<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'role' => $this->role,
            'branch_id' => $this->branch_id,
            'status' => $this->status,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null, // Pulls name into a flat key
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null, // Pulls name into a flat key
            'updated_at' => $this->updated_at,
        ];
    }
}
