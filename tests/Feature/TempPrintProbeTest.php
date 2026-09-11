<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Tests\TestCase as BaseTestCase;

class TempPrintProbeTest extends BaseTestCase
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
        return User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
    }

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    public function test_print_timetable_class_scope_no_longer_403(): void
    {
        $school = School::findOrFail($this->schoolId);
        $section = Section::where('school_id', $school->id)->withoutGlobalScopes()->firstOrFail();

        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $url = route('tenant.timetable.print', ['section' => $section->id]);
        $r = $this->get($url);
        $this->assertNotSame(403, $r->getStatusCode(), 'class print-timetable returned 403. Body: '.substr($r->getContent(), 0, 300));
        $r->assertOk();
        $this->assertStringContainsString('Official Weekly Timetable', $r->getContent());
    }

    public function test_print_timetable_stream_scope(): void
    {
        $school = School::findOrFail($this->schoolId);
        $course = Course::where('school_id', $school->id)->firstOrFail();

        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $url = route('tenant.timetable.print-stream', ['course' => $course->id]);
        $r = $this->get($url);
        $this->assertNotSame(403, $r->getStatusCode(), 'stream print-timetable returned 403. Body: '.substr($r->getContent(), 0, 300));
        $r->assertOk();
        $this->assertStringContainsString('Official Weekly Timetable', $r->getContent());
    }

    public function test_print_timetable_invalid_section_returns_404_not_403(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get(route('tenant.timetable.print', ['section' => 999999]));
        $this->assertSame(404, $r->getStatusCode());
    }
}