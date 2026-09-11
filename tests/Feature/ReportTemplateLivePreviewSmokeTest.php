<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\URL;
use Modules\Academics\Models\ReportTemplate;

class ReportTemplateLivePreviewSmokeTest extends TestCase
{
    protected int $schoolId;

    protected function adminUser(): User
    {
        return User::where('school_id', $this->schoolId)->where('requested_role', 'administrator')->firstOrFail();
    }

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

    protected function tenantHost(): string
    {
        $school = School::findOrFail($this->schoolId);

        return $school->subdomain.'.'.parse_url(config('app.url'), PHP_URL_HOST).':8000';
    }

    public function test_report_template_create_page_renders_live_preview(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $r = $this->get('/workspace/report-templates/create');
        $r->assertOk();

        $html = $r->getContent();
        $this->assertStringNotContainsString('Preview could not be rendered', $html);
        $this->assertStringContainsString('preview-sheet-canvas', $html);
        $this->assertStringContainsString('Landscape is recommended', $html);
        $this->assertStringContainsString('Report Sign-off &amp; Validation', $html);
    }

    public function test_report_template_edit_page_renders_live_preview(): void
    {
        $user = $this->adminUser();
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => $this->tenantHost()]);

        $tpl = ReportTemplate::create([
            'school_id' => $this->schoolId,
            'name' => 'Smoke Test Report Layout',
            'design_theme' => 'classic_line',
            'target_level' => 'all',
            'scope_type' => 'level',
            'is_active' => false,
            'layout_config' => [
                'page_orientation' => 'landscape',
                'included_assessments' => [],
                'show_subject_teacher_remarks' => true,
            ],
        ]);

        try {
            $r = $this->get("/workspace/report-templates/{$tpl->id}/edit");
            $r->assertOk();
            $html = $r->getContent();

            $this->assertStringNotContainsString('Preview could not be rendered', $html);
            $this->assertStringContainsString('preview-sheet-canvas', $html);

            $a = $this->get('/workspace/report-templates');
            $a->assertOk();
        } finally {
            $tpl->delete();
        }
    }
}