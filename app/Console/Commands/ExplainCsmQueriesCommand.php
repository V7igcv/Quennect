<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExplainCsmQueriesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:csm-explain
        {--office_id= : Office id to analyze (required)}
        {--service_type=all : external|internal|all}
        {--period=daily : daily|monthly|yearly}
        {--date= : Date for daily period (Y-m-d)}
        {--month= : Month for monthly period (1-12)}
        {--year= : Year for monthly/yearly period}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run EXPLAIN ANALYZE for core CSM overview query patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $officeId = (int) $this->option('office_id');
        if ($officeId <= 0) {
            $this->error('office_id is required and must be a positive integer.');
            return self::FAILURE;
        }

        $serviceType = strtolower((string) $this->option('service_type'));
        if (!in_array($serviceType, ['external', 'internal', 'all'], true)) {
            $this->error('service_type must be external, internal, or all.');
            return self::FAILURE;
        }

        $period = strtolower((string) $this->option('period'));
        if (!in_array($period, ['daily', 'monthly', 'yearly'], true)) {
            $this->error('period must be daily, monthly, or yearly.');
            return self::FAILURE;
        }

        [$startDate, $endDate] = $this->resolveDateRange($period);

        $this->line('CSM EXPLAIN ANALYZE');
        $this->line(sprintf('Office: %d | Service Type: %s | Period: %s | Range: %s to %s', $officeId, $serviceType, $period, $startDate, $endDate));
        $this->newLine();

        if ($serviceType !== 'internal') {
            $this->printPlan(
                title: 'External Total Transactions',
                sql: "SELECT COUNT(*)\n"
                    . "FROM queue_transactions qt\n"
                    . "WHERE qt.office_id = ?\n"
                    . "  AND qt.queue_date >= ?\n"
                    . "  AND qt.queue_date < ?\n"
                    . "  AND EXISTS (\n"
                    . "      SELECT 1\n"
                    . "      FROM queue_transaction_services qts\n"
                    . "      JOIN services s ON s.id = qts.service_id\n"
                    . "      WHERE qts.queue_transaction_id = qt.id\n"
                    . "        AND s.service_type = ?\n"
                    . "  )",
                bindings: [$officeId, $startDate, $endDate, 'External']
            );

            $this->printPlan(
                title: 'External CC1 Aggregation',
                sql: "SELECT COUNT(*) AS total,\n"
                    . "       SUM(CASE WHEN er.answer_option IN (1,2,3) THEN 1 ELSE 0 END) AS included\n"
                    . "FROM evaluation_responses er\n"
                    . "JOIN evaluation_questions eq ON eq.id = er.question_id\n"
                    . "JOIN queue_transactions qt ON qt.id = er.queue_transaction_id\n"
                    . "WHERE er.answer_option IS NOT NULL\n"
                    . "  AND eq.question_code = ?\n"
                    . "  AND qt.office_id = ?\n"
                    . "  AND qt.queue_date >= ?\n"
                    . "  AND qt.queue_date < ?\n"
                    . "  AND EXISTS (\n"
                    . "      SELECT 1\n"
                    . "      FROM queue_transaction_services qts\n"
                    . "      JOIN services s ON s.id = qts.service_id\n"
                    . "      WHERE qts.queue_transaction_id = qt.id\n"
                    . "        AND s.service_type = ?\n"
                    . "  )",
                bindings: ['CC1', $officeId, $startDate, $endDate, 'External']
            );
        }

        if ($serviceType !== 'external') {
            $this->printPlan(
                title: 'Internal Total Transactions',
                sql: "SELECT COUNT(*)\n"
                    . "FROM internal_transactions it\n"
                    . "WHERE it.office_id = ?\n"
                    . "  AND it.transaction_date >= ?\n"
                    . "  AND it.transaction_date < ?",
                bindings: [$officeId, $startDate, $endDate]
            );

            $this->printPlan(
                title: 'Internal CC1 Aggregation',
                sql: "SELECT COUNT(*) AS total,\n"
                    . "       SUM(CASE WHEN er.answer_option IN (1,2,3) THEN 1 ELSE 0 END) AS included\n"
                    . "FROM evaluation_responses er\n"
                    . "JOIN evaluation_questions eq ON eq.id = er.question_id\n"
                    . "JOIN internal_transactions it ON it.id = er.internal_transaction_id\n"
                    . "WHERE er.answer_option IS NOT NULL\n"
                    . "  AND eq.question_code = ?\n"
                    . "  AND it.office_id = ?\n"
                    . "  AND it.transaction_date >= ?\n"
                    . "  AND it.transaction_date < ?",
                bindings: ['CC1', $officeId, $startDate, $endDate]
            );
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolveDateRange(string $period): array
    {
        $today = now();

        if ($period === 'daily') {
            $dateOption = (string) ($this->option('date') ?? $today->toDateString());
            $date = Carbon::createFromFormat('Y-m-d', $dateOption)->startOfDay();
            return [$date->toDateString(), $date->copy()->addDay()->toDateString()];
        }

        if ($period === 'monthly') {
            $month = (int) ($this->option('month') ?? $today->month);
            $year = (int) ($this->option('year') ?? $today->year);

            if ($month < 1 || $month > 12) {
                throw new \InvalidArgumentException('month must be from 1 to 12 for monthly period.');
            }

            $start = Carbon::create($year, $month, 1)->startOfDay();
            return [$start->toDateString(), $start->copy()->addMonth()->toDateString()];
        }

        $year = (int) ($this->option('year') ?? $today->year);
        $start = Carbon::create($year, 1, 1)->startOfDay();

        return [$start->toDateString(), $start->copy()->addYear()->toDateString()];
    }

    /**
     * @param array<int, mixed> $bindings
     */
    private function printPlan(string $title, string $sql, array $bindings): void
    {
        $this->line("=== {$title} ===");

        $planRows = DB::select(
            'EXPLAIN (ANALYZE, BUFFERS) ' . $sql,
            $bindings
        );

        foreach ($planRows as $row) {
            $this->line((string) $row->{'QUERY PLAN'});
        }

        $this->newLine();
    }
}
