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
        'assistance_provided',
        'assistance_provided_at',
    ];

    protected $casts = [
        'assistance_provided' => 'decimal:2',
        'assistance_provided_at' => 'datetime',
    ];

    /**
     * Get the queue transaction service that this assistance belongs to
     */
    public function queueTransactionService()
    {
        return $this->belongsTo(QueueTransactionService::class);
    }
}
