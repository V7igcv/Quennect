<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\QuestionType;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            [
                'question_code' => 'CC1',
                'question_text' => 'Which of the following best describes your awareness of a CC?',
                'question_type' => QuestionType::MULTIPLE_CHOICE,
            ],
            [
                'question_code' => 'CC2',
                'question_text' => 'If aware of CC (answered 1-3 in CC1), would you say that the CC of this office was…?',
                'question_type' => QuestionType::MULTIPLE_CHOICE,
            ],
            [
                'question_code' => 'CC3',
                'question_text' => 'If aware of CC (answered 1-3 in CC1), how much did the cc help you in your transaction?',
                'question_type' => QuestionType::MULTIPLE_CHOICE,
            ],
            [
                'question_code' => 'SQD0',
                'question_text' => 'I am satisfied with the service that I availed',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD1',
                'question_text' => 'I spent a reasonable amount of time for my transaction',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD2',
                'question_text' => 'The office allowed the transaction\'s requirements and steps based on the information provided.',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD3',
                'question_text' => 'The steps (including payment) I needed to do for my transaction were easy and simple.',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD4',
                'question_text' => 'I easily found information about my transaction from the office or its website.',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD5',
                'question_text' => 'I paid a reasonable amount of fees for my transaction.',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD6',
                'question_text' => 'I feel the office was fair to everyone, or "walang palakasan", during my transaction.',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD7',
                'question_text' => 'I was treated courteously by the staff and (if I asked for help) the staff was helpful',
                'question_type' => QuestionType::LIKERT,
            ],
            [
                'question_code' => 'SQD8',
                'question_text' => 'I got what I needed from the government office, or (if denied) denial of request was sufficiently explained to me.',
                'question_type' => QuestionType::LIKERT,
            ],
        ];

        foreach ($questions as $question) {
            DB::table('evaluation_questions')->updateOrInsert(
                [
                    'question_code' => $question['question_code'],
                ],
                [
                    'question_text' => $question['question_text'],
                    'question_type' => $question['question_type']->value,
                    'updated_at' => now(),
                ]
            );
        }
    }
}