<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'office_id',
        'service_name',
        'service_code',
        'service_description',
        'service_type',
        'classification',
        'is_free',
        'status',
        'used_count',
        'is_locked',
        'provides_assistance',
    ];

    protected $casts = [
        'is_free' => 'boolean',
        'is_locked' => 'boolean',
        'provides_assistance' => 'boolean',
        'used_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->service_name . ' (' . $this->service_code . ')';
    }

    /**
     * Get the office that offers this service
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get all queue transactions for this service
     * (Many-to-many relationship)
     */
    public function queueTransactions()
    {
        return $this->belongsToMany(QueueTransaction::class, 'queue_transaction_services')
                    ->withTimestamps();
    }
}
