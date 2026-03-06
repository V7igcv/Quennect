<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\TransactionStatus;

class QueueTransaction extends Model
{
    use HasFactory;

    protected $table = 'queue_transactions';

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
        'skipped_at',
        'average_satisfaction_rating',
    ];

    protected $casts = [
        'queue_date' => 'date',
        'is_priority' => 'boolean',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
        'skipped_at' => 'datetime',
        'status' => TransactionStatus::class,  // ✅ Enum casting
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
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'queue_transaction_services')
                    ->withTimestamps();
    }

    /**
     * Get all priority sectors for this transaction
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
     * ==================== FIXED SCOPES ====================
     * Gamit ang Enum para walang conflict
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', TransactionStatus::WAITING);
    }

    public function scopeServing($query)
    {
        return $query->where('status', TransactionStatus::SERVING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::COMPLETED);
    }

    public function scopeSkipped($query)
    {
        return $query->where('status', TransactionStatus::SKIPPED);
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
        return $query->whereDate('queue_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('queue_date', now()->toDateString());
    }

    /**
     * Get the queue display format
     */
    public function getDisplayQueueNumberAttribute()
    {
        return $this->full_queue_number;
    }

    /**
     * Get status as string (for frontend)
     */
    public function getStatusStringAttribute(): string
    {
        return strtolower($this->status->value);
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

    /**
     * Calculate average satisfaction rating
     */
    public function computeAverageSatisfactionRating(): ?float
    {
        // ✅ REMOVED the ->likert() call
        $average = $this->evaluationResponses()
            ->whereNotNull('rating_value')
            ->avg('rating_value');

        if ($average !== null) {
            $average = round($average, 2);
        }

        $this->update([
            'average_satisfaction_rating' => $average
        ]);

        return $average;
    }

    /**
     * Generate next queue number (with race condition prevention)
     */
    public static function generateNextNumber(int $officeId, bool $isPriority): array
    {
        $office = Office::findOrFail($officeId);
        $today = now()->toDateString();
        
        return \DB::transaction(function () use ($officeId, $today, $isPriority, $office) {
            $lastTransaction = self::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('is_priority', $isPriority)
                ->lockForUpdate()
                ->orderBy('queue_number', 'desc')
                ->first();

            $nextNumber = $lastTransaction ? $lastTransaction->queue_number + 1 : 1;
            $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $priorityPrefix = $isPriority ? 'P' : '';
            $fullQueueNumber = $office->office_acronym . '-' . $priorityPrefix . $formattedNumber;

            return [
                'queue_number' => $nextNumber,
                'full_queue_number' => $fullQueueNumber,
                'queue_prefix' => $office->office_acronym . '-'
            ];
        });
    }
}