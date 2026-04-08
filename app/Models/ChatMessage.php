<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'chat_messages';
    
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'sender_office_id',
        'receiver_office_id',
        'type',
        'content',
        'file_name',
        'file_path',
        'file_mime_type',
        'file_size',
        'is_read',
        'read_at'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the sender office
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'sender_office_id');
    }

    /**
     * Get the receiver office
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'receiver_office_id');
    }

    /**
     * Scope: Get unread messages for an office
     */
    public function scopeUnreadForOffice($query, $officeId)
    {
        return $query->where('receiver_office_id', $officeId)
                     ->where('is_read', false);
    }

    /**
     * Scope: Get messages between two offices
     */
    public function scopeBetweenOffices($query, $officeId1, $officeId2)
    {
        return $query->where(function ($q) use ($officeId1, $officeId2) {
            $q->where('sender_office_id', $officeId1)
              ->where('receiver_office_id', $officeId2);
        })->orWhere(function ($q) use ($officeId1, $officeId2) {
            $q->where('sender_office_id', $officeId2)
              ->where('receiver_office_id', $officeId1);
        });
    }

    /**
     * Scope: Get recent conversations for an office
     */
    public function scopeRecentConversations($query, $officeId)
    {
        return $query->where('sender_office_id', $officeId)
                     ->orWhere('receiver_office_id', $officeId)
                     ->orderBy('created_at', 'desc');
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now()
            ]);
        }
    }

    /**
     * Check if message is a text message
     */
    public function isText(): bool
    {
        return $this->type === 'text';
    }

    /**
     * Check if message is an image
     */
    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    /**
     * Check if message is a file
     */
    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSize(): string
    {
        if (!$this->file_size) {
            return '';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file URL
     */
    public function getFileUrl(): ?string
    {
        if ($this->file_path) {
            return asset('storage/' . $this->file_path);
        }
        return null;
    }
}