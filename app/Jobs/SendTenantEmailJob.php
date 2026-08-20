<?php

namespace App\Jobs;

use App\Models\School;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Services\TenantEmailConfigurationService;

/**
 * Sends a tenant-scoped email in the background so registration, admission
 * and CMS contact-form flows never block the request on an SMTP round-trip.
 *
 * The tenant's SMTP credentials are re-resolved inside the worker, so the job
 * payload only needs the mailable, the category and the school id.
 */
class SendTenantEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $backoff = 10;

    public function __construct(
        public Mailable $mailable,
        public EmailCategory $category,
        public int $schoolId,
    ) {}

    public function handle(TenantEmailConfigurationService $emailConfig): void
    {
        $school = School::query()->find($this->schoolId);
        if (! $school) {
            return;
        }

        try {
            $emailConfig->send($this->mailable, $this->category, $school);
        } catch (\Throwable $e) {
            Log::error('Queued tenant email failed.', [
                'school_id' => $this->schoolId,
                'category' => $this->category->value,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
