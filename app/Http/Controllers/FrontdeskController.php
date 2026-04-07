<?php

namespace App\Http\Controllers;

use App\Events\MonitorUpdated;
use App\Models\QueueTransaction;
use App\Models\Counter;
use App\Services\MonitorDataService;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FrontdeskController extends Controller
{

    protected $queueService;
    protected $monitorDataService;

    public function __construct(QueueService $queueService, MonitorDataService $monitorDataService)
    {
        $this->queueService = $queueService;
        $this->monitorDataService = $monitorDataService;
    }

    /**
     * Get dashboard statistics (Waiting, Serving, Completed, Skipped)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardStats()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            if (!$user->office_id) {
                return response()->json([
                    'message' => 'User is not assigned to any office.'
                ], 403);
            }

            $today = $this->queueService->getTodayDate();

            $stats = [
                'waiting' => QueueTransaction::where('office_id', $user->office_id)
                    ->whereDate('queue_date', $today)
                    ->where('status', 'WAITING')
                    ->count(),
                
                'serving' => QueueTransaction::where('office_id', $user->office_id)
                    ->whereDate('queue_date', $today)
                    ->where('status', 'SERVING')
                    ->count(),
                
                'completed' => QueueTransaction::where('office_id', $user->office_id)
                    ->whereDate('queue_date', $today)
                    ->where('status', 'COMPLETED')
                    ->count(),
                
                'skipped' => QueueTransaction::where('office_id', $user->office_id)
                    ->whereDate('queue_date', $today)
                    ->where('status', 'SKIPPED')
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'date' => $today
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching dashboard stats',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get queue table data (currently waiting)
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getQueueTable()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            if (!$user->office_id) {
                return response()->json([
                    'message' => 'User is not assigned to any office.'
                ], 403);
            }

            $today = $this->queueService->getTodayDate();

            $queueEntries = QueueTransaction::with(['services' => function($query) {
                    $query->select('services.id', 'services.service_code');
                }])
                ->where('office_id', $user->office_id)
                ->whereDate('queue_date', $today)
                ->where('status', 'WAITING')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function($queue) {
                    return [
                        'id' => $queue->id,
                        'queue_number' => $queue->full_queue_number,
                        'services' => $queue->services->pluck('service_code')->implode(', '),
                        'lane_type' => $queue->is_priority ? 'Priority' : 'Regular',
                        'time' => $queue->created_at->format('h:i A'),
                        'client_name' => $queue->client_name,
                        'contact_number' => $queue->contact_number,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $queueEntries,
                'date' => $today
            ]);

        } catch (\Exception $e) {
            Log::error('Queue table error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching queue table',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call a queue number (move from WAITING to SERVING)
     * 
     * @param Request $request
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function callQueue(Request $request, $queueId)
    {
        $request->validate([
            'counter_id' => 'required|exists:counters,id'
        ]);

        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Find the queue transaction
            $queue = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'WAITING')
                ->first();

            if (!$queue) {
                return response()->json([
                    'message' => 'Queue not found or already called.'
                ], 404);
            }

            // Check if counter exists, is enabled, and belongs to the user's office
            $counter = Counter::where('id', $request->counter_id)
                ->where('office_id', $user->office_id)
                ->where('is_enabled', true)  // Changed from 'status' to 'is_enabled'
                ->first();

            if (!$counter) {
                return response()->json([
                    'message' => 'Counter is not available or disabled.'
                ], 400);
            }

            // Check if counter is currently serving another queue
            $servingQueue = QueueTransaction::where('counter_id', $counter->id)
                ->where('status', 'SERVING')
                ->whereDate('queue_date', now()->toDateString())
                ->first();

            if ($servingQueue) {
                return response()->json([
                    'message' => 'Counter is currently serving another queue.',
                    'current_queue' => $servingQueue->full_queue_number
                ], 400);
            }

            // Update queue transaction
            $queue->status = 'SERVING';
            $queue->counter_id = $counter->id;
            $queue->called_at = now();
            
            // Calculate and store waiting time
            if ($queue->created_at) {
                $queue->waiting_time = (int) round($queue->created_at->diffInMinutes($queue->called_at));
            }
            
            $queue->save();

            DB::commit();
            $this->broadcastMonitorUpdate((int) $user->office_id);

            return response()->json([
                'message' => 'Queue called successfully',
                'queue' => [
                    'id' => $queue->id,
                    'queue_number' => $queue->full_queue_number,
                    'counter_number' => $counter->counter_number
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error calling queue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Skip a queue number (from waiting list)
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function skipFromTable($queueId)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $queue = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'WAITING')
                ->first();

            if (!$queue) {
                return response()->json([
                    'message' => 'Queue not found or already processed.'
                ], 404);
            }

            $queue->status = 'SKIPPED';
            $queue->skipped_at = now();
            $queue->save();

            DB::commit();
            $this->broadcastMonitorUpdate((int) $user->office_id);

            return response()->json([
                'message' => 'Queue skipped successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error skipping queue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Auto-skip all stale waiting queues from previous days for the current office.
     *
     * Intended to be triggered around midnight by the frontdesk dashboard.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function autoSkipStaleQueues()
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        if (!$user->office_id) {
            return response()->json([
                'message' => 'User is not assigned to any office.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $today = now()->toDateString();

            $staleQueues = QueueTransaction::where('office_id', $user->office_id)
                ->whereIn('status', ['WAITING', 'SERVING'])
                ->whereDate('queue_date', '<', $today)
                ->lockForUpdate()
                ->get();

            foreach ($staleQueues as $queue) {
                $queue->status = 'SKIPPED';
                $queue->skipped_at = now();

                // If this queue was already called (SERVING), clear the counter assignment
                if ($queue->counter_id) {
                    $queue->counter_id = null;
                }

                $queue->save();
            }

            DB::commit();

            if ($staleQueues->isNotEmpty()) {
                $this->broadcastMonitorUpdate((int) $user->office_id);
            }

            return response()->json([
                'success' => true,
                'message' => 'Stale queues skipped successfully.',
                'skipped_count' => $staleQueues->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Auto-skip stale queues error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error auto-skipping stale queues',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Skip a queue number from counter card
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function skipFromCounter($queueId)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $queue = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'SERVING')
                ->first();

            if (!$queue) {
                return response()->json([
                    'message' => 'Queue not found or not currently being served.'
                ], 404);
            }

            $queue->status = 'SKIPPED';
            $queue->skipped_at = now();
            $queue->counter_id = null; // Remove counter assignment
            $queue->save();

            DB::commit();
            $this->broadcastMonitorUpdate((int) $user->office_id);

            return response()->json([
                'message' => 'Queue skipped successfully from counter'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error skipping queue',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a queue transaction (move from SERVING to COMPLETED)
     * 
     * @param int $queueId
     * @return \Illuminate\Http\JsonResponse
     */
    public function completeTransaction($queueId)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            // Find the queue transaction that is currently being served
            $queue = QueueTransaction::where('id', $queueId)
                ->where('office_id', $user->office_id)
                ->where('status', 'SERVING')
                ->first();

            if (!$queue) {
                return response()->json([
                    'message' => 'Queue not found or not currently being served.'
                ], 404);
            }

            // Update queue transaction
            $queue->status = 'COMPLETED';
            $queue->completed_at = now();
            
            // Calculate and store serving time
            if ($queue->called_at) {
                $queue->serving_time = (int) round($queue->called_at->diffInMinutes($queue->completed_at));
            }
            
            $queue->save();

            DB::commit();
            $this->broadcastMonitorUpdate((int) $user->office_id);

            return response()->json([
                'message' => 'Transaction completed successfully',
                'data' => [
                    'id' => $queue->id,
                    'queue_number' => $queue->full_queue_number,
                    'client_name' => $queue->client_name,
                    'contact_number' => $queue->contact_number,
                    'completed_at' => $queue->completed_at->format('h:i A')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error completing transaction',
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
            Log::warning('Failed to broadcast monitor update', [
                'office_id' => $officeId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}