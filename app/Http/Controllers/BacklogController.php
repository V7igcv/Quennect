<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\QueueTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BacklogController extends Controller
{
    /**
     * Move a serving transaction to backlog.
     * POST /frontdesk/queue/backlog/{id}
     */
    public function moveToBacklog(int $id): JsonResponse
    {
        $transaction = QueueTransaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        if ($transaction->status !== TransactionStatus::SERVING) {
            return response()->json([
                'success' => false,
                'message' => 'Only active (serving) transactions can be moved to backlog.',
            ], 422);
        }

        $transaction->update([
            'status'     => TransactionStatus::BACKLOG,
            'counter_id' => null,
            'backlog_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Queue {$transaction->queue_number} moved to backlog.",
        ]);
    }

    /**
     * Get today's backlog transactions for the current office.
     * GET /frontdesk/backlog
     */
    public function getBacklog(): JsonResponse
    {
        $officeId = auth()->user()->office_id;

        $transactions = QueueTransaction::with(['services', 'barangay'])
            ->where('office_id', $officeId)
            ->where('status', TransactionStatus::BACKLOG)
            ->orderBy('backlog_at', 'asc')
            ->get()
            ->map(function($t) {
                return [
                    'id'            => $t->id,
                    'queue_number'  => $t->full_queue_number ?? $t->queue_number,
                    'client_name'   => $t->client_name,
                    'service_codes' => $t->services->pluck('service_code')->join(', '), // ✅ codes for display
                    'service_names' => $t->services->pluck('service_name')->join(', '), // ✅ full names for tooltip
                    'lane_type'     => $t->is_priority ? 'Priority' : 'Regular',
                   'backlog_time' => $t->backlog_at?->format('M d, Y h:i A'), // 
                ];
            });

        return response()->json([
            'success' => true,
            'data'    => $transactions,
        ]);
    }

    /**
     * Skip a backlog transaction.
     * POST /frontdesk/backlog/skip/{id}
     */
    public function skipFromBacklog(int $id): JsonResponse
    {
        $transaction = QueueTransaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.',
            ], 404);
        }

        if ($transaction->status !== TransactionStatus::BACKLOG) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction is not in backlog.',
            ], 422);
        }

        $transaction->update([
            'status'     => TransactionStatus::SKIPPED,
            'skipped_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Queue {$transaction->full_queue_number} skipped.",
        ]);
    }
}