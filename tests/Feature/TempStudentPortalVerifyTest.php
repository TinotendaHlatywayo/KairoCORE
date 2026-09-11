<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Modules\Academics\Models\AcademicReport;
use Modules\Academics\Models\AssessmentMark;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Tests\TestCase as BaseTestCase;

class TempStudentPortalVerifyTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);
    }

    protected function school(): School
    {
        return School::findOrFail((int) config('tenancy.single_tenant_id'));
    }

    protected function demoStudent(): Student
    {
        $school = $this->school();
        $user = User::where('school_id', $school->id)
            ->where('username', 'LIKE', 'TEST-STU-%')
            ->where('account_status', 'active')
            ->firstOrFail();

        return Student::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('school_id', $school->id)
            ->where('user_id', $user->id)
            ->firstOrFail();
    }

    public function test_bare_domain_student_portal_is_blocked(): void
    {
        $baseHost = parse_url(config('app.url'), PHP_URL_HOST);
        $response = $this->get('/student/my-results', ['HTTP_HOST' => $baseHost]);

        $response->assertStatus(404);
    }

    public function test_student_portal_shows_published_data_under_subdomain(): void
    {
        $school = $this->school();
        $student = $this->demoStudent();
        $user = User::findOrFail($student->user_id);
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $reports = AcademicReport::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('student_id', $student->id)
            ->where('status', 'published')
            ->count();
        $this->assertGreaterThan(0, $reports, 'demo student should have published reports');

        $enrollmentIds = Student::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('id', $student->id)
            ->firstOrFail()
            ->enrollments()
            ->pluck('enrollments.id');
        $marks = AssessmentMark::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->whereIn('enrollment_id', $enrollmentIds)
            ->whereHas('assessmentType', fn ($q) => $q->where('status', 'published'))
            ->count();
        $this->assertGreaterThan(0, $marks, 'demo student should have published continuous assessment marks');

        $page = $this->get($tenantUrl.'/student/my-results');
        $page->assertOk();
        $html = $page->getContent();
        $this->assertStringContainsString('Report Cards', $html);
        $this->assertStringContainsString('View Report Card PDF', $html);
        $this->assertStringContainsString('Continuous Assessment Marks', $html);
    }

    public function test_student_report_card_pdf_streams_under_subdomain(): void
    {
        $school = $this->school();
        $student = $this->demoStudent();
        $user = User::findOrFail($student->user_id);
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $report = AcademicReport::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('student_id', $student->id)
            ->where('status', 'published')
            ->firstOrFail();

        $response = $this->get($tenantUrl.'/student/reports/'.$report->id.'/pdf');
        $response->assertOk();
        $this->assertStringContainsString('%PDF', substr($response->getContent(), 0, 200));
    }

    public function test_digital_assessments_list_in_student_portal(): void
    {
        $school = $this->school();
        $student = $this->demoStudent();
        $user = User::findOrFail($student->user_id);
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $page = $this->get($tenantUrl.'/student/digital-assessments');
        $page->assertOk();
        $this->assertStringContainsString('Digital Assessments', $page->getContent());
    }

    public function test_student_take_assessment_page_loads(): void
    {
        $school = $this->school();
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $assessment = DigitalAssessment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('school_id', $school->id)
            ->active()
            ->whereNotNull('section_id')
            ->firstOrFail();

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Take Assessment Tester',
            'username' => 'TAKE-ASSESSMENT-'.uniqid(),
            'email' => 'take-assessment-'.uniqid().'@example.test',
            'password' => 'Password123!',
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Take',
            'last_name' => 'Assessment',
            'gender' => 'male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $academicYearId = \Modules\Academics\Models\AcademicYear::where('school_id', $school->id)->orderByDesc('id')->value('id');
        $courseId = \Modules\Academics\Models\Section::where('id', $assessment->section_id)->value('course_id');

        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'section_id' => $assessment->section_id,
            'academic_year_id' => $academicYearId,
            'course_id' => $courseId,
        ]);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $attempt = null;

        try {
            $page = $this->get($tenantUrl.'/student/take-assessment/'.$assessment->id);
            $page->assertOk();
            $this->assertStringContainsString($assessment->title, $page->getContent());
        } finally {
            $attempt = \Modules\DigitalAssessment\Models\DigitalAssessmentAttempt::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('student_id', $student->id)
                ->where('digital_assessment_id', $assessment->id)
                ->get(['id']);

            \Modules\DigitalAssessment\Models\DigitalAssessmentResponse::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->whereIn('digital_assessment_attempt_id', $attempt->pluck('id'))
                ->forceDelete();

            \Modules\DigitalAssessment\Models\DigitalAssessmentAutoSave::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->whereIn('digital_assessment_attempt_id', $attempt->pluck('id'))
                ->forceDelete();

            \Modules\DigitalAssessment\Models\DigitalAssessmentAttempt::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->whereIn('id', $attempt->pluck('id'))
                ->forceDelete();

            $enrollment->forceDelete();
            $student->unsetEventDispatcher();
            $student->forceDelete();
            $user->unsetEventDispatcher();
            $user->forceDelete();
        }
    }

    public function test_student_take_assessment_gate_shows_access_code_screen(): void
    {
        $school = $this->school();
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $assessment = DigitalAssessment::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('school_id', $school->id)
            ->active()
            ->whereNotNull('section_id')
            ->firstOrFail();

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Access Code Gate Tester',
            'username' => 'ACCESS-GATE-'.uniqid(),
            'email' => 'access-gate-'.uniqid().'@example.test',
            'password' => 'Password123!',
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Access',
            'last_name' => 'Gate',
            'gender' => 'male',
            'date_of_birth' => '2015-01-01',
            'admission_date' => now()->toDateString(),
            'status' => 'enrolled',
        ]);

        $academicYearId = \Modules\Academics\Models\AcademicYear::where('school_id', $school->id)->orderByDesc('id')->value('id');
        $courseId = \Modules\Academics\Models\Section::where('id', $assessment->section_id)->value('course_id');

        $enrollment = Enrollment::create([
            'school_id' => $school->id,
            'student_id' => $student->id,
            'section_id' => $assessment->section_id,
            'academic_year_id' => $academicYearId,
            'course_id' => $courseId,
        ]);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $original = $assessment->only(['password_protection', 'settings']);

        try {
            $assessment->update([
                'password_protection' => true,
                'settings' => array_merge((array) ($original['settings'] ?? []), ['access_code' => 'SPRING-24']),
            ]);

            $page = $this->get($tenantUrl.'/student/take-assessment/'.$assessment->id);
            $page->assertOk();
            $html = $page->getContent();
            $this->assertStringContainsString('Assessment Access Code', $html);
            $this->assertStringContainsString('Enter the access code provided by your teacher', $html);

            $attemptCount = \Modules\DigitalAssessment\Models\DigitalAssessmentAttempt::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
                ->where('student_id', $student->id)
                ->where('digital_assessment_id', $assessment->id)
                ->count();
            $this->assertSame(0, $attemptCount, 'no attempt should be created while the access-code gate is shown');
        } finally {
            $assessment->update($original);

            $enrollment->forceDelete();
            $student->unsetEventDispatcher();
            $student->forceDelete();
            $user->unsetEventDispatcher();
            $user->forceDelete();
        }
    }

    public function test_student_profile_photo_lock_state(): void
    {
        $school = $this->school();
        $student = Student::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->where('school_id', $school->id)
            ->where('user_id', 17)
            ->firstOrFail();
        $user = User::findOrFail($student->user_id);
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        $original = $student->only([
            'photo_path', 'photo_approved_at', 'photo_approved_by',
            'photo_rejected_at', 'photo_rejected_reason', 'photo_rejected_by',
        ]);

        try {
            $student->update([
                'photo_path' => 'student-photos/locked.jpg',
                'photo_approved_at' => now(),
                'photo_approved_by' => null,
            ]);

            $page = $this->get($tenantUrl.'/student/my-profile');
            $page->assertOk();
            $html = $page->getContent();
            $this->assertStringContainsString('Photo Locked', $html);
            $this->assertStringContainsString('Only an administrator can remove it', $html);
        } finally {
            $student->update($original);
        }
    }

    public function test_student_profile_photo_admin_removal_state(): void
    {
        $school = $this->school();
        $tenantUrl = 'http://'.$school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST);

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Photo Removal Tester',
            'username' => 'PHOTO-REMOVED-'.uniqid(),
            'email' => 'photo-removed-'.uniqid().'@example.test',
            'password' => 'Password123!',
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $student = Student::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'first_name' => 'Photo',
            'last_name' => 'Removal',
            'gender' => 'female',
            'date_of_birth' => '2015-01-01',
            'admission_date' => now()->toDateString(),
            'status' => 'enrolled',
            'photo_path' => null,
            'photo_approved_at' => null,
            'photo_rejected_at' => now(),
            'photo_rejected_reason' => 'Photo was blurry / not a clear single face',
            'photo_rejected_by' => $user->id,
        ]);

        $this->actingAs($user, Filament::getPanel('student')->getAuthGuard());

        try {
            $page = $this->get($tenantUrl.'/student/my-profile');
            $page->assertOk();
            $html = $page->getContent();
            $this->assertStringContainsString('Your photo was removed', $html);
            $this->assertStringContainsString('Photo was blurry / not a clear single face', $html);
        } finally {
            $student->unsetEventDispatcher();
            $student->forceDelete();
            $user->unsetEventDispatcher();
            $user->forceDelete();
        }
    }
}