<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase as BaseTestCase;

class TempTeacherAssignmentVerifyTest extends BaseTestCase
{
    protected int $schoolId;

    private ?\Modules\DigitalAssessment\Models\DigitalAssessment $createdAssessment = null;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $this->schoolId = (int) config('tenancy.single_tenant_id');
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    protected function tearDown(): void
    {
        if ($this->createdAssessment !== null) {
            $this->createdAssessment->forceDelete();
        }

        parent::tearDown();
    }

    protected function adminUser(): User
    {
        return User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    protected function demoAssessment(): \Modules\DigitalAssessment\Models\DigitalAssessment
    {
        $assessment = \Modules\DigitalAssessment\Models\DigitalAssessment::withoutGlobalScopes()
            ->where('school_id', $this->schoolId)
            ->active()
            ->whereNotNull('section_id')
            ->where('total_marks', '>', 0)
            ->first();

        if ($assessment !== null) {
            return $assessment;
        }

        $section = \Modules\Academics\Models\Section::withoutGlobalScopes()
            ->where('school_id', $this->schoolId)
            ->whereNotNull('course_id')
            ->firstOrFail();
        $subject = \Modules\Academics\Models\Subject::withoutGlobalScopes()
            ->where('school_id', $this->schoolId)
            ->firstOrFail();

        $assessment = \Modules\DigitalAssessment\Models\DigitalAssessment::withoutGlobalScopes()->create([
            'school_id' => $this->schoolId,
            'subject_id' => $subject->id,
            'section_id' => $section->id,
            'created_by_id' => $this->adminUser()->id,
            'title' => 'Verify DA '.uniqid(),
            'status' => \Modules\DigitalAssessment\Enums\AssessmentStatus::Published,
            'total_marks' => 100,
        ]);
        $assessment->refresh();
        $this->createdAssessment = $assessment;

        return $assessment;
    }

    public function test_teacher_assignments_index_renders(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/teacher-assignments');
        $r->assertOk();
    }

    public function test_lms_homework_is_accessible_and_not_empty(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/homework');
        $r->assertOk();
        $this->assertStringContainsString('Homework', $r->getContent());

        $hub = $this->get('/workspace/lms-lms');
        $hub->assertRedirect('/workspace/homework');
    }

    public function test_sidebar_search_index_includes_module_sub_pages(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/teacher-assignments');
        $r->assertOk();

        $html = $r->getContent();
        $this->assertStringContainsString('__workspaceSearchIndex = [', $html, 'server page registry should render into the sidebar search');

        $items = \App\Support\WorkspaceSearchIndex::items();
        $this->assertGreaterThan(0, count($items), 'search index should be populated for the workspace panel');

        $paths = collect($items)->pluck('url');
        $this->assertTrue($paths->contains(fn ($u) => str_ends_with($u, '/workspace/time-slots')), 'academics more-tab sub-page should be searchable');
        $this->assertTrue($paths->contains(fn ($u) => str_ends_with($u, '/workspace/homework')), 'LMS homework sub-page should be searchable');
        $this->assertTrue($paths->contains(fn ($u) => str_ends_with($u, '/workspace/journal-entries')), 'finance sub-page should be searchable');
    }

    public function test_digital_assessment_admin_pages_render(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $list = $this->get('/workspace/digital-assessments');
        $list->assertOk();

        $assessment = $this->demoAssessment();

        $edit = $this->get('/workspace/digital-assessments/'.$assessment->id.'/edit');
        $edit->assertOk();
        $this->assertStringContainsString($assessment->title, $edit->getContent());
        $this->assertStringContainsString('data.section_id', $edit->getContent());
        $this->assertStringContainsString($assessment->section->name, $edit->getContent());
    }
}