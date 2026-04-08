<?php

namespace App\Http\Controllers;

use App\Models\EvaluationQuestion;
use App\Models\EvaluationResponse;
use App\Models\InternalTransaction;
use App\Models\Office;
use App\Models\QueueTransaction;
use App\Services\Analytics\ChartImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CsmAnalyticsController extends Controller
{
    private ChartImageService $chartImageService;

    public function __construct(ChartImageService $chartImageService)
    {
        $this->chartImageService = $chartImageService;
    }

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

            $externalTransactionCount = $this->getServiceWeightedTransactionCountForSource(
                source: 'external',
                officeId: $officeId,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );

            $internalTransactionCount = $this->getServiceWeightedTransactionCountForSource(
                source: 'internal',
                officeId: $officeId,
                period: $period,
                date: $date,
                month: $month,
                year: $year,
            );

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

            $payload = [
                'service_type' => $serviceType,
                'total_transactions' => $totalTransactions,
                'cc_awareness' => $awareness,
                'cc_visibility' => $visibility,
                'cc_helpfulness' => $helpfulness,
                'overall_score' => $overallScoreData['service_total_percentage'],
            ];

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

            $meta = $this->getCitizenCharterMeta();
            $counts = $this->getCitizenCharterCountsRaw($officeId, $serviceType, $startDate, $endDate);

            $payload = $this->buildCitizenCharterPayload($counts, $meta);

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

            $payload = [
                'sqd_code' => $displaySqdCode,
                'question_code' => $dbSqdCode,
                'description' => $description,
                'distribution' => $distribution,
                'total_responses' => $totalRespondents,
                'overall_percentage' => $overallPercentage,
            ];

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

            $payload = [
                'category' => $this->getDemographicCategoryDisplayName($category),
                'distribution' => $distribution,
                'total_responses' => $totalResponses,
            ];

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

            $payload = $this->computeOverallScorePerServiceData(
                officeId: $officeId,
                serviceType: $serviceType,
                startDate: $startDate,
                endDate: $endDate,
            );

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

    /**
     * Export selected CSM analytics tables as a multi-sheet Excel file.
     *
        * Current implementation supports Overview, Surveyed Services,
    * Citizen's Charter Count, and Overall Score Per Service sheets.
     */
    public function exportTables(Request $request)
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
                'tables' => 'required|array|min:1',
                'tables.*' => 'string|in:overview,surveyed-services,citizens-charter-count,overall-score-per-service,age,sex,customer-type,external-sqd-results,internal-sqd-results',
                'service_type' => 'nullable|in:external,internal,all',
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'office_id' => 'nullable|integer|exists:offices,id',
            ]);

            $officeId = $this->resolveOfficeId($user, $validated);

            $selectedTables = collect($validated['tables'] ?? [])->unique()->values()->all();
            $unsupportedTables = array_values(array_diff($selectedTables, [
                'overview',
                'surveyed-services',
                'citizens-charter-count',
                'overall-score-per-service',
                'age',
                'sex',
                'customer-type',
                'external-sqd-results',
                'internal-sqd-results',
            ]));

            if (!empty($unsupportedTables)) {
                return response()->json([
                    'success' => false,
                    'message' => "Only Overview, Surveyed Services, Citizen's Charter Count, Overall Score Per Service, Demographic Profile, and SQD Results table exports are currently supported.",
                    'unsupported_tables' => $unsupportedTables,
                ], 422);
            }

            $selectedDemographicKeys = collect($selectedTables)
                ->intersect(['age', 'sex', 'customer-type'])
                ->values()
                ->all();
            $hasDemographicSelection = !empty($selectedDemographicKeys);
            $demographicSheetGenerated = false;

            $selectedSqdKeys = collect($selectedTables)
                ->intersect(['external-sqd-results', 'internal-sqd-results'])
                ->values()
                ->all();
            $hasSqdSelection = !empty($selectedSqdKeys);
            $sqdSheetGenerated = false;

            $serviceType = $validated['service_type'] ?? 'external';
            $period = $validated['period'] ?? 'daily';
            $today = now();

            $date = isset($validated['date'])
                ? Carbon::createFromFormat('Y-m-d', $validated['date'])->startOfDay()
                : $today->copy()->startOfDay();

            $month = (int) ($validated['month'] ?? $today->month);
            $year = (int) ($validated['year'] ?? $today->year);

            [$startDate, $endDate] = $this->resolveDateRange($period, $date, $month, $year);

            $spreadsheet = new Spreadsheet();
            $sheetIndex = 0;

            foreach ($selectedTables as $tableKey) {
                if ($tableKey === 'overview') {
                    // Overview export should always reflect ALL services, independent of current filter.
                    $overviewRows = $this->buildOverviewExportRows(
                        officeId: $officeId,
                        serviceType: 'all',
                        period: $period,
                        date: $date,
                        month: $month,
                        year: $year,
                        startDate: $startDate,
                        endDate: $endDate,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle('Overview');
                    $sheet->fromArray($overviewRows, null, 'A1', true);
                    $sheet->getStyle('A1:B1')->getFont()->setBold(true);
                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    $sheet->getColumnDimension('B')->setAutoSize(true);
                    $sheetIndex++;
                    continue;
                }

                if ($tableKey === 'surveyed-services') {
                    $surveyedPayload = $this->buildSurveyedServicesExportRows(
                        officeId: $officeId,
                        period: $period,
                        date: $date,
                        month: $month,
                        year: $year,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle('Surveyed Services');
                    $sheet->fromArray($surveyedPayload['rows'], null, 'A1', true);

                    foreach ($surveyedPayload['bold_rows'] as $boldRow) {
                        $sheet->getStyle("A{$boldRow}:C{$boldRow}")->getFont()->setBold(true);
                    }

                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    $sheet->getColumnDimension('B')->setAutoSize(true);
                    $sheet->getColumnDimension('C')->setAutoSize(true);

                    $sheetIndex++;
                    continue;
                }

                if ($tableKey === 'citizens-charter-count') {
                    // Citizen's Charter Count export should always reflect ALL services.
                    $citizenCharterPayload = $this->buildCitizenCharterCountExportRows(
                        officeId: $officeId,
                        serviceType: 'all',
                        startDate: $startDate,
                        endDate: $endDate,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle("Citizen's Charter Count");
                    $sheet->fromArray($citizenCharterPayload['rows'], null, 'A1', true);

                    foreach ($citizenCharterPayload['bold_rows'] as $boldRow) {
                        $sheet->getStyle("A{$boldRow}:C{$boldRow}")->getFont()->setBold(true);
                    }

                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    $sheet->getColumnDimension('B')->setAutoSize(true);
                    $sheet->getColumnDimension('C')->setAutoSize(true);

                    $sheetIndex++;
                    continue;
                }

                if ($tableKey === 'overall-score-per-service') {
                    $overallScorePayload = $this->buildOverallScorePerServiceExportRows(
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle('Overall Score Per Service');
                    $sheet->fromArray($overallScorePayload['rows'], null, 'A1', true);

                    foreach ($overallScorePayload['bold_rows'] as $boldRow) {
                        $sheet->getStyle("A{$boldRow}:B{$boldRow}")->getFont()->setBold(true);
                    }

                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    $sheet->getColumnDimension('B')->setAutoSize(true);

                    $sheetIndex++;
                    continue;
                }

                if ($hasDemographicSelection
                    && !$demographicSheetGenerated
                    && in_array($tableKey, ['age', 'sex', 'customer-type'], true)
                ) {
                    $demographicPayload = $this->buildDemographicProfileExportRows(
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                        selectedDemographicKeys: $selectedDemographicKeys,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle('Demographic Profile');
                    $sheet->fromArray($demographicPayload['rows'], null, 'A1', true);

                    foreach ($demographicPayload['bold_rows'] as $boldRow) {
                        $sheet->getStyle("A{$boldRow}:D{$boldRow}")->getFont()->setBold(true);
                    }

                    $sheet->getColumnDimension('A')->setAutoSize(true);
                    $sheet->getColumnDimension('B')->setAutoSize(true);
                    $sheet->getColumnDimension('C')->setAutoSize(true);
                    $sheet->getColumnDimension('D')->setAutoSize(true);

                    $sheetIndex++;
                    $demographicSheetGenerated = true;
                    continue;
                }

                if ($hasSqdSelection
                    && !$sqdSheetGenerated
                    && in_array($tableKey, ['external-sqd-results', 'internal-sqd-results'], true)
                ) {
                    $sqdPayload = $this->buildSqdResultsExportRows(
                        officeId: $officeId,
                        startDate: $startDate,
                        endDate: $endDate,
                        selectedSqdKeys: $selectedSqdKeys,
                    );

                    $sheet = $sheetIndex === 0
                        ? $spreadsheet->getActiveSheet()
                        : $spreadsheet->createSheet($sheetIndex);

                    $sheet->setTitle('SQD Results');
                    $sheet->fromArray($sqdPayload['rows'], null, 'A1', true);

                    foreach ($sqdPayload['bold_rows'] as $boldRow) {
                        $sheet->getStyle("A{$boldRow}:I{$boldRow}")->getFont()->setBold(true);
                    }

                    foreach (range('A', 'I') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }

                    $sheetIndex++;
                    $sqdSheetGenerated = true;
                }
            }

            $fileName = $this->buildCsmExportFilename($period, $date, $month, $year);
            $tempFile = tempnam(sys_get_temp_dir(), 'csm_report_');

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
            Log::error('CSM export analytics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting CSM analytics tables',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export CSM analytics graphs as a PDF file.
     *
     * Includes:
     * - Overview stat cards
     * - Citizen's Charter graphs (CC1, CC2, CC3)
     * - SQD graphs (SQD0-SQD8)
     * - Demographic profile pies (Age, Sex, Client Type)
     * - Overall Score Per Service graph
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
            $periodLabel = $this->buildPeriodLabel($period, $date, $month, $year);

            $office = Office::find($officeId);
            $officeName = $office?->office_name ?? 'Unknown Office';
            $officeAcronym = $office?->office_acronym;
            $officeDisplayName = $officeAcronym
                ? sprintf('%s (%s)', $officeName, $officeAcronym)
                : $officeName;

            $serviceTypeLabel = $this->mapServiceTypeLabel($serviceType);

            // Overview stats
            $overviewResponse = $this->getOverviewStats($request);
            $overviewPayload = $overviewResponse->getData(true);
            $overviewData = $overviewPayload['data'] ?? [];

            // Citizen's Charter data
            $citizenCharterResponse = $this->getCitizenCharterCounts($request);
            $citizenCharterPayload = $citizenCharterResponse->getData(true);
            $citizenCharterData = $citizenCharterPayload['data'] ?? [];

            $ccQuestions = [
                'awareness' => (string) ($citizenCharterData['questions']['awareness']['text'] ?? self::CC_CHART_CONFIG['CC1']['default_question']),
                'visibility' => (string) ($citizenCharterData['questions']['visibility']['text'] ?? self::CC_CHART_CONFIG['CC2']['default_question']),
                'helpfulness' => (string) ($citizenCharterData['questions']['helpfulness']['text'] ?? self::CC_CHART_CONFIG['CC3']['default_question']),
            ];

            // Overall score per service data
            $overallScorePayload = $this->computeOverallScorePerServiceData(
                officeId: $officeId,
                serviceType: $serviceType,
                startDate: $startDate,
                endDate: $endDate,
            );

            // Demographic distributions (Age, Sex, Client Type)
            $demographicCategories = ['age', 'sex', 'client_type'];
            $demographicDistributions = [];

            foreach ($demographicCategories as $category) {
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

                $demographicDistributions[$category] = [
                    'category' => $this->getDemographicCategoryDisplayName($category),
                    'distribution' => $distribution,
                    'total_responses' => $totalResponses,
                ];
            }

            // SQD distributions for SQD0-SQD8
            $sqdCodes = ['SQD0', 'SQD1', 'SQD2', 'SQD3', 'SQD4', 'SQD5', 'SQD6', 'SQD7', 'SQD8'];
            $sqdDistributions = [];

            foreach ($sqdCodes as $sqdCode) {
                $dbSqdCode = $this->normalizeSqdCode($sqdCode);

                $question = EvaluationQuestion::query()
                    ->whereIn('question_type', ['LIKERT'])
                    ->where(function (Builder $query) use ($dbSqdCode) {
                        $query->where('question_code', $dbSqdCode)
                            ->orWhere('question_text', 'like', $dbSqdCode . '%');
                    })
                    ->first();

                $description = $question?->question_text ?? $dbSqdCode;

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

                $sqdDistributions[$sqdCode] = [
                    'sqd_code' => $dbSqdCode,
                    'description' => $description,
                    'distribution' => $distribution,
                    'total_responses' => $totalRespondents,
                    'overall_percentage' => $overallPercentage,
                ];
            }

            // Generate chart images via QuickChart
            $ccChartPaths = [
                'awareness' => null,
                'visibility' => null,
                'helpfulness' => null,
            ];

            if (!empty($citizenCharterData['awareness'] ?? [])) {
                $ccChartPaths['awareness'] = $this->chartImageService->generateCitizenCharterBarChart(
                    $citizenCharterData['awareness'],
                    'CC1',
                    $ccQuestions['awareness'],
                    $officeDisplayName,
                    $periodLabel,
                );
            }

            if (!empty($citizenCharterData['visibility'] ?? [])) {
                $ccChartPaths['visibility'] = $this->chartImageService->generateCitizenCharterBarChart(
                    $citizenCharterData['visibility'],
                    'CC2',
                    $ccQuestions['visibility'],
                    $officeDisplayName,
                    $periodLabel,
                );
            }

            if (!empty($citizenCharterData['helpfulness'] ?? [])) {
                $ccChartPaths['helpfulness'] = $this->chartImageService->generateCitizenCharterBarChart(
                    $citizenCharterData['helpfulness'],
                    'CC3',
                    $ccQuestions['helpfulness'],
                    $officeDisplayName,
                    $periodLabel,
                );
            }

            $sqdChartPaths = [];

            foreach ($sqdDistributions as $code => $payload) {
                $sqdChartPaths[$code] = $this->chartImageService->generateSqdBarChart(
                    $code,
                    $payload['description'],
                    $payload['distribution'],
                    $officeDisplayName,
                    $periodLabel,
                    $serviceTypeLabel,
                );
            }

            $demographicChartPaths = [];

            foreach ($demographicDistributions as $key => $payload) {
                $demographicChartPaths[$key] = $this->chartImageService->generateDemographicPieChart(
                    $payload['category'],
                    $payload['distribution'],
                    $officeDisplayName,
                    $periodLabel,
                );
            }

            $overallScoreChartPath = null;

            if (!empty($overallScorePayload['chart_data'] ?? [])) {
                $overallScoreChartPath = $this->chartImageService->generateOverallScorePerServiceBarChart(
                    $overallScorePayload['chart_data'],
                    $serviceTypeLabel,
                    $officeDisplayName,
                    $periodLabel,
                );
            }

            $pdf = Pdf::loadView('analytics.csm-graphs-report', [
                'officeName' => $officeName,
                'officeAcronym' => $officeAcronym,
                'officeDisplayName' => $officeDisplayName,
                'periodLabel' => $periodLabel,
                'period' => $period,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'serviceType' => $serviceType,
                'serviceTypeLabel' => $serviceTypeLabel,
                'overviewData' => $overviewData,
                'citizenCharterData' => $citizenCharterData,
                'ccQuestions' => $ccQuestions,
                'sqdDistributions' => $sqdDistributions,
                'demographicDistributions' => $demographicDistributions,
                'overallScorePayload' => $overallScorePayload,
                'ccChartPaths' => $ccChartPaths,
                'sqdChartPaths' => $sqdChartPaths,
                'demographicChartPaths' => $demographicChartPaths,
                'overallScoreChartPath' => $overallScoreChartPath,
            ])->setPaper('a4', 'portrait');

            $safeOfficeName = str_replace(['/', '\\'], '-', $officeDisplayName);
            $fileName = sprintf('%s Client Satisfaction Measurement Graph - %s.pdf', $safeOfficeName, $periodLabel);

            return $pdf->download($fileName);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('CSM export graphs error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting CSM analytics graphs',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function buildOverviewExportRows(
        int $officeId,
        string $serviceType,
        string $period,
        Carbon $date,
        int $month,
        int $year,
        string $startDate,
        string $endDate
    ): array {
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

        $overallScoreData = $this->computeOverallScorePerServiceData(
            officeId: $officeId,
            serviceType: $serviceType,
            startDate: $startDate,
            endDate: $endDate,
        );

        $responseRate = $this->computeOverviewResponseRate(
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        return [
            ['Criteria', 'Score'],
            ['CC Awareness', $this->formatExportPercentage($awareness)],
            ['CC Visibility', $this->formatExportPercentage($visibility)],
            ['CC Helpfulness', $this->formatExportPercentage($helpfulness)],
            ['Response Rate', $this->formatExportPercentage($responseRate)],
            ['Overall Score', $this->formatExportPercentage((float) ($overallScoreData['service_total_percentage'] ?? 0))],
        ];
    }

    private function computeOverviewResponseRate(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): float {
        $externalResponsesQuery = DB::table('queue_transaction_services as qts')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->where('qt.office_id', $officeId)
            ->where('s.service_type', 'External')
            ->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('evaluation_sessions as es')
                    ->whereColumn('es.queue_transaction_id', 'qt.id');
            });

        $this->applyQueueDateFilter($externalResponsesQuery, $period, $date, $month, $year);
        $externalResponses = (int) $externalResponsesQuery->count('qts.id');

        $internalResponsesQuery = DB::table('queue_transaction_services as qts')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->join('internal_transactions as it', 'it.id', '=', 'qts.internal_transaction_id')
                        ->where(function ($q) use ($officeId) {
                                $q->where('it.office_id', $officeId)
                                    ->orWhere('it.to_office_id', $officeId);
                        })
            ->where('s.service_type', 'Internal')
            ->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('evaluation_sessions as es')
                    ->whereColumn('es.internal_transaction_id', 'it.id');
            });

        $this->applyInternalDateFilter($internalResponsesQuery, $period, $date, $month, $year);
        $internalResponses = (int) $internalResponsesQuery->count('qts.id');

        $externalTransactionsQuery = DB::table('queue_transaction_services as qts')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->where('qt.office_id', $officeId)
            ->where('qt.status', 'COMPLETED')
            ->where('s.service_type', 'External');

        $this->applyQueueDateFilter($externalTransactionsQuery, $period, $date, $month, $year);
        $externalTransactions = (int) $externalTransactionsQuery->count('qts.id');

        $internalTransactionsQuery = DB::table('queue_transaction_services as qts')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->join('internal_transactions as it', 'it.id', '=', 'qts.internal_transaction_id')
            ->where(function ($q) use ($officeId) {
                $q->where('it.office_id', $officeId)
                  ->orWhere('it.to_office_id', $officeId);
            })
            ->where('s.service_type', 'Internal');

        $this->applyInternalDateFilter($internalTransactionsQuery, $period, $date, $month, $year);
        $internalTransactions = (int) $internalTransactionsQuery->count('qts.id');

        $responses = $externalResponses + $internalResponses;
        $transactions = $externalTransactions + $internalTransactions;

        if ($transactions === 0) {
            return 0.0;
        }

        return round(($responses / $transactions) * 100, 2);
    }

    private function formatExportPercentage(float $value): string
    {
        return number_format($value, 2, '.', '') . '%';
    }

    private function buildCsmExportFilename(string $period, Carbon $date, int $month, int $year): string
    {
        $dateLabel = $this->buildPeriodLabel($period, $date, $month, $year);

        return "Client Satisfaction Measurement (CSM) Report - {$dateLabel}.xlsx";
    }

    private function buildPeriodLabel(string $period, Carbon $date, int $month, int $year): string
    {
        return match ($period) {
            'monthly' => Carbon::create($year, $month, 1)->format('F Y'),
            'yearly' => (string) $year,
            default => $date->format('F j, Y'),
        };
    }

    private function mapServiceTypeLabel(string $serviceType): string
    {
        return match ($serviceType) {
            'internal' => 'Internal',
            'all' => 'All (External and Internal)',
            default => 'External',
        };
    }

    /**
     * @return array{rows: array<int, array<int, string|int>>, bold_rows: array<int, int>}
     */
    private function buildCitizenCharterCountExportRows(
        int $officeId,
        string $serviceType,
        string $startDate,
        string $endDate
    ): array {
        $meta = $this->getCitizenCharterMeta();
        $counts = $this->getCitizenCharterCountsRaw($officeId, $serviceType, $startDate, $endDate);
        $payload = $this->buildCitizenCharterPayload($counts, $meta);

        $awarenessRows = $payload['awareness'] ?? [];
        $visibilityRows = $payload['visibility'] ?? [];
        $helpfulnessRows = $payload['helpfulness'] ?? [];

        $awarenessQuestion = (string) ($payload['questions']['awareness']['text'] ?? self::CC_CHART_CONFIG['CC1']['default_question']);
        $visibilityQuestion = (string) ($payload['questions']['visibility']['text'] ?? self::CC_CHART_CONFIG['CC2']['default_question']);
        $helpfulnessQuestion = (string) ($payload['questions']['helpfulness']['text'] ?? self::CC_CHART_CONFIG['CC3']['default_question']);

        $getOption = static function (array $rows, int $option): array {
            foreach ($rows as $row) {
                if ((int) ($row['option'] ?? 0) === $option) {
                    return [
                        'count' => (int) ($row['count'] ?? 0),
                        'percentage' => (float) ($row['percentage'] ?? 0),
                    ];
                }
            }

            return ['count' => 0, 'percentage' => 0.0];
        };

        $cc1Option1 = $getOption($awarenessRows, 1);
        $cc1Option2 = $getOption($awarenessRows, 2);
        $cc1Option3 = $getOption($awarenessRows, 3);
        $cc1Option4 = $getOption($awarenessRows, 4);

        $cc2Option1 = $getOption($visibilityRows, 1);
        $cc2Option2 = $getOption($visibilityRows, 2);
        $cc2Option3 = $getOption($visibilityRows, 3);
        $cc2Option4 = $getOption($visibilityRows, 4);

        $cc3Option1 = $getOption($helpfulnessRows, 1);
        $cc3Option2 = $getOption($helpfulnessRows, 2);
        $cc3Option3 = $getOption($helpfulnessRows, 3);

        $rows = [
            ["Citizen's Charter Answers", 'Responses', 'Percentage'],
            [$awarenessQuestion, '', ''],
            ["1. I know what a CC is and I saw this office's CC", $cc1Option1['count'], $this->formatExportPercentage($cc1Option1['percentage'])],
            ["2. I know what a CC is but I did not see this office's CC", $cc1Option2['count'], $this->formatExportPercentage($cc1Option2['percentage'])],
            ["3. I learned of the CC only when I saw this office's CC", $cc1Option3['count'], $this->formatExportPercentage($cc1Option3['percentage'])],
            ["I do not know what a CC is and I did not see this office's CC", $cc1Option4['count'], $this->formatExportPercentage($cc1Option4['percentage'])],
            ['', '', ''],
            [$visibilityQuestion, '', ''],
            ['1. Easy to See', $cc2Option1['count'], $this->formatExportPercentage($cc2Option1['percentage'])],
            ['2. Somewhat easy to see', $cc2Option2['count'], $this->formatExportPercentage($cc2Option2['percentage'])],
            ['3. Difficult to see', $cc2Option3['count'], $this->formatExportPercentage($cc2Option3['percentage'])],
            ['4. Not visible at all', $cc2Option4['count'], $this->formatExportPercentage($cc2Option4['percentage'])],
            ['', '', ''],
            [$helpfulnessQuestion, '', ''],
            ['1. Helped very much', $cc3Option1['count'], $this->formatExportPercentage($cc3Option1['percentage'])],
            ['2. Somewhat helped', $cc3Option2['count'], $this->formatExportPercentage($cc3Option2['percentage'])],
            ['3. Did not help', $cc3Option3['count'], $this->formatExportPercentage($cc3Option3['percentage'])],
        ];

        return [
            'rows' => $rows,
            'bold_rows' => [1, 2, 8, 14],
        ];
    }

    /**
     * @return array{rows: array<int, array<int, string|int>>, bold_rows: array<int, int>}
     */
    private function buildOverallScorePerServiceExportRows(
        int $officeId,
        string $startDate,
        string $endDate
    ): array {
        $externalPayload = $this->computeOverallScorePerServiceData(
            officeId: $officeId,
            serviceType: 'external',
            startDate: $startDate,
            endDate: $endDate,
        );

        $internalPayload = $this->computeOverallScorePerServiceData(
            officeId: $officeId,
            serviceType: 'internal',
            startDate: $startDate,
            endDate: $endDate,
        );

        $allPayload = $this->computeOverallScorePerServiceData(
            officeId: $officeId,
            serviceType: 'all',
            startDate: $startDate,
            endDate: $endDate,
        );

        $rows = [];
        $boldRows = [];
        $rowIndex = 1;

        $externalRatingsByService = collect($externalPayload['chart_data'] ?? [])
            ->mapWithKeys(function (array $row) {
                return [
                    (string) ($row['service_name'] ?? $row['name'] ?? '') => (float) ($row['percentage'] ?? 0),
                ];
            });

        $internalRatingsByService = collect($internalPayload['chart_data'] ?? [])
            ->mapWithKeys(function (array $row) {
                return [
                    (string) ($row['service_name'] ?? $row['name'] ?? '') => (float) ($row['percentage'] ?? 0),
                ];
            });

        $externalServices = DB::table('services')
            ->where('office_id', $officeId)
            ->where('service_type', 'External')
            ->whereNull('deleted_at')
            ->orderBy('service_name')
            ->pluck('service_name')
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $internalServices = DB::table('services')
            ->where('office_id', $officeId)
            ->where('service_type', 'Internal')
            ->whereNull('deleted_at')
            ->orderBy('service_name')
            ->pluck('service_name')
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();

        $rows[] = ['External Services', 'Overall Rating (Percentage)'];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        foreach ($externalServices as $serviceName) {
            $rows[] = [
                $serviceName,
                $this->formatExportPercentage((float) ($externalRatingsByService[$serviceName] ?? 0)),
            ];
            $rowIndex++;
        }

        $rows[] = ['External Service Total', $this->formatExportPercentage((float) ($externalPayload['service_total_percentage'] ?? 0))];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        $rows[] = ['Internal Services', ''];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        foreach ($internalServices as $serviceName) {
            $rows[] = [
                $serviceName,
                $this->formatExportPercentage((float) ($internalRatingsByService[$serviceName] ?? 0)),
            ];
            $rowIndex++;
        }

        $rows[] = ['Internal Service Total', $this->formatExportPercentage((float) ($internalPayload['service_total_percentage'] ?? 0))];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        $rows[] = ['OVERALL TOTAL', $this->formatExportPercentage((float) ($allPayload['service_total_percentage'] ?? 0))];
        $boldRows[] = $rowIndex;

        return [
            'rows' => $rows,
            'bold_rows' => $boldRows,
        ];
    }

    /**
     * @param array<int, string> $selectedSqdKeys
     * @return array{rows: array<int, array<int, string|int>>, bold_rows: array<int, int>}
     */
    private function buildSqdResultsExportRows(
        int $officeId,
        string $startDate,
        string $endDate,
        array $selectedSqdKeys
    ): array {
        $includeExternal = in_array('external-sqd-results', $selectedSqdKeys, true);
        $includeInternal = in_array('internal-sqd-results', $selectedSqdKeys, true);

        if (!$includeExternal && !$includeInternal) {
            return [
                'rows' => [
                    ['SQD Results'],
                    ['No SQD options selected.'],
                ],
                'bold_rows' => [1],
            ];
        }

        $criteriaRows = [
            ['code' => 'SQD0', 'label' => 'SQD0'],
            ['code' => 'SQD1', 'label' => 'SQD1 (Responsiveness)'],
            ['code' => 'SQD2', 'label' => 'SQD2 (Reliability)'],
            ['code' => 'SQD3', 'label' => 'SQD3 (Access and Facilities)'],
            ['code' => 'SQD4', 'label' => 'SQD4 (Communication)'],
            ['code' => 'SQD5', 'label' => 'SQD5 (Costs)'],
            ['code' => 'SQD6', 'label' => 'SQD6 (Integrity)'],
            ['code' => 'SQD7', 'label' => 'SQD7 (Assurance)'],
            ['code' => 'SQD8', 'label' => 'SQD8 (Outcome)'],
        ];

        $rows = [];
        $boldRows = [];
        $rowIndex = 1;

        $appendSourceSection = function (string $sourceTitle, string $sourceKey) use (
            $officeId,
            $startDate,
            $endDate,
            $criteriaRows,
            &$rows,
            &$boldRows,
            &$rowIndex
        ): void {
            $rows[] = [$sourceTitle, '', '', '', '', '', '', '', ''];
            $boldRows[] = $rowIndex;
            $rowIndex++;

            $rows[] = [
                'Criteria',
                'Strongly Agree',
                'Agree',
                'Neither Agree nor Disagree',
                'Disagree',
                'Strongly Disagree',
                'N/A',
                'Total Responses',
                'Overall (Percentage)',
            ];
            $boldRows[] = $rowIndex;
            $rowIndex++;

            $totalResponses = $this->getSqdTotalRespondents(
                officeId: $officeId,
                serviceType: $sourceKey,
                startDate: $startDate,
                endDate: $endDate,
            );

            $aggregateStronglyAgree = 0;
            $aggregateAgree = 0;
            $aggregateNeither = 0;
            $aggregateDisagree = 0;
            $aggregateStronglyDisagree = 0;
            $aggregateNa = 0;
            $aggregateTotalResponses = 0;

            foreach ($criteriaRows as $criteria) {
                $counts = $this->getSqdCriteriaCountsForSource(
                    source: $sourceKey,
                    officeId: $officeId,
                    startDate: $startDate,
                    endDate: $endDate,
                    sqdCode: (string) $criteria['code'],
                );

                $stronglyAgree = (int) ($counts[5] ?? 0);
                $agree = (int) ($counts[4] ?? 0);
                $neither = (int) ($counts[3] ?? 0);
                $disagree = (int) ($counts[2] ?? 0);
                $stronglyDisagree = (int) ($counts[1] ?? 0);
                $na = (int) ($counts[0] ?? 0);

                $denominator = $totalResponses - $na;
                $overallPercentage = $denominator <= 0
                    ? 0.0
                    : round((($stronglyAgree + $agree) / $denominator) * 100, 2);

                $rows[] = [
                    (string) $criteria['label'],
                    $stronglyAgree,
                    $agree,
                    $neither,
                    $disagree,
                    $stronglyDisagree,
                    $na,
                    $totalResponses,
                    $this->formatExportPercentage($overallPercentage),
                ];
                $rowIndex++;

                $aggregateStronglyAgree += $stronglyAgree;
                $aggregateAgree += $agree;
                $aggregateNeither += $neither;
                $aggregateDisagree += $disagree;
                $aggregateStronglyDisagree += $stronglyDisagree;
                $aggregateNa += $na;
                $aggregateTotalResponses += $totalResponses;
            }

            $aggregateDenominator = $aggregateTotalResponses - $aggregateNa;
            $aggregateOverallPercentage = $aggregateDenominator <= 0
                ? 0.0
                : round((($aggregateStronglyAgree + $aggregateAgree) / $aggregateDenominator) * 100, 2);

            $rows[] = [
                'Overall',
                $aggregateStronglyAgree,
                $aggregateAgree,
                $aggregateNeither,
                $aggregateDisagree,
                $aggregateStronglyDisagree,
                $aggregateNa,
                $aggregateTotalResponses,
                $this->formatExportPercentage($aggregateOverallPercentage),
            ];
            $boldRows[] = $rowIndex;
            $rowIndex++;
        };

        if ($includeExternal) {
            $appendSourceSection('External Services SQD Results', 'external');
        }

        if ($includeExternal && $includeInternal) {
            $rows[] = ['', '', '', '', '', '', '', '', ''];
            $rowIndex++;
        }

        if ($includeInternal) {
            $appendSourceSection('Internal Services SQD Results', 'internal');
        }

        return [
            'rows' => $rows,
            'bold_rows' => $boldRows,
        ];
    }

    /**
     * @param array<int, string> $selectedDemographicKeys
     * @return array{rows: array<int, array<int, string>>, bold_rows: array<int, int>}
     */
    private function buildDemographicProfileExportRows(
        int $officeId,
        string $startDate,
        string $endDate,
        array $selectedDemographicKeys
    ): array {
        $normalizedKeyMap = [
            'age' => 'age',
            'sex' => 'sex',
            'customer-type' => 'client_type',
        ];

        $selectedCategories = collect($selectedDemographicKeys)
            ->map(function (string $key) use ($normalizedKeyMap) {
                return $normalizedKeyMap[$key] ?? null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($selectedCategories)) {
            $selectedCategories = ['age'];
        }

        $sectionConfig = [
            'age' => [
                'header' => 'Age',
                'rows' => [
                    '19 or lower' => '1. 19 or lower',
                    '20-34' => '2. 20-34',
                    '35-49' => '3. 35-49',
                    '50-64' => '4. 50-64',
                    '65-Higher' => '5. 65 or higher',
                    'Did not specify' => '6. Did not Specify',
                ],
            ],
            'sex' => [
                'header' => 'Sex',
                'rows' => [
                    'Male' => '1. Male',
                    'Female' => '2. Female',
                    'Did not specify' => '3. Did not Specify',
                ],
            ],
            'client_type' => [
                'header' => 'Customer Type',
                'rows' => [
                    'Citizen' => '1. Citizen',
                    'Business' => '2. Business',
                    'Government' => '3. Government',
                    'Did not specify' => '4. Did not specify',
                ],
            ],
        ];

        $rows = [];
        $boldRows = [];
        $rowIndex = 1;

        foreach ($selectedCategories as $categoryIndex => $category) {
            $config = $sectionConfig[$category] ?? null;

            if (!$config) {
                continue;
            }

            $externalCounts = $this->getDemographicCountsForSource(
                source: 'external',
                category: $category,
                officeId: $officeId,
                startDate: $startDate,
                endDate: $endDate,
            );

            $internalCounts = $this->getDemographicCountsForSource(
                source: 'internal',
                category: $category,
                officeId: $officeId,
                startDate: $startDate,
                endDate: $endDate,
            );

            $externalTotal = array_sum($externalCounts);
            $internalTotal = array_sum($internalCounts);
            $overallTotal = $externalTotal + $internalTotal;

            $rows[] = [
                (string) $config['header'],
                'External (Percentage)',
                'Internal (Percentage)',
                'Overall (Percentage)',
            ];
            $boldRows[] = $rowIndex;
            $rowIndex++;

            foreach ($config['rows'] as $segmentKey => $label) {
                $externalValue = (int) ($externalCounts[$segmentKey] ?? 0);
                $internalValue = (int) ($internalCounts[$segmentKey] ?? 0);
                $overallValue = $externalValue + $internalValue;

                $externalPercentage = $externalTotal === 0
                    ? 0.0
                    : round(($externalValue / $externalTotal) * 100, 2);
                $internalPercentage = $internalTotal === 0
                    ? 0.0
                    : round(($internalValue / $internalTotal) * 100, 2);
                $overallPercentage = $overallTotal === 0
                    ? 0.0
                    : round(($overallValue / $overallTotal) * 100, 2);

                $rows[] = [
                    $label,
                    $this->formatExportPercentage($externalPercentage),
                    $this->formatExportPercentage($internalPercentage),
                    $this->formatExportPercentage($overallPercentage),
                ];
                $rowIndex++;
            }

            if ($categoryIndex < count($selectedCategories) - 1) {
                $rows[] = ['', '', '', ''];
                $rowIndex++;
            }
        }

        return [
            'rows' => $rows,
            'bold_rows' => $boldRows,
        ];
    }

    /**
     * @return array{rows: array<int, array<int, string|int>>, bold_rows: array<int, int>}
     */
    private function buildSurveyedServicesExportRows(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $externalRows = $this->getSurveyedServiceRowsForSource(
            source: 'external',
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        $internalRows = $this->getSurveyedServiceRowsForSource(
            source: 'internal',
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        $externalResponseTotal = array_sum(array_column($externalRows, 'response_count'));
        $externalTransactionTotal = array_sum(array_column($externalRows, 'total_transactions'));
        $internalResponseTotal = array_sum(array_column($internalRows, 'response_count'));
        $internalTransactionTotal = array_sum(array_column($internalRows, 'total_transactions'));

        $overallResponseTotal = $externalResponseTotal + $internalResponseTotal;
        $overallTransactionTotal = $externalTransactionTotal + $internalTransactionTotal;

        $rows = [];
        $boldRows = [];
        $rowIndex = 1;

        $rows[] = ['External Services', 'Responses', 'Total Transactions'];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        foreach ($externalRows as $serviceRow) {
            $rows[] = [
                $serviceRow['service_name'],
                $serviceRow['response_count'],
                $serviceRow['total_transactions'],
            ];
            $rowIndex++;
        }

        $rows[] = ['External Service Total', $externalResponseTotal, $externalTransactionTotal];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        $rows[] = ['Internal Services', '', ''];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        foreach ($internalRows as $serviceRow) {
            $rows[] = [
                $serviceRow['service_name'],
                $serviceRow['response_count'],
                $serviceRow['total_transactions'],
            ];
            $rowIndex++;
        }

        $rows[] = ['Internal Service Total', $internalResponseTotal, $internalTransactionTotal];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        $rows[] = ['OVERALL TOTAL', $overallResponseTotal, $overallTransactionTotal];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        $rows[] = ['', '', ''];
        $rowIndex++;
        $rows[] = ['', '', ''];
        $rowIndex++;

        $zeroClientServices = $this->getZeroClientServices(
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        $rows[] = ['The following services had no clients/transactions', '', ''];
        $boldRows[] = $rowIndex;
        $rowIndex++;

        if (empty($zeroClientServices)) {
            $rows[] = ['1. None', '', ''];
            return [
                'rows' => $rows,
                'bold_rows' => $boldRows,
            ];
        }

        foreach ($zeroClientServices as $index => $serviceName) {
            $number = $index + 1;
            $rows[] = ["{$number}. {$serviceName}", '', ''];
        }

        return [
            'rows' => $rows,
            'bold_rows' => $boldRows,
        ];
    }

    /**
     * @return array<int, array{service_id: int, service_name: string, response_count: int, total_transactions: int}>
     */
    private function getSurveyedServiceRowsForSource(
        string $source,
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $isExternal = $source === 'external';
        $serviceType = $isExternal ? 'External' : 'Internal';

        $query = DB::table('services as s')
            ->leftJoin('queue_transaction_services as qts', 'qts.service_id', '=', 's.id')
            ->where('s.office_id', $officeId)
            ->where('s.service_type', $serviceType)
            ->whereNull('s.deleted_at')
            ->groupBy('s.id', 's.service_name')
            ->orderBy('s.service_name')
            ->selectRaw('s.id AS service_id')
            ->selectRaw('s.service_name AS service_name');

        if ($isExternal) {
            $query
                ->leftJoin('queue_transactions as qt', function ($join) use ($officeId, $period, $date, $month, $year) {
                    $join->on('qt.id', '=', 'qts.queue_transaction_id')
                        ->where('qt.office_id', '=', $officeId)
                        ->where('qt.status', '=', 'COMPLETED');

                    $this->applyDateConstraintToJoin(
                        join: $join,
                        column: 'qt.queue_date',
                        period: $period,
                        date: $date,
                        month: $month,
                        year: $year,
                    );
                })
                ->leftJoin('evaluation_responses as er', 'er.queue_transaction_id', '=', 'qt.id')
                ->selectRaw('COUNT(DISTINCT qt.id) AS total_transactions')
                ->selectRaw('COUNT(DISTINCT er.queue_transaction_id) AS response_count');
        } else {
            $query
                ->leftJoin('internal_transactions as it', function ($join) use ($officeId, $period, $date, $month, $year) {
                    $join->on('it.id', '=', 'qts.internal_transaction_id')
                        ->where(function ($q) use ($officeId) {
                            $q->where('it.office_id', '=', $officeId)
                              ->orWhere('it.to_office_id', '=', $officeId);
                        });

                    $this->applyDateConstraintToJoin(
                        join: $join,
                        column: 'it.transaction_date',
                        period: $period,
                        date: $date,
                        month: $month,
                        year: $year,
                    );
                })
                ->leftJoin('evaluation_responses as er', 'er.internal_transaction_id', '=', 'it.id')
                ->selectRaw('COUNT(DISTINCT it.id) AS total_transactions')
                ->selectRaw('COUNT(DISTINCT er.internal_transaction_id) AS response_count');
        }

        return $query
            ->havingRaw('COUNT(DISTINCT ' . ($isExternal ? 'qt.id' : 'it.id') . ') > 0')
            ->get()
            ->map(function ($row) {
                return [
                    'service_id' => (int) $row->service_id,
                    'service_name' => (string) $row->service_name,
                    'response_count' => (int) ($row->response_count ?? 0),
                    'total_transactions' => (int) ($row->total_transactions ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function getZeroClientServices(
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): array {
        $externalRows = $this->getSurveyedServiceRowsForSource(
            source: 'external',
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        $internalRows = $this->getSurveyedServiceRowsForSource(
            source: 'internal',
            officeId: $officeId,
            period: $period,
            date: $date,
            month: $month,
            year: $year,
        );

        $servicesWithTransactions = collect($externalRows)
            ->pluck('service_id')
            ->merge(collect($internalRows)->pluck('service_id'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return DB::table('services as s')
            ->where('s.office_id', $officeId)
            ->whereNull('s.deleted_at')
            ->when($servicesWithTransactions->isNotEmpty(), function ($query) use ($servicesWithTransactions) {
                $query->whereNotIn('s.id', $servicesWithTransactions->all());
            })
            ->orderBy('s.service_type')
            ->orderBy('s.service_name')
            ->pluck('s.service_name')
            ->map(fn ($name) => (string) $name)
            ->values()
            ->all();
    }

    private function applyDateConstraintToJoin(
        $join,
        string $column,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): void {
        if ($period === 'monthly') {
            $join->whereYear($column, '=', $year)
                ->whereMonth($column, '=', $month);
            return;
        }

        if ($period === 'yearly') {
            $join->whereYear($column, '=', $year);
            return;
        }

        $join->whereDate($column, '=', $date->toDateString());
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

        $query = DB::table('evaluation_responses as er')
            ->join('evaluation_questions as eq', 'eq.id', '=', 'er.question_id')
            ->where(function ($innerQuery) {
                $innerQuery
                    ->whereNotNull('er.answer_option')
                    ->orWhereNotNull('er.answer_value');
            })
            ->where(function ($innerQuery) use ($questionPrefix) {
                $innerQuery
                    ->where('eq.question_code', $questionPrefix)
                    ->orWhere('eq.question_text', 'like', $questionPrefix . '%');
            });

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'er.queue_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.queue_transaction_id', '=', 'qt.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('s.service_type', 'External');

            $this->applyQueueDateFilter($query, $period, $date, $month, $year);
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.internal_transaction_id', '=', 'it.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.internal_transaction_id')
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
                ->where('s.service_type', 'Internal');

            $this->applyInternalDateFilter($query, $period, $date, $month, $year);
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

    private function getServiceWeightedTransactionCountForSource(
        string $source,
        int $officeId,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): int {
        $query = DB::table('queue_transaction_services as qts')
            ->join('services as s', 's.id', '=', 'qts.service_id');

        if ($source === 'external') {
            $query
                ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
                ->whereNotNull('qts.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('s.service_type', 'External')
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('evaluation_sessions as es')
                        ->whereColumn('es.queue_transaction_id', 'qt.id');
                });

            $this->applyQueueDateFilter($query, $period, $date, $month, $year);

            return (int) $query->count('qts.id');
        }

        $query
            ->join('internal_transactions as it', 'it.id', '=', 'qts.internal_transaction_id')
            ->whereNotNull('qts.internal_transaction_id')
            ->where(function ($q) use ($officeId) {
                $q->where('it.office_id', $officeId)
                  ->orWhere('it.to_office_id', $officeId);
            })
            ->where('s.service_type', 'Internal')
            ->whereExists(function ($subQuery) {
                $subQuery->selectRaw('1')
                    ->from('evaluation_sessions as es')
                    ->whereColumn('es.internal_transaction_id', 'it.id');
            });

        $this->applyInternalDateFilter($query, $period, $date, $month, $year);

        return (int) $query->count('qts.id');
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
        Builder|\Illuminate\Database\Query\Builder $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): Builder|\Illuminate\Database\Query\Builder {
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
        Builder|\Illuminate\Database\Query\Builder $query,
        string $period,
        Carbon $date,
        int $month,
        int $year
    ): Builder|\Illuminate\Database\Query\Builder {
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
                ->join('queue_transaction_services as qts', 'qts.queue_transaction_id', '=', 'qt.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->where('s.service_type', 'External');
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.internal_transaction_id', '=', 'it.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.internal_transaction_id')
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->where('s.service_type', 'Internal');
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
                ->join('queue_transaction_services as qts', 'qts.queue_transaction_id', '=', 'qt.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('es.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->where('s.service_type', 'External');
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'es.internal_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.internal_transaction_id', '=', 'it.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('es.internal_transaction_id')
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->where('s.service_type', 'Internal');
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
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
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
                ->join('queue_transaction_services as qts', 'qts.queue_transaction_id', '=', 'qt.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->where('s.service_type', 'External');
        }

        if ($source === 'internal') {
            $query
                ->join('internal_transactions as it', 'it.id', '=', 'er.internal_transaction_id')
                ->join('queue_transaction_services as qts', 'qts.internal_transaction_id', '=', 'it.id')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->whereNotNull('er.internal_transaction_id')
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->where('s.service_type', 'Internal');
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
            $externalCount = DB::table('queue_transaction_services as qts')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
                ->where('qt.office_id', $officeId)
                ->where('qt.status', 'COMPLETED')
                ->where('qt.queue_date', '>=', $startDate)
                ->where('qt.queue_date', '<', $endDate)
                ->where('s.service_type', 'External')
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('evaluation_sessions as es')
                        ->whereColumn('es.queue_transaction_id', 'qt.id');
                })
                ->count('qts.id');
        }

        if ($serviceType !== 'external') {
            $internalCount = DB::table('queue_transaction_services as qts')
                ->join('services as s', 's.id', '=', 'qts.service_id')
                ->join('internal_transactions as it', 'it.id', '=', 'qts.internal_transaction_id')
                ->where(function ($q) use ($officeId) {
                    $q->where('it.office_id', $officeId)
                      ->orWhere('it.to_office_id', $officeId);
                })
                ->where('it.status', 'COMPLETED')
                ->where('it.transaction_date', '>=', $startDate)
                ->where('it.transaction_date', '<', $endDate)
                ->where('s.service_type', 'Internal')
                ->whereExists(function ($subQuery) {
                    $subQuery->selectRaw('1')
                        ->from('evaluation_sessions as es')
                        ->whereColumn('es.internal_transaction_id', 'it.id');
                })
                ->count('qts.id');
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
