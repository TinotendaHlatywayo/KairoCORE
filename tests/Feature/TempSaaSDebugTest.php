<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Support\TeacherOptions;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase as BaseTestCase;

class TempSaaSDebugTest extends BaseTestCase
{
    protected int $schoolId;

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

    protected function adminUser(): User
    {
        return User::where('school_id', $this->schoolId)->where('custom_role_id', 2)->firstOrFail();
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    public function test_saas_billing_overview_renders(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/saas-billing-overview');
        $r->assertOk();
    }

    public function test_saas_hub_renders(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/saas-hub');
        $this->assertTrue(in_array($r->getStatusCode(), [200, 302], true), 'saas-hub should render or redirect into the billing module');
    }

    public function test_timetable_lessons_page_renders_drag_drop_grid(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/timetable-lessons');
        $r->assertOk();
        $this->assertStringContainsString('viewScope', $r->getContent());
        $this->assertStringContainsString('swapLesson', $r->getContent());
    }

    public function test_manual_marking_and_gamification_removed_from_nav(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/dashboard');
        $html = $r->getContent();
        $this->assertStringNotContainsString('manual-marking', $html, 'Manual Marking should not appear in navigation');
        $this->assertStringNotContainsString('gamification', $html, 'Gamification should not appear in navigation');
    }

    public function test_saas_my_subscriptions_renders(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/saa-s-my-subscriptions');
        $r->assertOk();
    }

    public function test_time_slots_index_renders_after_fix(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/time-slots');
        $r->assertOk();
        $this->assertStringNotContainsString('hasConflicts', $r->getContent());
    }

    public function test_teacher_assignment_create_renders(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/teacher-assignments/create');
        $r->assertOk();
        $this->assertStringContainsString('Teacher', $r->getContent());
    }

    public function test_teacher_fuzzy_search_matches_typos(): void
    {
        $school = School::findOrFail($this->schoolId);
        app()->instance('current_tenant', $school);

        $this->assertTrue(TeacherOptions::fuzzyMatches('john mutasa', 'jhn'));
        $this->assertTrue(TeacherOptions::fuzzyMatches('grace mhaka', 'grka'));
        $this->assertTrue(TeacherOptions::fuzzyMatches('munashe chayambuka', 'mun chy'));
        $this->assertFalse(TeacherOptions::fuzzyMatches('james zvobgo', 'zxqw'));
        $this->assertTrue(TeacherOptions::fuzzyMatches('agness taruvinga', 'agness tar'));

        $options = TeacherOptions::options();
        $this->assertGreaterThanOrEqual(7, count($options), 'every teaching-staff employee account should be listed');
        $this->assertArrayHasKey('Chipo Mandizvidza', array_flip($options), 'fuzzy dropdown must surface the linked teaching-staff accounts');

        $results = TeacherOptions::search('muta');
        $this->assertArrayHasKey('Tariro Mutasa', array_flip($results), 'substring search should find the teacher by surname fragment');
        $this->assertArrayNotHasKey('Charles Mungoshi', array_flip($results), 'administrators must never appear as teachers');
    }

    public function test_time_slot_model_syncs_type_duration_and_break(): void
    {
        $slot = \Modules\Timetables\Models\TimeSlot::create([
            'school_id' => $this->schoolId,
            'name' => 'Temp Fuzzy Period',
            'type' => 'break',
            'start_time' => '10:30:00',
            'end_time' => '10:45:00',
        ]);

        $this->assertTrue((bool) $slot->is_break, 'break type should mark is_break');
        $this->assertSame(15, (int) $slot->duration_minutes, 'duration should be auto-computed');
        $this->assertTrue($slot->hasConflicts() === false || is_bool($slot->hasConflicts()), 'hasConflicts should return a boolean');

        $slot->delete();
    }

    public function test_course_form_contains_fuzzy_form_teacher_and_classroom_picker(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $course = \Modules\Academics\Models\Course::where('school_id', $this->schoolId)->firstOrFail();
        $r = $this->get('/workspace/courses/'.$course->id.'/edit');
        $r->assertOk();

        $content = $r->getContent();
        $this->assertStringContainsString('Form Teacher', $content);
        $this->assertStringContainsString('Classroom', $content);
    }
}