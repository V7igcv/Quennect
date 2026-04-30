<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Enums\TransactionStatus;
use App\Models\EvaluationResponse;
use App\Models\Office;
use App\Models\QueueTransaction;
use App\Models\Service;
use App\Services\Analytics\ChartImageService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FrontdeskAnalyticsController extends Controller
{
    private ChartImageService $chartImageService;

    public function __construct(ChartImageService $chartImageService)
    {
        $this->chartImageService = $chartImageService;
    }

    /**
     * Get analytics card metrics for Front Desk.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getCardStats(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = $validated['month'] ?? $today->month;
            $year = $validated['year'] ?? $today->year;

            $baseQuery = QueueTransaction::query()
                ->where('office_id', $officeId)
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                });

            $filteredQuery = $this->applyDateFilter(
                $baseQuery,
                $period,
                $date,
                (int) $month,
                (int) $year
            );

            $totalClients = (clone $filteredQuery)->count();

            $totalServed = (clone $filteredQuery)
                ->where('status', TransactionStatus::COMPLETED->value)
                ->count();

            $totalSkipped = (clone $filteredQuery)
                ->where('status', TransactionStatus::SKIPPED->value)
                ->count();

            $averageWaitingTime = (clone $filteredQuery)
                ->whereNotNull('waiting_time')
                ->avg('waiting_time');

            $averageServiceTime = (clone $filteredQuery)
                ->whereNotNull('serving_time')
                ->where('status', TransactionStatus::COMPLETED->value)
                ->avg('serving_time');

            return response()->json([
                'success' => true,
                'data' => [
                    'total_clients' => $totalClients,
                    'total_served' => $totalServed,
                    'total_skipped' => $totalSkipped,
                    'average_waiting_time' => QueueTransaction::formatDuration($averageWaitingTime),
                    'average_service_time' => QueueTransaction::formatDuration($averageServiceTime),
                ],
                'filter' => $this->buildFilterPayload($period, $date, (int) $month, (int) $year),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics card stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching analytics card stats',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get client satisfaction distribution for chart bars and total responses.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getClientSatisfactionDistribution(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $queueTransactionsQuery = QueueTransaction::query()
                ->where('office_id', $officeId)
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                })
                ->where('status', TransactionStatus::COMPLETED->value)
                ->whereHas('evaluationResponses');

            $filteredQueueTransactionsQuery = $this->applyDateFilter(
                $queueTransactionsQuery,
                $period,
                $date,
                $month,
                $year
            );

            $distribution = [
                [
                    'label' => 'Strongly Disagree',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNotNull('average_satisfaction_rating')
                        ->whereRaw('ROUND(average_satisfaction_rating) = 1')
                        ->count(),
                ],
                [
                    'label' => 'Disagree',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNotNull('average_satisfaction_rating')
                        ->whereRaw('ROUND(average_satisfaction_rating) = 2')
                        ->count(),
                ],
                [
                    'label' => 'Neither',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNotNull('average_satisfaction_rating')
                        ->whereRaw('ROUND(average_satisfaction_rating) = 3')
                        ->count(),
                ],
                [
                    'label' => 'Agree',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNotNull('average_satisfaction_rating')
                        ->whereRaw('ROUND(average_satisfaction_rating) = 4')
                        ->count(),
                ],
                [
                    'label' => 'Strongly Agree',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNotNull('average_satisfaction_rating')
                        ->whereRaw('ROUND(average_satisfaction_rating) = 5')
                        ->count(),
                ],
                [
                    'label' => 'Not Applicable',
                    'value' => (clone $filteredQueueTransactionsQuery)
                        ->whereNull('average_satisfaction_rating')
                        ->count(),
                ],
            ];

            $totalResponses = EvaluationResponse::query()
                ->whereNotNull('queue_transaction_id')
                ->whereHas('queueTransaction', function (Builder $query) use ($officeId, $period, $date, $month, $year) {
                    $query->where('office_id', $officeId)
                        ->whereHas('services', function (Builder $serviceQuery) {
                            $serviceQuery->where('service_type', 'External');
                        });

                    $this->applyDateFilter($query, $period, $date, $month, $year);
                })
                ->distinct('queue_transaction_id')
                ->count('queue_transaction_id');

            return response()->json([
                'success' => true,
                'data' => [
                    'distribution' => $distribution,
                    'total_responses' => $totalResponses,
                ],
                'filter' => $this->buildFilterPayload($period, $date, $month, $year),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics client satisfaction error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching client satisfaction distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get barangay distribution for donut chart and total clients.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getBarangayDistribution(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $baseTransactionsQuery = QueueTransaction::query()
                ->with(['barangay:id,barangay_name'])
                ->where('office_id', $officeId)
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                })
                ->where('status', TransactionStatus::COMPLETED->value)
                ->whereHas('evaluationResponses');

            $filteredTransactionsQuery = $this->applyDateFilter(
                $baseTransactionsQuery,
                $period,
                $date,
                $month,
                $year
            );

            $transactions = $filteredTransactionsQuery->get();
            $totalClients = $transactions->count();

            $groupedByBarangay = $transactions->groupBy(function (QueueTransaction $transaction) {
                return $transaction->barangay_id ?? 0;
            });

            $distribution = $groupedByBarangay->map(function ($group, $barangayId) {
                /** @var QueueTransaction $first */
                $first = $group->first();
                $name = $first->barangay?->barangay_name ?? 'Unspecified';

                return [
                    'name' => $name,
                    'value' => $group->count(),
                ];
            })->values()->sortByDesc('value')->values()->all();

            $distributionWithPercentage = array_map(function (array $segment) use ($totalClients) {
                return [
                    ...$segment,
                    'percentage' => $totalClients === 0
                        ? 0
                        : round(($segment['value'] / $totalClients) * 100, 2),
                ];
            }, $distribution);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_clients' => $totalClients,
                    'distribution' => $distributionWithPercentage,
                ],
                'filter' => $this->buildFilterPayload($period, $date, $month, $year),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics barangay distribution error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching barangay distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get lane type distribution for donut chart and total clients.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     */
    public function getLaneTypeDistribution(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $baseTransactionsQuery = QueueTransaction::query()
                ->where('office_id', $officeId)
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                })
                ->where('status', TransactionStatus::COMPLETED->value)
                ->whereHas('evaluationResponses');

            $filteredTransactionsQuery = $this->applyDateFilter(
                $baseTransactionsQuery,
                $period,
                $date,
                $month,
                $year
            );

            $totalClients = (clone $filteredTransactionsQuery)->count();

            $distribution = [
                [
                    'name' => 'Regular',
                    'value' => (clone $filteredTransactionsQuery)
                        ->where('is_priority', false)
                        ->count(),
                ],
                [
                    'name' => 'Senior Citizen',
                    'value' => (clone $filteredTransactionsQuery)
                        ->whereHas('prioritySectors', function (Builder $query) {
                            $query->where('sector_name', 'Senior Citizen');
                        })
                        ->distinct('queue_transactions.id')
                        ->count('queue_transactions.id'),
                ],
                [
                    'name' => 'Pregnant',
                    'value' => (clone $filteredTransactionsQuery)
                        ->whereHas('prioritySectors', function (Builder $query) {
                            $query->where('sector_name', 'Pregnant');
                        })
                        ->distinct('queue_transactions.id')
                        ->count('queue_transactions.id'),
                ],
                [
                    'name' => 'PWD',
                    'value' => (clone $filteredTransactionsQuery)
                        ->whereHas('prioritySectors', function (Builder $query) {
                            $query->where('sector_name', 'PWD');
                        })
                        ->distinct('queue_transactions.id')
                        ->count('queue_transactions.id'),
                ],
                [
                    'name' => 'Member of Indigenous People',
                    'value' => (clone $filteredTransactionsQuery)
                        ->whereHas('prioritySectors', function (Builder $query) {
                            $query->where('sector_name', 'Member of Indigenous People');
                        })
                        ->distinct('queue_transactions.id')
                        ->count('queue_transactions.id'),
                ],
            ];

            $distributionWithPercentage = array_map(function (array $segment) use ($totalClients) {
                return [
                    ...$segment,
                    'percentage' => $totalClients === 0
                        ? 0
                        : round(($segment['value'] / $totalClients) * 100, 2),
                ];
            }, $distribution);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_clients' => $totalClients,
                    'distribution' => $distributionWithPercentage,
                ],
                'filter' => $this->buildFilterPayload($period, $date, $month, $year),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics lane type error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching lane type distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assistance distribution for donut chart and summary totals.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     * - barangay_id=<id> (optional)
     */
    public function getAssistanceDistribution(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
                'barangay_id' => 'nullable|integer|exists:barangays,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);
            $barangayId = isset($validated['barangay_id']) ? (int) $validated['barangay_id'] : null;

            $assistancePayload = $this->buildAssistanceDistributionPayload(
                $officeId,
                $period,
                $date,
                $month,
                $year,
                $barangayId
            );

            $availableBarangays = $this->getAssistanceBarangayOptionsWithData(
                $officeId,
                $period,
                $date,
                $month,
                $year
            );

            return response()->json([
                'success' => true,
                'data' => [
                    ...$assistancePayload,
                    'available_barangays' => $availableBarangays,
                ],
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'barangay_id' => $barangayId,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics assistance distribution error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching assistance distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assistance indicator graph (indicator 1 and 2) counts.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     * - barangay_id=<id> (optional)
     */
    public function getAssistanceIndicatorGraph(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
                'barangay_id' => 'nullable|integer|exists:barangays,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);
            $barangayId = isset($validated['barangay_id']) ? (int) $validated['barangay_id'] : null;

            $baseQuery = DB::table('service_assistance as sa')
                ->join('queue_transaction_services as qts', 'qts.id', '=', 'sa.queue_transaction_service_id')
                ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
                ->whereNotNull('qts.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->whereIn('sa.indicator', [1, 2]);

            $this->applyAssistanceDateFilter($baseQuery, $period, $date, $month, $year);

            if ($barangayId !== null) {
                $baseQuery->where('qt.barangay_id', $barangayId);
            }

            $indicatorRows = (clone $baseQuery)
                ->selectRaw('sa.indicator as indicator')
                ->selectRaw('COUNT(DISTINCT qt.id) as total_clients')
                ->groupBy('sa.indicator')
                ->get()
                ->keyBy('indicator');

            $distribution = [
                [
                    'indicator' => 1,
                    'label' => '1',
                    'total_clients' => (int) ($indicatorRows->get(1)->total_clients ?? 0),
                ],
                [
                    'indicator' => 2,
                    'label' => '2',
                    'total_clients' => (int) ($indicatorRows->get(2)->total_clients ?? 0),
                ],
            ];

            $availableBarangays = $this->getAssistanceIndicatorBarangayOptions(
                $officeId,
                $period,
                $date,
                $month,
                $year
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'distribution' => $distribution,
                    'available_barangays' => $availableBarangays,
                ],
                'filter' => [
                    ...$this->buildFilterPayload($period, $date, $month, $year),
                    'barangay_id' => $barangayId,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics assistance indicator graph error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching assistance indicator graph',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get queue summary table data with pagination.
     *
     * Supported filters:
     * - period=daily&date=YYYY-MM-DD (default)
     * - period=monthly&month=1-12&year=YYYY
     * - period=yearly&year=YYYY
     *
     * Pagination:
     * - page (default: 1)
     * - per_page (default: 10, max: 50)
     */
    public function getQueueSummary(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);
            $page = (int) ($validated['page'] ?? 1);
            $perPage = (int) ($validated['per_page'] ?? 10);

            $baseQuery = QueueTransaction::query()
                ->with([
                    'services' => function ($query) {
                        $query->select('services.id', 'services.service_code', 'services.service_name')
                            ->where('service_type', 'External');
                    },
                    'queueTransactionServices' => function ($query) {
                        $query->with([
                            'serviceAssistance' => function ($query) {
                                $query->with('assistanceType');  // ✅ Eager load assistance type
                            },
                            'service'
                        ]);
                    },
                    'barangay' => function ($query) {
                        $query->select('id', 'barangay_name');
                    },
                    'prioritySectors' => function ($query) {
                        $query->select('priority_sectors.id', 'priority_sectors.sector_name');
                    },
                    'evaluationSession' => function ($query) {
                        $query->select('id', 'queue_transaction_id', 'sex', 'age');
                    },
                ])
                ->where('office_id', $officeId)
                ->whereIn('status', [
                    TransactionStatus::COMPLETED->value,
                    TransactionStatus::SKIPPED->value,
                ])
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                })
                ->orderByRaw('COALESCE(skipped_at, completed_at) DESC')
                ->orderBy('id', 'desc');

            $filteredQuery = $this->applyDateFilter(
                $baseQuery,
                $period,
                $date,
                $month,
                $year
            );

            $paginated = $filteredQuery->paginate($perPage, ['*'], 'page', $page);

            $rows = $paginated->getCollection()->map(function (QueueTransaction $transaction) {
                $isSkipped = $transaction->status->value === TransactionStatus::SKIPPED->value
                    || !is_null($transaction->skipped_at);

                $completionTime = $isSkipped
                    ? $transaction->skipped_at
                    : $transaction->completed_at;

                $serviceCodes = $transaction->services
                    ->pluck('service_code')
                    ->filter()
                    ->values();

                $serviceNames = $transaction->services
                    ->pluck('service_name')
                    ->filter()
                    ->values();

                $prioritySectors = $transaction->prioritySectors
                    ->pluck('sector_name')
                    ->filter()
                    ->values();

                $evaluationSession = $transaction->evaluationSession;

                // Build service assistance details
                $serviceAssistanceDetails = [];
                foreach ($transaction->queueTransactionServices as $qts) {
                    if ($qts->serviceAssistance) {
                        $assistanceTypeName = $qts->serviceAssistance->assistanceType?->assistance_name;
                        
                        // Format service label with assistance type if available
                        $serviceLabel = $qts->service?->service_name ?? 'Unknown';
                        if ($assistanceTypeName) {
                            $serviceLabel .= " ({$assistanceTypeName})";
                        }
                        
                        $serviceAssistanceDetails[] = [
                            'service_id' => $qts->service_id,
                            'service_name' => $serviceLabel,
                            'assistance_provided' => $qts->serviceAssistance->assistance_provided,
                            'assistance_provided_at' => $qts->serviceAssistance->assistance_provided_at
                                ? $qts->serviceAssistance->assistance_provided_at->format('M d, Y h:i A')
                                : null,
                        ];
                    }
                }

                return [
                    'id' => $transaction->id,
                    'queue_number' => $transaction->full_queue_number,
                    'client_name' => $transaction->client_name,
                    'barangay_name' => $transaction->barangay?->barangay_name,
                    'contact_number' => $transaction->contact_number,
                    'service_code' => $serviceCodes->implode(', '),
                    'service_names' => $serviceNames->all(),
                    'lane_type' => $transaction->is_priority ? 'Priority' : 'Regular',
                    'priority_sectors' => $prioritySectors->all(),
                    'status' => $isSkipped ? 'Skipped' : 'Completed',
                    'completion_time' => $completionTime?->format('M d, Y h:i A'),
                    'waiting_time' => $transaction->waiting_time_formatted,
                    'service_time' => $transaction->serving_time_formatted,
                    'average_satisfaction_rating' => $isSkipped
                        ? '-'
                        : $this->mapAverageSatisfactionLabel($transaction->average_satisfaction_rating),
                    'sex' => $evaluationSession?->sex,
                    'age' => $evaluationSession?->age,
                    'service_assistance_details' => $serviceAssistanceDetails,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'rows' => $rows,
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'per_page' => $paginated->perPage(),
                        'total_rows' => $paginated->total(),
                        'total_pages' => $paginated->lastPage(),
                        'start_row' => $paginated->firstItem() ?? 0,
                        'end_row' => $paginated->lastItem() ?? 0,
                    ],
                ],
                'filter' => $this->buildFilterPayload($period, $date, $month, $year),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics queue summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching queue summary data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export queue analytics graphs as an HTML report for browser print-to-PDF.
     *
     * This endpoint is shared by both SUPERADMIN (via office_id) and FRONTDESK
     * users (via their assigned office_id).
     */
    public function exportGraphs(Request $request)
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $periodLabel = $this->buildPeriodLabel($period, $date, $month, $year);

            $office = Office::find($officeId);
            $officeName = $office?->office_name ?? 'Unknown Office';
            $officeAcronym = $office?->office_acronym;
            $officeDisplayName = $officeAcronym
                ? sprintf('%s (%s)', $officeName, $officeAcronym)
                : $officeName;

            // Reuse existing analytics endpoints internally to avoid duplicating logic.
            $cardStatsResponse = $this->getCardStats($request);
            $cardStatsPayload = $cardStatsResponse->getData(true);
            $cardStats = $cardStatsPayload['data'] ?? [];

            $barangayResponse = $this->getBarangayDistribution($request);
            $barangayPayload = $barangayResponse->getData(true);
            $barangayStats = $barangayPayload['data'] ?? [];

            $laneTypeResponse = $this->getLaneTypeDistribution($request);
            $laneTypePayload = $laneTypeResponse->getData(true);
            $laneTypeStats = $laneTypePayload['data'] ?? [];

            $assistanceAllPayload = $this->buildAssistanceDistributionPayload(
                $officeId,
                $period,
                $date,
                $month,
                $year,
                null
            );
            $hasAssistanceServices = (bool) ($assistanceAllPayload['has_assistance_services'] ?? false);
            $assistanceBarangayOptions = $this->getAssistanceBarangayOptionsWithData(
                $officeId,
                $period,
                $date,
                $month,
                $year
            );

            $barangayChartPath = null;
            $laneTypeChartPath = null;
            $assistanceGraphs = [];

            $barangayDistribution = $barangayStats['distribution'] ?? [];
            $barangayTotalClients = (int) ($barangayStats['total_clients'] ?? 0);
            $hasBarangayData = $barangayTotalClients > 0
                && collect($barangayDistribution)->sum(function (array $segment) {
                    return (int) ($segment['value'] ?? 0);
                }) > 0;

            $laneDistribution = $laneTypeStats['distribution'] ?? [];
            $laneTotalClients = (int) ($laneTypeStats['total_clients'] ?? 0);
            $hasLaneTypeData = $laneTotalClients > 0
                && collect($laneDistribution)->sum(function (array $segment) {
                    return (int) ($segment['value'] ?? 0);
                }) > 0;

            if ($hasBarangayData) {
                $barangayChartPath = $this->chartImageService->generateBarangayBarChart(
                    $barangayDistribution,
                    $officeDisplayName,
                    $periodLabel
                );
            }

            if ($hasLaneTypeData) {
                $laneTypeChartPath = $this->chartImageService->generateLaneTypeDonutChart(
                    $laneDistribution,
                    $officeDisplayName,
                    $periodLabel
                );
            }

            if ($hasAssistanceServices
                && !empty($assistanceAllPayload['distribution'] ?? [])
            ) {
                $chartPath = $this->chartImageService->generateAssistanceDonutChart(
                    $assistanceAllPayload['distribution'],
                    $officeDisplayName,
                    $periodLabel,
                    'All Barangay'
                );

                if (!empty($chartPath)) {
                    $assistanceGraphs[] = [
                        'label' => 'All Barangay',
                        'chart_path' => $chartPath,
                        'summary' => $assistanceAllPayload['summary'] ?? [
                            'total_clients' => 0,
                            'total_assistance' => 0,
                        ],
                    ];
                }
            }

            foreach ($assistanceBarangayOptions as $barangayOption) {
                $barangayAssistancePayload = $this->buildAssistanceDistributionPayload(
                    $officeId,
                    $period,
                    $date,
                    $month,
                    $year,
                    (int) $barangayOption['barangay_id']
                );

                if (empty($barangayAssistancePayload['distribution'] ?? [])) {
                    continue;
                }

                $chartPath = $this->chartImageService->generateAssistanceDonutChart(
                    $barangayAssistancePayload['distribution'],
                    $officeDisplayName,
                    $periodLabel,
                    $barangayOption['barangay_name']
                );

                if (empty($chartPath)) {
                    continue;
                }

                $assistanceGraphs[] = [
                    'label' => $barangayOption['barangay_name'],
                    'chart_path' => $chartPath,
                    'summary' => $barangayAssistancePayload['summary'] ?? [
                        'total_clients' => 0,
                        'total_assistance' => 0,
                    ],
                ];
            }

            return response()->view('analytics.queue-graphs-report', [
                'officeName' => $officeName,
                'officeAcronym' => $officeAcronym,
                'officeDisplayName' => $officeDisplayName,
                'periodLabel' => $periodLabel,
                'period' => $period,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'cardStats' => $cardStats,
                'barangayStats' => $barangayStats,
                'laneTypeStats' => $laneTypeStats,
                'barangayChartPath' => $barangayChartPath,
                'laneTypeChartPath' => $laneTypeChartPath,
                'assistanceGraphs' => $assistanceGraphs,
                'hasAssistanceServices' => $hasAssistanceServices,
            ], 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics export graphs error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting analytics graphs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get aggregated Office Efficiency dashboard data in a single request.
     */
    public function getOfficeEfficiencyDashboardData(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
                'barangay_id' => 'nullable|integer|exists:barangays,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);
            $barangayId = isset($validated['barangay_id']) ? (int) $validated['barangay_id'] : null;

            $baseDateParams = $this->buildFilterPayload($period, $date, $month, $year);
            $csmController = app(CsmAnalyticsController::class);

            $targetYear = $period === 'yearly'
                ? $year
                : ($period === 'monthly' ? $year : (int) $date->year);

            $monthlyScores = [];
            for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
                $monthlyRequest = $this->makeInternalAnalyticsRequest([
                    'office_id' => $officeId,
                    'service_type' => 'all',
                    'period' => 'monthly',
                    'month' => $monthNumber,
                    'year' => $targetYear,
                ], $user);

                $monthlyResponse = $csmController->getOverallScorePerService($monthlyRequest);
                $monthlyPayload = $monthlyResponse->getData(true);
                $monthlyScores[] = round((float) ($monthlyPayload['data']['service_total_percentage'] ?? 0), 2);
            }

            $sqdCodes = ['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8'];
            $sqdMap = [];

            foreach ($sqdCodes as $sqdCode) {
                $sqdRequest = $this->makeInternalAnalyticsRequest([
                    ...$baseDateParams,
                    'office_id' => $officeId,
                    'service_type' => 'all',
                    'sqd' => $sqdCode,
                ], $user);

                $sqdResponse = $csmController->getSqdResults($sqdRequest);
                $sqdPayload = $sqdResponse->getData(true);
                $sqdMap[$sqdCode] = round((float) ($sqdPayload['data']['overall_percentage'] ?? 0), 2);
            }

            $overallSqdAverage = empty($sqdMap)
                ? 0
                : round((float) collect($sqdMap)->avg(), 2);

            $assistanceParams = [
                ...$baseDateParams,
                'office_id' => $officeId,
            ];

            if ($barangayId !== null) {
                $assistanceParams['barangay_id'] = $barangayId;
            }

            $assistanceRequest = $this->makeInternalAnalyticsRequest($assistanceParams, $user);
            $assistanceResponse = $this->getAssistanceIndicatorGraph($assistanceRequest);
            $assistancePayload = $assistanceResponse->getData(true);
            $assistanceData = $assistancePayload['data'] ?? [];

            $distribution = is_array($assistanceData['distribution'] ?? null)
                ? $assistanceData['distribution']
                : [];

            $counts = [
                1 => 0,
                2 => 0,
            ];

            foreach ($distribution as $row) {
                $indicator = (int) ($row['indicator'] ?? 0);
                if (in_array($indicator, [1, 2], true)) {
                    $counts[$indicator] = (int) ($row['total_clients'] ?? 0);
                }
            }

            $availableBarangays = is_array($assistanceData['available_barangays'] ?? null)
                ? $assistanceData['available_barangays']
                : [];

            $selectedStillAvailable = $barangayId === null
                || collect($availableBarangays)->contains(fn ($row) => (int) ($row['barangay_id'] ?? 0) === $barangayId);

            $hasAssistanceServices = Service::query()
                ->where('office_id', $officeId)
                ->where('service_type', 'External')
                ->where('provides_assistance', true)
                ->exists();

            return response()->json([
                'success' => true,
                'data' => [
                    'monthly_scores' => $monthlyScores,
                    'monthly_year' => $targetYear,
                    'sqd_percentages' => $sqdMap,
                    'overall_sqd_average' => $overallSqdAverage,
                    'assistance_indicator' => [
                        'counts' => $counts,
                        'available_barangays' => $availableBarangays,
                        'selected_still_available' => $selectedStillAvailable,
                    ],
                    'has_assistance_services' => $hasAssistanceServices,
                ],
                'filter' => [
                    ...$baseDateParams,
                    'office_id' => $officeId,
                    'barangay_id' => $barangayId,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Office efficiency dashboard data error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching office efficiency dashboard data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the office performance ranking across all offices for the selected date filter.
     */
    public function getOfficePerformanceRanking(Request $request): JsonResponse
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
            ]);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $baseDateParams = $this->buildFilterPayload($period, $date, $month, $year);
            $csmController = app(CsmAnalyticsController::class);
            $rankingRows = $this->getOfficePerformanceRankingRows($baseDateParams, $user, $csmController);

            return response()->json([
                'success' => true,
                'data' => [
                    'ranking' => $rankingRows,
                    'office_count' => count($rankingRows),
                ],
                'filter' => [
                    ...$baseDateParams,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Office performance ranking analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching office performance ranking analytics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve the Office Performance ranking band for a percentage.
     *
     * @return array{label: string, color: string}
     */
    private function resolveOfficePerformanceScale(float $percentage): array
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
     * @param  array<string, mixed>  $baseDateParams
     * @return array<int, array<string, mixed>>
     */
    private function getOfficePerformanceRankingRows(array $baseDateParams, $user, CsmAnalyticsController $csmController): array
    {
        $offices = Office::query()
            ->select(['id', 'office_name', 'office_acronym'])
            ->orderBy('office_name')
            ->get();

        $rankingRows = [];

        foreach ($offices as $office) {
            $officeId = (int) ($office->id ?? 0);
            if ($officeId <= 0) {
                continue;
            }

            $overallScoreRequest = $this->makeInternalAnalyticsRequest([
                ...$baseDateParams,
                'office_id' => $officeId,
                'service_type' => 'all',
            ], $user);

            $overallScoreResponse = $csmController->getOverallScorePerService($overallScoreRequest);
            $overallScorePayload = $overallScoreResponse->getData(true);
            $percentage = round((float) ($overallScorePayload['data']['service_total_percentage'] ?? 0), 2);
            $scale = $this->resolveOfficePerformanceScale($percentage);

            $officeName = (string) ($office->office_name ?? 'Unknown Office');
            $officeAcronym = trim((string) ($office->office_acronym ?? ''));

            $rankingRows[] = [
                'office_id' => $officeId,
                'office_name' => $officeName,
                'office_acronym' => $officeAcronym !== '' ? $officeAcronym : null,
                'display_name' => $officeAcronym !== ''
                    ? sprintf('%s (%s)', $officeName, $officeAcronym)
                    : $officeName,
                'percentage' => $percentage,
                'rating' => $scale['label'],
                'color' => $scale['color'],
            ];
        }

        usort($rankingRows, function (array $left, array $right): int {
            $percentageComparison = $right['percentage'] <=> $left['percentage'];
            if ($percentageComparison !== 0) {
                return $percentageComparison;
            }

            return strcasecmp((string) ($left['office_name'] ?? ''), (string) ($right['office_name'] ?? ''));
        });

        foreach ($rankingRows as $index => $row) {
            $rankingRows[$index]['rank'] = $index + 1;
        }

        return $rankingRows;
    }

    /**
     * @return array<string, string>
     */
    private function getSqdDescriptions(): array
    {
        return [
            'SQD0' => 'I am satisfied with the service that I availed.',
            'SQD1' => 'I spent a reasonable amount of time for my transaction.',
            'SQD2' => 'The office followed the transaction requirements and steps based on the information provided.',
            'SQD3' => 'The steps, including payment, needed for my transaction were easy and simple.',
            'SQD4' => 'I easily found information about my transaction from the office or its website.',
            'SQD5' => 'I paid a reasonable amount of fees for my transaction.',
            'SQD6' => 'I feel the office was fair to everyone, or "walang palakasan", during my transaction.',
            'SQD7' => 'I was treated courteously by the staff, and the staff was helpful when I asked for help.',
            'SQD8' => 'I got what I needed from the office, or if denied, the reason was sufficiently explained to me.',
        ];
    }

    /**
     * Export Office Efficiency graphs as an HTML report for browser print-to-PDF.
     */
    public function exportOfficeEfficiencyGraphs(Request $request)
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);
            $forPdf = filter_var($request->input('for_pdf', false), FILTER_VALIDATE_BOOLEAN);

            $periodLabel = $this->buildPeriodLabel($period, $date, $month, $year);

            $office = Office::find($officeId);
            $officeName = $office?->office_name ?? 'Unknown Office';
            $officeAcronym = $office?->office_acronym;
            $officeDisplayName = $officeAcronym
                ? sprintf('%s (%s)', $officeName, $officeAcronym)
                : $officeName;

            $baseDateParams = $this->buildFilterPayload($period, $date, $month, $year);
            $csmController = app(CsmAnalyticsController::class);
            $sqdDescriptions = $this->getSqdDescriptions();

            $targetYear = $period === 'yearly'
                ? $year
                : ($period === 'monthly' ? $year : (int) $date->year);

            $monthlyScores = [];
            for ($monthNumber = 1; $monthNumber <= 12; $monthNumber++) {
                $monthlyRequest = $this->makeInternalAnalyticsRequest([
                    'office_id' => $officeId,
                    'service_type' => 'all',
                    'period' => 'monthly',
                    'month' => $monthNumber,
                    'year' => $targetYear,
                ], $user);

                $monthlyResponse = $csmController->getOverallScorePerService($monthlyRequest);
                $monthlyPayload = $monthlyResponse->getData(true);
                $monthlyScores[] = round((float) ($monthlyPayload['data']['service_total_percentage'] ?? 0), 2);
            }

            $officeEfficiencyLineChartPath = $this->chartImageService->generateOfficeEfficiencyLineChart(
                $monthlyScores,
                $officeDisplayName,
                $targetYear
            );

            $sqdCodes = ['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8'];
            $sqdPercentages = [];

            foreach ($sqdCodes as $sqdCode) {
                $sqdRequest = $this->makeInternalAnalyticsRequest([
                    ...$baseDateParams,
                    'office_id' => $officeId,
                    'service_type' => 'all',
                    'sqd' => $sqdCode,
                ], $user);

                $sqdResponse = $csmController->getSqdResults($sqdRequest);
                $sqdPayload = $sqdResponse->getData(true);

                $sqdPercentages[] = [
                    'sqd' => $sqdCode,
                    'description' => $sqdDescriptions[$sqdCode] ?? '',
                    'percentage' => round((float) ($sqdPayload['data']['overall_percentage'] ?? 0), 2),
                ];
            }

            $overallSqdAverage = empty($sqdPercentages)
                ? 0
                : round(collect($sqdPercentages)->avg('percentage'), 2);

            $officePerformanceRanking = $this->getOfficePerformanceRankingRows($baseDateParams, $user, $csmController);

            $hasAssistanceServices = Service::query()
                ->where('office_id', $officeId)
                ->where('service_type', 'External')
                ->where('provides_assistance', true)
                ->exists();

            $assistanceIndicatorGraphs = [];
            if ($hasAssistanceServices) {
                $allAssistanceRequest = $this->makeInternalAnalyticsRequest([
                    ...$baseDateParams,
                    'office_id' => $officeId,
                ], $user);
                $allAssistanceResponse = $this->getAssistanceIndicatorGraph($allAssistanceRequest);
                $allAssistancePayload = $allAssistanceResponse->getData(true);
                $allData = $allAssistancePayload['data'] ?? [];

                $allDistribution = $allData['distribution'] ?? [];
                $allCounts = $this->formatAssistanceIndicatorCounts($allDistribution);

                $assistanceIndicatorGraphs[] = [
                    'label' => 'All Barangay',
                    'indicator_1' => $allCounts['indicator_1'],
                    'indicator_2' => $allCounts['indicator_2'],
                    'total_clients' => $allCounts['total_clients'],
                ];

                $availableBarangays = collect($allData['available_barangays'] ?? []);
                foreach ($availableBarangays as $barangay) {
                    $barangayId = (int) ($barangay['barangay_id'] ?? 0);
                    if ($barangayId <= 0) {
                        continue;
                    }

                    $barangayRequest = $this->makeInternalAnalyticsRequest([
                        ...$baseDateParams,
                        'office_id' => $officeId,
                        'barangay_id' => $barangayId,
                    ], $user);
                    $barangayResponse = $this->getAssistanceIndicatorGraph($barangayRequest);
                    $barangayPayload = $barangayResponse->getData(true);
                    $barangayDistribution = $barangayPayload['data']['distribution'] ?? [];
                    $barangayCounts = $this->formatAssistanceIndicatorCounts($barangayDistribution);

                    if ($barangayCounts['total_clients'] <= 0) {
                        continue;
                    }

                    $assistanceIndicatorGraphs[] = [
                        'label' => (string) ($barangay['barangay_name'] ?? 'Unknown Barangay'),
                        'indicator_1' => $barangayCounts['indicator_1'],
                        'indicator_2' => $barangayCounts['indicator_2'],
                        'total_clients' => $barangayCounts['total_clients'],
                    ];
                }
            }

            return response()->view('analytics.office-efficiency-graphs-report', [
                'officeName' => $officeName,
                'officeAcronym' => $officeAcronym,
                'officeDisplayName' => $officeDisplayName,
                'periodLabel' => $periodLabel,
                'period' => $period,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'monthlyScores' => $monthlyScores,
                'monthlyYear' => $targetYear,
                'officePerformanceRanking' => $officePerformanceRanking,
                'sqdPercentages' => $sqdPercentages,
                'overallSqdAverage' => $overallSqdAverage,
                'hasAssistanceServices' => $hasAssistanceServices,
                'assistanceIndicatorGraphs' => $assistanceIndicatorGraphs,
                'officeEfficiencyLineChartPath' => $officeEfficiencyLineChartPath,
                'exportPdfUrl' => url('/api/city-mayor/analytics/office-efficiency/export-graphs-pdf')
                    . '?' . http_build_query([
                        'office_id' => $officeId,
                        ...$baseDateParams,
                        'for_pdf' => 1,
                    ]),
                'forPdf' => $forPdf,
            ], 200, [
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Office efficiency export graphs error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting office efficiency graphs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export Office Efficiency graphs directly as a downloadable PDF file.
     */
    public function exportOfficeEfficiencyGraphsPdf(Request $request)
    {
        try {
            $request->merge(['for_pdf' => 1]);
            $htmlResponse = $this->exportOfficeEfficiencyGraphs($request);

            if (!method_exists($htmlResponse, 'getContent')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to prepare report content for PDF.',
                ], 500);
            }

            $contentType = (string) ($htmlResponse->headers->get('Content-Type') ?? '');
            if (!str_contains($contentType, 'text/html')) {
                return $htmlResponse;
            }

            $html = (string) $htmlResponse->getContent();
            $html = $this->injectBarWidthsForPdf($html);

            $validated = $request->validate([
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeName = 'Office Efficiency';
            if (!empty($validated['office_id'])) {
                $office = Office::find((int) $validated['office_id']);
                if ($office) {
                    $officeName = $office->office_acronym
                        ? sprintf('%s (%s)', $office->office_name, $office->office_acronym)
                        : $office->office_name;
                }
            }

            $safeOfficeName = trim(preg_replace('/[\\\\\/:*?"<>|]+/', '-', $officeName));
            $safeOfficeName = preg_replace('/\s+/', ' ', $safeOfficeName);
            $fileName = sprintf('%s - Office Efficiency Graph Report.pdf', $safeOfficeName ?: 'Office Efficiency');

            return Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->download($fileName);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Office efficiency export graphs PDF error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting office efficiency graphs PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export queue summary data as an Excel file grouped by barangay.
     *
     * This endpoint is primarily intended for SUPERADMIN usage (via office_id)
     * but shares the same filtering rules as the queue summary API.
     */
    public function exportQueueSummary(Request $request)
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
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            $periodLabel = $this->buildPeriodLabel($period, $date, $month, $year);

            $office = Office::find($officeId);
            $officeName = $office?->office_name ?? 'Unknown Office';
            $officeAcronym = $office?->office_acronym;
            $officeDisplayName = $officeAcronym
                ? sprintf('%s (%s)', $officeName, $officeAcronym)
                : $officeName;

            $baseQuery = QueueTransaction::query()
                ->with([
                    'services' => function ($query) {
                        $query->select('services.id', 'services.service_code', 'services.service_name')
                            ->where('service_type', 'External');
                    },
                    'queueTransactionServices' => function ($query) {
                        $query->with([
                            'serviceAssistance' => function ($query) {
                                $query->with('assistanceType');  // ✅ Eager load assistance type
                            },
                            'service'
                        ]);
                    },
                    'barangay' => function ($query) {
                        $query->select('id', 'barangay_name');
                    },
                    'prioritySectors' => function ($query) {
                        $query->select('priority_sectors.id', 'priority_sectors.sector_name');
                    },
                    'evaluationSession' => function ($query) {
                        $query->select('id', 'queue_transaction_id', 'sex', 'age');
                    },
                ])
                ->whereHas('services', function (Builder $query) {
                    $query->where('service_type', 'External');
                })
                ->where('status', TransactionStatus::COMPLETED->value)
                ->orderByRaw('COALESCE(skipped_at, completed_at) DESC')
                ->orderBy('id', 'desc');

            $filteredQuery = $this->applyDateFilter(
                $baseQuery,
                $period,
                $date,
                $month,
                $year
            );

            $transactions = $filteredQuery->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Queue Summary');

            $rowIndex = 1;

            if ($transactions->isEmpty()) {
                $sheet->setCellValue("A{$rowIndex}", 'No queue summary data available for the selected filters.');
                $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
            } else {
                $groupedByBarangay = $transactions->groupBy(function (QueueTransaction $transaction) {
                    return $transaction->barangay?->barangay_name ?? 'No Barangay';
                });

                foreach ($groupedByBarangay as $barangayName => $barangayTransactions) {
                    // Check if this barangay has any assistance records
                    $hasAssistanceInBarangay = false;
                    foreach ($barangayTransactions as $transaction) {
                        foreach ($transaction->queueTransactionServices as $qts) {
                            if ($qts->serviceAssistance && $qts->serviceAssistance->assistance_provided !== null) {
                                $hasAssistanceInBarangay = true;
                                break 2;
                            }
                        }
                    }

                    // Barangay title row
                    $sheet->setCellValue("A{$rowIndex}", (string) $barangayName);
                    $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
                    $rowIndex++;

                    // Header row (conditionally include assistance columns)
                    $headers = [
                        'Services',
                        'Queue Number',
                        'Client Name',
                        'Contact Number',
                        'Sex',
                        'Age',
                        'Barangay',
                        'Lane Type',
                        'Completion Date and Time',
                        'Waiting Time (min)',
                        'Service Time (min)',
                    ];

                    if ($hasAssistanceInBarangay) {
                        $headers[] = 'Assistance Provided';
                        $headers[] = 'Assistance Record Date';
                    }

                    $sheet->fromArray($headers, null, "A{$rowIndex}", true);
                    $endColumn = $hasAssistanceInBarangay ? 'M' : 'K';
                    $sheet->getStyle("A{$rowIndex}:{$endColumn}{$rowIndex}")->getFont()->setBold(true);
                    $rowIndex++;

                    // Track total assistance for this barangay
                    $barangayTotalAssistance = 0;

                    // Expand each transaction into one row per associated service,
                    // then group by service label (including assistance type) so each service+type combination
                    // appears separately with its related transactions listed underneath.
                    $expanded = collect();

                    foreach ($barangayTransactions as $transaction) {
                        $services = $transaction->services;

                        if ($services->isEmpty()) {
                            $expanded->push([
                                'service_label' => 'N/A',
                                'service_id' => null,
                                'transaction' => $transaction,
                            ]);
                            continue;
                        }

                        foreach ($services as $service) {
                            $label = $service->service_name
                                ?: $service->service_code
                                ?: 'N/A';

                            // Check if this service has assistance with a type
                            $assistanceTypeName = null;
                            foreach ($transaction->queueTransactionServices as $qts) {
                                if ($qts->service_id === $service->id && $qts->serviceAssistance) {
                                    $assistanceTypeName = $qts->serviceAssistance->assistanceType?->assistance_name;
                                    break;
                                }
                            }

                            // Append assistance type to label if it exists
                            if ($assistanceTypeName) {
                                $label .= " ({$assistanceTypeName})";
                            }

                            $expanded->push([
                                'service_label' => $label,
                                'service_id' => $service->id,
                                'transaction' => $transaction,
                            ]);
                        }
                    }

                    $serviceGroups = $expanded->groupBy('service_label');

                    foreach ($serviceGroups as $serviceLabel => $entries) {
                        $isFirstForService = true;

                        foreach ($entries as $entry) {
                            /** @var QueueTransaction $transaction */
                            $transaction = $entry['transaction'];
                            $serviceId = $entry['service_id'];

                            $laneType = $transaction->is_priority ? 'Priority' : 'Regular';

                            if ($transaction->is_priority) {
                                $sectors = $transaction->prioritySectors
                                    ->pluck('sector_name')
                                    ->filter()
                                    ->values();

                                if ($sectors->isNotEmpty()) {
                                    $laneType .= ' - ' . $sectors->implode(', ');
                                }
                            }

                            $evaluationSession = $transaction->evaluationSession;

                            $isSkipped = $transaction->status->value === TransactionStatus::SKIPPED->value
                                || !is_null($transaction->skipped_at);

                            $completionDateTime = $isSkipped
                                ? $transaction->skipped_at
                                : $transaction->completed_at;

                            // Find the service assistance for this specific service and transaction
                            $serviceAssistance = null;
                            if ($serviceId !== null) {
                                foreach ($transaction->queueTransactionServices as $qts) {
                                    if ($qts->service_id === $serviceId && $qts->serviceAssistance) {
                                        $serviceAssistance = $qts->serviceAssistance;
                                        break;
                                    }
                                }
                            }

                            $assistanceProvided = $serviceAssistance?->assistance_provided;
                            
                            // Track total assistance for this barangay
                            if ($assistanceProvided !== null) {
                                $barangayTotalAssistance += $assistanceProvided;
                            }
                            
                            $assistanceProvidedAt = $serviceAssistance?->assistance_provided_at
                                ? $serviceAssistance->assistance_provided_at->format('M d, Y h:i A')
                                : null;

                            $rowData = [
                                $isFirstForService ? (string) $serviceLabel : '',
                                $transaction->full_queue_number,
                                $transaction->client_name,
                                $transaction->contact_number,
                                $evaluationSession?->sex,
                                $evaluationSession?->age,
                                $transaction->barangay?->barangay_name,
                                $laneType,
                                $completionDateTime?->format('M d, Y h:i A'),
                                $transaction->waiting_time_formatted,
                                $transaction->serving_time_formatted,
                            ];

                            // Conditionally add assistance columns
                            if ($hasAssistanceInBarangay) {
                                $rowData[] = $assistanceProvided;
                                $rowData[] = $assistanceProvidedAt;
                            }

                            $sheet->fromArray($rowData, null, "A{$rowIndex}", true);
                            $rowIndex++;
                            $isFirstForService = false;
                        }
                    }

                    // Add total assistance row for this barangay (only if barangay has assistance)
                    if ($hasAssistanceInBarangay) {
                        $sheet->setCellValue("A{$rowIndex}", 'TOTAL ASSISTANCE PROVIDED');
                        $sheet->getStyle("A{$rowIndex}")->getFont()->setBold(true);
                        $sheet->setCellValue("L{$rowIndex}", $barangayTotalAssistance);
                        $sheet->getStyle("L{$rowIndex}")->getFont()->setBold(true);
                        $rowIndex++;
                    }

                    // Blank row between barangays
                    $rowIndex++;
                }
            }

            // Auto-size columns up to assistance record date.
            $endColumn = 'M';
            foreach (range('A', $endColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            $fileName = sprintf('%s Queue Data Summary - %s.xlsx', $officeDisplayName, $periodLabel);
            $tempFile = tempnam(sys_get_temp_dir(), 'queue_summary_');

            if ($tempFile === false) {
                throw new \RuntimeException('Unable to create temporary file for export.');
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            return response()->download(
                $tempFile,
                $fileName,
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )->deleteFileAfterSend(true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Frontdesk analytics export queue summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting queue summary data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build assistance distribution payload used by both API response and PDF export.
     */
    private function buildAssistanceDistributionPayload(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year,
        ?int $barangayId
    ): array {
        $hasAssistanceServices = Service::query()
            ->where('office_id', $officeId)
            ->where('service_type', 'External')
            ->where('provides_assistance', true)
            ->exists();

        if (!$hasAssistanceServices) {
            return [
                'has_assistance_services' => false,
                'distribution' => [],
                'summary' => [
                    'total_clients' => 0,
                    'total_assistance' => 0,
                ],
            ];
        }

        $baseQuery = DB::table('service_assistance as sa')
            ->join('queue_transaction_services as qts', 'qts.id', '=', 'sa.queue_transaction_service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->leftJoin('assistance_types as at', 'at.id', '=', 'sa.assistance_type_id')
            ->whereNotNull('qts.queue_transaction_id')
            ->where('qt.office_id', $officeId)
            ->where('qt.status', TransactionStatus::COMPLETED->value)
            ->where('s.service_type', 'External')
            ->where('s.provides_assistance', true);

        $this->applyDateFilterToColumn($baseQuery, $period, $date, $month, $year, 'qt.completed_at');

        if ($barangayId !== null) {
            $baseQuery->where('qt.barangay_id', $barangayId);
        }

        $distribution = (clone $baseQuery)
            ->selectRaw('s.id as service_id')
            ->selectRaw('s.service_name as service_name')
            ->selectRaw('at.id as assistance_type_id')
            ->selectRaw('at.assistance_name as assistance_type_name')
            ->selectRaw('COUNT(DISTINCT qt.id) as total_clients')
            ->selectRaw('COALESCE(SUM(sa.assistance_provided), 0) as total_assistance')
            ->groupBy('s.id', 's.service_name', 'at.id', 'at.assistance_name')
            ->orderByDesc('total_assistance')
            ->get()
            ->map(function ($row) {
                $hasType = !is_null($row->assistance_type_id) && !empty($row->assistance_type_name);

                return [
                    'service_id' => (int) $row->service_id,
                    'service_name' => (string) $row->service_name,
                    'assistance_type_id' => $row->assistance_type_id === null ? null : (int) $row->assistance_type_id,
                    'assistance_type_name' => $row->assistance_type_name,
                    'label' => $hasType
                        ? sprintf('%s (%s)', $row->service_name, $row->assistance_type_name)
                        : (string) $row->service_name,
                    'total_clients' => (int) $row->total_clients,
                    'total_assistance' => round((float) $row->total_assistance, 2),
                ];
            })
            ->values()
            ->all();

        $summaryRow = (clone $baseQuery)
            ->selectRaw('COUNT(DISTINCT qt.id) as total_clients')
            ->selectRaw('COALESCE(SUM(sa.assistance_provided), 0) as total_assistance')
            ->first();

        return [
            'has_assistance_services' => true,
            'distribution' => $distribution,
            'summary' => [
                'total_clients' => (int) ($summaryRow->total_clients ?? 0),
                'total_assistance' => round((float) ($summaryRow->total_assistance ?? 0), 2),
            ],
        ];
    }

    /**
     * Get barangay options with assistance records for the selected filters.
     */
    private function getAssistanceBarangayOptionsWithData(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $query = DB::table('service_assistance as sa')
            ->join('queue_transaction_services as qts', 'qts.id', '=', 'sa.queue_transaction_service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->join('barangays as b', 'b.id', '=', 'qt.barangay_id')
            ->whereNotNull('qts.queue_transaction_id')
            ->where('qt.office_id', $officeId)
            ->where('qt.status', TransactionStatus::COMPLETED->value)
            ->where('s.service_type', 'External')
            ->where('s.provides_assistance', true)
            ->whereNotNull('qt.barangay_id');

        $this->applyDateFilterToColumn($query, $period, $date, $month, $year, 'qt.completed_at');

        return $query
            ->selectRaw('qt.barangay_id as barangay_id')
            ->selectRaw('b.barangay_name as barangay_name')
            ->selectRaw('COALESCE(SUM(sa.assistance_provided), 0) as total_assistance')
            ->groupBy('qt.barangay_id', 'b.barangay_name')
            ->orderBy('b.barangay_name')
            ->get()
            ->map(static function ($row) {
                return [
                    'barangay_id' => (int) $row->barangay_id,
                    'barangay_name' => (string) $row->barangay_name,
                    'total_assistance' => round((float) $row->total_assistance, 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get barangay options that have assistance indicator records (1 or 2)
     * for the selected office/date filters.
     */
    private function getAssistanceIndicatorBarangayOptions(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $query = DB::table('service_assistance as sa')
            ->join('queue_transaction_services as qts', 'qts.id', '=', 'sa.queue_transaction_service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->join('barangays as b', 'b.id', '=', 'qt.barangay_id')
            ->whereNotNull('qts.queue_transaction_id')
            ->where('qt.office_id', $officeId)
            ->whereIn('sa.indicator', [1, 2])
            ->whereNotNull('qt.barangay_id');

        $this->applyAssistanceDateFilter($query, $period, $date, $month, $year);

        return $query
            ->selectRaw('qt.barangay_id as barangay_id')
            ->selectRaw('b.barangay_name as barangay_name')
            ->selectRaw('COUNT(DISTINCT qt.id) as total_clients')
            ->groupBy('qt.barangay_id', 'b.barangay_name')
            ->orderBy('b.barangay_name')
            ->get()
            ->map(static function ($row) {
                return [
                    'barangay_id' => (int) $row->barangay_id,
                    'barangay_name' => (string) $row->barangay_name,
                    'total_clients' => (int) $row->total_clients,
                ];
            })
            ->values()
            ->all();
    }

    private function mapAverageSatisfactionLabel($averageRating): string
    {
        if ($averageRating === null) {
            return '-';
        }

        $labelMap = [
            1 => 'Strongly Disagree',
            2 => 'Disagree',
            3 => 'Neither',
            4 => 'Agree',
            5 => 'Strongly Agree',
        ];

        $normalizedRating = (int) round((float) $averageRating);

        return $labelMap[$normalizedRating] ?? '-';
    }

    private function resolveOfficeId($user, array $validated): int
    {
        if ($user->isSuperadmin() || $user->isCityMayor()) {
            if (empty($validated['office_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'office_id' => ['The office_id field is required for superadmin/city mayor analytics.'],
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

    private function applyDateFilter(
        $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ) {
        return match ($period) {
            'monthly' => $query
                ->whereRaw("EXTRACT(YEAR FROM COALESCE(completed_at, skipped_at)) = ?", [$year])
                ->whereRaw("EXTRACT(MONTH FROM COALESCE(completed_at, skipped_at)) = ?", [$month]),
            'yearly' => $query
                ->whereRaw("EXTRACT(YEAR FROM COALESCE(completed_at, skipped_at)) = ?", [$year]),
            default => $query
                ->whereRaw("DATE(COALESCE(completed_at, skipped_at)) = ?", [$date->toDateString()]),
        };
    }

    /**
     * Apply date filter to joined/aliased query builders using qt.completed_at / qt.skipped_at.
     */
    private function applyDateFilterToColumn(
        $query,
        string $period,
        Carbon $date,
        int $month,
        int $year,
        string $dateColumn = 'qt.completed_at'
    ) {
        return match ($period) {
            'monthly' => $query
                ->whereRaw("EXTRACT(YEAR FROM COALESCE(qt.completed_at, qt.skipped_at)) = ?", [$year])
                ->whereRaw("EXTRACT(MONTH FROM COALESCE(qt.completed_at, qt.skipped_at)) = ?", [$month]),
            'yearly' => $query
                ->whereRaw("EXTRACT(YEAR FROM COALESCE(qt.completed_at, qt.skipped_at)) = ?", [$year]),
            default => $query
                ->whereRaw("DATE(COALESCE(qt.completed_at, qt.skipped_at)) = ?", [$date->toDateString()]),
        };
    }

    /**
     * Apply date filter for assistance analytics using either the transaction's
     * resolution date (completed_at) or assistance_provided_at.
     */
    private function applyAssistanceDateFilter(
        $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ) {
        return match ($period) {
            'monthly' => $query->where(function ($subQuery) use ($year, $month) {
                $subQuery
                    ->where(function ($q) use ($year, $month) {
                        $q->whereRaw("EXTRACT(YEAR FROM qt.completed_at) = ?", [$year])
                          ->whereRaw("EXTRACT(MONTH FROM qt.completed_at) = ?", [$month]);
                    })
                    ->orWhere(function ($q) use ($year, $month) {
                        $q->whereYear('sa.assistance_provided_at', $year)
                          ->whereMonth('sa.assistance_provided_at', $month);
                    });
            }),
            'yearly' => $query->where(function ($subQuery) use ($year) {
                $subQuery
                    ->whereRaw("EXTRACT(YEAR FROM qt.completed_at) = ?", [$year])
                    ->orWhereYear('sa.assistance_provided_at', $year);
            }),
            default => $query->where(function ($subQuery) use ($date) {
                $subQuery
                    ->whereRaw("DATE(qt.completed_at) = ?", [$date->toDateString()])
                    ->orWhereDate('sa.assistance_provided_at', $date->toDateString());
            }),
        };
    }

    private function makeInternalAnalyticsRequest(array $params, $user): Request
    {
        $request = Request::create('/', 'GET', $params);
        $request->setUserResolver(static fn () => $user);

        return $request;
    }

    private function formatAssistanceIndicatorCounts(array $distribution): array
    {
        $indicator1 = 0;
        $indicator2 = 0;

        foreach ($distribution as $row) {
            $indicator = (int) ($row['indicator'] ?? 0);
            $count = (int) ($row['total_clients'] ?? 0);

            if ($indicator === 1) {
                $indicator1 = $count;
            }

            if ($indicator === 2) {
                $indicator2 = $count;
            }
        }

        return [
            'indicator_1' => $indicator1,
            'indicator_2' => $indicator2,
            'total_clients' => $indicator1 + $indicator2,
        ];
    }

    private function injectBarWidthsForPdf(string $html): string
    {
        return (string) preg_replace_callback(
            '/(<div[^>]*class="[^"]*bar-fill[^"]*"[^>]*data-width="([^"]+)"[^>]*)(>)/i',
            static function (array $matches): string {
                $openTag = $matches[1];
                $width = max(0, min(100, (float) ($matches[2] ?? 0)));
                $widthStyle = sprintf('width:%s%%;', rtrim(rtrim(number_format($width, 2, '.', ''), '0'), '.'));

                if (preg_match('/style="([^"]*)"/i', $openTag, $styleMatches)) {
                    $style = trim($styleMatches[1]);
                    if ($style !== '' && !str_ends_with($style, ';')) {
                        $style .= ';';
                    }
                    $style .= $widthStyle;
                    $openTag = preg_replace('/style="[^"]*"/i', 'style="' . $style . '"', $openTag, 1);
                } else {
                    $openTag .= ' style="' . $widthStyle . '"';
                }

                return $openTag . $matches[3];
            },
            $html
        );
    }

    /**
     * Build a human-readable label for the current period filter.
     *
     * Examples:
     * - daily  => "April 8, 2026"
     * - monthly => "April 2026"
     * - yearly  => "2026"
     */
    private function buildPeriodLabel(string $period, Carbon $date, int $month, int $year): string
    {
        return match ($period) {
            'monthly' => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
            'yearly' => (string) $year,
            default => $date->translatedFormat('F j, Y'),
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
}
