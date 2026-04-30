<?php

namespace App\Http\Controllers;

use App\Events\InternalRequestNotificationCreated;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationSession;
use App\Models\InternalRequestNotification;
use App\Models\InternalTransaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InternalEvaluationController extends Controller
{
    /**
     * Get evaluation questions for internal transaction
     */
    public function getQuestions()
    {
        try {
            $multipleChoiceQuestions = EvaluationQuestion::multipleChoice()
                ->orderBy('id')
                ->get()
                ->map(function($question) {
                    return [
                        'id' => $question->id,
                        'question_code' => $question->question_code,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'options' => $question->multiple_choice_options
                    ];
                });

            $likertQuestions = EvaluationQuestion::likert()
                ->orderBy('id')
                ->get()
                ->map(function($question) {
                    return [
                        'id' => $question->id,
                        'question_code' => $question->question_code,
                        'question_text' => $question->question_text,
                        'question_type' => $question->question_type,
                        'scale' => [
                            '1' => 'Strongly Disagree',
                            '2' => 'Disagree',
                            '3' => 'Neutral',
                            '4' => 'Agree',
                            '5' => 'Strongly Agree',
                            'NA' => 'Not Applicable'
                        ]
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'multiple_choice' => $multipleChoiceQuestions,
                    'likert' => $likertQuestions
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get evaluation questions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch evaluation questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Submit evaluation for completed internal transaction
     */
    public function submitEvaluation(Request $request, $id)
    {
        $request->validate([
            'session' => 'required|array',
            'session.client_type' => 'required|in:Citizen,Business,Government',
            'session.sex' => 'required|in:Male,Female',
            'session.age' => 'required|integer|min:1|max:120',
            'responses' => 'required|array',
            'responses.multiple_choice' => 'sometimes|array',
            'responses.multiple_choice.*' => 'required|string', // question_id => answer_value
            'responses.likert' => 'sometimes|array',
            'responses.likert.*' => 'required|string' // question_id => rating_value or 'NA'
        ]);

        try {
            DB::beginTransaction();
            
            $internalTransaction = InternalTransaction::findOrFail($id);
            
            // Check if transaction is completed
            if ($internalTransaction->status !== InternalTransaction::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation can only be submitted for completed transactions'
                ], 400);
            }
            
            // Check if evaluation already submitted
            $existingEvaluation = EvaluationSession::where('internal_transaction_id', $id)->exists();
            if ($existingEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation already submitted for this transaction'
                ], 400);
            }
            
            $totalRating = 0;
            $likertCount = 0;

            $multipleChoiceResponses = $request->input('responses.multiple_choice', []);
            $multipleChoiceQuestions = EvaluationQuestion::query()
                ->whereIn('question_type', ['MULTIPLE_CHOICE', 'MULTIPLE CHOICE'])
                ->whereIn('question_code', ['CC1', 'CC2', 'CC3'])
                ->get()
                ->keyBy('question_code');

            $cc1Question = $multipleChoiceQuestions->get('CC1');
            $cc2Question = $multipleChoiceQuestions->get('CC2');
            $cc3Question = $multipleChoiceQuestions->get('CC3');

            $cc1Answer = $cc1Question ? ($multipleChoiceResponses[$cc1Question->id] ?? null) : null;

            if (empty($cc1Answer)) {
                throw ValidationException::withMessages([
                    'responses.multiple_choice' => ['CC1 answer is required.'],
                ]);
            }

            if ((string) $cc1Answer === '4') {
                if ($cc2Question) {
                    $multipleChoiceResponses[$cc2Question->id] = $this->resolveNaAnswerValue($cc2Question, '5');
                }

                if ($cc3Question) {
                    $multipleChoiceResponses[$cc3Question->id] = $this->resolveNaAnswerValue($cc3Question, '4');
                }
            } else {
                $cc2Answer = $cc2Question ? ($multipleChoiceResponses[$cc2Question->id] ?? null) : null;
                $cc3Answer = $cc3Question ? ($multipleChoiceResponses[$cc3Question->id] ?? null) : null;

                if ($cc2Question && empty($cc2Answer)) {
                    throw ValidationException::withMessages([
                        'responses.multiple_choice' => ['CC2 answer is required when CC1 is 1-3.'],
                    ]);
                }

                if ($cc3Question && empty($cc3Answer)) {
                    throw ValidationException::withMessages([
                        'responses.multiple_choice' => ['CC3 answer is required when CC1 is 1-3.'],
                    ]);
                }
            }

            $evaluationSession = EvaluationSession::create([
                'internal_transaction_id' => $internalTransaction->id,
                'client_type' => 'Government', // Force Government as instructed
                'sex' => $request->input('session.sex'),
                'age' => $request->input('session.age'),
            ]);

            // Save multiple choice responses
            if (!empty($multipleChoiceResponses)) {
                foreach ($multipleChoiceResponses as $questionId => $answerValue) {
                    $answerOption = $this->extractOptionNumber((string) $answerValue);

                    EvaluationResponse::create([
                        'internal_transaction_id' => $internalTransaction->id,
                        'evaluation_session_id' => $evaluationSession->id,
                        'question_id' => $questionId,
                        'answer_value' => $answerValue,
                        'answer_option' => $answerOption,
                        'rating_value' => null
                    ]);
                }
            }

            // Save likert responses and calculate average
            if (isset($request->responses['likert'])) {
                foreach ($request->responses['likert'] as $questionId => $value) {
                    $ratingValue = null;
                    
                    // Convert 'NA' to null, otherwise use the numeric value
                    if ($value !== 'NA') {
                        $ratingValue = (int) $value;
                        $totalRating += $ratingValue;
                        $likertCount++;
                    }

                    EvaluationResponse::create([
                        'internal_transaction_id' => $internalTransaction->id,
                        'evaluation_session_id' => $evaluationSession->id,
                        'question_id' => $questionId,
                        'answer_value' => $value,
                        'answer_option' => $ratingValue,
                        'rating_value' => $ratingValue
                    ]);
                }
            }

            // Calculate average satisfaction rating
            $averageRating = null;
            if ($likertCount > 0) {
                $averageRating = round($totalRating / $likertCount, 2);
            }

            $internalTransaction->average_satisfaction_rating = $averageRating;
            $internalTransaction->evaluated_at = now();
            $internalTransaction->save();

            DB::commit();

            // Fire notification AFTER commit so a Pusher failure doesn't roll back the evaluation
            try {
                foreach ($requesterUsers as $user) {
                    $notification = InternalRequestNotification::create([
                        'internal_transaction_id' => $internalTransaction->id,
                        'user_id' => $user->id,
                        'title' => 'Evaluation Completed',
                        'message' => "The service evaluation for your request (#{$internalTransaction->transaction_id}) has been submitted successfully.",
                        'type' => 'evaluation_completed',
                    ]);

                    event(new InternalRequestNotificationCreated($notification));
                }
            } catch (\Exception $notifEx) {
                Log::warning('submitEvaluation: notification failed for ' . $internalTransaction->transaction_id . ': ' . $notifEx->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluation submitted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to submit evaluation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evaluation: ' . $e->getMessage()
            ], 500);
        }
    }

    private function resolveNaAnswerValue(EvaluationQuestion $question, string $fallback): string
    {
        $options = $question->multiple_choice_options ?? [];

        foreach ($options as $option) {
            $optionText = (string) $option;

            if (stripos($optionText, 'N/A') === false) {
                continue;
            }

            if (preg_match('/^(\d+)\s*[-.)]/', $optionText, $matches)) {
                return (string) $matches[1];
            }

            return 'NA';
        }

        return $fallback;
    }

    private function extractOptionNumber(string $answerValue): ?int
    {
        $trimmed = trim($answerValue);

        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d+)/', $trimmed, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
    
    /**
     * Check if evaluation has been submitted
     */
    public function checkEvaluationStatus($id)
    {
        try {
            $hasEvaluation = EvaluationSession::where('internal_transaction_id', $id)->exists();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'has_evaluation' => $hasEvaluation
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check evaluation status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to check evaluation status'
            ], 500);
        }
    }
}