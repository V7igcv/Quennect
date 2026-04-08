<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Office extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'office_name',
        'office_description',
        'office_acronym',
        'logo',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    // Optional: Add an accessor for logo URL
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return asset('images/default-office-logo.png');
        }

        $logo = ltrim(trim($this->logo), '/');

        // Accept full/absolute URLs as-is.
        if (Str::startsWith($logo, ['http://', 'https://', '//'])) {
            return $this->logo;
        }

        // Stored as storage/... path.
        if (Str::startsWith($logo, 'storage/')) {
            return asset($logo);
        }

        // Stored as logos/... path from public disk.
        if (Str::startsWith($logo, 'logos/')) {
            return asset('storage/' . $logo);
        }

        // Stored as raw filename.
        return asset('storage/logos/' . $logo);
    }

    /**
     * Get all services offered by this office
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * Get all counters in this office
     */
    public function counters()
    {
        return $this->hasMany(Counter::class);
    }

    /**
     * Get all users (staff) assigned to this office
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get all queue transactions for this office
     */
    public function queueTransactions()
    {
        return $this->hasMany(QueueTransaction::class);
    }

    /**
     * Scope to get only active offices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ============= CHAT RELATIONSHIPS =============

    /**
     * Get all messages sent by this office
     */
    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_office_id');
    }

    /**
     * Get all messages received by this office
     */
    public function receivedMessages()
    {
        return $this->hasMany(ChatMessage::class, 'receiver_office_id');
    }

    /**
     * Get all messages (sent and received)
     */
    public function getAllMessages()
    {
        return ChatMessage::where('sender_office_id', $this->id)
            ->orWhere('receiver_office_id', $this->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread messages count for this office
     */
    public function getUnreadMessagesCount(): int
    {
        return ChatMessage::where('receiver_office_id', $this->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get last message (sent or received)
     */
    public function getLastMessage(): ?ChatMessage
    {
        return ChatMessage::where('sender_office_id', $this->id)
            ->orWhere('receiver_office_id', $this->id)
            ->latest()
            ->first();
    }

    /**
     * Get all offices that this office has chatted with
     */
    public function getConversations()
    {
        // Get unique office IDs from sent messages
        $sentOfficeIds = ChatMessage::where('sender_office_id', $this->id)
            ->distinct()
            ->pluck('receiver_office_id')
            ->toArray();
            
        // Get unique office IDs from received messages
        $receivedOfficeIds = ChatMessage::where('receiver_office_id', $this->id)
            ->distinct()
            ->pluck('sender_office_id')
            ->toArray();
            
        // Merge and remove duplicates
        $officeIds = array_unique(array_merge($sentOfficeIds, $receivedOfficeIds));
        
        // Return offices (excluding self)
        return Office::whereIn('id', $officeIds)
            ->where('id', '!=', $this->id)
            ->get();
    }

    /**
     * Check if office is online (can be expanded with presence channels later)
     */
    public function isOnline(): bool
    {
        // For now, return true if office is active
        // Later, this can be tied to WebSocket presence
        return $this->is_active;
    }
}