<?php

namespace App\Http\Controllers;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\InternalTransaction;
use App\Models\QueueTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CsmAnalyticsController extends Controller
{
    private const CC_CHART_CONFIG = [
        'CC1' => [
            'key' => 'awareness',
            'label' => 'Awareness',
            'default_question' => 'Which of the following best describes your awareness of a CC?',
            'allowed_options' => [1, 2, 3, 4],
        ],
        'CC2' => [
            'key' => 'visibility',
            'label' => 'Visibility',
            'default_question' => 'If aware of CC, would you say that the CC of this office was...?',
            'allowed_options' => [1, 2, 3, 4, 5],
        ],
        'CC3' => [
            'key' => 'helpfulness',
            'label' => 'Helpfulness',
            'default_question' => 'If aware of CC, how much did the CC help you in your transactions?',
            'allowed_options' => [1, 2, 3, 4],
        ],
    ];

    private const SQD_CRITERIA = [
        1 => 'Strongly Disagree',
        2 => 'Disagree',
        3 => 'Neither Agree nor Disagree',
        4 => 'Agree',
        5 => 'Strongly Agree',
        0 => 'Not Applicable',
    ];

    /**
     * Get CSM overview metrics.
     *
     * Supported filters:
     * - service_type=external|internal|all (default: external)
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getOverviewStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validated = $request->validate([
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $cacheKey = $this->buildOverviewCacheKey(
                officeId: $officeId,
                serviceType: $serviceType,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use (
                $officeId,
                $serviceType,
                $period,
                $date,
                $month,
                $year
            ) {
                $queueTransactionsQuery = QueueTransaction::query()
                    ->where('office_id', $officeId)
                    ->whereHas('services', function (Builder $query) {
                        $query->where('service_type', 'External');
                    });

                $filteredQueueTransactionsQuery = $this->applyQueueDateFilter(
                    $queueTransactionsQuery,
                    $period,
                    $date,
                    $month,
                    $year
                );

                $internalTransactionsQuery = InternalTransaction::query()
                    ->where('office_id', $officeId);

                $filteredInternalTransactionsQuery = $this->applyInternalDateFilter(
                    $internalTransactionsQuery,
                    $period,
                    $date,
                    $month,
                    $year
                );

                // Count only queue respondents that actually submitted an evaluation.
                $externalTransactionCount = (clone $filteredQueueTransactionsQuery)
                    ->whereExists(function ($subQuery) {
                        $subQuery->selectRaw('1')
                            ->from('evaluation_sessions as es')
                            ->whereColumn('es.queue_transaction_id', 'queue_transactions.id');
                    })
                    ->count();
                $internalTransactionCount = (clone $filteredInternalTransactionsQuery)->count();

                [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);
                $overallScoreData = $this->computeOverallScorePerServiceData(
                    officeId: $officeId,
                    serviceType: $serviceType,
                    startDate: $startDate,
                    endDate: $endDate,
                );

                $totalTransactions = match ($serviceType) {
                    'internal' => $internalTransactionCount,
                    'all' => $externalTransactionCount + $internalTransactionCount,
                    default => $externalTransactionCount,
                };

                $awareness = $this->computeCcMetric(
                    questionPrefix: 'CC1',
                    includedOptions: [1, 2, 3],
                    serviceType: $serviceType,
                    officeId: $officeId,
                    period: $period,
                    date: $date,
                    month: $month,
                    year: $year,
                );

                $visibility = $this->computeCcMetric(
                    questionPrefix: 'CC2',
                    includedOptions: [1],
                    serviceType: $serviceType,
                    officeId: $officeId,
                    period: $period,
                    date: $date,
                    month: $month,
                    year: $year,
                );

                $helpfulness = $this->computeCcMetric(
                    questionPrefix: 'CC3',
                    includedOptions: [1],
                    serviceType: $serviceType,
                    officeId: $officeId,
                    period: $period,
                    date: $date,
                    month: $month,
                    year: $year,
                );

                return [
                    'service_type' => $serviceType,
                    'total_transactions' => $totalTransactions,
                    'cc_awareness' => $awareness,
                    'cc_visibility' => $visibility,
                    'cc_helpfulness' => $helpfulness,
                    'overall_score' => $overallScoreData['service_total_percentage'],
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'service_type' => $serviceType,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM overview analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching CSM overview analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CSM Citizen's Charter chart data.
     *
     * Response shape is aligned with CSMAnalytics.vue ccData structure:
     * - awareness: [{ description, count, percentage, option, label }]
     * - visibility: [{ description, count, percentage, option, label }]
     * - helpfulness: [{ description, count, percentage, option, label }]
     */
    public function getCitizenCharterCounts(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validated = $request->validate([
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);

            $cacheKey = $this->buildCitizenCharterCacheKey(
                officeId: $officeId,
                serviceType: $serviceType,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use (
                $officeId,
                $serviceType,
                $startDate,
                $endDate
            ) {
                $meta = $this->getCitizenCharterMeta();
                $counts = $this->getCitizenCharterCountsRaw($officeId, $serviceType, $startDate, $endDate);

                return $this->buildCitizenCharterPayload($counts, $meta);
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'service_type' => $serviceType,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM Citizen Charter analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching CSM Citizen Charter analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CSM SQD graph data for a selected SQD question.
     *
     * Supported filters:
    * - sqd=SQD0..SQD8
     * - service_type=external|internal|all (default: external)
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getSqdResults(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validated = $request->validate([
                'sqd' => 'nullable|string|max:10',
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $rawSqd = (string) ($validated['sqd'] ?? 'SQD0');
            $dbSqdCode = $this->normalizeSqdCode($rawSqd);
            $displaySqdCode = $dbSqdCode;

            [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);

            $cacheKey = $this->buildSqdCacheKey(
                officeId: $officeId,
                serviceType: $serviceType,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
                sqdCode: $dbSqdCode,
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use (
                $officeId,
                $serviceType,
                $startDate,
                $endDate,
                $dbSqdCode,
                $displaySqdCode
            ) {
                $question = EvaluationQuestion::query()
                    ->whereIn('question_type', ['LIKERT'])
                    ->where(function (Builder $query) use ($dbSqdCode) {
                        $query->where('question_code', $dbSqdCode)
                            ->orWhere('question_text', 'like', $dbSqdCode . '%');
                    })
                    ->first();

                $description = $question?->question_text ?? $displaySqdCode;

                $criteriaCounts = [
                    1 => 0,
                    2 => 0,
                    3 => 0,
                    4 => 0,
                    5 => 0,
                    0 => 0,
                ];

                if ($serviceType !== 'internal') {
                    $externalCounts = $this->getSqdCriteriaCountsForSource(
                        source: 'external',
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                        sqdCode: $dbSqdCode,
                    );

                    foreach ($externalCounts as $key => $value) {
                        $criteriaCounts[$key] += $value;
                    }
                }

                if ($serviceType !== 'external') {
                    $internalCounts = $this->getSqdCriteriaCountsForSource(
                        source: 'internal',
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                        sqdCode: $dbSqdCode,
                    );

                    foreach ($internalCounts as $key => $value) {
                        $criteriaCounts[$key] += $value;
                    }
                }

                $totalRespondents = $this->getSqdTotalRespondents(
                    officeId: $officeId,
                    serviceType: $serviceType,
                    startDate: $startDate,
                    endDate: $endDate,
                );

                $naCount = $criteriaCounts[0];
                $numerator = $criteriaCounts[4] + $criteriaCounts[5];
                $denominator = $totalRespondents - $naCount;
                $overallPercentage = $denominator <= 0
                    ? 0.0
                    : round(($numerator / $denominator) * 100, 2);

                $distribution = [
                    [
                        'criteria' => self::SQD_CRITERIA[1],
                        'value' => $criteriaCounts[1],
                        'option' => 1,
                    ],
                    [
                        'criteria' => self::SQD_CRITERIA[2],
                        'value' => $criteriaCounts[2],
                        'option' => 2,
                    ],
                    [
                        'criteria' => self::SQD_CRITERIA[3],
                        'value' => $criteriaCounts[3],
                        'option' => 3,
                    ],
                    [
                        'criteria' => self::SQD_CRITERIA[4],
                        'value' => $criteriaCounts[4],
                        'option' => 4,
                    ],
                    [
                        'criteria' => self::SQD_CRITERIA[5],
                        'value' => $criteriaCounts[5],
                        'option' => 5,
                    ],
                    [
                        'criteria' => self::SQD_CRITERIA[0],
                        'value' => $criteriaCounts[0],
                        'option' => 0,
                    ],
                ];

                return [
                    'sqd_code' => $displaySqdCode,
                    'question_code' => $dbSqdCode,
                    'description' => $description,
                    'distribution' => $distribution,
                    'total_responses' => $totalRespondents,
                    'overall_percentage' => $overallPercentage,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'service_type' => $serviceType,
                    'sqd' => $displaySqdCode,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM SQD analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching CSM SQD analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CSM demographic profile pie-chart data.
     *
     * Supported filters:
     * - category=age|sex|client_type (also accepts Age|Sex|Client Type)
     * - service_type=external|internal|all (default: external)
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getDemographicProfile(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validated = $request->validate([
                'category' => 'nullable|string|max:30',
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $category = $this->normalizeDemographicCategory((string) ($validated['category'] ?? 'age'));
            [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);

            $cacheKey = $this->buildDemographicCacheKey(
                officeId: $officeId,
                serviceType: $serviceType,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
                category: $category,
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use (
                $officeId,
                $serviceType,
                $startDate,
                $endDate,
                $category
            ) {
                $segments = $this->getDemographicSegments($category);
                $counts = array_fill_keys($segments, 0);

                if ($serviceType !== 'internal') {
                    $externalCounts = $this->getDemographicCountsForSource(
                        source: 'external',
                        category: $category,
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                    );

                    foreach ($externalCounts as $segment => $value) {
                        $counts[$segment] = ($counts[$segment] ?? 0) + $value;
                    }
                }

                if ($serviceType !== 'external') {
                    $internalCounts = $this->getDemographicCountsForSource(
                        source: 'internal',
                        category: $category,
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                    );

                    foreach ($internalCounts as $segment => $value) {
                        $counts[$segment] = ($counts[$segment] ?? 0) + $value;
                    }
                }

                $totalResponses = array_sum($counts);
                $distribution = [];

                foreach ($segments as $segment) {
                    $value = (int) ($counts[$segment] ?? 0);
                    $distribution[] = [
                        'name' => $segment,
                        'value' => $value,
                        'percentage' => $totalResponses === 0
                            ? 0
                            : round(($value / $totalResponses) * 100, 2),
                    ];
                }

                return [
                    'category' => $this->getDemographicCategoryDisplayName($category),
                    'distribution' => $distribution,
                    'total_responses' => $totalResponses,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'service_type' => $serviceType,
                    'category' => $payload['category'],
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM demographic analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching CSM demographic analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get CSM Overall Score Per Service chart data.
     *
     * Formula per service:
    * ((Strongly Agree + Agree) / (Total SQD Answers - N/A)) * 100
     *
     * Service total formula:
     * (Sum of service percentages / Number of services)
     */
    public function getOverallScorePerService(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $validated = $request->validate([
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);

            $cacheKey = $this->buildOverallScorePerServiceCacheKey(
                officeId: $officeId,
                serviceType: $serviceType,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );

            $payload = Cache::remember($cacheKey, now()->addMinutes(3), function () use (
                $officeId,
                $serviceType,
                $startDate,
                $endDate
            ) {
                return $this->computeOverallScorePerServiceData(
                    officeId: $officeId,
                    serviceType: $serviceType,
                    startDate: $startDate,
                    endDate: $endDate,
                );
            });

            return response()->json([
                'success' => true,
                'data' => $payload,
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'service_type' => $serviceType,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM overall score per service analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching CSM overall score per service analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function computeCcMetric(
        string $questionPrefix,
        array $includedOptions,
        string $serviceType,
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): float {
        $externalCounts = ['total' => 0, 'included' => 0];
        $internalCounts = ['total' => 0, 'included' => 0];

        if ($serviceType !== 'internal') {
            $externalCounts = $this->getCcCountsForSource(
                source: 'external',
                questionPrefix: $questionPrefix,
                includedOptions: $includedOptions,
                officeId: $officeId,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );
        }

        if ($serviceType !== 'external') {
            $internalCounts = $this->getCcCountsForSource(
                source: 'internal',
                questionPrefix: $questionPrefix,
                includedOptions: $includedOptions,
                officeId: $officeId,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );
        }

        $total = $externalCounts['total'] + $internalCounts['total'];
        $included = $externalCounts['included'] + $internalCounts['included'];

        if ($total === 0) {
            return 0.0;
        }

        return round(($included / $total) * 100, 2);
    }

    private function getCcCountsForSource(
        string $source,
        string $questionPrefix,
        array $includedOptions,
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $answerOptionExpression = $this->getAnswerOptionExpression();
        $includedOptionList = implode(',', array_map('intval', $includedOptions));

        $query = EvaluationResponse::query()
            ->where(function (Builder $innerQuery) {
                $innerQuery
                    ->whereNotNull('answer_option')
                    ->orWhereNotNull('answer_value');
            })
            ->whereHas('question', function (Builder $questionQuery) use ($questionPrefix) {
                $questionQuery->where(function (Builder $innerQuery) use ($questionPrefix) {
                    $innerQuery
                        ->where('question_code', $questionPrefix)
                        ->orWhere('question_text', 'like', $questionPrefix . '%');
                });
            });

        if ($source === 'external') {
            $query
                ->whereNotNull('queue_transaction_id')
                ->whereHas('queueTransaction', function (Builder $transactionQuery) use ($officeId, $period, $date, $month, $year) {
                    $transactionQuery
                        ->where('office_id', $officeId)
                        ->whereHas('services', function (Builder $serviceQuery) {
                            $serviceQuery->where('service_type', 'External');
                        });

                    $this->applyQueueDateFilter($transactionQuery, $period, $date, $month, $year);
                });
        }

        if ($source === 'internal') {
            $query
                ->whereNotNull('internal_transaction_id')
                ->whereHas('internalTransaction', function (Builder $transactionQuery) use ($officeId, $period, $date, $month, $year) {
                    $transactionQuery->where('office_id', $officeId);

                    $this->applyInternalDateFilter($transactionQuery, $period, $date, $month, $year);
                });
        }

        $counts = $query
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} IN ({$includedOptionList}) THEN 1 ELSE 0 END) AS included")
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'included' => (int) ($counts?->included ?? 0),
        ];
    }

    private function getAnswerOptionExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "COALESCE(answer_option, NULLIF(SUBSTRING(answer_value FROM '^\\d+'), '')::INT)";
        }

        if ($driver === 'mysql') {
            return "COALESCE(answer_option, CAST(REGEXP_SUBSTR(answer_value, '^[0-9]+') AS UNSIGNED))";
        }

        // Fallback for unsupported drivers: only use normalized numeric column.
        return 'answer_option';
    }

    private function resolveOfficeId($user, array $validated): int
    {
        if ($user->isSuperadmin()) {
            if (empty($validated['office_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'office_id' => ['The office_id field is required for superadmin analytics.'],
                ]);
            }

            return (int) $validated['office_id'];
        }

        if (!$user->office_id) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'office_id' => ['No office is assigned to this user.'],
            ]);
        }

        return (int) $user->office_id;
    }

    private function applyQueueDateFilter(
        Builder $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): Builder {
        return match ($period) {
            'monthly' => $query
                ->whereYear('queue_date', $year)
                ->whereMonth('queue_date', $month),
            'yearly' => $query
                ->whereYear('queue_date', $year),
            default => $query
                ->whereDate('queue_date', $date->toDateString()),
        };
    }

    private function applyInternalDateFilter(
        Builder $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): Builder {
        return match ($period) {
            'monthly' => $query
                ->whereYear('transaction_date', $year)
                ->whereMonth('transaction_date', $month),
            'yearly' => $query
                ->whereYear('transaction_date', $year),
            default => $query
                ->whereDate('transaction_date', $date->toDateString()),
        };
    }

    private function buildFilterPayload(string $period, Carbon $date, int $month, int $year): array
    {
        return match ($period) {
            'monthly' => [
                'period' => 'monthly',
                'month' => $month,
                'year' => $year,
            ],
            'yearly' => [
                'period' => 'yearly',
                'year' => $year,
            ],
            default => [
                'period' => 'daily',
                'date' => $date->toDateString(),
            ],
        };
    }

    private function buildOverviewCacheKey(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): string {
        return match ($period) {
            'monthly' => sprintf(
                'csm:overview:v2:office:%d:service:%s:period:monthly:month:%d:year:%d',
                $officeId,
                $serviceType,
                $month,
                $year
            ),
            'yearly' => sprintf(
                'csm:overview:v2:office:%d:service:%s:period:yearly:year:%d',
                $officeId,
                $serviceType,
                $year
            ),
            default => sprintf(
                'csm:overview:v2:office:%d:service:%s:period:daily:date:%s',
                $officeId,
                $serviceType,
                $date->toDateString()
            ),
        };
    }

    private function buildCitizenCharterCacheKey(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): string {
        return match ($period) {
            'monthly' => sprintf(
                'csm:citizen-charter:v1:office:%d:service:%s:period:monthly:month:%d:year:%d',
                $officeId,
                $serviceType,
                $month,
                $year
            ),
            'yearly' => sprintf(
                'csm:citizen-charter:v1:office:%d:service:%s:period:yearly:year:%d',
                $officeId,
                $serviceType,
                $year
            ),
            default => sprintf(
                'csm:citizen-charter:v1:office:%d:service:%s:period:daily:date:%s',
                $officeId,
                $serviceType,
                $date->toDateString()
            ),
        };
    }

    private function buildSqdCacheKey(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year,
        string $sqdCode
    ): string {
        return match ($period) {
            'monthly' => sprintf(
                'csm:sqd:v1:office:%d:service:%s:period:monthly:month:%d:year:%d:sqd:%s',
                $officeId,
                $serviceType,
                $month,
                $year,
                $sqdCode
            ),
            'yearly' => sprintf(
                'csm:sqd:v1:office:%d:service:%s:period:yearly:year:%d:sqd:%s',
                $officeId,
                $serviceType,
                $year,
                $sqdCode
            ),
            default => sprintf(
                'csm:sqd:v1:office:%d:service:%s:period:daily:date:%s:sqd:%s',
                $officeId,
                $serviceType,
                $date->toDateString(),
                $sqdCode
            ),
        };
    }

    private function buildDemographicCacheKey(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year,
        string $category
    ): string {
        return match ($period) {
            'monthly' => sprintf(
                'csm:demographic:v1:office:%d:service:%s:period:monthly:month:%d:year:%d:category:%s',
                $officeId,
                $serviceType,
                $month,
                $year,
                $category
            ),
            'yearly' => sprintf(
                'csm:demographic:v1:office:%d:service:%s:period:yearly:year:%d:category:%s',
                $officeId,
                $serviceType,
                $year,
                $category
            ),
            default => sprintf(
                'csm:demographic:v1:office:%d:service:%s:period:daily:date:%s:category:%s',
                $officeId,
                $serviceType,
                $date->toDateString(),
                $category
            ),
        };
    }

    private function buildOverallScorePerServiceCacheKey(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): string {
        return match ($period) {
            'monthly' => sprintf(
                'csm:overall-service:v2:office:%d:service:%s:period:monthly:month:%d:year:%d',
                $officeId,
                $serviceType,
                $month,
                $year
            ),
            'yearly' => sprintf(
                'csm:overall-service:v2:office:%d:service:%s:period:yearly:year:%d',
                $officeId,
                $serviceType,
                $year
            ),
            default => sprintf(
                'csm:overall-service:v2:office:%d:service:%s:period:daily:date:%s',
                $officeId,
                $serviceType,
                $date->toDateString()
            ),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(string $period, Carbon $date, int $month, int $year): array
    {
        if ($period === 'yearly') {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            return [$start->toDateString(), $start->copy()->addYear()->toDateString()];
        }

        if ($period === 'monthly') {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
        }

        $start = $date->copy()->startOfDay();
        return [$start->toDateString(), $start->copy()->addDay()->toDateString()];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getCitizenCharterMeta(): array
    {
        $questions = EvaluationQuestion::query()
            ->whereIn('question_type', ['MULTIPLE_CHOICE', 'MULTIPLE CHOICE'])
            ->where(function (Builder $query) {
                foreach (array_keys(self::CC_CHART_CONFIG) as $questionCode) {
                    $query->orWhere('question_code', $questionCode)
                        ->orWhere('question_text', 'like', $questionCode . '%');
                }
            })
            ->get();

        $meta = [];

        foreach (self::CC_CHART_CONFIG as $questionCode => $config) {
            $question = $questions->first(function (EvaluationQuestion $item) use ($questionCode) {
                if (strtoupper((string) $item->question_code) === $questionCode) {
                    return true;
                }

                return str_starts_with(strtoupper((string) $item->question_text), $questionCode);
            });

            $options = [];

            if ($question) {
                $options = $this->mapQuestionOptionsByNumber($question->multiple_choice_options ?? []);
            }

            $meta[$questionCode] = [
                'key' => $config['key'],
                'label' => $config['label'],
                'question_text' => $question?->question_text ?? $config['default_question'],
                'allowed_options' => $config['allowed_options'],
                'option_descriptions' => $options,
            ];
        }

        return $meta;
    }

    /**
     * @param array<int, string> $options
     * @return array<int, string>
     */
    private function mapQuestionOptionsByNumber(array $options): array
    {
        $mapped = [];

        foreach ($options as $optionText) {
            $text = (string) $optionText;

            if (preg_match('/^(\d+)\s*[-.)]\s*(.+)$/', $text, $matches) === 1) {
                $mapped[(int) $matches[1]] = trim($matches[2]);
                continue;
            }

            if (preg_match('/^(\d+)\s*(.+)$/', $text, $matches) === 1) {
                $mapped[(int) $matches[1]] = trim($matches[2]);
            }
        }

        return $mapped;
    }

    private function getCitizenCharterCountsRaw(
        int $officeId,
        string $serviceType,
        string $startDate,
        string $endDate
    ) {
        $externalRows = collect();
        $internalRows = collect();

        if ($serviceType !== 'internal') {
            $externalRows = $this->getCitizenCharterCountsRawForSource(
                source: 'external',
                officeId: $officeId,
                startDate: $startDate,
                endDate: $endDate,
            );
        }

        if ($serviceType !== 'external') {
            $internalRows = $this->getCitizenCharterCountsRawForSource(
                source: 'internal',
                officeId: $officeId,
                startDate: $startDate,
                endDate: $endDate,
            );
        }

        return $externalRows
            ->concat($internalRows)
            ->groupBy(function ($row) {
                return $row->question_code . '|' . $row->answer_option;
            })
            ->map(function ($rows) {
                return (object) [
                    'question_code' => $rows->first()->question_code,
                    'answer_option' => (int) $rows->first()->answer_option,
                    'response_count' => (int) $rows->sum(function ($row) {
                        return (int) $row->response_count;
                    }),
                ];
            })
            ->values();
    }

    private function getCitizenCharterCountsRawForSource(
        string $source,
        int $officeId,
        string $startDate,
        string $endDate
    ) {
        $answerOptionExpression = $this->getAnswerOptionExpression();
        $questionCodeExpression = $this->getQuestionCodeExpression();

        $query = DB::table('evaluation_responses as er')
            ->join('evaluation_questions as eq', 'eq.id', '=', 'er.question_id')
            ->whereRaw("{$questionCodeExpression} IN ('CC1', 'CC2', 'CC3')")
            ->whereRaw("{$answerOptionExpression} IS NOT NULL");

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'er.queue_transaction_id')
                ->whereNotNull('er.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.queue_transaction_id', 'qt.id')
                        ->where('s.service_type', 'External');
                });
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->whereNotNull('er.internal_transaction_id')
                ->where('it.office_id', $officeId)
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.internal_transaction_id', 'it.id')
                        ->where('s.service_type', 'Internal');
                });
        }

        return $query
            ->groupByRaw($questionCodeExpression)
            ->groupByRaw($answerOptionExpression)
            ->selectRaw("{$questionCodeExpression} AS question_code")
            ->selectRaw("{$answerOptionExpression} AS answer_option")
            ->selectRaw('COUNT(*) AS response_count')
            ->get();
    }

    private function getQuestionCodeExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "COALESCE(eq.question_code, UPPER(SUBSTRING(eq.question_text FROM '^(CC[0-9]+)')))";
        }

        if ($driver === 'mysql') {
            return "COALESCE(eq.question_code, UPPER(SUBSTRING_INDEX(eq.question_text, ' ', 1)))";
        }

        return 'eq.question_code';
    }

    private function normalizeSqdCode(string $input): string
    {
        $normalized = strtoupper(trim($input));

        if (preg_match('/^S[QD]D?(\d)$/', $normalized, $matches) === 1) {
            return 'SQD' . $matches[1];
        }

        if (preg_match('/^SQD(\\d)$/', $normalized, $matches) === 1) {
            return 'SQD' . $matches[1];
        }

        return 'SQD0';
    }

    private function normalizeDemographicCategory(string $input): string
    {
        $normalized = strtolower(trim($input));

        return match ($normalized) {
            'age' => 'age',
            'sex' => 'sex',
            'client type', 'client_type', 'clienttype' => 'client_type',
            default => 'age',
        };
    }

    private function getDemographicCategoryDisplayName(string $category): string
    {
        return match ($category) {
            'sex' => 'Sex',
            'client_type' => 'Client Type',
            default => 'Age',
        };
    }

    /**
     * @return array<int, string>
     */
    private function getDemographicSegments(string $category): array
    {
        return match ($category) {
            'sex' => [
                'Male',
                'Female',
                'Did not specify',
            ],
            'client_type' => [
                'Citizen',
                'Business',
                'Government',
                'Did not specify',
            ],
            default => [
                '19 or lower',
                '20-34',
                '35-49',
                '50-64',
                '65-Higher',
                'Did not specify',
            ],
        };
    }

    /**
     * @return array<string, int>
     */
    private function getDemographicCountsForSource(
        string $source,
        string $category,
        int $officeId,
        string $startDate,
        string $endDate
    ): array {
        $segments = $this->getDemographicSegments($category);
        $counts = array_fill_keys($segments, 0);

        $segmentExpression = $this->getDemographicSegmentExpression($category);

        $query = DB::table('evaluation_sessions as es')
            ->selectRaw("{$segmentExpression} AS segment")
            ->selectRaw('COUNT(*) AS response_count');

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'es.queue_transaction_id')
                ->whereNotNull('es.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.queue_transaction_id', 'qt.id')
                        ->where('s.service_type', 'External');
                });
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'es.internal_transaction_id')
                ->whereNotNull('es.internal_transaction_id')
                ->where('it.office_id', $officeId)
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.internal_transaction_id', 'it.id')
                        ->where('s.service_type', 'Internal');
                });
        }

        $rows = $query
            ->groupByRaw($segmentExpression)
            ->get();

        foreach ($rows as $row) {
            $segment = (string) $row->segment;
            if (!array_key_exists($segment, $counts)) {
                continue;
            }
            $counts[$segment] = (int) $row->response_count;
        }

        return $counts;
    }

    private function getDemographicSegmentExpression(string $category): string
    {
        return match ($category) {
            'sex' => "CASE
                WHEN es.sex IS NULL OR TRIM(es.sex) = '' THEN 'Did not specify'
                WHEN LOWER(TRIM(es.sex)) = 'male' THEN 'Male'
                WHEN LOWER(TRIM(es.sex)) = 'female' THEN 'Female'
                ELSE 'Did not specify'
            END",
            'client_type' => "CASE
                WHEN es.client_type IS NULL OR TRIM(es.client_type) = '' THEN 'Did not specify'
                WHEN LOWER(TRIM(es.client_type)) = 'citizen' THEN 'Citizen'
                WHEN LOWER(TRIM(es.client_type)) = 'business' THEN 'Business'
                WHEN LOWER(TRIM(es.client_type)) = 'government' THEN 'Government'
                ELSE 'Did not specify'
            END",
            default => "CASE
                WHEN es.age IS NULL THEN 'Did not specify'
                WHEN es.age <= 19 THEN '19 or lower'
                WHEN es.age BETWEEN 20 AND 34 THEN '20-34'
                WHEN es.age BETWEEN 35 AND 49 THEN '35-49'
                WHEN es.age BETWEEN 50 AND 64 THEN '50-64'
                WHEN es.age >= 65 THEN '65-Higher'
                ELSE 'Did not specify'
            END",
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getOverallScorePerServiceRowsForSource(
        string $source,
        int $officeId,
        string $startDate,
        string $endDate
    ): array {
        $answerOptionExpression = $this->getAnswerOptionExpression();
        $sqdCodes = ['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8'];

        $query = DB::table('evaluation_responses as er')
            ->join('evaluation_questions as eq', 'eq.id', '=', 'er.question_id')
            // Use all SQD questions (SQD0-SQD8) for per-service overall scoring.
            ->where(function ($innerQuery) use ($sqdCodes) {
                $innerQuery->where('eq.question_type', 'LIKERT')
                    ->where(function ($q) use ($sqdCodes) {
                        $q->whereIn('eq.question_code', $sqdCodes);

                        foreach ($sqdCodes as $sqdCode) {
                            $q->orWhere('eq.question_text', 'like', $sqdCode . '%');
                        }
                    });
            })
            ->selectRaw('s.id AS service_id')
            ->selectRaw('s.service_code AS service_code')
            ->selectRaw('s.service_name AS service_name')
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 4 THEN 1 ELSE 0 END) AS agree_count")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 5 THEN 1 ELSE 0 END) AS strongly_agree_count")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 0 OR {$answerOptionExpression} IS NULL THEN 1 ELSE 0 END) AS na_count")
            ->selectRaw('COUNT(*) AS total_answers');

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'er.queue_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.queue_transaction_id', '=', 'qt.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('s.service_type', 'External')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate);
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.internal_transaction_id', '=', 'it.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->where('it.office_id', $officeId)
                ->where('it.status', 'COMPLETED')
                ->where('s.service_type', 'Internal')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate);
        }

        return $query
            ->groupBy('s.id', 's.service_code', 's.service_name')
            ->get()
            ->map(function ($row) {
                return [
                    'service_id' => (int) $row->service_id,
                    'service_code' => (string) ($row->service_code ?? ''),
                    'service_name' => (string) $row->service_name,
                    'total_answers' => (int) ($row->total_answers ?? 0),
                    'agree_count' => (int) ($row->agree_count ?? 0),
                    'strongly_agree_count' => (int) ($row->strongly_agree_count ?? 0),
                    'na_count' => (int) ($row->na_count ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<string, mixed>
     */
    private function buildOverallScorePerServicePayload(array $rows, string $serviceType): array
    {
        $mergedByService = [];

        foreach ($rows as $row) {
            $serviceId = (int) ($row['service_id'] ?? 0);

            if (!isset($mergedByService[$serviceId])) {
                $mergedByService[$serviceId] = [
                    'service_id' => $serviceId,
                    'service_code' => (string) ($row['service_code'] ?? ''),
                    'service_name' => (string) ($row['service_name'] ?? ''),
                    'total_answers' => 0,
                    'agree_count' => 0,
                    'strongly_agree_count' => 0,
                    'na_count' => 0,
                ];
            }

            $mergedByService[$serviceId]['total_answers'] += (int) ($row['total_answers'] ?? 0);
            $mergedByService[$serviceId]['agree_count'] += (int) ($row['agree_count'] ?? 0);
            $mergedByService[$serviceId]['strongly_agree_count'] += (int) ($row['strongly_agree_count'] ?? 0);
            $mergedByService[$serviceId]['na_count'] += (int) ($row['na_count'] ?? 0);
        }

        $services = array_values($mergedByService);

        usort($services, function (array $a, array $b) {
            return strcmp((string) $a['service_name'], (string) $b['service_name']);
        });

        $chartData = [];
        $sumPercentages = 0.0;

        foreach ($services as $service) {
            $totalAnswers = (int) ($service['total_answers'] ?? 0);
            $naCount = (int) ($service['na_count'] ?? 0);
            $numerator = (int) ($service['agree_count'] ?? 0) + (int) ($service['strongly_agree_count'] ?? 0);
            $denominator = $totalAnswers - $naCount;

            $percentage = $denominator <= 0
                ? 0.0
                : round(($numerator / $denominator) * 100, 2);

            $scale = $this->resolvePerformanceScale($percentage);

            $sumPercentages += $percentage;

            $chartData[] = [
                'service_id' => (int) ($service['service_id'] ?? 0),
                'name' => $this->resolveServiceLabel(
                    serviceCode: (string) ($service['service_code'] ?? ''),
                    serviceName: (string) ($service['service_name'] ?? ''),
                ),
                'service_code' => (string) ($service['service_code'] ?? ''),
                'service_name' => (string) ($service['service_name'] ?? ''),
                'percentage' => $percentage,
                'rating' => $scale['label'],
                'color' => $scale['color'],
                'counts' => [
                    'strongly_agree' => (int) ($service['strongly_agree_count'] ?? 0),
                    'agree' => (int) ($service['agree_count'] ?? 0),
                    'na' => $naCount,
                    'total_answers' => $totalAnswers,
                    'denominator' => max($denominator, 0),
                ],
            ];
        }

        $serviceCount = count($chartData);
        $totalPercentage = $serviceCount === 0
            ? 0.0
            : round($sumPercentages / $serviceCount, 2);

        $totalScale = $this->resolvePerformanceScale($totalPercentage);

        return [
            'chart_data' => $chartData,
            'service_total_label' => $this->resolveServiceTotalLabel($serviceType),
            'service_total_percentage' => $totalPercentage,
            'service_total_rating' => $totalScale['label'],
            'service_total_color' => $totalScale['color'],
            'service_count' => $serviceCount,
            'performance_scale' => [
                ['min' => 95.0, 'max' => 100.0, 'label' => 'Outstanding', 'color' => '#22C55E'],
                ['min' => 90.0, 'max' => 94.9, 'label' => 'Very Satisfactory', 'color' => '#3B82F6'],
                ['min' => 80.0, 'max' => 89.9, 'label' => 'Satisfactory', 'color' => '#EAB308'],
                ['min' => 60.0, 'max' => 79.9, 'label' => 'Fair', 'color' => '#F97316'],
                ['min' => 0.0, 'max' => 59.9, 'label' => 'Poor', 'color' => '#EF4444'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computeOverallScorePerServiceData(
        int $officeId,
        string $serviceType,
        string $startDate,
        string $endDate
    ): array {
        $rows = [];

        if ($serviceType !== 'internal') {
            $rows = array_merge(
                $rows,
                $this->getOverallScorePerServiceRowsForSource(
                    source: 'external',
                    officeId: $officeId,
                    startDate: $startDate,
                    endDate: $endDate,
                )
            );
        }

        if ($serviceType !== 'external') {
            $rows = array_merge(
                $rows,
                $this->getOverallScorePerServiceRowsForSource(
                    source: 'internal',
                    officeId: $officeId,
                    startDate: $startDate,
                    endDate: $endDate,
                )
            );
        }

        return $this->buildOverallScorePerServicePayload($rows, $serviceType);
    }

    private function resolveServiceLabel(string $serviceCode, string $serviceName): string
    {
        $code = trim($serviceCode);
        if ($code !== '') {
            return $code;
        }

        return $serviceName;
    }

    private function resolveServiceTotalLabel(string $serviceType): string
    {
        return match ($serviceType) {
            'internal' => 'Internal Service Total',
            'all' => 'Overall Service Total',
            default => 'External Service Total',
        };
    }

    /**
     * @return array{label: string, color: string}
     */
    private function resolvePerformanceScale(float $percentage): array
    {
        if ($percentage >= 95.0) {
            return ['label' => 'Outstanding', 'color' => '#22C55E'];
        }

        if ($percentage >= 90.0) {
            return ['label' => 'Very Satisfactory', 'color' => '#3B82F6'];
        }

        if ($percentage >= 80.0) {
            return ['label' => 'Satisfactory', 'color' => '#EAB308'];
        }

        if ($percentage >= 60.0) {
            return ['label' => 'Fair', 'color' => '#F97316'];
        }

        return ['label' => 'Poor', 'color' => '#EF4444'];
    }

    /**
     * @return array<int, int>
     */
    private function getSqdCriteriaCountsForSource(
        string $source,
        int $officeId,
        string $startDate,
        string $endDate,
        string $sqdCode
    ): array {
        $answerOptionExpression = $this->getAnswerOptionExpression();

        $query = DB::table('evaluation_responses as er')
            ->join('evaluation_questions as eq', 'eq.id', '=', 'er.question_id')
            ->where(function ($q) use ($sqdCode) {
                $q->where('eq.question_code', $sqdCode)
                    ->orWhere('eq.question_text', 'like', $sqdCode . '%');
            })
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 1 THEN 1 ELSE 0 END) AS opt1")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 2 THEN 1 ELSE 0 END) AS opt2")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 3 THEN 1 ELSE 0 END) AS opt3")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 4 THEN 1 ELSE 0 END) AS opt4")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} = 5 THEN 1 ELSE 0 END) AS opt5")
            ->selectRaw("SUM(CASE WHEN {$answerOptionExpression} IS NULL THEN 1 ELSE 0 END) AS opt_na");

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'er.queue_transaction_id')
                ->whereNotNull('er.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.queue_transaction_id', 'qt.id')
                        ->where('s.service_type', 'External');
                });
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->whereNotNull('er.internal_transaction_id')
                ->where('it.office_id', $officeId)
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.internal_transaction_id', 'it.id')
                        ->where('s.service_type', 'Internal');
                });
        }

        $row = $query->first();

        return [
            1 => (int) ($row?->opt1 ?? 0),
            2 => (int) ($row?->opt2 ?? 0),
            3 => (int) ($row?->opt3 ?? 0),
            4 => (int) ($row?->opt4 ?? 0),
            5 => (int) ($row?->opt5 ?? 0),
            0 => (int) ($row?->opt_na ?? 0),
        ];
    }

    private function getSqdTotalRespondents(
        int $officeId,
        string $serviceType,
        string $startDate,
        string $endDate
    ): int {
        $externalCount = 0;
        $internalCount = 0;

        if ($serviceType !== 'internal') {
            $externalCount = DB::table('queue_transactions as qt')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.queue_transaction_id', 'qt.id')
                        ->where('s.service_type', 'External');
                })
                ->count();
        }

        if ($serviceType !== 'external') {
            $internalCount = DB::table('internal_transactions as it')
                ->where('it.office_id', $officeId)
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('queue_transaction_services as qts')
                        ->join('services as s', 's.id', '=', 'qts.service_id')
                        ->whereColumn('qts.internal_transaction_id', 'it.id')
                        ->where('s.service_type', 'Internal');
                })
                ->count();
        }

        return $externalCount + $internalCount;
    }

    /**
     * @param \Illuminate\Support\Collection<int, object> $counts
     * @param array<string, array<string, mixed>> $meta
     * @return array<string, mixed>
     */
    private function buildCitizenCharterPayload($counts, array $meta): array
    {
        $payload = [
            'awareness' => [],
            'visibility' => [],
            'helpfulness' => [],
            'questions' => [],
        ];

        foreach (self::CC_CHART_CONFIG as $questionCode => $config) {
            $questionMeta = $meta[$questionCode];

            $optionCounts = $counts
                ->filter(function ($row) use ($questionCode, $questionMeta) {
                    return strtoupper((string) $row->question_code) === $questionCode
                        && in_array((int) $row->answer_option, $questionMeta['allowed_options'], true);
                })
                ->groupBy(function ($row) {
                    return (int) $row->answer_option;
                })
                ->map(function ($rows) {
                    return (int) $rows->sum(function ($row) {
                        return (int) $row->response_count;
                    });
                });

            $totalResponses = (int) $optionCounts->sum();

            foreach ($questionMeta['allowed_options'] as $optionNumber) {
                $count = (int) ($optionCounts->get($optionNumber, 0));
                $description = $questionMeta['option_descriptions'][$optionNumber] ?? "Option {$optionNumber}";

                $payload[$config['key']][] = [
                    'option' => $optionNumber,
                    'label' => "Option {$optionNumber}",
                    'description' => $description,
                    'count' => $count,
                    'percentage' => $totalResponses === 0
                        ? 0
                        : round(($count / $totalResponses) * 100, 2),
                ];
            }

            $payload['questions'][$config['key']] = [
                'code' => $questionCode,
                'label' => $questionMeta['label'],
                'text' => $questionMeta['question_text'],
                'total_responses' => $totalResponses,
            ];
        }

        return $payload;
    }
}
