<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\DummyDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\Admin\Models\SystemSetting;

class ExerciseDemoSandbox extends Command
{
    protected $signature = 'demo:sandbox:exercise {schoolId} {operation} {--json}';

    protected $description = 'Run one isolated demo-sandbox operation for concurrency tests';

    public function handle(): int
    {
        $schoolId = (int) $this->argument('schoolId');
        $operation = (string) $this->argument('operation');

        if (! in_array($operation, ['wipe', 'upload', 'fees', 'read'], true)) {
            $this->error('Unknown operation: '.$operation);

            return self::FAILURE;
        }

        $school = School::withoutGlobalScopes()->findOrFail($schoolId);
        app()->instance('current_tenant', $school);

        if ($operation === 'wipe') {
            app(DummyDataSeeder::class)->wipe($schoolId);
        }

        if ($operation === 'upload') {
            Storage::disk('public')->put('demo-sandboxes/'.$schoolId.'/probe.txt', 'upload-'.$schoolId);
        }

        if ($operation === 'fees') {
            SystemSetting::set('finance', 'demo_sandbox_probe', 'fees-'.$schoolId, $schoolId);
        }

        $payload = [
            'school_id' => $schoolId,
            'operation' => $operation,
            'marker' => SystemSetting::get('finance', 'demo_sandbox_probe'),
            'probe_file' => Storage::disk('public')->exists('demo-sandboxes/'.$schoolId.'/probe.txt'),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        }

        foreach ($payload as $key => $value) {
            $this->line($key.': '.(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
        }

        return self::SUCCESS;
    }
}
