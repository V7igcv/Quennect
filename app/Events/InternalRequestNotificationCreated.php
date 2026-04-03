<?php

namespace App\Events;

use App\Models\InternalRequestNotification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InternalRequestNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public InternalRequestNotification $notification
    ) {
    }

    public function broadcastOn(): array
    {
        $notification = $this->notification->loadMissing('user');
        $officeId = optional($notification->user)->office_id;

        if (!$officeId) {
            return [];
        }

        return [new Channel('internal.notifications.office.' . $officeId)];
    }

    public function broadcastAs(): string
    {
        return 'internal.notifications.created';
    }

    public function broadcastWith(): array
    {
        $notification = $this->notification->loadMissing(['transaction.fromOffice', 'transaction.toOffice']);
        $request = $notification->transaction;

        $formattedNotification = [
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'is_read' => $notification->is_read,
            'created_at' => optional($notification->created_at)->format('Y-m-d H:i:s'),
            'created_at_formatted' => optional($notification->created_at)->diffForHumans(),
            'request' => $request ? [
                'id' => $request->id,
                'transaction_id' => $request->transaction_id,
                'from_office' => $request->fromOffice?->office_name . ' (' . $request->fromOffice?->office_acronym . ')',
                'to_office' => $request->toOffice?->office_name . ' (' . $request->toOffice?->office_acronym . ')',
                'status' => $request->status,
            ] : null,
        ];

        $unreadCount = InternalRequestNotification::where('user_id', $notification->user_id)
            ->where('is_read', false)
            ->count();

        return [
            'notification' => $formattedNotification,
            'unread_count' => $unreadCount,
        ];
    }
}
