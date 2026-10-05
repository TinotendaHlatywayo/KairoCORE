<?php

namespace Tests\Feature;

use App\Navigation\ModuleNavigationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Tests\TestCase;

/**
 * The module header and its contextual tabs are rendered by a PAGE_START render
 * hook, so Filament re-renders them on every Livewire request. Livewire posts
 * every modal interaction (Help, Import Courses, row actions, forms) to
 * /livewire/update, where request()->path() is "livewire/update" and belongs to
 * no module. The header then resolved to null and rendered nothing, and Livewire
 * morphed that empty result over the existing markup, so the header disappeared
 * until a full page refresh.
 *
 * Livewire records the real page path in the component snapshot and exposes it
 * through originalPath(). These tests pin the module, its active tab and its
 * category group to that path, for Livewire requests and for plain XHR requests.
 */
class ModuleNavigationLivewireRequestTest extends TestCase
{
    private function service(): ModuleNavigationService
    {
        return app(ModuleNavigationService::class);
    }

    /**
     * Bind a fresh request into the container so request()->path() is genuinely
     * the given URI, the way a real page load or Livewire request behaves.
     */
    private function bindRequest(string $uri, string $method = 'GET', array $headers = []): Request
    {
        $server = [];

        foreach ($headers as $key => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value;
        }

        $request = Request::create($uri, $method, [], [], [], $server);

        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');

        return $request;
    }

    /**
     * A Livewire update: posted to /livewire/update, flagged with X-Livewire and
     * carrying the component snapshot, which holds the page it was rendered on.
     */
    private function bindLivewireUpdate(string $pagePath): Request
    {
        $snapshot = json_encode([
            'data' => [],
            'memo' => ['path' => $pagePath, 'method' => 'GET'],
            'checksum' => 'test',
        ]);

        return $this->bindRequest('/livewire/update', 'POST', [
            'X-Livewire' => 'true',
            'Referer' => 'https://school.test/'.$pagePath.'?tab=1',
        ])->merge([
            'components' => [
                [
                    'snapshot' => $snapshot,
                    'updates' => [],
                    'calls' => [],
                ],
            ],
        ]);
    }

    public function test_page_request_resolves_the_module_directly(): void
    {
        $this->bindRequest('/workspace/subjects');

        $this->assertSame('workspace/subjects', $this->service()->currentPath());
        $this->assertSame('academics', $this->service()->currentModule()['slug'] ?? null);
    }

    /**
     * The livewire/update endpoint itself belongs to no module, so it must not be
     * used as the page path.
     */
    public function test_livewire_update_resolves_the_page_from_the_snapshot(): void
    {
        $this->bindLivewireUpdate('workspace/subjects');

        $this->assertSame('livewire/update', request()->path());
        $this->assertSame('workspace/subjects', $this->service()->currentPath());

        $service = $this->service();

        $this->assertSame('academics', $service->currentModule()['slug'] ?? null);
        $this->assertSame('Subjects', $service->activeTabLabel($service->moduleBySlug('academics')));
        $this->assertSame(
            'Setup & Structure',
            $service->activeTabGroup($service->moduleBySlug('academics'))
        );
    }

    /**
     * Every page a modal can be opened on must keep its module and active tab.
     */
    public function test_livewire_update_resolves_every_reported_page(): void
    {
        $expectations = [
            'workspace/academic-operations-center' => 'academics',
            'workspace/subjects' => 'academics',
            'workspace/classrooms' => 'academics',
            'workspace/academic-years' => 'academics',
            'workspace/courses' => 'academics',
            'workspace/visual-timetable-builder' => 'academics',
            'workspace/timetable-viewer-page' => 'academics',
            'workspace/teacher-assignments' => 'academics',
            'workspace/promotion-runs' => 'academics',
            'workspace/screening-runs' => 'academics',
            'workspace/applications' => 'admissions',
            'workspace/admission-settings' => 'admissions',
            'workspace/students' => 'students',
        ];

        foreach ($expectations as $pagePath => $moduleSlug) {
            $this->bindLivewireUpdate($pagePath);

            $service = $this->service();

            $this->assertSame(
                $moduleSlug,
                $service->currentModule()['slug'] ?? null,
                "{$pagePath} must keep its module during a modal interaction"
            );
            $this->assertNotNull(
                $service->activeTabLabel($service->moduleBySlug($moduleSlug)),
                "{$pagePath} must keep an active tab highlighted"
            );
        }
    }

    /**
     * The tab list for the category the user is in must be unchanged, so the
     * header does not reshape itself when a modal opens or closes.
     */
    public function test_category_tabs_are_stable_across_a_modal_interaction(): void
    {
        $this->bindRequest('/workspace/academic-operations-center');
        $before = $this->tabsOf($this->service(), 'academics');

        $this->bindLivewireUpdate('workspace/academic-operations-center');
        $after = $this->tabsOf($this->service(), 'academics');

        $this->assertSame($before, $after);
        $this->assertNotEmpty($before);
    }

    /**
     * XHR requests that are not Livewire (search, filters) still carry the page
     * URL, so they resolve normally.
     */
    public function test_ajax_request_keeps_the_module_visible(): void
    {
        $this->bindRequest('/workspace/students', 'GET', ['X-Requested-With' => 'XMLHttpRequest']);

        $service = $this->service();

        $this->assertSame('students', $service->currentModule()['slug'] ?? null);
        $this->assertSame('Students', $service->activeTabLabel($service->moduleBySlug('students')));
    }

    /**
     * A page outside every module must not inherit a module header, otherwise the
     * Academics tabs would show up on the dashboard.
     */
    public function test_a_page_outside_every_module_resolves_to_nothing(): void
    {
        $this->bindLivewireUpdate('workspace');

        $this->assertNull($this->service()->currentModule());
    }

    /**
     * A snapshot without a path falls back to the referring page.
     */
    public function test_referer_is_used_when_the_snapshot_carries_no_path(): void
    {
        $this->bindRequest('/livewire/update', 'POST', [
            'X-Livewire' => 'true',
            'Referer' => 'https://school.test/workspace/visual-timetable-builder',
        ])->merge([
            'components' => [
                ['snapshot' => json_encode(['data' => [], 'memo' => []]), 'updates' => [], 'calls' => []],
            ],
        ]);

        $this->assertSame('workspace/visual-timetable-builder', $this->service()->currentPath());
        $this->assertSame('academics', $this->service()->currentModule()['slug'] ?? null);
    }

    /**
     * Tab URLs keep their panel prefix, so the resolved path must keep it too.
     * Stripping it makes every module lookup fail and the header vanish.
     */
    public function test_resolved_path_keeps_the_panel_prefix(): void
    {
        $this->bindLivewireUpdate('workspace/subjects');

        $this->assertSame('workspace/subjects', $this->service()->currentPath());
    }

    /**
     * @return array<int, string>
     */
    private function tabsOf(ModuleNavigationService $service, string $moduleSlug): array
    {
        $labels = [];

        // Permission filtering needs an authenticated user, which this test does
        // not set up; the point here is that the tab list does not change shape
        // when a modal opens or closes.
        foreach ($service->moduleTabs($service->moduleBySlug($moduleSlug), false) as $tab) {
            $labels[] = $tab['label'];
        }

        return $labels;
    }
}
