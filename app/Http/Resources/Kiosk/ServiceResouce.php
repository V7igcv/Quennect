<?php

namespace App\Http\Resources\Kiosk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->service_name,
            'code' => $this->service_code,
            'display_name' => $this->display_name, // "Service Name (SN)"
            'description' => $this->service_description
        ];
    }
}