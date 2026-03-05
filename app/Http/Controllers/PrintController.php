<?php

namespace App\Http\Controllers;

use App\Models\QueueTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PrintController extends Controller
{
    /**
     * Mark queue as printed
     * Used by: Kiosk only
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function markAsPrinted(Request $request, int $id): JsonResponse
    {
        try {
            $queue = QueueTransaction::where('id', $id)
                ->whereDate('queue_date', now()->toDateString())
                ->first();

            if (!$queue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue transaction not found.'
                ], 404);
            }

            // You can add printed_at column if needed
            // $queue->update(['printed_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Queue number marked as printed.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to mark as printed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Unable to update print status.'
            ], 500);
        }
    }
}