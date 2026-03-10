<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\QueueTransaction;
use App\Models\Counter;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MonitorController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Get office details for the monitor display
     * 
     * @param int $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOfficeDetails($officeId)
    {
        try {
            $office = Office::where('id', $officeId)
                ->where('is_active', true)
                ->first();

            if (!$office) {
                return response()->json([
                    'message' => 'Office not found or inactive.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $office->id,
                    'name' => $office->office_name,
                    'acronym' => $office->office_acronym,
                    'description' => $office->office_description,
                    'logo_url' => $office->logo_url
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get office details error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching office details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current serving per counter data
     * 
     * @param int $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentServing($officeId)
    {
        try {
            $today = $this->queueService->getTodayDate();

            // Get all SERVING transactions for today with their counter information
            $servingQueues = QueueTransaction::with('counter')
                ->where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'SERVING')
                ->whereNotNull('counter_id')
                ->orderBy('called_at', 'asc')
                ->get()
                ->map(function($queue) {
                    return [
                        'queue_number' => $queue->full_queue_number,
                        'counter' => $queue->counter->counter_number,
                        'called_at' => $queue->called_at ? $queue->called_at->format('h:i A') : null,
                        'is_priority' => $queue->is_priority
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $servingQueues
            ]);

        } catch (\Exception $e) {
            Log::error('Get current serving error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching current serving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the latest called number (Now Serving)
     * 
     * @param int $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getNowServing($officeId)
    {
        try {
            $today = $this->queueService->getTodayDate();

            // Get the most recent SERVING transaction (the one called last)
            $latestServing = QueueTransaction::with('counter')
                ->where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'SERVING')
                ->whereNotNull('counter_id')
                ->orderBy('called_at', 'desc')
                ->first();

            if (!$latestServing) {
                return response()->json([
                    'success' => true,
                    'data' => null
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'queue_number' => $latestServing->full_queue_number,
                    'counter' => $latestServing->counter->counter_number,
                    'called_at' => $latestServing->called_at ? $latestServing->called_at->format('h:i A') : null,
                    'message' => "Please proceed to counter {$latestServing->counter->counter_number}"
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get now serving error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching now serving data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get waiting list (12 most recent waiting numbers)
     * 
     * @param int $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWaitingList($officeId)
    {
        try {
            $today = $this->queueService->getTodayDate();

            // Get the 12 most recent WAITING transactions
            $waitingQueues = QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'WAITING')
                ->orderBy('created_at', 'asc') // Oldest first (FIFO)
                ->limit(12)
                ->get()
                ->map(function($queue) {
                    return [
                        'queue_number' => $queue->full_queue_number,
                        'created_at' => $queue->created_at->format('h:i A'),
                        'is_priority' => $queue->is_priority,
                        'waiting_time' => $queue->created_at->diffInMinutes(now()) . ' min'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $waitingQueues
            ]);

        } catch (\Exception $e) {
            Log::error('Get waiting list error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching waiting list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get complete monitor dashboard data in one request
     * 
     * @param int $officeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonitorData($officeId)
    {
        try {
            $today = $this->queueService->getTodayDate();

            // Check if office exists and is active
            $office = Office::where('id', $officeId)
                ->where('is_active', true)
                ->first();

            if (!$office) {
                return response()->json([
                    'message' => 'Office not found or inactive.'
                ], 404);
            }

            // Get all serving queues
            $servingQueues = QueueTransaction::with('counter')
                ->where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'SERVING')
                ->whereNotNull('counter_id')
                ->orderBy('called_at', 'asc')
                ->get()
                ->map(function($queue) {
                    return [
                        'queue_number' => $queue->full_queue_number,
                        'counter' => $queue->counter->counter_number
                    ];
                });

            // Get latest serving for "Now Serving"
            $latestServing = $servingQueues->isNotEmpty() ? $servingQueues->last() : null;
            
            $nowServing = null;
            if ($latestServing) {
                $nowServing = [
                    'queue_number' => $latestServing['queue_number'],
                    'counter' => $latestServing['counter'],
                    'message' => "Please proceed to counter {$latestServing['counter']}"
                ];
            }

            // Get waiting list (12 most recent)
            $waitingList = QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'WAITING')
                ->orderBy('created_at', 'asc')
                ->limit(12)
                ->get()
                ->map(function($queue) {
                    return [
                        'queue_number' => $queue->full_queue_number
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'office' => [
                        'id' => $office->id,
                        'name' => $office->office_name,
                        'acronym' => $office->office_acronym
                    ],
                    'current_serving' => $servingQueues,
                    'now_serving' => $nowServing,
                    'waiting_list' => $waitingList,
                    'date' => $today,
                    'server_time' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Get monitor data error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching monitor data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}