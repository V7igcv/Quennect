<?php

namespace App\Http\Controllers;

use App\Events\MonitorUpdated;
use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\EvaluationSession;
use App\Models\QueueTransaction;
use App\Models\QueueTransactionService;
use App\Models\ServiceAssistance;
use App\Services\MonitorDataService;
use App\Services\Sms\SmsNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class EvaluationController extends Controller
{
    public function __construct(protected MonitorDataService $monitorDataService)
    {
    }

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
            $transaction = QueueTransaction::with([
                'barangay',
                'office', // Load the office relationship
                'queueTransactionServices' => function ($query) {
                    $query->with('service.assistanceTypes');
                }
            ])
                ->where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->whereIn('status', ['SERVING', 'BACKLOG'])  // ✅ FIXED: Allow both SERVING and BACKLOG
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found or not in a state that can be evaluated.'
                ], 404);
            }

            // Check if evaluation already exists
            if ($transaction->hasEvaluation()) {
                return response()->json([
                    'message' => 'This transaction already has an evaluation.'
                ], 400);
            }

            // NEW: Check if this office requires evaluation
            $requiresEvaluation = $transaction->office?->requires_evaluation ?? true;

            // Transform queue_transaction_services to include both service and pivot data
            $services = $transaction->queueTransactionServices->map(function ($queueTransactionService) {
                $service = $queueTransactionService->service;
                return [
                    'id' => $service->id,
                    'queue_transaction_service_id' => $queueTransactionService->id,
                    'service_name' => $service->service_name,
                    'service_code' => $service->service_code,
                    'provides_assistance' => $service->provides_assistance,
                    'assistance_types' => $service->assistanceTypes->map(function ($type) {
                        return [
                            'id' => $type->id,
                            'assistance_name' => $type->assistance_name
                        ];
                    })->values()->all()
                ];
            });

            return response()->json([
                'success' => true,
                'requires_evaluation' => $requiresEvaluation, // Add this flag
                'data' => [
                    'queue_id' => $transaction->id,
                    'queue_number' => $transaction->full_queue_number,
                    'client_name' => $transaction->client_name,
                    'contact_number' => $transaction->contact_number,
                    'barangay_name' => $transaction->barangay?->barangay_name,
                    'services' => $services
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
            'responses.likert.*' => 'required|string', // question_id => rating_value or 'NA'
            'assistance_provided' => 'nullable|numeric|min:0',
            'assistance_per_queue_transaction_service' => 'nullable|array',
            'assistance_per_queue_transaction_service.*.queue_transaction_service_id' => 'required_with:assistance_per_queue_transaction_service|integer|exists:queue_transaction_services,id',
            'assistance_per_queue_transaction_service.*.assistance_type_id' => 'nullable|integer|exists:assistance_types,id',
            'assistance_per_queue_transaction_service.*.amount' => 'required_with:assistance_per_queue_transaction_service|numeric|min:0',
            'assistance_per_queue_transaction_service.*.indicator' => 'nullable|integer|in:1,2'
        ]);

        try {
            DB::beginTransaction();

            // ✅ FIXED: Allow both SERVING and BACKLOG statuses
            $transaction = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->whereIn('status', ['SERVING', 'BACKLOG'])
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found or not in a state that can be evaluated.'
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
                'queue_transaction_id' => $transaction->id,
                'client_type' => $request->input('session.client_type'),
                'sex' => $request->input('session.sex'),
                'age' => $request->input('session.age'),
            ]);

            // Save multiple choice responses
            if (!empty($multipleChoiceResponses)) {
                foreach ($multipleChoiceResponses as $questionId => $answerValue) {
                    $answerOption = $this->extractOptionNumber((string) $answerValue);

                    EvaluationResponse::create([
                        'queue_transaction_id' => $transaction->id,
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
                        'queue_transaction_id' => $transaction->id,
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

            // Update transaction status to COMPLETED
            $transaction->status = 'COMPLETED';
            $transaction->completed_at = now();
            
            // Calculate serving time based on when it was called or created
            if ($transaction->called_at) {
                $transaction->serving_time = (int) round($transaction->called_at->diffInMinutes($transaction->completed_at));
            } elseif ($transaction->created_at) {
                // For backlog transactions without called_at
                $transaction->serving_time = (int) round($transaction->created_at->diffInMinutes($transaction->completed_at));
            }
            
            // Clear counter assignment if it exists
            if ($transaction->counter_id) {
                $transaction->counter_id = null;
            }
            
            $transaction->average_satisfaction_rating = $averageRating;
            
            // Save per-queue-transaction-service assistance records
            $assistancePerQueueTxService = $request->input('assistance_per_queue_transaction_service');
            
            if (!empty($assistancePerQueueTxService) && is_array($assistancePerQueueTxService)) {
                foreach ($assistancePerQueueTxService as $serviceAssistance) {
                    $queueTransactionServiceId = $serviceAssistance['queue_transaction_service_id'] ?? null;
                    $assistanceTypeId = $serviceAssistance['assistance_type_id'] ?? null;
                    $amount = $serviceAssistance['amount'] ?? null;
                    $indicator = $serviceAssistance['indicator'] ?? null;
                    $normalizedIndicator = $indicator !== null ? (int) $indicator : null;
                    
                    if ($queueTransactionServiceId && $amount !== null) {
                        // Create or update service assistance record
                        // assistance_type_id can be NULL (traditional service) or have a value (categorized service)
                        ServiceAssistance::updateOrCreate(
                            ['queue_transaction_service_id' => $queueTransactionServiceId],
                            [
                                'assistance_type_id' => $assistanceTypeId,
                                'assistance_provided' => $amount,
                                'indicator' => in_array($normalizedIndicator, [1, 2], true) ? $normalizedIndicator : null,
                                'assistance_provided_at' => now()
                            ]
                        );
                    }
                }
            }
            
            $transaction->save();

            DB::commit();

            $this->broadcastMonitorUpdate((int) $transaction->office_id);

            // Prepare data for SMS message
            $transaction->loadMissing(['services', 'office']);
            $clientName = $transaction->client_name ?? 'Kliyente';
            $services = $transaction->services
                ->map(fn ($service) => $service->display_name)
                ->implode(', ');

            if ($services === '') {
                $services = 'inyong transaksyon';
            }

            $officeName = $transaction->office?->office_name ?? null;

            $smsSent = false;

            try {
                $smsSent = app(SmsNotificationService::class)->sendEvaluationSubmittedMessage(
                    $transaction->contact_number,
                    $clientName,
                    $services,
                    $averageRating,
                    $officeName
                );
            } catch (\Throwable $smsException) {
                Log::warning('Evaluation completed but SMS sending failed.', [
                    'queue_transaction_id' => $transaction->id,
                    'contact_number' => $transaction->contact_number,
                    'error' => $smsException->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Evaluation submitted successfully',
                'data' => [
                    'queue_number' => $transaction->full_queue_number,
                    'average_rating' => $averageRating,
                    'completed_at' => $transaction->completed_at->format('h:i A'),
                    'sms_sent' => $smsSent,
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
     * Complete transaction without evaluation
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeWithoutEvaluation($queueId)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $transaction = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'SERVING')
                ->first();

            if (!$transaction) {
                return response()->json([
                    'message' => 'Transaction not found or not currently being served.'
                ], 404);
            }

            // Update transaction status to COMPLETED
            $transaction->status = 'COMPLETED';
            $transaction->completed_at = now();
            if ($transaction->called_at) {
                $transaction->serving_time = (int) round($transaction->called_at->diffInMinutes($transaction->completed_at));
            }
            $transaction->save();

            DB::commit();

            $this->broadcastMonitorUpdate((int) $transaction->office_id);

            return response()->json([
                'message' => 'Transaction completed successfully',
                'data' => [
                    'queue_number' => $transaction->full_queue_number,
                    'completed_at' => $transaction->completed_at->format('h:i A'),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Complete without evaluation error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error completing transaction',
                'error' => $e->getMessage()
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

    private function broadcastMonitorUpdate(int $officeId): void
    {
        try {
            $monitorData = $this->monitorDataService->getOfficeMonitorData($officeId);

            if ($monitorData) {
                event(new MonitorUpdated($officeId, $monitorData));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast monitor update from evaluation submit', [
                'office_id' => $officeId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}