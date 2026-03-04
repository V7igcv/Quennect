<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\QuestionType;
use Ramsey\Uuid\Uuid;

class EvaluationQuestionSeeder extends Seeder
{
    public function run(): void
    {
        $questions = [
            ['CC1 Which of the following best describes your awareness of a CC?', QuestionType::MULTIPLE_CHOICE],
            ['CC2 If aware of CC (answered 1-3 in CC1), would you say that the CC of this office was…?', QuestionType::MULTIPLE_CHOICE],
            ['CC3 If aware of CC (answered 1-3 in CC1), how much did the cc help you in your transaction?', QuestionType::MULTIPLE_CHOICE],
            ['SQD0. I am satisfied with the service that I availed', QuestionType::LIKERT],
            ['SQD1. I spent a reasonable amount of time for my transaction', QuestionType::LIKERT],
            ['SQD2. The office allowed the transaction’s requirements and steps based on the information provided.', QuestionType::LIKERT],
            ['SQD3.The steps (including payment) I needed to do for my transaction were easy and simple.', QuestionType::LIKERT],
            ['SQD4. I easily found information about my transaction from the office or its website.', QuestionType::LIKERT],
            ['SQD5. I paid a reasonable amount of fees for my transaction.', QuestionType::LIKERT],
            ['SQD6. I feel the office was fair to everyone, or “walang palakasan”, during my transaction.', QuestionType::LIKERT],
            ['SQD7. I was treated courteously by the staff and (if I asked for help) the staff was helpful', QuestionType::LIKERT],
            ['SQD8. I got what I needed from the government office, or (if denied) denial of request was sufficiently explained to me.', QuestionType::LIKERT],
        ];

        foreach ($questions as $question) {
            DB::table('evaluation_questions')->insert([
                'question_text' => $question[0],
                'question_type' => $question[1]->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
