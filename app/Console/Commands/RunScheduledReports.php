<?php

namespace App\Console\Commands;

use App\Jobs\ProcessScheduledReportJob;
use Illuminate\Console\Command;
use Modules\Reports\Models\ReportSchedule;

class RunScheduledReports extends Command
{
    protected $signature = 'schoolcore:run-scheduled-reports';

    protected $description = 'Scan the active report schedule ledger and dispatch due compilation runs to the queue';

    public function handle(): void
    {
        $now = now();

        // Retrieve active schedules whose next run parameter has elapsed
        $schedules = ReportSchedule::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', $now);
            })
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No report schedules are currently due for execution.');

            return;
        }

        foreach ($schedules as $schedule) {
            // Dispatch compilation process securely to background queues
            ProcessScheduledReportJob::dispatch($schedule);

            // Update execution timestamps dynamically based on set frequency
            $nextRun = match ($schedule->frequency) {
                'daily' => $now->addDay(),
                'weekly' => $now->addWeek(),
                'monthly' => $now->addMonth(),
                'termly' => $now->addMonths(4),
                'yearly' => $now->addYear(),
                default => $now->addDay(),
            };

            $schedule->update([
                'last_run_at' => $now,
                'next_run_at' => $nextRun,
            ]);

            $this->info("Dispatched scheduled run: [{$schedule->name}] to background queues.");
        }
    }
}
