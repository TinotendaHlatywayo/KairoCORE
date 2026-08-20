<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('system_settings')
            ->where('group', 'admission')
            ->whereIn('key', ['contact_email', 'email_enabled'])
            ->get()
            ->groupBy('school_id');

        foreach ($rows as $schoolId => $settings) {
            $contactEmail = $settings->firstWhere('key', 'contact_email');
            $emailEnabled = $settings->firstWhere('key', 'email_enabled');

            $fromEmail = $contactEmail ? (string) $contactEmail->value : null;
            if (! $fromEmail) {
                continue;
            }

            DB::table('email_configurations')->updateOrInsert(
                ['school_id' => $schoolId, 'category' => 'admissions'],
                [
                    'mailer' => 'platform',
                    'from_email' => $fromEmail,
                    'is_enabled' => $emailEnabled ? filter_var($emailEnabled->value, FILTER_VALIDATE_BOOL) : false,
                    'is_verified' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        // No-op: backfill is not reversible without risking data loss.
    }
};
