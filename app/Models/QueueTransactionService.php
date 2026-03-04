<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class QueueTransactionService extends Pivot
{
    use HasFactory;

    protected $table = 'queue_transaction_services';

    protected $fillable = [
        'queue_transaction_id',
        'service_id'
    ];

    public function queueTransaction()
    {
        return $this->belongsTo(QueueTransaction::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
