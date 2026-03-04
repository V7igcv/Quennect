<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'queue_date',
        'queue_number',
        'queue_prefix',
        'is_priority',
        'full_queue_number',
        'client_name',
        'contact_number',
        'barangay_id',
        'status',
        'counter_id',
        'called_at',
        'completed_at',
        'skipped_at'
    ];

    protected $casts = [
        'queue_date' => 'date',
        'is_priority' => 'boolean',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
    ];

    /**
     * Get the office for this transaction
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get the barangay of the client
     */
    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    /**
     * Get the counter that served this transaction
     */
    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    /**
     * Get all services for this transaction
     * (Many-to-many relationship)
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'queue_transaction_services')
                    ->withTimestamps();
    }

    /**
     * Get all priority sectors for this transaction
     * (Many-to-many relationship)
     */
    public function prioritySectors()
    {
        return $this->belongsToMany(PrioritySector::class, 'queue_priority_sectors')
                    ->withTimestamps();
    }

    /**
     * Get evaluation responses for this transaction
     */
    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    /**
     * Scopes for different statuses
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', 'WAITING');
    }

    public function scopeServing($query)
    {
        return $query->where('status', 'SERVING');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETED');
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', 'SKIPPED');
    }

    public function scopePriority($query)
    {
        return $query->where('is_priority', true);
    }

    public function scopeRegular($query)
    {
        return $query->where('is_priority', false);
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('queue_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->where('queue_date', now()->toDateString());
    }

    /**
     * Get the queue display format
     */
    public function getDisplayQueueNumberAttribute()
    {
        return $this->full_queue_number;
    }

    /**
     * Calculate waiting time in minutes
     */
    public function getWaitingTimeAttribute()
    {
        if ($this->called_at && $this->created_at) {
            return $this->created_at->diffInMinutes($this->called_at);
        }
        return null;
    }

    /**
     * Calculate serving time in minutes
     */
    public function getServingTimeAttribute()
    {
        if ($this->completed_at && $this->called_at) {
            return $this->called_at->diffInMinutes($this->completed_at);
        }
        return null;
    }
}
