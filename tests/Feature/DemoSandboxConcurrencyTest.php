<?php

namespace Tests\Feature;

use App\Models\School;
use App\Services\DemoSandboxService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Admin\Models\SystemSetting;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class DemoSandboxConcurrencyTest extends TestCase
{
    /** @var array<int, int> */
    private array $createdSandboxIds = [];

    /** @var array<int, int> */
    private array $createdRealSchoolIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore_testing');
        config()->set('cache.default', 'array');
        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->withoutTenant();
    }

    protected function tearDown(): void
    {
        $service = app(DemoSandboxService::class);

        foreach ($this->createdSandboxIds as $schoolId) {
            try {
                $service->delete($schoolId);
            } catch (\Throwable) {
                School::withoutGlobalScopes()->withTrashed()->whereKey($schoolId)->forceDelete();
            }
        }

        foreach ($this->createdRealSchoolIds as $schoolId) {
            School::withoutGlobalScopes()->withTrashed()->whereKey($schoolId)->forceDelete();
        }

        parent::tearDown();
    }

    public function test_four_simultaneous_sessions_keep_data_isolated(): void
    {
        $realSchoolId = $this->createRealSchool();
        SystemSetting::set('finance', 'demo_sandbox_probe', 'real-before', $realSchoolId);

        $claims = $this->runClaims(4);
        $schoolIds = array_column($claims, 'school_id');

        $this->assertCount(4, $schoolIds);
        $this->assertCount(4, array_unique($schoolIds));
        $this->assertCount(4, array_unique(array_column($claims, 'demo_session_id')));
        $this->assertCount(4, array_unique(array_column($claims, 'subdomain')));

        $operations = ['wipe', 'upload', 'fees', 'read'];
        $processes = [];

        foreach ($schoolIds as $index => $schoolId) {
            $processes[] = $this->makeProcess([
                'demo:sandbox:exercise',
                (string) $schoolId,
                $operations[$index],
                '--json',
            ]);
        }

        $results = $this->waitForProcesses($processes);

        foreach ($results as $result) {
            $this->assertTrue(
                $result['successful'],
                (string) $result['error']."\n".(string) $result['output']
            );
        }

        $markers = DB::table('system_settings')
            ->whereIn('school_id', $schoolIds)
            ->where('group', 'finance')
            ->where('key', 'demo_sandbox_probe')
            ->pluck('value', 'school_id')
            ->all();

        $this->assertArrayNotHasKey($schoolIds[0], $markers);
        $this->assertArrayNotHasKey($schoolIds[1], $markers);
        $this->assertSame('fees-'.$schoolIds[2], $markers[$schoolIds[2]] ?? null);
        $this->assertArrayNotHasKey($schoolIds[3], $markers);
        $this->assertSame(
            'real-before',
            DB::table('system_settings')->where('school_id', $realSchoolId)->where('group', 'finance')->where('key', 'demo_sandbox_probe')->value('value')
        );

        $this->assertSame('upload-'.$schoolIds[1], Storage::disk('public')->get('demo-sandboxes/'.$schoolIds[1].'/probe.txt'));
        $this->assertFalse(Storage::disk('public')->exists('demo-sandboxes/'.$schoolIds[0].'/probe.txt'));
        $this->assertFalse(Storage::disk('public')->exists('demo-sandboxes/'.$schoolIds[2].'/probe.txt'));
    }

    public function test_ten_parallel_demo_starts_complete_and_report_timing(): void
    {
        $startedAt = microtime(true);
        $claims = $this->runClaims(10);
        $elapsed = microtime(true) - $startedAt;

        $schoolIds = array_column($claims, 'school_id');
        $seedTimes = array_values(array_filter(array_column($claims, 'seed_duration_ms'), fn ($value) => $value !== null));

        $this->assertCount(10, $schoolIds);
        $this->assertCount(10, array_unique($schoolIds));
        $this->assertCount(10, array_unique(array_column($claims, 'demo_session_id')));

        fwrite(STDOUT, sprintf(
            "\n10 parallel demo starts: %.2fs wall time; average seed %.2fs; database bytes %.1f KB\n",
            $elapsed,
            $seedTimes === [] ? 0 : (array_sum($seedTimes) / count($seedTimes) / 1000),
            app(DemoSandboxService::class)->estimateDatabaseBytes((int) $schoolIds[0]) / 1024
        ));
    }

    /** @return array<int, array<string, mixed>> */
    private function runClaims(int $count): array
    {
        $processes = [];

        for ($i = 0; $i < $count; $i++) {
            $processes[] = $this->makeProcess(['demo:sandbox:claim', '--json']);
        }

        $results = $this->waitForProcesses($processes);
        $claims = [];

        foreach ($results as $result) {
            $this->assertTrue(
                $result['successful'],
                (string) $result['error']."\n".(string) $result['output']
            );

            $payload = json_decode((string) $result['output'], true, 512, JSON_THROW_ON_ERROR);
            $this->assertIsArray($payload);
            $this->assertArrayHasKey('school_id', $payload);

            $this->createdSandboxIds[] = (int) $payload['school_id'];
            $claims[] = $payload;
        }

        return $claims;
    }

    private function makeProcess(array $arguments): Process
    {
        return new Process(
            array_merge([PHP_BINARY, base_path('artisan')], $arguments),
            base_path(),
            [
                'APP_CONFIG_CACHE' => '/tmp/config-testing.php',
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => (string) config('database.connections.mysql.driver', 'mysql'),
                'DB_HOST' => (string) config('database.connections.mysql.host', 'db'),
                'DB_PORT' => (string) config('database.connections.mysql.port', '3306'),
                'DB_DATABASE' => (string) config('database.connections.mysql.database', 'schoolcore_testing'),
                'DB_USERNAME' => (string) config('database.connections.mysql.username', 'root'),
                'DB_PASSWORD' => (string) config('database.connections.mysql.password', ''),
            ],
            null,
            300
        );
    }

    /** @param array<int, Process> $processes @return array<int, array<string, mixed>> */
    private function waitForProcesses(array $processes): array
    {
        foreach ($processes as $process) {
            $process->start();
        }

        $results = [];

        foreach ($processes as $process) {
            $process->wait();
            $results[] = [
                'successful' => $process->isSuccessful(),
                'output' => trim($process->getOutput()),
                'error' => trim($process->getErrorOutput()),
            ];
        }

        return $results;
    }

    private function createRealSchool(): int
    {
        $suffix = strtolower(Str::random(8));

        $school = School::withoutGlobalScopes()->create([
            'name' => 'Concurrency Real School',
            'subdomain' => 'real-'.$suffix,
            'login_code' => 'CON-'.strtoupper($suffix),
            'status' => 'active',
            'institution_type' => 'secondary',
        ]);

        $this->createdRealSchoolIds[] = (int) $school->id;

        return (int) $school->id;
    }
}
