<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'service_name',
        'service_code',
        'service_description',
        // 'is_active'  // Uncomment if you add this column
    ];

    // protected $casts = [
    //     'is_active' => 'boolean',
    // ];

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

    /**
     * Scope for active services (if you add is_active column)
     */
    // public function scopeActive($query)
    // {
    //     return $query->where('is_active', true);
    // }
}
