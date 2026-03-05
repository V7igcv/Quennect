<?php

namespace App\Http\Controllers;

use App\Models\QueueTransaction;
use App\Models\Office;
use App\Http\Requests\GenerateQueueRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    /**
     * Generate new queue number
     * Used by: Kiosk
     * 
     * @param GenerateQueueRequest $request
     * @return JsonResponse
     */
    public function store(GenerateQueueRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $validated = $request->validated();
            
            $office = Office::findOrFail($validated['office_id']);
            
            // Generate queue number
            $queueData = $this->generateNextNumber(
                $office->id,
                $validated['is_priority']
            );

            // Create transaction
            $queueTransaction = QueueTransaction::create([
                'office_id' => $office->id,
                'queue_date' => now()->toDateString(),
                'queue_number' => $queueData['queue_number'],
                'queue_prefix' => $queueData['queue_prefix'],
                'is_priority' => $validated['is_priority'],
                'full_queue_number' => $queueData['full_queue_number'],
                'client_name' => $validated['client_name'],
                'contact_number' => $validated['contact_number'],
                'barangay_id' => $validated['barangay_id'],
                'status' => QueueTransaction::STATUS_WAITING
            ]);

            // Attach services
            $queueTransaction->services()->attach($validated['service_ids']);

            // Attach priority sectors if applicable
            if ($validated['is_priority'] && !empty($validated['priority_sector_ids'])) {
                $queueTransaction->prioritySectors()->attach($validated['priority_sector_ids']);
            }

            DB::commit();

            // Load relationships for response
            $queueTransaction->load(['office', 'services', 'prioritySectors', 'barangay']);

            return response()->json([
                'success' => true,
                'message' => 'Queue number generated successfully.',
                'data' => $this->formatQueueResponse($queueTransaction)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Queue generation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate queue number. Please try again.'
            ], 500);
        }
    }

    /**
     * Get queue details by full queue number
     * Used by: Kiosk (print page), Monitor, Frontdesk
     * 
     * @param string $queueNumber
     * @return JsonResponse
     */
    public function show(string $queueNumber): JsonResponse
    {
        try {
            $queueTransaction = QueueTransaction::with(['office', 'services', 'prioritySectors', 'barangay'])
                ->where('full_queue_number', $queueNumber)
                ->whereDate('queue_date', now()->toDateString())
                ->first();

            if (!$queueTransaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue number not found for today.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatQueueResponse($queueTransaction)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch queue details: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch queue details. Please try again.'
            ], 500);
        }
    }

    /**
     * Get today's queue for an office
     * Used by: Frontdesk, Monitor
     * 
     * @param int $officeId
     * @return JsonResponse
     */
    public function getTodayQueue(int $officeId): JsonResponse
    {
        try {
            $queues = QueueTransaction::with(['services', 'prioritySectors'])
                ->where('office_id', $officeId)
                ->whereDate('queue_date', now()->toDateString())
                ->orderBy('created_at')
                ->get();

            $stats = [
                'waiting' => $queues->where('status', QueueTransaction::STATUS_WAITING)->count(),
                'serving' => $queues->where('status', QueueTransaction::STATUS_SERVING)->count(),
                'completed' => $queues->where('status', QueueTransaction::STATUS_COMPLETED)->count(),
                'skipped' => $queues->where('status', QueueTransaction::STATUS_SKIPPED)->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'statistics' => $stats,
                    'queues' => $queues->map(function ($queue) {
                        return $this->formatQueueResponse($queue);
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch today\'s queue: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch queue data. Please try again.'
            ], 500);
        }
    }

    /**
     * Generate next queue number (with race condition prevention)
     */
    private function generateNextNumber(int $officeId, bool $isPriority): array
    {
        $office = Office::findOrFail($officeId);
        $today = now()->toDateString();
        
        return DB::transaction(function () use ($officeId, $today, $isPriority, $office) {
            // Lock the table to prevent race conditions
            $lastTransaction = QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('is_priority', $isPriority)
                ->lockForUpdate()
                ->orderBy('queue_number', 'desc')
                ->first();

            $nextNumber = $lastTransaction ? $lastTransaction->queue_number + 1 : 1;
            $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
            $priorityPrefix = $isPriority ? 'P' : '';
            $fullQueueNumber = $office->office_acronym . '-' . $priorityPrefix . $formattedNumber;

            return [
                'queue_number' => $nextNumber,
                'full_queue_number' => $fullQueueNumber,
                'queue_prefix' => $office->office_acronym . '-'
            ];
        });
    }

    /**
     * Format queue response (reusable)
     */
    private function formatQueueResponse($queue): array
    {
        $position = QueueTransaction::where('office_id', $queue->office_id)
            ->whereDate('queue_date', $queue->queue_date)
            ->where('status', QueueTransaction::STATUS_WAITING)
            ->where('created_at', '<', $queue->created_at)
            ->count() + 1;

        return [
            'id' => $queue->id,
            'queue_number' => $queue->full_queue_number,
            'office' => [
                'id' => $queue->office->id,
                'name' => $queue->office->office_name,
                'acronym' => $queue->office->office_acronym,
                'display_name' => $queue->office->display_name
            ],
            'client_name' => $queue->client_name,
            'contact_number' => $queue->contact_number,
            'barangay' => $queue->barangay?->barangay_name,
            'lane_type' => $queue->is_priority ? 'priority' : 'regular',
            'priority_sectors' => $queue->prioritySectors->pluck('sector_name'),
            'services' => $queue->services->map(fn($s) => [
                'name' => $s->service_name,
                'code' => $s->service_code,
                'display_name' => $s->display_name
            ]),
            'status' => strtolower($queue->status),
            'position' => $position,
            'estimated_wait_time' => $position * 5,
            'created_at' => $queue->created_at->format('Y-m-d H:i:s'),
            'created_at_formatted' => $queue->created_at->format('h:i A'),
            'called_at' => $queue->called_at?->format('h:i A'),
            'completed_at' => $queue->completed_at?->format('h:i A')
        ];
    }
}