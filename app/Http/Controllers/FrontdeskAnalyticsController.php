<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\EvaluationResponse;
use App\Models\Office;
use App\Models\QueueTransaction;
use App\Services\Analytics\ChartImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                    'average_waiting_time' => round((float) ($averageWaitingTime ?? 0), 2),
                    'average_service_time' => round((float) ($averageServiceTime ?? 0), 2),
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
                    'completion_time' => $completionTime?->format('h:i A'),
                    'waiting_time' => $transaction->waiting_time === null
                        ? null
                        : round((float) $transaction->waiting_time, 2),
                    'service_time' => $transaction->serving_time === null
                        ? null
                        : round((float) $transaction->serving_time, 2),
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
     * Export queue analytics graphs (stat cards + distributions) as a PDF file.
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

            $barangayChartPath = null;
            $laneTypeChartPath = null;

            if (!empty($barangayStats['distribution'] ?? [])) {
                $barangayChartPath = $this->chartImageService->generateBarangayBarChart(
                    $barangayStats['distribution'],
                    $officeDisplayName,
                    $periodLabel
                );
            }

            if (!empty($laneTypeStats['distribution'] ?? [])) {
                $laneTypeChartPath = $this->chartImageService->generateLaneTypeDonutChart(
                    $laneTypeStats['distribution'],
                    $officeDisplayName,
                    $periodLabel
                );
            }

            $pdf = Pdf::loadView('analytics.queue-graphs-report', [
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
            ])->setPaper('a4', 'portrait');

            $safeOfficeName = str_replace(['/', '\\'], '-', $officeDisplayName);
            $fileName = sprintf('%s Queue Analytics Graph - %s.pdf', $safeOfficeName, $periodLabel);

            return $pdf->download($fileName);
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
                        'Sex',
                        'Age',
                        'Barangay',
                        'Lane Type',
                        'Waiting Time (min)',
                        'Service Time (min)',
                    ];

                    if ($hasAssistanceInBarangay) {
                        $headers[] = 'Assistance Provided';
                        $headers[] = 'Assistance Record Date';
                    }

                    $sheet->fromArray($headers, null, "A{$rowIndex}", true);
                    $endColumn = $hasAssistanceInBarangay ? 'K' : 'I';
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
                                $evaluationSession?->sex,
                                $evaluationSession?->age,
                                $transaction->barangay?->barangay_name,
                                $laneType,
                                $transaction->waiting_time === null
                                    ? null
                                    : round((float) $transaction->waiting_time, 2),
                                $transaction->serving_time === null
                                    ? null
                                    : round((float) $transaction->serving_time, 2),
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
                        $sheet->setCellValue("J{$rowIndex}", $barangayTotalAssistance);
                        $sheet->getStyle("J{$rowIndex}")->getFont()->setBold(true);
                        $rowIndex++;
                    }

                    // Blank row between barangays
                    $rowIndex++;
                }
            }

            // Auto-size columns (K if assistance is included, I if not)
            $endColumn = 'K';
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

    private function applyDateFilter(
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
