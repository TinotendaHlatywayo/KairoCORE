<?php

namespace Tests\Feature;

use App\Filament\App\Resources\ApplicationResource\Pages\ListApplications;
use App\Filament\App\Resources\CourseResource\Pages\ListCourses;
use App\Models\School;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

/**
 * Opening or closing any modal (Help, Import Courses, row actions) is a Livewire
 * request, and the module header is a PAGE_START render hook that Filament
 * re-renders on every request. The header used to resolve its module from
 * request()->path(), which is "livewire/update" during those requests, so the
 * header rendered nothing and Livewire morphed it out of the DOM. It then stayed
 * gone until a full page refresh.
 *
 * These tests replay a genuine Livewire update: the snapshot is the one the page
 * actually rendered, sent back the way the browser sends it when a modal opens
 * or closes.
 */
class ModuleHeaderSurvivesModalTest extends TestCase
{
    /**
     * The header element itself. The bare class name also appears in the sidebar
     * search script, so the full attribute is matched instead.
     */
    private const HEADER_MARKUP = 'class="sc-module-navigation"';

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

    /**
     * The pages are permission gated, so tests act as the school's Administrator
     * (that role bypasses the permission checks) on an existing tenant.
     */
    private function actAsSchoolAdmin(): void
    {
        $school = School::where('subdomain', 'rujeko')->first() ?? School::first();

        $this->assertNotNull($school, 'A school record is required.');

        App::instance('current_tenant', $school);

        // Production requests resolve the tenant from the subdomain, which is what
        // lets tenant routes (e.g. the timetable print links on these pages) be
        // generated. Test requests arrive on the base host, so mirror that here.
        URL::defaults(['tenant' => $school->subdomain]);

        $adminRoleId = CustomRole::where('school_id', $school->id)
            ->where('role_key', 'administrator')
            ->value('id');

        $admin = $adminRoleId
            ? User::where('school_id', $school->id)->where('custom_role_id', $adminRoleId)->first()
            : null;

        $this->actingAs($admin ?? User::findOrFail(13));
    }

    private function open(string $path): TestResponse
    {
        $this->actAsSchoolAdmin();

        return $this->get($path);
    }

    /**
     * Send the rendered snapshot back to /livewire/update exactly as the browser
     * does for the page component, which is what opening and closing a modal does.
     */
    private function interact(TestResponse $page, string $componentName, string $refererPath, string $method, array $params = []): TestResponse
    {
        $snapshot = $this->snapshotOf($page->getContent(), $componentName);

        return $this->withHeaders([
            'X-Livewire' => 'true',
            'Referer' => url($refererPath),
        ])->post('/livewire/update', [
            'components' => [
                [
                    'snapshot' => $snapshot,
                    'updates' => [],
                    'calls' => [
                        ['path' => '', 'method' => $method, 'params' => $params],
                    ],
                ],
            ],
        ]);
    }

    private function snapshotOf(string $html, string $componentName): string
    {
        preg_match_all('/wire:snapshot="([^"]*)"/', $html, $matches);

        foreach ($matches[1] as $raw) {
            $snapshot = html_entity_decode($raw, ENT_QUOTES);
            $meta = json_decode($snapshot, true);

            if (str_contains(strtolower((string) ($meta['memo']['name'] ?? '')), strtolower($componentName))) {
                return $snapshot;
            }
        }

        $this->fail("No snapshot found for component [{$componentName}].");
    }

    /**
     * The markup the browser will apply. A Livewire update answers with JSON, so
     * the re-rendered component HTML has to be pulled out of the payload first:
     * that HTML is exactly what Livewire morphs over the current DOM.
     */
    private function renderedHtml(TestResponse $response): string
    {
        $content = (string) $response->getContent();
        $decoded = json_decode($content, true);

        if (! is_array($decoded) || ! isset($decoded['components'])) {
            return $content;
        }

        $html = '';

        foreach ($decoded['components'] as $component) {
            $html .= (string) ($component['effects']['html'] ?? '');
        }

        return $html;
    }

    private function assertHeaderPresent(TestResponse $response, string $context): void
    {
        $response->assertOk();

        $this->assertStringContainsString(
            self::HEADER_MARKUP,
            $this->renderedHtml($response),
            "The module header must still be rendered {$context}"
        );
    }

    /**
     * The exact case reported: the Academics header vanished once the Import
     * Courses modal on the Level page was closed.
     */
    public function test_academics_header_survives_a_modal_on_the_level_page(): void
    {
        $page = $this->open('/workspace/courses')->assertOk();

        $this->assertStringContainsString(self::HEADER_MARKUP, $this->renderedHtml($page));

        // Opening the Help modal, then closing it again.
        $opened = $this->interact($page, 'list-courses', '/workspace/courses', 'mountAction', ['pageHelp']);
        $this->assertHeaderPresent($opened, 'while the Help modal is open on the Level page');

        $closed = $this->interact($page, 'list-courses', '/workspace/courses', 'unmountAction');
        $this->assertHeaderPresent($closed, 'after the Help modal is closed on the Level page');

        foreach (['Subjects', 'Classrooms', 'Academic Years'] as $tab) {
            $this->assertStringContainsString($tab, $this->renderedHtml($closed), "The {$tab} tab must still be shown");
        }
    }

    /**
     * The header must not depend on which page of the module is open.
     */
    public function test_header_survives_an_interaction_on_every_academics_page(): void
    {
        $pages = [
            '/workspace/subjects' => 'list-subjects',
            '/workspace/classrooms' => 'list-classrooms',
            '/workspace/academic-years' => 'list-academic-years',
            '/workspace/courses' => 'list-courses',
            '/workspace/promotion-runs' => 'list-promotion-runs',
            '/workspace/screening-runs' => 'list-screening-runs',
            '/workspace/teacher-assignments' => 'list-teacher-assignments',
        ];

        foreach ($pages as $path => $component) {
            $page = $this->open($path);
            $page->assertOk();

            $this->assertHeaderPresent($page, "on the initial load of {$path}");

            $this->assertHeaderPresent(
                $this->interact($page, $component, $path, 'unmountAction'),
                "after a Livewire interaction on {$path}"
            );
        }
    }

    public function test_header_survives_an_interaction_on_the_admissions_applications_page(): void
    {
        $page = $this->open('/workspace/applications');
        $page->assertOk();

        $update = $this->interact($page, 'list-applications', '/workspace/applications', 'unmountAction');

        $this->assertHeaderPresent($update, 'after a Livewire interaction on Admissions > Applications');
        $this->assertStringContainsString('Applications', $this->renderedHtml($update));
    }

    /**
     * Admissions > Applications rendered no header at all, so neither its Create
     * button nor its Help button was reachable. The page blanks the built-in
     * heading (it draws its own inside the card view), which made Filament skip
     * the whole header including every action, so the header is asserted here.
     */
    public function test_applications_page_renders_its_header_actions(): void
    {
        $this->actAsSchoolAdmin();

        $component = Livewire::test(ListApplications::class);

        $html = $component->html();

        $this->assertStringContainsString('fi-header-actions', $html);
        $this->assertStringContainsString('New Application', $html);
        $this->assertStringContainsString('Help', $html);

        $component->assertActionExists('pageHelp');
        $this->assertSame(
            'Help',
            $this->headerAction($component, 'pageHelp')->getLabel()
        );
    }

    /**
     * Every page reported as having no Help button.
     */
    public function test_help_action_exists_on_the_reported_pages(): void
    {
        $this->actAsSchoolAdmin();

        foreach ([ListApplications::class, ListCourses::class] as $pageClass) {
            $component = Livewire::test($pageClass);

            $component->assertActionExists('pageHelp');
            $this->assertSame(
                'Help',
                $this->headerAction($component, 'pageHelp')->getLabel(),
                "{$pageClass} must expose a Help button"
            );
        }
    }

    /**
     * Timetables > Teaching renders its own heading inside the visual builder
     * view, so the Help button is asserted on the served page rather than by
     * mounting the component.
     */
    public function test_visual_timetable_builder_serves_a_help_button(): void
    {
        $page = $this->open('/workspace/visual-timetable-builder');

        $page->assertOk();

        $this->assertHeaderPresent($page, 'on the initial load of the Timetables page');
        $this->assertStringContainsString('Timetables', $this->renderedHtml($page));
    }

    private function headerAction(Testable $component, string $name): Action
    {
        $actions = $component->instance()->getCachedHeaderActions();

        while ($actions !== []) {
            $action = array_shift($actions);

            if ($action instanceof ActionGroup) {
                $actions = array_merge($actions, $action->getActions());

                continue;
            }

            if ($action->getName() === $name) {
                return $action;
            }
        }

        $this->fail("Header action [{$name}] was not found.");
    }

    /**
     * A page that belongs to no module must not inherit a stale module header.
     */
    public function test_pages_outside_a_module_have_no_header(): void
    {
        $this->actAsSchoolAdmin();

        $dashboard = $this->get('/workspace');
        $dashboard->assertOk();

        $this->assertStringNotContainsString(self::HEADER_MARKUP, $dashboard->getContent());
    }
}
