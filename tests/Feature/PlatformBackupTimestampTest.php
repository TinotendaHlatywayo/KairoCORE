<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Modules\Recovery\Models\PlatformBackup;
use Tests\TestCase;

class PlatformBackupTimestampTest extends TestCase
{
    /**
     * The backup manager serialises rows with toArray(), which turns created_at
     * into a UTC ISO-8601 string. The view helper must convert it back to the
     * app timezone, otherwise Africa/Harare users see the time 2 hours behind.
     */
    public function test_backup_timestamp_is_converted_back_to_app_timezone(): void
    {
        Config::set('app.timezone', 'Africa/Harare');

        // 22:22 local serialised by toArray() to UTC.
        $utcIso = Carbon::parse('2026-10-07 22:22:04', 'Africa/Harare')
            ->utc()
            ->toIso8601String();

        $this->assertSame('2026-10-07T20:22:04+00:00', $utcIso);
        $this->assertSame(
            '07 Oct 2026, 22:22',
            PlatformBackup::formatTimestamp($utcIso),
        );
        $this->assertSame(
            '2026-10-07 22:22',
            PlatformBackup::formatTimestamp($utcIso, 'Y-m-d H:i'),
        );
    }

    public function test_missing_timestamp_returns_empty_string(): void
    {
        $this->assertSame('', PlatformBackup::formatTimestamp(null));
        $this->assertSame('', PlatformBackup::formatTimestamp(''));
    }
}
