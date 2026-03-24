<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalTransaction extends Model
{
    use HasFactory;

    protected $table = 'internal_transactions';

    protected $fillable = [
        'office_id',
        'transaction_date',
        'full_name',
        'contact_number',
        'requirement_link',
        'status',
        'processed_at',
        'completed_at',
        'denied_at',
        'average_satisfaction_rating',
        'expected_completion_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'denied_at' => 'datetime',
        'expected_completion_date' => 'datetime',
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class, 'internal_transaction_id');
    }
}