<?php

namespace App\Http\Resources\Kiosk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->office_name,
            'acronym' => $this->office_acronym,
            'display_name' => $this->display_name, // "Office Name (ACR)"
            'description' => $this->office_description,
            'logo' => $this->logo ? asset('storage/' . $this->logo) : null
        ];
    }
}