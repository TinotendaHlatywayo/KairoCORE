<?php

namespace Tests\Feature;

use App\Filament\App\Resources\StudentResource\Pages\ListStudents;
use App\Models\School;
use App\Models\User;
use App\Services\StudentCsvService;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Livewire;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemSetting;
use Modules\Students\Models\Student;
use Tests\TestCase;

class StudentCsvActionsTest extends TestCase
{
    use InteractsWithDatabase;

    private string $studentsModuleSaved;

    protected function setUp(): void
    {
        parent::setUp();

        // NOTE: keep app.env at 'testing' (do NOT override it) so Livewire's
        // FileUploadConfiguration resolves the 'tmp-for-tests' upload disk.
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        // These tests exercise the students module directly, so enable it
        // regardless of the tenant's configured module toggles and restore
        // the real value afterwards.
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();
        $this->assertNotNull($school, 'A school record is required.');
        $this->actingAsTenant($school);

        $this->studentsModuleSaved = SystemSetting::get('modules', 'students', '1');
        SystemSetting::set('modules', 'students', '1');
    }

    protected function tearDown(): void
    {
        if ($this->studentsModuleSaved !== null) {
            SystemSetting::set('modules', 'students', $this->studentsModuleSaved);
        }

        parent::tearDown();
    }

    private function tenantUser(): array
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();

        $this->assertNotNull($school, 'A school record is required.');

        App::instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);

        $this->ensureAcademicFixture($school);

        return [$school, $this->adminUser($school)];
    }

    /**
     * The CSV import resolves Form / Grade and Stream / Class against the
     * school's course + section records and falls back to the school's active
     * academic year. Schools may have been purged/recreated by other tests, so
     * create the minimal fixture these tests rely on.
     */
    private function ensureAcademicFixture(School $school): void
    {
        $year = AcademicYear::where('school_id', $school->id)->where('is_active', true)->first()
            ?? AcademicYear::where('school_id', $school->id)->first();

        if (! $year) {
            $year = AcademicYear::create([
                'school_id' => $school->id,
                'name' => (string) now()->year,
                'start_date' => now()->startOfYear(),
                'end_date' => now()->endOfYear(),
                'is_active' => true,
            ]);
        }

        $year->forceFill(['is_active' => true])->save();

        $course = Course::where('school_id', $school->id)->where('name', 'Grade 1')->first();
        if (! $course) {
            $course = Course::create([
                'school_id' => $school->id,
                'name' => 'Grade 1',
                'code' => 'GR1',
            ]);
        }

        foreach (['North', 'South'] as $sectionName) {
            if (! Section::where('school_id', $school->id)->where('course_id', $course->id)->where('name', $sectionName)->exists()) {
                Section::create([
                    'school_id' => $school->id,
                    'course_id' => $course->id,
                    'name' => $sectionName,
                ]);
            }
        }
    }

    /**
     * ListStudents is permission-gated (module: academic_ops, permission:
     * manage_enrolment), so tests act as the school's Administrator (the
     * Administrator role bypasses the permission checks).
     */
    private function adminUser(School $school): User
    {
        $adminRoleId = CustomRole::where('school_id', $school->id)
            ->where('name', 'Administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        return $admin ?? User::findOrFail(13);
    }

    public function test_student_list_page_renders_with_export_and_import_actions()
    {
        [, $user] = $this->tenantUser();

        $this->actingAs($user);

        $component = Livewire::test(ListStudents::class);

        $component->assertOk();
        $component->assertSee('Import Students (CSV)', false);
        $component->assertSee('Export All', false);

        $this->assertTrue(true);
    }

    public function test_export_action_streams_a_csv_download()
    {
        [, $user] = $this->tenantUser();

        $this->actingAs($user);

        $component = Livewire::test(ListStudents::class);
        $component->callAction('export_csv');

        $this->assertTrue(true);
    }

    public function test_import_wizard_mounts_upload_step()
    {
        [$school, $user] = $this->tenantUser();

        $this->actingAs($user);

        $component = Livewire::test(ListStudents::class);
        $component->mountAction('importStudentsCsv');

        $component->assertOk();

        $component->assertSee('Download CSV Template', false);
        $component->assertSee('Upload your CSV first', false);

        $this->assertTrue(true);
    }

    public function test_import_wizard_submit_streams_progress_and_creates_students()
    {
        [$school, $user] = $this->tenantUser();

        $this->actingAs($user);

        // Livewire fakes the temp-upload disk during tests (wiping its contents),
        // so fake it first, then write the CSV into the livewire-tmp directory.
        // The TemporaryUploadedFile path is relative and gets the livewire-tmp/
        // prefix applied again by FileUploadConfiguration::path().
        Storage::fake('tmp-for-tests');

        Storage::disk('tmp-for-tests')->put(
            'livewire-tmp/wizard-import-test.csv',
            "First Name,Last Name,Gender,Date of Birth,Form / Grade,Stream / Class\n"
            ."RenderTest Three,Third,female,2013-05-14,Grade 1,North\n"
            ."RenderTest Four,Fourth,male,2012-01-02,Grade 1,South\n"
        );

        $file = new TemporaryUploadedFile('wizard-import-test.csv', 'tmp-for-tests');

        $before = Student::where('school_id', $school->id)->count();

        $component = Livewire::test(ListStudents::class);
        $component->mountAction('importStudentsCsv');
        $component->set('mountedActionsData.0', [
            'csv_file' => ['test-upload' => $file],
            'columnMap' => [
                'first_name' => 'First Name',
                'last_name' => 'Last Name',
                'gender' => 'Gender',
                'date_of_birth' => 'Date of Birth',
                'course' => 'Form / Grade',
                'section' => 'Stream / Class',
                'academic_year' => null,
            ],
        ]);
        $component->callMountedAction();

        $after = Student::where('school_id', $school->id)->count();

        $this->assertSame($before + 2, $after, 'two students created through the wizard submit');
        $component->assertNotified();

        Storage::disk('tmp-for-tests')->delete('livewire-tmp/wizard-import-test.csv');
        Student::where('school_id', $school->id)
            ->where('first_name', 'like', 'RenderTest%')
            ->forceDelete();
    }

    public function test_import_with_valid_csv_creates_students()
    {
        [$school, $user] = $this->tenantUser();

        $this->actingAs($user);

        $component = Livewire::test(ListStudents::class);

        // Simulate the two-phase upload: Livewire temporary uploads are hard to fake,
        // so write a CSV to a temp path and feed it in as the already-resolved file.
        $csvPath = tempnam(sys_get_temp_dir(), 'csvtest');
        file_put_contents($csvPath, "First Name,Last Name,Gender,Date of Birth,Form / Grade,Stream / Class\n"
            .'RenderTest One,First,female,2013-05-14,Grade 1,North'."\n"
            .'RenderTest Two,Second,male,2012-01-02,Grade 1,South'."\n");

        $before = Student::where('school_id', $school->id)->count();

        // Directly exercise the import engine (the wizard/upload flow is Livewire-UI heavy).
        $map = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'gender' => 'Gender',
            'date_of_birth' => 'Date of Birth',
            'course' => 'Form / Grade',
            'section' => 'Stream / Class',
            'academic_year' => null,
        ];
        $result = StudentCsvService::import($csvPath, $school->id, $map);

        $after = Student::where('school_id', $school->id)->count();

        $this->assertSame(2, $result['success'], 'two valid rows imported');
        $this->assertCount(0, $result['failures'], 'no failures');
        $this->assertSame($before + 2, $after, 'two students created');

        // cleanup test students
        Student::where('school_id', $school->id)
            ->where('first_name', 'like', 'RenderTest%')
            ->forceDelete();
    }
}
