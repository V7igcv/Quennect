<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\QueueTransaction;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AicsAnalyticsController extends Controller
{
    private const AICS_SERVICE_ID = 126;

    /**
     * Get AICS card statistics (total transactions and assistance distributed)
     */
    public function getCardStats()
    {
        $period = request('period', 'daily');
        $date = Carbon::parse(request('date', now()->format('Y-m-d')));
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);

        // Get total AICS transactions
        $transactionQuery = QueueTransaction::query()
            ->whereHas('services', function ($query) {
                $query->where('services.id', self::AICS_SERVICE_ID);
            })
            ->where('status', TransactionStatus::COMPLETED->value);

        $this->applyDateFilter($transactionQuery, $period, $date, $month, $year);
        $totalTransactions = $transactionQuery->count();

        // Get total assistance distributed
        $assistanceQuery = DB::table('queue_transactions as qt')
            ->join('queue_transaction_services as qts', 'qt.id', '=', 'qts.queue_transaction_id')
            ->join('service_assistance as sa', 'qts.id', '=', 'sa.queue_transaction_service_id')
            ->where('qts.service_id', self::AICS_SERVICE_ID)
            ->where('qt.status', TransactionStatus::COMPLETED->value)
            ->select(DB::raw('COALESCE(SUM(sa.assistance_provided), 0) as total'));

        $this->applyDateFilterToColumn($assistanceQuery, $period, $date, $month, $year, 'qt.completed_at');

        $totalAssistance = $assistanceQuery->first()?->total ?? 0;

        return response()->json([
            'totalTransactions' => (int) $totalTransactions,
            'totalAssistanceDistributed' => (float) $totalAssistance,
        ]);
    }

    /**
     * Get AICS assistance distribution by assistance type with barangay filtering
     */
    public function getAssistanceDistribution()
    {
        try {
            $period = request('period', 'daily');
            $date = Carbon::parse(request('date', now()->format('Y-m-d')));
            $month = (int) request('month', now()->month);
            $year = (int) request('year', now()->year);
            $barangayId = request('barangay_id') ? (int) request('barangay_id') : null;

            $assistancePayload = $this->buildAssistanceDistributionPayload(
                $period,
                $date,
                $month,
                $year,
                $barangayId
            );

            $availableBarangays = $this->getAssistanceBarangayOptions(
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
                    'period' => $period,
                    'date' => $date->format('Y-m-d'),
                    'month' => $month,
                    'year' => $year,
                    'barangay_id' => $barangayId,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching assistance distribution',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build assistance distribution payload for AICS service only
     */
    private function buildAssistanceDistributionPayload(
        string $period,
        Carbon $date,
        int $month,
        int $year,
        ?int $barangayId
    ): array {
        $baseQuery = DB::table('service_assistance as sa')
            ->join('queue_transaction_services as qts', 'qts.id', '=', 'sa.queue_transaction_service_id')
            ->join('queue_transactions as qt', 'qt.id', '=', 'qts.queue_transaction_id')
            ->join('services as s', 's.id', '=', 'qts.service_id')
            ->leftJoin('assistance_types as at', 'at.id', '=', 'sa.assistance_type_id')
            ->whereNotNull('qts.queue_transaction_id')
            ->where('qt.status', TransactionStatus::COMPLETED->value)
            ->where('qts.service_id', self::AICS_SERVICE_ID);

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
     * Get available barangays with assistance records for AICS
     */
    private function getAssistanceBarangayOptions(
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
            ->where('qt.status', TransactionStatus::COMPLETED->value)
            ->where('qts.service_id', self::AICS_SERVICE_ID)
            ->whereNotNull('qt.barangay_id');

        $this->applyDateFilterToColumn($query, $period, $date, $month, $year, 'qt.completed_at');

        return $query
            ->selectRaw('b.id as barangay_id')
            ->selectRaw('b.barangay_name as barangay_name')
            ->groupBy('b.id', 'b.barangay_name')
            ->orderBy('b.barangay_name')
            ->get()
            ->map(function ($row) {
                return [
                    'barangay_id' => (int) $row->barangay_id,
                    'barangay_name' => (string) $row->barangay_name,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Get AICS queue summary with pagination
     */
    public function getQueueSummary(Request $request)
    {
        try {
            $validated = $request->validate([
                'period' => 'nullable|in:daily,monthly,yearly',
                'date' => 'nullable|date_format:Y-m-d',
                'month' => 'nullable|integer|min:1|max:12',
                'year' => 'nullable|integer|min:2000|max:2100',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:50',
            ]);

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
                                $query->with('assistanceType');
                            },
                            'service',
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
                ->whereHas('services', function (EloquentBuilder $query) {
                    $query->where('services.id', self::AICS_SERVICE_ID);
                })
                ->whereIn('status', [
                    TransactionStatus::COMPLETED->value,
                    TransactionStatus::SKIPPED->value,
                ])
                ->orderByRaw('COALESCE(skipped_at, completed_at) DESC')
                ->orderBy('id', 'desc');

            $filteredQuery = $this->applyDateFilter($baseQuery, $period, $date, $month, $year);
            $paginated = $filteredQuery->paginate($perPage, ['*'], 'page', $page);

            $rows = $paginated->getCollection()->map(function (QueueTransaction $transaction) {
                $statusValue = $transaction->status instanceof TransactionStatus
                    ? $transaction->status->value
                    : (string) $transaction->status;

                $isSkipped = $statusValue === TransactionStatus::SKIPPED->value
                    || !is_null($transaction->skipped_at);

                $completionTime = $isSkipped ? $transaction->skipped_at : $transaction->completed_at;

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

                $serviceAssistanceDetails = [];
                foreach ($transaction->queueTransactionServices as $qts) {
                    if ((int) $qts->service_id !== self::AICS_SERVICE_ID) {
                        continue;
                    }

                    if (!$qts->serviceAssistance) {
                        continue;
                    }

                    $assistanceTypeName = $qts->serviceAssistance->assistanceType?->assistance_name;
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
                    'waiting_time' => $transaction->waiting_time === null ? null : round((float) $transaction->waiting_time, 2),
                    'service_time' => $transaction->serving_time === null ? null : round((float) $transaction->serving_time, 2),
                    'average_satisfaction_rating' => $isSkipped ? '-' : ($transaction->average_satisfaction_rating ?? 'N/A'),
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
                'filter' => [
                    'period' => $period,
                    'date' => $date->format('Y-m-d'),
                    'month' => $month,
                    'year' => $year,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching queue summary',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export AICS queue summary data as an Excel file.
     */
    public function exportQueueSummary(Request $request)
    {
        try {
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

            $periodLabel = match ($period) {
                'monthly' => Carbon::createFromDate($year, $month, 1)->format('F Y'),
                'yearly' => (string) $year,
                default => $date->format('M d, Y'),
            };

            $transactions = $this->applyDateFilter(
                QueueTransaction::query()
                    ->with([
                        'services' => function ($query) {
                            $query->select('services.id', 'services.service_code', 'services.service_name')
                                ->where('service_type', 'External');
                        },
                        'queueTransactionServices' => function ($query) {
                            $query->with([
                                'serviceAssistance' => function ($query) {
                                    $query->with('assistanceType'); // ✅ Eager load assistance type
                                },
                                'service',
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
                    ->whereHas('services', function (EloquentBuilder $query) {
                        $query->where('services.id', self::AICS_SERVICE_ID);
                    })
                    ->where('status', TransactionStatus::COMPLETED->value)
                    ->orderByRaw('COALESCE(skipped_at, completed_at) DESC')
                    ->orderBy('id', 'desc'),
                $period,
                $date,
                $month,
                $year
            )->get();

            // Group transactions by barangay and sort alphabetically
            $groupedTransactions = $transactions->groupBy(function ($transaction) {
                return $transaction->barangay?->barangay_name ?? 'Unassigned';
            })->sortKeys(SORT_NATURAL);

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('AICS Queue Summary');

            $headers = [
                'Service', 'Queue Number', 'Client Name', 'Contact Number',
                'Sex', 'Age', 'Barangay', 'Lane Type', 'Completion Date and Time',
                'Waiting Time (min)', 'Service Time (min)', 'Assistance Provided', 'Assistance Record Date'
            ];

            $currentRow = 1;
            
            foreach ($groupedTransactions as $barangayName => $barangayTransactions) {
                if ($barangayTransactions->isEmpty()) {
                    continue;
                }
                
                // Check if this barangay has any assistance records
                $hasAssistanceInBarangay = false;
                foreach ($barangayTransactions as $transaction) {
                    foreach ($transaction->queueTransactionServices as $qts) {
                        if ((int) $qts->service_id === self::AICS_SERVICE_ID && 
                            $qts->serviceAssistance && 
                            $qts->serviceAssistance->assistance_provided !== null) {
                            $hasAssistanceInBarangay = true;
                            break 2;
                        }
                    }
                }
                
                // Write Barangay header
                $sheet->setCellValue("A{$currentRow}", $barangayName);
                $sheet->getStyle("A{$currentRow}")->getFont()->setBold(true);
                $sheet->getStyle("A{$currentRow}")->getFont()->setSize(12);
                $currentRow++;
                
                // Write headers (conditionally include assistance columns)
                $displayHeaders = $hasAssistanceInBarangay ? $headers : array_slice($headers, 0, 11);
                $sheet->fromArray($displayHeaders, null, "A{$currentRow}", true);
                $endColumn = $hasAssistanceInBarangay ? 'M' : 'K';
                $sheet->getStyle("A{$currentRow}:{$endColumn}{$currentRow}")->getFont()->setBold(true);
                $currentRow++;
                
                // Write data rows
                $barangayTotalAssistance = 0;
                
                // Expand each transaction into one row per service (though for AICS it's usually just one)
                $expanded = collect();
                
                foreach ($barangayTransactions as $transaction) {
                    $services = $transaction->services;
                    
                    if ($services->isEmpty()) {
                        $expanded->push([
                            'service_label' => 'AICS',
                            'service_id' => self::AICS_SERVICE_ID,
                            'transaction' => $transaction,
                        ]);
                        continue;
                    }
                    
                    foreach ($services as $service) {
                        // Only process AICS services (though the query already filters for AICS)
                        if ((int) $service->id !== self::AICS_SERVICE_ID) {
                            continue;
                        }
                        
                        $label = $service->service_name
                            ?: $service->service_code
                            ?: 'AICS';
                        
                        // Get assistance type for this service
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
                        
                        $evaluationSession = $transaction->evaluationSession;
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

                        $completionDateTime = $transaction->completed_at ?? $transaction->skipped_at;
                        
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
                        $assistanceProvidedAt = $serviceAssistance?->assistance_provided_at
                            ? $serviceAssistance->assistance_provided_at->format('M d, Y h:i A')
                            : null;
                        
                        // Track total assistance for this barangay (extract numeric amount)
                        $assistanceAmount = 0;
                        if ($assistanceProvided !== null) {
                            // Remove currency symbols and commas, keep numbers and decimal points
                            $numericAmount = preg_replace('/[^0-9.-]/', '', (string) $assistanceProvided);
                            if (is_numeric($numericAmount)) {
                                $assistanceAmount = (float) $numericAmount;
                            }
                            $barangayTotalAssistance += $assistanceAmount;
                        }
                        
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
                            $transaction->waiting_time === null ? null : round((float) $transaction->waiting_time, 2),
                            $transaction->serving_time === null ? null : round((float) $transaction->serving_time, 2),
                        ];
                        
                        // Conditionally add assistance columns
                        if ($hasAssistanceInBarangay) {
                            $rowData[] = $assistanceProvided;
                            $rowData[] = $assistanceProvidedAt;
                        }
                        
                        $sheet->fromArray($rowData, null, "A{$currentRow}", true);
                        $currentRow++;
                        $isFirstForService = false;
                    }
                }
                
                // Write total assistance for this barangay (only if barangay has assistance)
                if ($hasAssistanceInBarangay) {
                    $totalRow = $currentRow;
                    $sheet->setCellValue("A{$totalRow}", "Total Assistance Provided: ");
                    $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);
                    $sheet->setCellValue("L{$totalRow}", number_format($barangayTotalAssistance, 2));
                    $sheet->getStyle("L{$totalRow}")->getFont()->setBold(true);
                    $currentRow++;
                }
                
                // Add empty row after total
                $currentRow++;
            }
            
            // Remove any empty rows at the end if needed
            $highestRow = $sheet->getHighestRow();
            for ($row = $highestRow; $row >= 1; $row--) {
                $isEmpty = true;
                for ($col = 'A'; $col <= 'M'; $col++) {
                    if (!empty($sheet->getCell($col . $row)->getValue())) {
                        $isEmpty = false;
                        break;
                    }
                }
                if ($isEmpty) {
                    $sheet->removeRow($row, 1);
                } else {
                    break;
                }
            }

            // Auto-size columns
            $endColumn = 'M';
            foreach (range('A', $endColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            // Apply borders and styling to all data
            $highestRow = $sheet->getHighestRow();
            $highestColumn = $sheet->getHighestColumn();
            
            // Style all data rows with light grey borders
            $styleArray = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['rgb' => 'D3D3D3'],
                    ],
                ],
            ];
            
            if ($highestRow >= 1) {
                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray($styleArray);
            }

            $safePeriodLabel = preg_replace('/\s+/', ' ', trim($periodLabel));
            $fileName = sprintf('AICS Queue Data Summary - %s.xlsx', $safePeriodLabel);
            $tempFile = tempnam(sys_get_temp_dir(), 'aics_queue_summary_');

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
            
        } catch (\Exception $e) {
            Log::error('AICS analytics export queue summary error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error exporting AICS queue summary data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function applyDateFilter($query, string $period, Carbon $date, int $month, int $year)
    {
        return match ($period) {
            'daily' => $query->whereDate('queue_transactions.completed_at', $date),
            'monthly' => $query->whereMonth('queue_transactions.completed_at', $month)
                ->whereYear('queue_transactions.completed_at', $year),
            'yearly' => $query->whereYear('queue_transactions.completed_at', $year),
            default => $query,
        };
    }

    /**
     * Apply date filter to raw query column
     */
    private function applyDateFilterToColumn(&$query, string $period, Carbon $date, int $month, int $year, string $column): void
    {
        if ($period === 'daily') {
            $query->whereDate($column, $date);
        } elseif ($period === 'monthly') {
            $query->whereMonth($column, $month)
                ->whereYear($column, $year);
        } elseif ($period === 'yearly') {
            $query->whereYear($column, $year);
        }
    }
}
