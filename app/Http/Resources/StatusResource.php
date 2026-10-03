<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatusResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $locale = $request->header('Accept-Language', $request->input('lang', 'en'));
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'name' => $this->name,
            'name_kh' => $this->name_kh,
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator ? $this->creator->name : null,
            'created_at' => $this->created_at,
            'updated_by' => $this->updated_by,
            'updated_by_name' => $this->updater ? $this->updater->name : null,
            'updated_at' => $this->updated_at,
        ];
    }
}
