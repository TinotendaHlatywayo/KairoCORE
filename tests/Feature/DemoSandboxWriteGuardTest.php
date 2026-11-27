<?php

namespace Tests\Feature;

use App\Models\School;
use App\Support\DemoSessionGuard;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Mockery;
use Modules\Students\Models\Student;
use RuntimeException;
use Tests\TestCase;

class DemoSandboxWriteGuardTest extends TestCase
{
    use DatabaseTransactions;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'mysql');
        $app['config']->set('database.connections.mysql.database', 'schoolcore_testing');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutTenant();
    }

    public function test_query_builder_writes_cannot_target_another_school(): void
    {
        $own = $this->createSchool();
        $other = $this->createSchool();

        DB::table('system_settings')->insert([
            ['school_id' => $own->id, 'group' => 'demo_guard', 'key' => 'own', 'value' => 'before'],
            ['school_id' => $other->id, 'group' => 'demo_guard', 'key' => 'other', 'value' => 'before'],
        ]);

        DemoSessionGuard::begin((int) $own->id);

        try {
            $blocked = false;

            try {
                DB::table('system_settings')
                    ->where('school_id', $other->id)
                    ->where('key', 'other')
                    ->update(['value' => 'hacked']);
            } catch (RuntimeException $exception) {
                $blocked = str_contains($exception->getMessage(), 'Demo sandbox sessions');
            }

            $this->assertTrue($blocked, 'A demo sandbox must not update another school through the query builder.');

            DB::table('system_settings')
                ->where('school_id', $own->id)
                ->where('key', 'own')
                ->update(['value' => 'updated']);
        } finally {
            DemoSessionGuard::end();
        }

        $this->assertDatabaseHas('system_settings', [
            'school_id' => $own->id,
            'key' => 'own',
            'value' => 'updated',
        ]);
        $this->assertDatabaseHas('system_settings', [
            'school_id' => $other->id,
            'key' => 'other',
            'value' => 'before',
        ]);
    }

    public function test_save_quietly_cannot_mutate_another_school_record(): void
    {
        $own = $this->createSchool();
        $other = $this->createSchool();
        $ownStudentId = $this->createStudent((int) $own->id, 'Own');
        $otherStudentId = $this->createStudent((int) $other->id, 'Other');

        DemoSessionGuard::begin((int) $own->id);

        try {
            $otherStudent = Student::withoutGlobalScopes()->findOrFail($otherStudentId);

            $blocked = false;

            try {
                $otherStudent->forceFill(['first_name' => 'Hacked'])->saveQuietly();
            } catch (RuntimeException $exception) {
                $blocked = str_contains($exception->getMessage(), 'Demo sandbox sessions');
            }

            $this->assertTrue($blocked, 'A demo sandbox must not mutate another school through saveQuietly.');

            Student::withoutGlobalScopes()
                ->findOrFail($ownStudentId)
                ->forceFill(['first_name' => 'Updated'])
                ->saveQuietly();
        } finally {
            DemoSessionGuard::end();
        }

        $this->assertDatabaseHas('students', ['id' => $ownStudentId, 'first_name' => 'Updated']);
        $this->assertDatabaseHas('students', ['id' => $otherStudentId, 'first_name' => 'Other']);
    }

    public function test_unscoped_writes_are_blocked(): void
    {
        $school = $this->createSchool();

        DB::table('system_settings')->insert([
            'school_id' => $school->id,
            'group' => 'demo_guard',
            'key' => 'unscoped',
            'value' => 'before',
        ]);

        DemoSessionGuard::begin((int) $school->id);

        $blocked = false;

        try {
            DB::table('system_settings')
                ->where('group', 'demo_guard')
                ->update(['value' => 'hacked']);
        } catch (RuntimeException $exception) {
            $blocked = str_contains($exception->getMessage(), 'Demo sandbox sessions');
        } finally {
            DemoSessionGuard::end();
        }

        $this->assertTrue($blocked, 'A demo sandbox must not run unscoped writes.');

        $this->assertDatabaseHas('system_settings', ['key' => 'unscoped', 'value' => 'before']);
    }

    public function test_stale_demo_jobs_are_deleted_before_handling(): void
    {
        $school = $this->createSchool();
        $schoolId = (int) $school->id;
        $school->forceDelete();

        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn(['demo_sandbox_school_id' => $schoolId]);
        $job->shouldReceive('delete')->once();

        Event::dispatch(new JobProcessing('sync', $job));

        $this->assertTrue(true);
    }

    public function test_active_demo_jobs_are_not_deleted(): void
    {
        $school = $this->createSchool();

        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn(['demo_sandbox_school_id' => $school->id]);
        $job->shouldReceive('delete')->never();

        Event::dispatch(new JobProcessing('sync', $job));

        $this->assertTrue(true);
    }

    private function createSchool(): School
    {
        return School::withoutGlobalScopes()->create([
            'name' => 'Write Guard '.strtoupper(Str::random(8)),
            'subdomain' => 'guard-'.strtolower(Str::random(12)),
            'login_code' => 'WRG-'.strtoupper(Str::random(12)),
            'status' => 'active',
            'institution_type' => 'secondary',
            'is_demo_sandbox' => true,
        ]);
    }

    private function createStudent(int $schoolId, string $name): int
    {
        return (int) DB::table('students')->insertGetId([
            'school_id' => $schoolId,
            'admission_number' => 'WRG-'.strtoupper(Str::random(12)),
            'first_name' => $name,
            'last_name' => 'Student',
            'gender' => 'male',
            'date_of_birth' => '2010-01-01',
            'admission_date' => now()->toDateString(),
            'status' => 'active',
            'card_status' => 'active',
            'boarding_status' => 'day',
            'credit_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
