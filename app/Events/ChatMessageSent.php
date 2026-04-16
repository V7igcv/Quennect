<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ChatMessage $message
    ) {
    }

    public function broadcastOn(): array
    {
        $senderOfficeId = $this->message->sender_office_id;
        $receiverOfficeId = $this->message->receiver_office_id;

        return [
            new Channel('chat.office.' . $senderOfficeId),
            new Channel('chat.office.' . $receiverOfficeId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'sender_office_id' => $this->message->sender_office_id,
            'receiver_office_id' => $this->message->receiver_office_id,
            'type' => $this->message->type,
            'content' => $this->message->content,
            'file_name' => $this->message->file_name,
            'is_read' => (bool) $this->message->is_read,
            'created_at' => optional($this->message->created_at)->toISOString(),
        ];
    }
}
