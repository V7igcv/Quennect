<?php

namespace App\Services;

use App\Models\QueueTransaction;
use App\Models\Counter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueService
{
    /**
     * Get today's date in the correct format
     */
    public function getTodayDate()
    {
        return now()->toDateString();
    }

    /**
     * Check if we need to reset (new day)
     * This is more for logging/monitoring purposes
     */
    public function checkForDayChange($officeId)
    {
        $lastQueueDate = QueueTransaction::where('office_id', $officeId)
            ->max('queue_date');

        $today = $this->getTodayDate();

        if ($lastQueueDate && $lastQueueDate < $today) {
            Log::info("Day changed for office {$officeId}. Last queue: {$lastQueueDate}, Today: {$today}");
            return true;
        }

        return false;
    }

    /**
     * Generate the next queue number for today
     */
    public function generateNextQueueNumber($officeId, $isPriority = false)
    {
        $today = $this->getTodayDate();

        // Get the highest queue number for today
        $lastQueue = QueueTransaction::where('office_id', $officeId)
            ->whereDate('queue_date', $today)
            ->where('is_priority', $isPriority)
            ->orderBy('queue_number', 'desc')
            ->first();

        $nextNumber = $lastQueue ? $lastQueue->queue_number + 1 : 1;

        // Determine prefix (you can customize this based on your needs)
        $prefix = $isPriority ? 'P' : 'R'; // P for Priority, R for Regular
        
        // Format: P001, R001, etc.
        $formattedNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $fullQueueNumber = $prefix . $formattedNumber;

        return [
            'queue_number' => $nextNumber,
            'queue_prefix' => $prefix,
            'full_queue_number' => $fullQueueNumber
        ];
    }

    /**
     * Get dashboard stats for today
     */
    public function getTodayStats($officeId)
    {
        $today = $this->getTodayDate();

        return [
            'waiting' => QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'WAITING')
                ->count(),
            
            'serving' => QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'SERVING')
                ->count(),
            
            'completed' => QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'COMPLETED')
                ->count(),
            
            'skipped' => QueueTransaction::where('office_id', $officeId)
                ->whereDate('queue_date', $today)
                ->where('status', 'SKIPPED')
                ->count(),
        ];
    }

    /**
     * Clean up any stale serving queues from previous days
     * This can be run via a scheduled job or on system startup
     */
    public function cleanupStaleQueues()
    {
        $yesterday = now()->subDay()->toDateString();

        // Find any queues that are still WAITING or SERVING from yesterday
        $staleQueues = QueueTransaction::whereDate('queue_date', '<', now()->toDateString())
            ->whereIn('status', ['WAITING', 'SERVING'])
            ->get();

        foreach ($staleQueues as $queue) {
            // For waiting queues, mark as skipped
            if ($queue->status === 'WAITING') {
                $queue->status = 'SKIPPED';
                $queue->skipped_at = now();
            }
            
            // For serving queues, mark as completed (or skipped, depending on your business logic)
            if ($queue->status === 'SERVING') {
                $queue->status = 'COMPLETED'; // or 'SKIPPED' - discuss with your team
                $queue->completed_at = now();
            }
            
            $queue->save();
            
            Log::info("Cleaned up stale queue: {$queue->full_queue_number} from {$queue->queue_date}");
        }

        return count($staleQueues);
    }

    /**
     * Get available counters for today
     * (counters that are enabled and not currently serving)
     */
    public function getAvailableCounters($officeId)
    {
        // Get all enabled counters
        $allCounters = Counter::where('office_id', $officeId)
            ->where('is_enabled', true)
            ->get();

        // Get counters that are currently serving
        $servingCounterIds = QueueTransaction::where('status', 'SERVING')
            ->whereDate('queue_date', $this->getTodayDate())
            ->whereNotNull('counter_id')
            ->pluck('counter_id')
            ->toArray();

        // Filter to only idle counters
        return $allCounters->filter(function($counter) use ($servingCounterIds) {
            return !in_array($counter->id, $servingCounterIds);
        })->values();
    }

    /**
     * Check if a queue number is valid for today
     */
    public function isValidQueueForToday($officeId, $queueNumber, $isPriority = false)
    {
        return QueueTransaction::where('office_id', $officeId)
            ->whereDate('queue_date', $this->getTodayDate())
            ->where('queue_number', $queueNumber)
            ->where('is_priority', $isPriority)
            ->exists();
    }
}