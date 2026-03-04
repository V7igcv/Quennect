<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{
    use HasFactory;

    protected $fillable = [
        'office_id',
        'counter_number',
        'is_enabled'
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Get the office this counter belongs to
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    /**
     * Get all queue transactions served at this counter
     */
    public function queueTransactions()
    {
        return $this->hasMany(QueueTransaction::class);
    }

    /**
     * Scope for enabled counters
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Get the user currently assigned to this counter (if any)
     */
    public function currentUser()
    {
        return $this->hasOne(User::class);
    }
}