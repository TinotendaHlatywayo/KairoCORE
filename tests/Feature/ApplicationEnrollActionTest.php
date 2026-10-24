<?php

namespace Tests\Feature;

use App\Filament\App\Resources\ApplicationResource\Pages\ListApplications;
use App\Jobs\SendTenantEmailJob;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\EmailConfiguration;
use Modules\Admin\Models\SystemSetting;
use Modules\Admissions\Models\Application;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase;

/**
 * Guards the "Enroll Student" table action: the auto-assign path used to
 * fatal with a missing User class import, leaving the application marked
 * enrolled with no student row and no confirmation email.
 */
class ApplicationEnrollActionTest extends TestCase
{
    private School $school;

    private ?string $admissionsModuleSaved = null;

    private ?string $admissionsPageSaved = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));

        // Keep every mailer in-memory — the activation email path sends
        // synchronously and must never hit a real SMTP server in tests.
        Config::set('mail.default', 'array');

        $this->school = School::where('subdomain', 'chiwariraprimary')->first()
            ?? School::firstOrFail();

        $this->actingAsTenant($this->school);

        $this->admissionsModuleSaved = SystemSetting::get('modules', 'admissions', '1');
        $this->admissionsPageSaved = SystemSetting::get('modules', 'admissions_applications', '1');
        SystemSetting::set('modules', 'admissions', '1');
        SystemSetting::set('modules', 'admissions_applications', '1');

        Filament::setCurrentPanel(Filament::getPanel('app'));
    }

    protected function tearDown(): void
    {
        if ($this->admissionsModuleSaved !== null) {
            SystemSetting::set('modules', 'admissions', $this->admissionsModuleSaved);
        }
        if ($this->admissionsPageSaved !== null) {
            SystemSetting::set('modules', 'admissions_applications', $this->admissionsPageSaved);
        }

        parent::tearDown();
    }

    public function test_enroll_with_auto_assigned_class_creates_student_enrollment_and_email(): void
    {
        Queue::fake();
        $notificationWatermark = (int) DB::connection('mysql')->table('notifications')->max('id');
        $fx = $this->fixtures();

        $component = Livewire::test(ListApplications::class);

        // The enroll window opens with the active academic year pre-selected.
        $component->mountTableAction('enroll', $fx['application']);
        $actionState = $component->instance()->getMountedTableActionForm()->getRawState();
        $this->assertSame($fx['activeYear']->id, $actionState['academic_year_id'] ?? null);

        // No class chosen (null) → the action auto-assigns the least populated one.
        $component->callTableAction('enroll', $fx['application'], [
            'academic_year_id' => $fx['year']->id,
            'course_id' => $fx['course']->id,
            'section_id' => null,
            'admission_number' => $fx['tag'].'/901',
        ])->assertOk();

        $application = $fx['application']->fresh();
        $this->assertSame('enrolled', $application->status);
        $this->assertSame($fx['course']->id, $application->course_id);

        $student = Student::where('application_id', $application->id)->first();
        $this->assertNotNull($student, 'Enrolling must create the student row for the directory.');
        $this->assertSame($fx['tag'].'/901', $student->admission_number);
        $this->assertNotNull($student->user_id, 'A portal user must be linked when the application has an email.');

        $enrollment = Enrollment::where('student_id', $student->id)->first();
        $this->assertNotNull($enrollment, 'Enrolling must create the enrollment row.');
        $this->assertSame($fx['year']->id, $enrollment->academic_year_id);
        // Both sections start empty; ties resolve to the first alphabetically.
        $this->assertSame($fx['sectionA']->id, $enrollment->section_id);

        // The admission confirmation email was queued for the learner/parent.
        Queue::assertPushed(SendTenantEmailJob::class, 1);

        Notification::assertNotified('Student Enrolled Successfully!');

        $this->cleanup($fx, $notificationWatermark);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixtures(): array
    {
        $this->actingAs($this->adminUser());

        $tag = 'ENRA_'.strtoupper(substr(uniqid(), -8));

        $activeYear = AcademicYear::where('is_active', true)->first();
        $createdYear = null;
        if (! $activeYear) {
            $createdYear = AcademicYear::create([
                'school_id' => $this->school->id,
                'name' => $tag,
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_active' => true,
            ]);
            $activeYear = $createdYear;
        }

        $year = AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => $tag.'_YEAR',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'is_active' => false,
        ]);

        $course = Course::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'name' => $tag.'_COURSE',
            'code' => strtoupper(substr(md5($tag), 0, 6)),
        ]);

        $sectionA = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => 'AAA_'.$tag,
            'code' => strtoupper(substr(md5($tag.'a'), 0, 6)),
        ]);
        $sectionB = Section::withoutGlobalScopes()->create([
            'school_id' => $this->school->id,
            'course_id' => $course->id,
            'name' => 'BBB_'.$tag,
            'code' => strtoupper(substr(md5($tag.'b'), 0, 6)),
        ]);

        $email = strtolower($tag).'@example.test';

        $application = Application::create([
            'school_id' => $this->school->id,
            'application_number' => $tag,
            'first_name' => 'Enrollee',
            'last_name' => $tag,
            'national_id' => null,
            'email' => $email,
            'physical_address' => '1 Test Road',
            'gender' => 'female',
            'date_of_birth' => '2015-04-01',
            'boarding_status' => 'day_scholar',
            'medical_notes' => null,
            'parent_name' => 'Pat Parent',
            'parent_email' => 'pat.'.$email,
            'parent_phone' => '+263771234567',
            'course_id' => $course->id,
            'applying_level' => $course->name,
            'applying_year' => (string) now()->year,
            'status' => 'pending',
        ]);

        // Admissions email channel so the confirmation mail can queue.
        $existingConfig = EmailConfiguration::where('school_id', $this->school->id)
            ->where('category', 'admissions')
            ->first();
        $createdConfig = null;
        $flippedConfig = null;
        $previousFromEmail = null;
        $previousEnabled = null;
        if ($existingConfig) {
            $previousFromEmail = $existingConfig->from_email;
            $previousEnabled = $existingConfig->is_enabled;
            $existingConfig->update([
                'is_enabled' => true,
                'from_email' => $existingConfig->from_email ?: 'admissions@fixture.test',
            ]);
            $flippedConfig = $existingConfig;
        } else {
            $createdConfig = EmailConfiguration::create([
                'school_id' => $this->school->id,
                'category' => 'admissions',
                'is_enabled' => true,
                'mailer' => 'platform',
                'from_name' => 'Admissions Office',
                'from_email' => 'admissions@fixture.test',
            ]);
        }

        return [
            'tag' => $tag,
            'activeYear' => $activeYear,
            'createdYear' => $createdYear,
            'year' => $year,
            'course' => $course,
            'sectionA' => $sectionA,
            'sectionB' => $sectionB,
            'application' => $application,
            'email' => $email,
            'createdConfig' => $createdConfig,
            'flippedConfig' => $flippedConfig,
            'previousFromEmail' => $previousFromEmail,
            'previousEnabled' => $previousEnabled,
        ];
    }

    /**
     * @param  array<string, mixed>  $fx
     */
    private function cleanup(array $fx, int $notificationWatermark): void
    {
        $email = $fx['email'];

        Enrollment::where('school_id', $this->school->id)->whereHas('student', function ($q) use ($email) {
            $q->where('email', $email);
        })->delete();

        Student::where('school_id', $this->school->id)->where('email', $email)->forceDelete();

        User::withTrashed()->where('school_id', $this->school->id)->where('email', $email)->forceDelete();

        $fx['application']->delete();
        $fx['sectionA']->delete();
        $fx['sectionB']->delete();
        $fx['course']->delete();
        $fx['year']->delete();

        if ($fx['createdYear']) {
            $fx['createdYear']->delete();
        }
        if ($fx['createdConfig']) {
            $fx['createdConfig']->delete();
        }
        if ($fx['flippedConfig']) {
            $fx['flippedConfig']->update([
                'is_enabled' => $fx['previousEnabled'],
                'from_email' => $fx['previousFromEmail'],
            ]);
        }

        DB::connection('mysql')->table('notifications')
            ->where('id', '>', $notificationWatermark)
            ->delete();
    }

    private function adminUser(): User
    {
        $adminRoleId = CustomRole::where('school_id', $this->school->id)
            ->where('role_key', 'administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $this->school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        return $admin ?? User::where('school_id', $this->school->id)->where('account_status', 'active')->firstOrFail();
    }
}
