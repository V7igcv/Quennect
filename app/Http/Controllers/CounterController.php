<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\QueueTransaction;
use App\Services\QueueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{

    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Get all counters for the current user's office
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
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

            $counters = Counter::where('office_id', $user->office_id)
                ->orderBy('counter_number', 'asc')
                ->get()
                ->map(function($counter) use ($today) {
                    // Check if this counter is currently serving a queue
                    $currentQueue = QueueTransaction::where('counter_id', $counter->id)
                        ->where('status', 'SERVING')
                        ->whereDate('queue_date', $today)
                        ->first();

                    return [
                        'id' => $counter->id,
                        'counter_number' => $counter->counter_number,
                        'is_enabled' => $counter->is_enabled,
                        'status' => $counter->is_enabled ? 'Available' : 'Disabled',
                        'current_queue' => $currentQueue ? [
                            'id' => $currentQueue->id,
                            'queue_number' => $currentQueue->full_queue_number,
                            'client_name' => $currentQueue->client_name,
                            'called_at' => $currentQueue->called_at ? $currentQueue->called_at->format('h:i A') : null
                        ] : null,
                        'created_at' => $counter->created_at,
                        'updated_at' => $counter->updated_at
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $counters,
                'date' => $today
            ]);

        } catch (\Exception $e) {
            Log::error('Counters index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching counters',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new counter
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->office_id) {
            return response()->json([
                'message' => 'User is not assigned to any office.'
            ], 403);
        }

        $request->validate([
            'counter_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('counters')->where(function ($query) use ($user) {
                    return $query->where('office_id', $user->office_id);
                })
            ],
            'is_enabled' => 'sometimes|boolean'
        ]);

        try {
            DB::beginTransaction();

            $counter = Counter::create([
                'office_id' => $user->office_id,
                'counter_number' => $request->counter_number,
                'is_enabled' => $request->is_enabled ?? true
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Counter created successfully',
                'data' => [
                    'id' => $counter->id,
                    'counter_number' => $counter->counter_number,
                    'is_enabled' => $counter->is_enabled,
                    'status' => $counter->is_enabled ? 'Available' : 'Disabled'
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating counter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update counter status (enable/disable)
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        $request->validate([
            'is_enabled' => 'required|boolean'
        ]);

        try {
            DB::beginTransaction();

            $counter = Counter::where('id', $id)
                ->where('office_id', $user->office_id)
                ->first();

            if (!$counter) {
                return response()->json([
                    'message' => 'Counter not found.'
                ], 404);
            }

            // Check if trying to disable a counter that is currently serving
            if (!$request->is_enabled && $counter->is_enabled) {
                $servingQueue = QueueTransaction::where('counter_id', $counter->id)
                    ->where('status', 'SERVING')
                    ->whereDate('queue_date', now()->toDateString())
                    ->first();

                if ($servingQueue) {
                    return response()->json([
                        'message' => 'Cannot disable counter while it is serving a queue.',
                        'current_queue' => $servingQueue->full_queue_number
                    ], 400);
                }
            }

            $counter->is_enabled = $request->is_enabled;
            $counter->save();

            DB::commit();

            return response()->json([
                'message' => $request->is_enabled ? 'Counter enabled successfully' : 'Counter disabled successfully',
                'data' => [
                    'id' => $counter->id,
                    'counter_number' => $counter->counter_number,
                    'is_enabled' => $counter->is_enabled,
                    'status' => $counter->is_enabled ? 'Available' : 'Disabled'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating counter status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a counter
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $user = Auth::user();

        try {
            DB::beginTransaction();

            $counter = Counter::where('id', $id)
                ->where('office_id', $user->office_id)
                ->first();

            if (!$counter) {
                return response()->json([
                    'message' => 'Counter not found.'
                ], 404);
            }

            $officeCounterCount = Counter::where('office_id', $user->office_id)->count();

            if ($officeCounterCount <= 1) {
                return response()->json([
                    'message' => 'Each office must have at least 1 counter. You cannot delete the last counter.'
                ], 400);
            }

            // Check if counter has any serving or waiting queues today
            $activeQueues = QueueTransaction::where('counter_id', $counter->id)
                ->whereIn('status', ['WAITING', 'SERVING'])
                ->whereDate('queue_date', now()->toDateString())
                ->exists();

            if ($activeQueues) {
                return response()->json([
                    'message' => 'Cannot delete counter while it has active queues.'
                ], 400);
            }

            // Set counter_id to null for any historical queues
            QueueTransaction::where('counter_id', $counter->id)
                ->update(['counter_id' => null]);

            $counter->delete();

            DB::commit();

            return response()->json([
                'message' => 'Counter deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting counter',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available counters (idle and enabled) for dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableCounters()
    {
        $user = Auth::user();

        if (!$user->office_id) {
            return response()->json([
                'message' => 'User is not assigned to any office.'
            ], 403);
        }

        // Get all enabled counters
        $allCounters = Counter::where('office_id', $user->office_id)
            ->where('is_enabled', true)
            ->orderBy('counter_number', 'asc')
            ->get();

        // Get counters that are currently serving
        $servingCounterIds = QueueTransaction::where('status', 'SERVING')
            ->whereDate('queue_date', now()->toDateString())
            ->whereNotNull('counter_id')
            ->pluck('counter_id')
            ->toArray();

        // Filter to only idle counters
        $availableCounters = $allCounters->filter(function($counter) use ($servingCounterIds) {
            return !in_array($counter->id, $servingCounterIds);
        })->values()->map(function($counter) {
            return [
                'id' => $counter->id,
                'counter_number' => $counter->counter_number,
                'display' => "Counter {$counter->counter_number}"
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $availableCounters
        ]);
    }
}