<?php

namespace App\Jobs;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Services\ReportExecutionService;

/**
 * Executes an enterprise report template through the new engine, optionally
 * distributing the artifact to scheduled recipients.
 */
class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected EnterpriseReportTemplate $template,
        protected ?ReportSchedule $schedule = null,
        protected array $runtimeFilters = [],
        protected ?int $userId = null,
    ) {}

    public function handle(ReportExecutionService $executor): void
    {
        $school = School::find($this->template->school_id);

        if (! $school) {
            return;
        }

        // Bind tenant context so global scopes + {school_id} resolution work in queue.
        session(['current_tenant' => $school]);
        app()->instance('current_tenant', $school);

        $format = $this->schedule?->output_format ?? 'pdf';
        $runtimeFilters = array_merge($this->schedule?->filter_overrides ?? [], $this->runtimeFilters);

        $report = $executor->execute(
            $this->template,
            $format,
            $runtimeFilters,
            $this->userId,
        );

        if ($this->schedule) {
            $this->schedule->update([
                'last_run_at' => now(),
                'next_run_at' => $this->scheduleNextRun($this->schedule),
            ]);
        }

        if ($report->status !== 'completed' || empty($report->file_path)) {
            return;
        }

        $this->distribute($report->file_path);
    }

    protected function distribute(string $filePath): void
    {
        if (! $this->schedule) {
            return;
        }

        $recipients = array_values(array_filter($this->schedule->recipients ?? []));
        $method = $this->schedule->distribution_method ?? 'email';

        if ($method !== 'email' && $method !== 'both') {
            return;
        }

        if (empty($recipients)) {
            return;
        }

        $attach = Storage::disk('public')->path($filePath);
        $extension = $this->schedule->output_format ?? 'pdf';

        Mail::raw(
            "Greetings,\n\nPlease find attached the scheduled enterprise report [{$this->schedule->template?->name}] generated on ".now()->format('Y-m-d H:i').".\n\nBest Regards,\nSchoolCore Automated Distribution Service",
            function ($message) use ($recipients, $attach, $extension) {
                $message->to($recipients)
                    ->subject('Scheduled Enterprise Report: ['.($this->schedule->template?->name ?? 'Report').']')
                    ->attach($attach, [
                        'as' => ($this->schedule->template?->name ?? 'report').'_'.date('Ymd_His').".{$extension}",
                    ]);
            }
        );
    }

    protected function scheduleNextRun(ReportSchedule $schedule): Carbon
    {
        $from = now();

        return match ($schedule->frequency) {
            'daily' => $from->addDay(),
            'weekly' => $from->addWeek(),
            'monthly' => $from->addMonth(),
            'quarterly' => $from->addMonths(3),
            'yearly' => $from->addYear(),
            'hourly' => $from->addHour(),
            default => $from->addDay(),
        };
    }
}
