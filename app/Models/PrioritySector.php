<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrioritySector extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_name'
    ];

    /**
     * Get all queue transactions for this priority sector
     * (Many-to-many relationship)
     */
    public function queueTransactions()
    {
        return $this->belongsToMany(QueueTransaction::class, 'queue_priority_sectors')
                    ->withTimestamps();
    }
}