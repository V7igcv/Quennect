<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\QuestionType;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_code',
        'question_text',
        'question_type'
    ];

    protected $casts = [
        'question_type' => 'string'
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
        return $query->whereIn('question_type', ['LIKERT']);
    }

    /**
     * Scope for Multiple Choice questions
     */
    public function scopeMultipleChoice($query)
    {
        return $query->whereIn('question_type', ['MULTIPLE_CHOICE', 'MULTIPLE CHOICE']);
    }

    /**
     * Get the multiple choice options based on question ID
     */
    public function getMultipleChoiceOptionsAttribute()
    {
        $options = [
            'CC1' => [
                '1 - I know what a CC is and I saw this offices CC.',
                '2 - I know what a CC is but I did NOT see this offices CC.',
                '3 - I learned the CC only when I saw this offices CC.',
                '4 - I do not know what a CC is and I did not see one in this office (N/A on CC2 & CC3)'
            ],
            'CC2' => [
                '1 - Easy to see',
                '2 - Somewhat easy to see',
                '3 - Difficult to see',
                '4 - Not visible at all',
                '5 - N/A'
            ],
            'CC3' => [
                '1 - Helped very much',
                '2 - Somewhat helped',
                '3 - Did not help',
                '4 - N/A'
            ]
        ];

        $normalizedCode = strtoupper((string) $this->question_code);

        return $options[$normalizedCode] ?? [];
    }
}

