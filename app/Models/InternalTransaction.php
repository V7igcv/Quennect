<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class InternalTransaction extends Model
{
    protected $table = 'internal_transactions';

    protected $fillable = [
        // Office information
        'office_id',
        'from_office_id',
        'to_office_id',
        
        // Transaction information
        'transaction_id',
        'transaction_date',
        'expected_completion_date',
        
        // Client information
        'full_name',
        'contact_number',
        
        // Services and requirements
        'service_ids',
        'requirement_link',
        
        // Notes
        'request_notes',
        'denial_reason',
        'completion_notes',
        
        // Status
        'status',
        
        // Timestamps
        'requested_at',
        'accepted_at',
        'completed_at',
        'denied_at',
        'overdue_at',
        'processed_at',
        
        // User tracking
        'created_by',
        'processed_by',
        
        // Evaluation
        'average_satisfaction_rating',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'expected_completion_date' => 'datetime',
        'service_ids' => 'array',
        'requested_at' => 'datetime',
        'accepted_at' => 'datetime',
        'completed_at' => 'datetime',
        'denied_at' => 'datetime',
        'overdue_at' => 'datetime',
        'processed_at' => 'datetime',
        'average_satisfaction_rating' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_PENDING = 'PENDING';
    const STATUS_ON_PROCESS = 'ON-PROCESS';
    const STATUS_COMPLETED = 'COMPLETED';
    const STATUS_DENIED = 'DENIED';
    const STATUS_OVERDUE = 'OVERDUE';

    /**
     * Calculate expected completion date based on selected services
     */
    public static function calculateExpectedCompletionDate($serviceIds): Carbon
    {
        $services = Service::whereIn('id', $serviceIds)->get();
        
        // Get the highest classification (longest days)
        $maxDays = $services->max(function ($service) {
            return match($service->classification) {
                'Simple' => 3,
                'Complex' => 7,
                'Highly Technical' => 20,
                default => 3,
            };
        });
        
        return now()->addDays($maxDays);
    }

    /**
     * Get the highest classification among services
     */
    public function getHighestClassificationAttribute(): string
    {
        $services = $this->getServicesListAttribute();
        
        $classifications = $services->pluck('classification')->toArray();
        
        if (in_array('Highly Technical', $classifications)) {
            return 'Highly Technical';
        }
        
        if (in_array('Complex', $classifications)) {
            return 'Complex';
        }
        
        return 'Simple';
    }

    /**
     * Check if request is overdue (based on expected_completion_date)
     */
    public function checkOverdue(): bool
    {
        // Skip if already completed or denied
        if ($this->status === self::STATUS_COMPLETED || $this->status === self::STATUS_DENIED) {
            return false;
        }
        
        // Check if expected completion date is passed
        if ($this->expected_completion_date && now()->greaterThan($this->expected_completion_date)) {
            if ($this->status !== self::STATUS_OVERDUE) {
                $this->update([
                    'status' => self::STATUS_OVERDUE,
                    'overdue_at' => now(),
                ]);
            }
            return true;
        }
        
        return false;
    }

    /**
     * Get remaining days
     */
    public function getRemainingDaysAttribute(): ?int
    {
        if (!$this->expected_completion_date || $this->isCompleted() || $this->isDenied()) {
            return null;
        }
        
        $remaining = now()->diffInDays($this->expected_completion_date, false);
        return floor($remaining);
    }

    /**
     * Get deadline status for frontend
     */
    public function getDeadlineStatusAttribute(): array
    {
        if ($this->isCompleted() || $this->isDenied()) {
            return ['status' => 'completed', 'label' => 'Completed', 'color' => 'gray'];
        }
        
        if (!$this->expected_completion_date) {
            return ['status' => 'no_deadline', 'label' => 'No deadline', 'color' => 'gray'];
        }
        
        $remaining = $this->remaining_days;
        
        if ($remaining < 0) {
            return ['status' => 'overdue', 'label' => 'Overdue', 'color' => 'red'];
        }
        
        if ($remaining <= 1) {
            return ['status' => 'urgent', 'label' => 'Urgent', 'color' => 'orange'];
        }
        
        if ($remaining <= 3) {
            return ['status' => 'soon', 'label' => 'Soon', 'color' => 'yellow'];
        }
        
        return ['status' => 'ok', 'label' => "{$remaining} days left", 'color' => 'green'];
    }

    /**
     * Get the requesting office (who sent the request)
     */
    public function fromOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'from_office_id');
    }

    /**
     * Get the receiving office (who received the request)
     */
    public function toOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'to_office_id');
    }

    /**
     * Get the office (original office_id column - for backward compatibility)
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    /**
     * Get the user who created the request
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who processed the request (accepted/denied/completed)
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Get the services list from service_ids JSON
     */
    public function getServicesListAttribute()
    {
        if (!$this->service_ids) {
            return collect();
        }
        return Service::whereIn('id', $this->service_ids)->get();
    }

    /**
     * Get notifications for this transaction
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(InternalRequestNotification::class, 'internal_transaction_id');
    }

    /**
     * Generate transaction ID
     * Format: INT-YYYY-0001
     */
    public static function generateTransactionId(): string
    {
        $year = date('Y');
        $lastRequest = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        if ($lastRequest && $lastRequest->transaction_id) {
            $lastNumber = (int) substr($lastRequest->transaction_id, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "INT-{$year}-{$nextNumber}";
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ON_PROCESS => 'On Process',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_DENIED => 'Denied',
            self::STATUS_OVERDUE => 'Overdue',
            default => ucfirst(strtolower($this->status)),
        };
    }

    /**
     * Get status color for frontend
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_ON_PROCESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_DENIED => 'red',
            self::STATUS_OVERDUE => 'orange',
            default => 'gray',
        };
    }

    /**
     * Check if request is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if request is on process
     */
    public function isOnProcess(): bool
    {
        return $this->status === self::STATUS_ON_PROCESS;
    }

    /**
     * Check if request is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if request is denied
     */
    public function isDenied(): bool
    {
        return $this->status === self::STATUS_DENIED;
    }

    /**
     * Check if request is overdue
     */
    public function isOverdue(): bool
    {
        return $this->status === self::STATUS_OVERDUE;
    }

    // ========== SCOPES ==========

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOnProcess($query)
    {
        return $query->where('status', self::STATUS_ON_PROCESS);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeDenied($query)
    {
        return $query->where('status', self::STATUS_DENIED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE);
    }

    public function scopeReceived($query, $officeId)
    {
        return $query->where('to_office_id', $officeId);
    }

    public function scopeSent($query, $officeId)
    {
        return $query->where('from_office_id', $officeId);
    }

    public function scopeForOffice($query, $officeId)
    {
        return $query->where(function($q) use ($officeId) {
            $q->where('from_office_id', $officeId)
              ->orWhere('to_office_id', $officeId);
        });
    }
}