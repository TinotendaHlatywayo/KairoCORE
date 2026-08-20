<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Services\ReportAuditService;

class AuditReports extends Command
{
    protected $signature = 'schoolcore:audit-reports {school? : Optional school ID. Defaults to auditing every school.} {--days=30 : Only re-verify reports generated within this many days.}';

    protected $description = 'Re-verify every compiled report against the live database and flag those whose source data changed since compilation.';

    public function handle(): int
    {
        $schoolId = $this->argument('school');
        $days = (int) $this->option('days');

        $query = GeneratedReport::query()
            ->where('status', 'completed')
            ->whereNotNull('data_checksum')
            ->where('created_at', '>=', now()->subDays($days));

        if ($schoolId) {
            $query->where('school_id', (int) $schoolId);
        }

        $reports = $query->with('template')->orderBy('id')->get();

        if ($reports->isEmpty()) {
            $this->info('No compiled reports to audit.');

            return self::SUCCESS;
        }

        $auditor = app(ReportAuditService::class);
        $valid = 0;
        $stale = 0;
        $errors = 0;

        foreach ($reports as $report) {
            try {
                if ($auditor->verify($report)) {
                    $valid++;
                    $this->line("  [OK]      #{$report->id} {$report->name}");
                } else {
                    $stale++;
                    $this->warn("  [STALE]   #{$report->id} {$report->name} — source data changed since compilation");
                }
            } catch (\Throwable $e) {
                $errors++;
                $this->error("  [ERROR]   #{$report->id} {$report->name} — {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info(sprintf('Audit complete: %d verified, %d stale, %d errored.', $valid, $stale, $errors));

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
