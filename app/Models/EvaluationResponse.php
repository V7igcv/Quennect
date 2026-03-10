<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationResponse extends Model
{
    use HasFactory;

    protected $table = 'evaluation_responses';

    protected $fillable = [
        'queue_transaction_id',
        'question_id',
        'answer_value',
        'rating_value'
    ];

        protected $casts = [
        'rating_value' => 'integer'
    ];

    /**
     * Get the queue transaction this response belongs to
     */
    public function queueTransaction()
    {
        return $this->belongsTo(QueueTransaction::class, 'queue_transaction_id');
    }

    /**
     * Get the question this response answers
     */
    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    /**
     * Scope for Likert responses
     */
    public function scopeLikert($query)
    {
        return $query->whereHas('question', function ($q) {
            $q->where('question_type', 'LIKERT');
        });
    }

    /**
     * Scope for Multiple Choice responses
     */
    public function scopeMultipleChoice($query)
    {
        return $query->whereHas('question', function ($q) {
            $q->where('question_type', 'MULTIPLE_CHOICE');
        });
    }
    
    /**
     * Get the rating label for likert questions
     */
    public function getRatingLabelAttribute()
    {
        $labels = [
            1 => 'Strongly Disagree',
            2 => 'Disagree',
            3 => 'Neutral',
            4 => 'Agree',
            5 => 'Strongly Agree'
        ];

        return $labels[$this->rating_value] ?? 'Not Applicable';
    }
}
