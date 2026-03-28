<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonitorUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $officeId,
        public array $data
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('monitor.office.'.$this->officeId)];
    }

    public function broadcastAs(): string
    {
        return 'monitor.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'office_id' => $this->officeId,
            'data' => $this->data,
        ];
    }
}
