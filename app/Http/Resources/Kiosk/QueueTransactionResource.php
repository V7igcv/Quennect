<?php

namespace App\Http\Resources\Kiosk;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QueueTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Get all services for this transaction
        $services = $this->services->map(function ($service) {
            return [
                'name' => $service->service_name,
                'code' => $service->service_code,
                'display_name' => $service->display_name
            ];
        });

        // Get priority sectors if any
        $prioritySectors = [];
        if ($this->is_priority && $this->prioritySectors->isNotEmpty()) {
            $prioritySectors = $this->prioritySectors->pluck('sector_name')->toArray();
        }

        // Calculate estimated position in queue
        $position = QueueTransaction::where('office_id', $this->office_id)
            ->whereDate('queue_date', $this->queue_date)
            ->where('status', QueueTransaction::STATUS_WAITING)
            ->where('created_at', '<', $this->created_at)
            ->count() + 1;

        return [
            'id' => $this->id,
            'queue_number' => $this->full_queue_number,
            'office' => [
                'id' => $this->office->id,
                'name' => $this->office->office_name,
                'acronym' => $this->office->office_acronym,
                'display_name' => $this->office->display_name
            ],
            'client_name' => $this->client_name,
            'contact_number' => $this->contact_number,
            'barangay' => $this->barangay ? $this->barangay->barangay_name : null,
            'lane_type' => $this->is_priority ? 'priority' : 'regular',
            'priority_sectors' => $prioritySectors,
            'services' => $services,
            'queue_date' => $this->queue_date->format('Y-m-d'),
            'status' => strtolower($this->status),
            'position' => $position,
            'estimated_wait_time' => $position * 5, // Assuming 5 minutes per transaction
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'created_at_formatted' => $this->created_at->format('h:i A')
        ];
    }
}