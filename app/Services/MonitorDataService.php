<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Office;
use App\Models\QueueTransaction;

class MonitorDataService
{
    public function __construct(protected QueueService $queueService)
    {
    }

    /**
     * Build the monitor payload for a given office.
     * Returns null when office does not exist or is inactive.
     */
    public function getOfficeMonitorData(int|string $officeId): ?array
    {
        $today = $this->queueService->getTodayDate();

        $office = Office::where('id', $officeId)
            ->where('is_active', true)
            ->first();

        if (! $office) {
            return null;
        }

        $servingQueueModels = QueueTransaction::with('counter')
            ->where('office_id', $officeId)
            ->whereDate('queue_date', $today)
            ->where('status', 'SERVING')
            ->whereNotNull('counter_id')
            ->orderBy('called_at', 'asc')
            ->get();

        $servingQueues = $servingQueueModels
            ->map(function ($queue) {
                return [
                    'queue_number' => $queue->full_queue_number,
                    'counter' => $queue->counter->counter_number,
                ];
            })
            ->values();

        $servingByCounter = $servingQueueModels->keyBy('counter_id');

        $counters = Counter::where('office_id', $officeId)
            ->orderBy('counter_number', 'asc')
            ->get()
            ->map(function ($counter) use ($servingByCounter) {
                $servingQueue = $servingByCounter->get($counter->id);

                return [
                    'id' => $counter->id,
                    'counter_number' => $counter->counter_number,
                    'is_enabled' => (bool) $counter->is_enabled,
                    'queue_number' => $servingQueue?->full_queue_number,
                ];
            })
            ->values();

        $latestServing = $servingQueues->isNotEmpty() ? $servingQueues->last() : null;

        $nowServing = null;
        if ($latestServing) {
            $nowServing = [
                'queue_number' => $latestServing['queue_number'],
                'counter' => $latestServing['counter'],
                'message' => "Please proceed to counter {$latestServing['counter']}",
            ];
        }

        $waitingList = QueueTransaction::where('office_id', $officeId)
            ->whereDate('queue_date', $today)
            ->where('status', 'WAITING')
            ->orderBy('created_at', 'asc')
            ->limit(12)
            ->get()
            ->map(function ($queue) {
                return [
                    'queue_number' => $queue->full_queue_number,
                ];
            })
            ->values();

        return [
            'office' => [
                'id' => $office->id,
                'name' => $office->office_name,
                'acronym' => $office->office_acronym,
                'logo_url' => $office->logo_url,
            ],
            'current_serving' => $servingQueues,
            'now_serving' => $nowServing,
            'counters' => $counters,
            'waiting_list' => $waitingList,
            'date' => $today,
            'server_time' => now()->toDateTimeString(),
        ];
    }
}
