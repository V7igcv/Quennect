<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceAssistance extends Model
{
    use HasFactory;

    protected $table = 'service_assistance';

    protected $fillable = [
        'queue_transaction_service_id',
        'assistance_type_id',
        'assistance_provided',
        'indicator',
        'assistance_provided_at',
    ];

    protected $casts = [
        'assistance_provided' => 'decimal:2',
        'indicator' => 'integer',
        'assistance_provided_at' => 'datetime',
    ];

    /**
     * Get the queue transaction service that this assistance belongs to
     */
    public function queueTransactionService()
    {
        return $this->belongsTo(QueueTransactionService::class);
    }

    /**
     * Get the assistance type (for categorized assistance like AICS)
     * Returns null if this is a traditional service assistance
     */
    public function assistanceType()
    {
        return $this->belongsTo(AssistanceType::class);
    }

    /**
     * Get the service associated with this assistance record
     * Works for both traditional and categorized services
     * For traditional: derives from queue_transaction_service → service
     * For categorized: derives from assistance_type → service
     */
    public function getService()
    {
        if ($this->assistanceType) {
            return $this->assistanceType->service;
        }

        return $this->queueTransactionService->service ?? null;
    }
}

