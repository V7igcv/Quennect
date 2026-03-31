<?php

namespace App\Http\Controllers;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\InternalTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InternalEvaluationController extends Controller
{
    /**
     * Get evaluation questions for internal transaction
     */
    public function getQuestions()
    {
        try {
            $questions = EvaluationQuestion::orderBy('id')->get();
            
            return response()->json([
                'success' => true,
                'data' => $questions->map(function ($question) {
                    return [
                        'id' => $question->id,
                        'code' => $question->question_code,
                        'text' => $question->question_text,
                        'type' => $question->question_type,
                        'options' => $question->multiple_choice_options
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch evaluation questions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch evaluation questions'
            ], 500);
        }
    }
    
    /**
     * Submit evaluation for completed internal transaction
     */
    public function submitEvaluation(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'responses' => 'required|array',
                'responses.*.question_id' => 'required|exists:evaluation_questions,id',
                'responses.*.answer_value' => 'required|string',
                'responses.*.rating_value' => 'nullable|integer|min:1|max:5'
            ]);
            
            $internalTransaction = InternalTransaction::findOrFail($id);
            
            // Check if transaction is completed
            if ($internalTransaction->status !== InternalTransaction::STATUS_COMPLETED) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation can only be submitted for completed transactions'
                ], 400);
            }
            
            // Check if evaluation already submitted
            $existingEvaluation = EvaluationResponse::where('internal_transaction_id', $id)->exists();
            if ($existingEvaluation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Evaluation already submitted for this transaction'
                ], 400);
            }
            
            DB::transaction(function () use ($validated, $internalTransaction, $id) {
                foreach ($validated['responses'] as $response) {
                    EvaluationResponse::create([
                        'internal_transaction_id' => $id,
                        'question_id' => $response['question_id'],
                        'answer_value' => $response['answer_value'],
                        'rating_value' => $response['rating_value'] ?? null,
                    ]);
                }
                
                // Compute average satisfaction rating
                $averageRating = EvaluationResponse::where('internal_transaction_id', $id)
                    ->whereNotNull('rating_value')
                    ->avg('rating_value');
                
                $internalTransaction->update([
                    'average_satisfaction_rating' => $averageRating ? round($averageRating, 2) : null
                ]);
            });
            
            return response()->json([
                'success' => true,
                'message' => 'Evaluation submitted successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to submit evaluation: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit evaluation: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Check if evaluation has been submitted
     */
    public function checkEvaluationStatus($id)
    {
        try {
            $hasEvaluation = EvaluationResponse::where('internal_transaction_id', $id)->exists();
            
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