<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\EvaluationResponse;
use App\Models\QueueTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FrontdeskAnalyticsController extends Controller
{
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
                        $query->select('services.id', 'services.service_code')
                            ->where('service_type', 'External');
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

                return [
                    'id' => $transaction->id,
                    'queue_number' => $transaction->full_queue_number,
                    'client_name' => $transaction->client_name,
                    'service_code' => $transaction->services
                        ->pluck('service_code')
                        ->filter()
                        ->values()
                        ->implode(', '),
                    'lane_type' => $transaction->is_priority ? 'Priority' : 'Regular',
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
