<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\QuestionType;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_text',
        'question_type'
    ];

    protected $casts = [
        'question_type' => QuestionType::class,
    ];

    /**
     * Get all responses for this question
     */
    public function responses()
    {
        return $this->hasMany(EvaluationResponse::class, 'question_id');
    }

    /**
     * Scope for Likert questions
     */
    public function scopeLikert($query)
    {
        return $query->where('question_type', 'LIKERT');
    }

    /**
     * Scope for Multiple Choice questions
     */
    public function scopeMultipleChoice($query)
    {
        return $query->where('question_type', 'MULTIPLE_CHOICE');
    }
}

