<?php

namespace App\Console\Commands;

use App\Models\InternalTransaction;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillInternalTransactionServicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:csm-backfill-internal-pivot
        {--office_id= : Limit to internal transactions for this office_id}
        {--from-date= : Only include transactions on/after this date (Y-m-d)}
        {--to-date= : Only include transactions on/before this date (Y-m-d)}
        {--dry-run : Do not write to the database, just report what would happen}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill queue_transaction_services for existing internal_transactions using service_ids JSON';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $officeId = $this->option('office_id');
        $fromDate = $this->option('from-date');
        $toDate = $this->option('to-date');
        $dryRun = (bool) $this->option('dry-run');

        if ($officeId !== null && !ctype_digit((string) $officeId)) {
            $this->error('office_id must be a positive integer when provided.');
            return self::FAILURE;
        }

        $officeId = $officeId !== null ? (int) $officeId : null;

        try {
            $from = $fromDate ? Carbon::createFromFormat('Y-m-d', $fromDate)->startOfDay() : null;
        } catch (\Exception $e) {
            $this->error('from-date must be in Y-m-d format.');
            return self::FAILURE;
        }

        try {
            $to = $toDate ? Carbon::createFromFormat('Y-m-d', $toDate)->endOfDay() : null;
        } catch (\Exception $e) {
            $this->error('to-date must be in Y-m-d format.');
            return self::FAILURE;
        }

        if ($from && $to && $from->greaterThan($to)) {
            $this->error('from-date cannot be after to-date.');
            return self::FAILURE;
        }

        $query = InternalTransaction::query()
            ->whereNotNull('service_ids');

        if ($officeId) {
            // CSM analytics uses internal_transactions.office_id as the office filter.
            $query->where('office_id', $officeId);
        }

        if ($from) {
            $query->whereDate('transaction_date', '>=', $from->toDateString());
        }

        if ($to) {
            $query->whereDate('transaction_date', '<=', $to->toDateString());
        }

        $this->line('Backfilling internal transaction service pivots...');
        if ($officeId) {
            $this->line("- Office ID: {$officeId}");
        }
        if ($from || $to) {
            $range = ($from ? $from->toDateString() : '(-inf)') . ' to ' . ($to ? $to->toDateString() : '(+inf)');
            $this->line("- Transaction date range: {$range}");
        }
        $this->line($dryRun ? '- Mode: DRY-RUN (no inserts will be performed)' : '- Mode: APPLY (inserts will be written to the database)');
        $this->newLine();

        $totalTransactions = 0;
        $transactionsWithNoServices = 0;
        $existingPivotPairs = 0;
        $insertedPivotPairs = 0;

        $query->orderBy('id')->chunkById(200, function ($transactions) use (&$totalTransactions, &$transactionsWithNoServices, &$existingPivotPairs, &$insertedPivotPairs, $dryRun) {
            foreach ($transactions as $transaction) {
                $totalTransactions++;

                $serviceIds = $transaction->service_ids ?? [];
                if (!is_array($serviceIds) || empty($serviceIds)) {
                    $transactionsWithNoServices++;
                    continue;
                }

                foreach ($serviceIds as $serviceId) {
                    if (!$serviceId) {
                        continue;
                    }

                    $exists = DB::table('queue_transaction_services')
                        ->where('internal_transaction_id', $transaction->id)
                        ->where('service_id', $serviceId)
                        ->exists();

                    if ($exists) {
                        $existingPivotPairs++;
                        continue;
                    }

                    if ($dryRun) {
                        $this->line("[DRY-RUN] Would insert pivot: internal_transaction_id={$transaction->id}, service_id={$serviceId}");
                        $insertedPivotPairs++;
                        continue;
                    }

                    DB::table('queue_transaction_services')->insert([
                        'queue_transaction_id'    => null,
                        'internal_transaction_id' => $transaction->id,
                        'service_id'              => $serviceId,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);

                    $insertedPivotPairs++;
                }
            }
        });

        $this->newLine();
        $this->info('Backfill complete.');
        $this->line("Total internal transactions scanned: {$totalTransactions}");
        $this->line("Transactions with empty/invalid service_ids: {$transactionsWithNoServices}");
        $this->line("Existing pivot pairs skipped: {$existingPivotPairs}");
        $this->line(($dryRun ? 'Pivot pairs that would be inserted: ' : 'Pivot pairs inserted: ') . $insertedPivotPairs);

        return self::SUCCESS;
    }
}
