<?php

namespace App\Jobs;

use App\Models\School;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Reports\Models\ReportSchedule;
use Modules\Reports\Services\ReportExecutionService;

/**
 * Queue worker for scheduled report runs. Executes the associated template
 * through the Enterprise Reporting Engine and distributes the artifact
 * according to the schedule's output format + distribution method.
 */
class ProcessScheduledReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ReportSchedule $schedule;

    public function __construct(ReportSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function handle(ReportExecutionService $executor): void
    {
        $school = School::find($this->schedule->school_id);
        if (! $school) {
            return;
        }

        // Bind the tenant context so global scopes + {school_id} resolution work during queue runs.
        session(['current_tenant' => $school]);
        app()->instance('current_tenant', $school);

        // Apply the school's locale so scheduled report output is generated in
        // the correct language instead of the queue worker's default 'en'.
        $locale = $school->locale;
        app()->setLocale(in_array($locale, ['en', 'sn', 'sw', 'fr', 'pt', 'es'], true) ? $locale : 'en');

        $template = $this->schedule->template;
        if (! $template) {
            return;
        }

        $report = $executor->execute(
            $template,
            $this->schedule->output_format ?? 'pdf',
            $this->schedule->filter_overrides ?? []
        );

        if ($report->status !== 'completed' || empty($report->file_path)) {
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

        $filePath = Storage::disk('public')->path($report->file_path);

        Mail::raw(
            "Greetings,\n\nPlease find attached the scheduled enterprise report [{$template->name}] generated on ".now()->format('Y-m-d H:i').".\n\nBest Regards,\nKairo CORE Automated Distribution Service",
            function ($message) use ($recipients, $template, $filePath) {
                $message->to($recipients)
                    ->subject("Scheduled Enterprise Report: [{$template->name}]")
                    ->attach($filePath, [
                        'as' => "{$template->name}_".date('Ymd_His').'.'.($this->schedule->output_format ?? 'pdf'),
                    ]);
            }
        );
    }
}
