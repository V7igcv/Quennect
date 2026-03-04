<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class QueuePrioritySector extends Pivot
{
    use HasFactory;

    protected $table = 'queue_priority_sectors';

    protected $fillable = [
        'queue_transaction_id',
        'priority_sector_id'
    ];

    public function queueTransaction()
    {
        return $this->belongsTo(QueueTransaction::class);
    }

    public function prioritySector()
    {
        return $this->belongsTo(PrioritySector::class);
    }
}
