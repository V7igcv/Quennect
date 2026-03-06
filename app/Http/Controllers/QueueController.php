<?php

namespace App\Http\Controllers;

use App\Models\QueueTransaction;
use App\Models\Office;
use App\Http\Requests\GenerateQueueRequest;
use App\Enums\TransactionStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueController extends Controller
{
    /**
     * Generate new queue number
     */
    public function store(GenerateQueueRequest $request): JsonResponse
    {
        // ============ DEBUGGING STEP 1 ============
        Log::info('===================');
        Log::info('QUEUE STORE METHOD CALLED');
        Log::info('===================');
        
        // I-log ang buong request
        Log::info('1. Raw request data:', $request->all());
        
        // I-log ang headers
        Log::info('2. Request headers:', [
            'content-type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept')
        ]);
        
        // I-log kung may specific fields
        Log::info('3. Specific fields:', [
            'has_office_id' => $request->has('office_id'),
            'office_id' => $request->input('office_id'),
            'has_client_name' => $request->has('client_name'),
            'client_name' => $request->input('client_name'),
            'has_contact' => $request->has('contact_number'),
            'contact' => $request->input('contact_number'),
            'has_barangay' => $request->has('barangay_id'),
            'barangay' => $request->input('barangay_id'),
            'has_lane_type' => $request->has('lane_type'),
            'lane_type' => $request->input('lane_type'),
            'has_services' => $request->has('service_ids'),
            'services' => $request->input('service_ids')
        ]);
        
        // Subukan muna nating i-access ang validated data
        try {
            $validated = $request->validated();
            Log::info('4. VALIDATION SUCCESS! Validated data:', $validated);
        } catch (\Exception $e) {
            Log::error('5. VALIDATION FAILED! Error: ' . $e->getMessage());
            
            // I-log ang validation errors kung meron
            if (method_exists($request, 'validator') && $request->validator && $request->validator->errors()->any()) {
                Log::error('6. Validation errors:', $request->validator->errors()->toArray());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => method_exists($request, 'validator') ? $request->validator->errors() : null
            ], 422);
        }
        
        // ============ END DEBUGGING ============
        
        // Kung umabot dito, ibig sabihin successful ang validation
        Log::info('7. Proceeding with queue generation...');
        
        DB::beginTransaction();

        try {
            $office = Office::findOrFail($validated['office_id']);
            Log::info('8. Office found:', ['id' => $office->id, 'name' => $office->office_name]);
            
            // Generate queue number
            $queueData = $this->generateNextNumber(
                $office->id,
                $validated['is_priority']
            );
            Log::info('9. Queue number generated:', $queueData);

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
                'status' => TransactionStatus::WAITING->value
            ]);
            
            Log::info('10. Queue transaction created:', ['id' => $queueTransaction->id, 'queue' => $queueTransaction->full_queue_number]);

            // Attach services
            $queueTransaction->services()->attach($validated['service_ids']);
            Log::info('11. Services attached:', ['service_ids' => $validated['service_ids']]);

            // Attach priority sectors if applicable
            if ($validated['is_priority'] && !empty($validated['priority_sector_ids'])) {
                $queueTransaction->prioritySectors()->attach($validated['priority_sector_ids']);
                Log::info('12. Priority sectors attached:', ['sectors' => $validated['priority_sector_ids']]);
            }

            DB::commit();
            Log::info('13. TRANSACTION COMMITTED SUCCESSFULLY!');

            // Load relationships for response
            $queueTransaction->load(['office', 'services', 'prioritySectors', 'barangay']);

            return response()->json([
                'success' => true,
                'message' => 'Queue number generated successfully.',
                'data' => $this->formatQueueResponse($queueTransaction)
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('14. EXCEPTION CAUGHT: ' . $e->getMessage());
            Log::error('15. Exception trace:', ['trace' => $e->getTraceAsString()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate queue number. Please try again.'
            ], 500);
        }
    }

    /**
     * Get queue details by full queue number
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
                'waiting' => $queues->where('status', TransactionStatus::WAITING->value)->count(),
                'serving' => $queues->where('status', TransactionStatus::SERVING->value)->count(),
                'completed' => $queues->where('status', TransactionStatus::COMPLETED->value)->count(),
                'skipped' => $queues->where('status', TransactionStatus::SKIPPED->value)->count()
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
        ->where('status', TransactionStatus::WAITING->value)
        ->where('created_at', '<', $queue->created_at)
        ->count() + 1;

    return [
        'id' => $queue->id,
        'queue_number' => $queue->full_queue_number,
        'office' => [
            'id' => $queue->office->id,
            'name' => $queue->office->office_name,
            'acronym' => $queue->office->office_acronym,
            'display_name' => $queue->office->office_name . ' (' . $queue->office->office_acronym . ')'
        ],
        'client_name' => $queue->client_name,
        'contact_number' => $queue->contact_number,
        'barangay' => $queue->barangay?->barangay_name,
        'lane_type' => $queue->is_priority ? 'priority' : 'regular',
        'priority_sectors' => $queue->prioritySectors->pluck('sector_name'),
        'services' => $queue->services->map(function($service) {
            return [
                'name' => $service->service_name,
                'code' => $service->service_code,
                'display_name' => $service->service_name . ' (' . $service->service_code . ')'
            ];
        }),
        'status' => strtolower($queue->status->value),  // ✅ ITO ANG TAMA
        'position' => $position,
        'estimated_wait_time' => $position * 5,
        'created_at' => $queue->created_at->format('Y-m-d H:i:s'),
        'created_at_formatted' => $queue->created_at->format('h:i A'),
        'called_at' => $queue->called_at?->format('h:i A'),
        'completed_at' => $queue->completed_at?->format('h:i A')
    ];
}
    
}