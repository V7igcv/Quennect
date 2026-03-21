<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationSession extends Model
{
    use HasFactory;

    protected $table = 'evaluation_sessions';

    protected $fillable = [
        'queue_transaction_id',
        'internal_transaction_id',
        'client_type',
        'sex',
        'age',
    ];

    protected $casts = [
        'age' => 'integer',
    ];

    public function queueTransaction()
    {
        return $this->belongsTo(QueueTransaction::class, 'queue_transaction_id');
    }

    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class, 'evaluation_session_id');
    }
}
