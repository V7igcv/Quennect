<?php

namespace App\Http\Controllers;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationSession;
use App\Models\QueueTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluationController extends Controller
{
    /**
     * Get all evaluation questions grouped by type
     * 
     * @return \Illuminate\Http\JsonResponse
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
            Log::error('Get questions error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching evaluation questions',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction details for evaluation modal
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionForEvaluation($queueId)
    {
        $user = Auth::user();

        try {
            $transaction = QueueTransaction::with('barangay')
                ->where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'SERVING')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found or not currently being served.'
                ], 404);
            }

            // Check if evaluation already exists
            if ($transaction->hasEvaluation()) {
                return response()->json([
                    'message' => 'This transaction already has an evaluation.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_id' => $transaction->id,
                    'queue_number' => $transaction->full_queue_number,
                    'client_name' => $transaction->client_name,
                    'contact_number' => $transaction->contact_number,
                    'barangay_name' => $transaction->barangay?->barangay_name,
                    'services' => $transaction->services->pluck('service_code')->implode(', ')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get transaction error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching transaction details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Submit evaluation responses and complete transaction
     * 
     * @param Request $request
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitEvaluation(Request $request, $queueId)
    {
        $user = Auth::user();

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

            // Find the transaction
            $transaction = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'SERVING')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found or not currently being served.'
                ], 404);
            }

            // Check if evaluation already exists
            if ($transaction->hasEvaluation()) {
                return response()->json([
                    'message' => 'This transaction already has an evaluation.'
                ], 400);
            }

            $totalRating = 0;
            $likertCount = 0;

            $evaluationSession = EvaluationSession::create([
                'queue_transaction_id' => $transaction->id,
                'client_type' => $request->input('session.client_type'),
                'sex' => $request->input('session.sex'),
                'age' => $request->input('session.age'),
            ]);

            // Save multiple choice responses
            if (isset($request->responses['multiple_choice'])) {
                foreach ($request->responses['multiple_choice'] as $questionId => $answerValue) {
                    EvaluationResponse::create([
                        'queue_transaction_id' => $transaction->id,
                        'evaluation_session_id' => $evaluationSession->id,
                        'question_id' => $questionId,
                        'answer_value' => $answerValue,
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
                        'queue_transaction_id' => $transaction->id,
                        'evaluation_session_id' => $evaluationSession->id,
                        'question_id' => $questionId,
                        'answer_value' => $value,
                        'rating_value' => $ratingValue
                    ]);
                }
            }

            // Calculate average satisfaction rating
            $averageRating = null;
            if ($likertCount > 0) {
                $averageRating = round($totalRating / $likertCount, 2);
            }

            // Update transaction status to COMPLETED
            $transaction->status = 'COMPLETED';
            $transaction->completed_at = now();
            if ($transaction->called_at) {
                $transaction->serving_time = (int) round($transaction->called_at->diffInMinutes($transaction->completed_at));
            }
            $transaction->average_satisfaction_rating = $averageRating;
            $transaction->save();

            DB::commit();

            // TODO: Send SMS notification (will be added later)

            return response()->json([
                'message' => 'Evaluation submitted successfully',
                'data' => [
                    'queue_number' => $transaction->full_queue_number,
                    'average_rating' => $averageRating,
                    'completed_at' => $transaction->completed_at->format('h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Submit evaluation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error submitting evaluation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get evaluation results for a completed transaction
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getEvaluationResults($queueId)
    {
        $user = Auth::user();

        try {
            $transaction = QueueTransaction::with(['evaluationResponses.question'])
                ->where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'COMPLETED')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Completed transaction not found.'
                ], 404);
            }

            $responses = [
                'multiple_choice' => [],
                'likert' => []
            ];

            foreach ($transaction->evaluationResponses as $response) {
                if ($response->question->question_type === 'MULTIPLE_CHOICE') {
                    $responses['multiple_choice'][] = [
                        'question_id' => $response->question_id,
                        'question_text' => $response->question->question_text,
                        'answer' => $response->answer_value
                    ];
                } else {
                    $responses['likert'][] = [
                        'question_id' => $response->question_id,
                        'question_text' => $response->question->question_text,
                        'rating' => $response->rating_value,
                        'rating_label' => $response->rating_label,
                        'answer' => $response->answer_value
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_number' => $transaction->full_queue_number,
                    'client_name' => $transaction->client_name,
                    'average_rating' => $transaction->average_satisfaction_rating,
                    'completed_at' => $transaction->completed_at,
                    'responses' => $responses
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get evaluation results error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching evaluation results',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}