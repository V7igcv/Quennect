<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalRequestNotification extends Model
{
    protected $table = 'internal_request_notifications';

    protected $fillable = [
        'internal_transaction_id',
        'user_id',
        'title',
        'message',
        'type',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    /**
     * Get the transaction associated with this notification
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InternalTransaction::class, 'internal_transaction_id');
    }

    /**
     * Get the user who receives this notification
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(): void
    {
        $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Check if notification is read
     */
    public function isRead(): bool
    {
        return $this->is_read;
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Get notification type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'request_created_self' => 'Request Submitted',
            'request_created_receiver' => 'New Request',
            'accepted' => 'Accepted',
            'denied' => 'Denied',
            'completed' => 'Completed',
            default => ucfirst($this->type),
        };
    }
}