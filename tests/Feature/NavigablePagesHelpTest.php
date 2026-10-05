<?php

namespace Tests\Feature;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Pages\AdministrationDashboard;
use App\Filament\App\Pages\WebsiteContentManager;
use App\Models\School;
use App\Models\User;
use App\Navigation\ModuleNavigation;
use App\Navigation\ModuleNavigationService;
use App\Support\HelpContent;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Tests\TestCase;

/**
 * Every page reachable from the module navigation must offer the Help button,
 * and the button must open guidance written for that page rather than the
 * generic fallback.
 *
 * The inventory is taken from ModuleNavigation instead of a hand-written list,
 * so a page added to the navigation cannot quietly ship without help.
 */
class NavigablePagesHelpTest extends TestCase
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

    /**
     * The pages are permission gated, so the tests act as the school's
     * Administrator, which bypasses the permission checks.
     */
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
     * Every tab of every module, keyed by URL so a page listed twice is checked
     * once. Permissions are deliberately not filtered, because coverage must not
     * depend on who is running the test.
     *
     * @return array<string, array{module:string,label:string,url:string,page:string,helpKey:string}>
     */
    private function navigablePages(): array
    {
        $service = app(ModuleNavigationService::class);
        $pages = [];

        foreach (ModuleNavigation::modules() as $module) {
            foreach (array_merge($service->moduleTabs($module, false), $service->moduleMoreTabs($module, false)) as $tab) {
                $helpKey = $tab['class'];

                $page = $tab['class'];

                if (isset($tab['resource']) && is_subclass_of($tab['resource'], Resource::class)) {
                    $page = $tab['resource']::getPages()['index']->getPage() ?? $tab['class'];
                }

                $url = trim((string) parse_url((string) $tab['url'], PHP_URL_PATH), '/');

                if ($url === '') {
                    continue;
                }

                $pages[$url] ??= [
                    'module' => (string) ($module['label'] ?? 'Module'),
                    'label' => (string) ($tab['label'] ?? $page),
                    'url' => $url,
                    'page' => $page,
                    'helpKey' => $helpKey,
                ];
            }
        }

        ksort($pages);

        return $pages;
    }

    public function test_the_navigation_inventory_is_discovered(): void
    {
        $pages = $this->navigablePages();

        $this->assertGreaterThanOrEqual(100, count($pages), 'The module navigation should expose a substantial number of pages.');

        foreach ($pages as $url => $page) {
            $this->assertTrue(
                class_exists($page['page']),
                "The page class [{$page['page']}] behind [{$url}] does not exist."
            );
        }
    }

    public function test_every_navigable_page_uses_the_help_trait(): void
    {
        foreach ($this->navigablePages() as $url => $page) {
            $this->assertContains(
                HasPageHelp::class,
                class_uses_recursive($page['page']),
                "[{$url}] ({$page['label']}) must use [".HasPageHelp::class.'] to offer Help.'
            );

            $this->assertTrue(
                method_exists($page['page'], 'getHeaderActions'),
                "[{$url}] ({$page['label']}) must expose header actions so Help can be rendered."
            );
        }
    }

    public function test_every_navigable_page_has_help_written_for_it(): void
    {
        $fallback = HelpContent::for('__no_such_page__');

        foreach ($this->navigablePages() as $url => $page) {
            $help = HelpContent::for($page['helpKey']);

            $this->assertNotSame(
                $fallback,
                $help,
                "[{$url}] ({$page['label']}) falls back to generic help. Add an entry for [{$page['helpKey']}]."
            );

            $this->assertNotEmpty($help['title'] ?? null, "[{$url}] needs a help title.");
            $this->assertNotEmpty($help['summary'] ?? null, "[{$url}] needs a help summary.");
            $this->assertNotEmpty($help['workflow'] ?? null, "[{$url}] needs a workflow.");
            $this->assertNotEmpty($help['details'] ?? null, "[{$url}] needs detail notes.");
        }
    }

    /**
     * The rendered page must actually show the Help button, and must ship the
     * modal container that button opens into. A page that draws its own layout
     * instead of <x-filament-panels::page> renders neither the header nor the
     * container unless it includes both by hand, which leaves a Help button
     * that silently does nothing. Pages that redirect (the category hubs forward
     * to the first page of their category) are checked through their
     * destination instead.
     */
    public function test_every_navigable_page_renders_the_help_button(): void
    {
        $this->actAsSchoolAdmin();

        $missing = [];
        $broken = [];
        $unopenable = [];

        foreach ($this->navigablePages() as $url => $page) {
            $response = $this->get('/'.$url);

            if ($response->isRedirect()) {
                continue;
            }

            if (! $response->isOk()) {
                $broken[] = "{$url} returned ".$response->getStatusCode();

                continue;
            }

            $html = (string) $response->getContent();

            if (! str_contains($html, 'pageHelp')) {
                $missing[] = "{$url} ({$page['label']})";

                continue;
            }

            if (! str_contains($html, 'fi-modal') && ! str_contains($html, 'modal-close-overlay')) {
                $unopenable[] = "{$url} ({$page['label']})";
            }
        }

        $this->assertSame([], $broken, 'Navigable pages must load without error.');
        $this->assertSame([], $missing, 'These pages render without a Help button.');
        $this->assertSame([], $unopenable, 'These pages show the Help button but ship no modal container to open it in.');
    }

    /**
     * The button is only worth having if pressing it opens guidance written for
     * that page. Sampled across every module, plus the two pages that draw their
     * own layout, because those assemble the header and modal container by hand.
     */
    public function test_the_help_modal_opens_guidance_written_for_the_page(): void
    {
        $this->actAsSchoolAdmin();

        $pages = $this->navigablePages();

        $sample = [];
        $sampledModules = [];

        foreach ($pages as $url => $page) {
            if (isset($sampledModules[$page['module']])) {
                continue;
            }

            $sampledModules[$page['module']] = true;
            $sample[$url] = $page;
        }

        foreach ($pages as $url => $page) {
            if (in_array($page['page'], [AdministrationDashboard::class, WebsiteContentManager::class], true)) {
                $sample[$url] = $page;
            }
        }

        $checked = 0;

        foreach ($sample as $url => $page) {
            if ($this->get('/'.$url)->isRedirect()) {
                continue;
            }

            $checked++;

            $component = Livewire::test($page['page']);
            $component->mountAction('pageHelp');

            $this->assertStringContainsString(
                HelpContent::for($page['helpKey'])['title'],
                html_entity_decode(strip_tags($component->html())),
                "[{$url}] ({$page['label']}) must open its own guidance when Help is pressed."
            );
        }

        $this->assertGreaterThanOrEqual(10, $checked, 'The Help modal should be exercised across every module.');
    }
}
