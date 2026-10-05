<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use App\Services\Academic\AcademicWorkflowEngine;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

/**
 * The Academics overview renders a "Complete: Terms" suggestion whose action is
 * resolved with route() and no arguments. That step used to point at
 * academic-years.edit, a route that requires a {record}, so the page threw
 * "Missing required parameter ... [record]" and returned HTTP 500 whenever Terms
 * was among the suggested next steps.
 *
 * Every route a workflow step can resolve to must therefore be generatable
 * without arguments.
 */
class AcademicWorkflowStepRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function actAsSchoolAdmin(): void
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();

        $this->assertNotNull($school, 'A school record is required.');

        App::instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);

        $adminRoleId = CustomRole::where('school_id', $school->id)
            ->where('role_key', 'administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        $this->actingAs($admin ?? User::findOrFail(13));
    }

    /**
     * The view calls route($stepRoute) with no parameters, so a route that
     * requires one throws and takes the whole page down.
     */
    public function test_every_workflow_step_route_resolves_without_parameters(): void
    {
        $this->actAsSchoolAdmin();

        $engine = app(AcademicWorkflowEngine::class);

        $checked = 0;

        foreach (array_keys(AcademicWorkflowEngine::SETUP_WORKFLOW) as $stepKey) {
            $route = $engine->getStepRoute($stepKey);

            $this->assertNotNull($route, "Step [{$stepKey}] must have a route");

            $this->assertIsString(
                route($route),
                "Step [{$stepKey}] must resolve to a URL with no route parameters"
            );

            $checked++;
        }

        $this->assertSame(count(AcademicWorkflowEngine::SETUP_WORKFLOW), $checked);
    }

    /**
     * Unknown steps must not produce a broken route; the view skips "#".
     */
    public function test_an_unknown_step_has_no_route(): void
    {
        $this->actAsSchoolAdmin();

        $this->assertNull(app(AcademicWorkflowEngine::class)->getStepRoute('not_a_real_step'));
    }

    /**
     * The regression itself: the Academics overview used to return HTTP 500.
     */
    public function test_the_academics_overview_page_renders(): void
    {
        $this->actAsSchoolAdmin();

        $this->withoutExceptionHandling();

        $response = $this->get('/workspace/academic-operations-center');

        $response->assertOk();

        $html = $response->getContent();

        // The module header and its Academics context tabs must be present.
        $this->assertStringContainsString('class="sc-module-navigation"', $html);
        $this->assertStringContainsString('Academic Operations Center', $html);

        // No suggestion may point at a route that needs a record.
        $this->assertStringNotContainsString('academic-years/{record}', $html);
    }
}
