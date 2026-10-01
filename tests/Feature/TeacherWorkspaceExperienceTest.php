<?php

namespace Tests\Feature;

use App\Filament\App\Pages\Academic\SetupStructureHub;
use App\Filament\App\Resources\StudentResource;
use App\Models\School;
use App\Models\User;
use App\Security\CapabilityCatalog;
use App\Security\RoleCatalogue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;
use Tests\TestCase;

/**
 * The whole point of the permission system is that a teacher never meets anything
 * they are not allowed to use. These tests sign in as a real teacher and inspect
 * the sidebar, the module tabs, the category hubs and the action buttons they
 * are actually given, rather than checking the resolver in isolation.
 *
 * Each test answers one of the requirements as a user would experience it: no
 * Finance in the sidebar, three of four Setup & Structure pages, no Publish to
 * Student Portal, no Export button.
 */
class TeacherWorkspaceExperienceTest extends TestCase
{
    private ?int $userId = null;

    private ?int $roleId = null;

    private ?int $schoolId = null;

    private ?string $subdomain = null;

    /** The central host, captured before any test rewrites app.url. */
    private ?string $baseHost = null;

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
     * Serve requests over the tenant's own subdomain, which is how the panel
     * resolves the school in a browser.
     *
     * Only the server variables are changed. Rewriting `app.url` as well would
     * make the tenant prefix apply twice to any URL built from that config, which
     * is how a subdomain ends up repeated in generated links.
     */
    private function serveAsSubdomain(School $school): void
    {
        $this->withServerVariables([
            'HTTP_HOST' => $school->subdomain.'.'.parse_url($this->baseHost(), PHP_URL_HOST),
        ]);
    }

    /**
     * The central host, captured before any test rewrites app.url.
     */
    private function baseHost(): string
    {
        return $this->baseHost ??= (string) parse_url(config('app.url'), PHP_URL_HOST);
    }

    protected function tearDown(): void
    {
        if ($this->roleId) {
            CustomRole::whereKey($this->roleId)->forceDelete();
        }

        if ($this->userId) {
            User::whereKey($this->userId)->forceDelete();
        }

        if ($this->schoolId) {
            School::whereKey($this->schoolId)->forceDelete();
        }

        parent::tearDown();
    }

    /**
     * Sign in as a staff member holding the given role.
     */
    private function actingAsRole(string $roleKey): void
    {
        $school = School::create([
            'name' => 'Teacher Experience School',
            'subdomain' => 'teacher-exp-'.uniqid(),
            'status' => 'active',
        ]);

        $this->schoolId = $school->id;
        $this->subdomain = $school->subdomain;

        $role = CustomRole::create([
            'school_id' => $school->id,
            'name' => RoleCatalogue::label($roleKey).' Probe',
            'permissions' => RoleCatalogue::permissionsFor($roleKey),
        ]);

        $this->roleId = $role->id;

        $user = User::create([
            'school_id' => $school->id,
            'name' => 'Teacher Probe',
            'email' => 'teacher-exp-'.uniqid().'@example.test',
            'password' => bcrypt('secret1234'),
            'account_status' => User::STATUS_ACTIVE,
        ]);

        $this->userId = $user->id;

        $user->forceFill([
            'custom_role_id' => $role->id,
            'permissions' => null,
        ])->save();

        // Every module on, so what follows is about permissions and nothing else.
        // The settings belong to the school this test creates, and that school is
        // deleted in tearDown, so there is nothing to put back afterwards.
        foreach (array_keys(CapabilityCatalog::modules()) as $module) {
            if ($module === 'universal') {
                continue;
            }

            SystemSetting::set('modules', $module, '1', $school->id);
        }

        $this->useMysql();
        $this->refreshApplication();
        $this->useMysql();

        $user = User::whereKey($this->userId)->firstOrFail();

        $this->actingAs($user);
        $this->actingAsTenant($school);
        $this->serveAsSubdomain($school);
    }

    /**
     * Point the app at the MySQL database the rest of the suite's tenant tests
     * use.
     *
     * Every connection setting is set explicitly. `refreshApplication()` re-reads
     * config, and the suite's default `.env.testing` points at an in-memory
     * SQLite database that has no driver here, so a partially reset config would
     * silently query the wrong database.
     */
    private function useMysql(): void
    {
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
     * The workspace landing page as rendered for the signed-in teacher.
     *
     * Requested by absolute URL on the tenant subdomain, which is how the panel
     * resolves the school in a browser; a relative path would be served by the
     * central host and 404 on the tenant route.
     */
    private function sidebarHtml(): string
    {
        return $this->tenantGet('/workspace')->getContent();
    }

    /**
     * Open a panel path on the tenant's own subdomain.
     *
     * The panel resolves the school from the host, so anything requested against
     * the central host is a different tenant (or none at all) and would quietly
     * test the wrong thing.
     */
    private function tenantGet(string $path)
    {
        return $this->get('https://'.$this->subdomain.'.'.$this->baseHost().'/'.ltrim($path, '/'));
    }

    /**
     * The module landing pages the signed-in person can actually open, taken
     * from the sidebar that was rendered for them.
     *
     * @return array<string, string> module label => url
     */
    private function visibleSidebarModules(): array
    {
        $html = $this->sidebarHtml();

        preg_match_all('/href="[^"]*\/workspace\/([a-z0-9\-]+)"/', $html, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    public function test_a_teacher_is_never_offered_the_finance_module(): void
    {
        $this->actingAsRole('teaching_staff');

        $slugs = $this->visibleSidebarModules();

        $this->assertNotContains('finance', $slugs, 'Finance must not appear in a teacher\'s sidebar');
        $this->assertNotContains('finance-dashboard', $slugs);
        $this->assertNotContains('student-billing', $slugs);

        // The sidebar is not the only thing that could leak it, so the rendered
        // page is checked for the Finance landing page as well.
        $html = $this->sidebarHtml();

        foreach (['Finance', 'Student Billing', 'Financial Statements'] as $label) {
            $this->assertStringNotContainsString(
                '>'.$label.'<',
                $html,
                "A teacher must not see \"{$label}\" in the sidebar",
            );
        }
    }

    public function test_a_teacher_still_sees_the_modules_their_role_covers(): void
    {
        $this->actingAsRole('teaching_staff');

        $html = $this->sidebarHtml();

        $this->assertStringContainsString('Academics', $html, 'A teacher must see Academics');
        $this->assertStringContainsString('Exams', $html, 'A teacher must see Exams');
    }

    public function test_setup_and_structure_shows_only_the_pages_the_teacher_may_open(): void
    {
        // The example from the requirements: Level, Subjects and Classrooms are
        // granted, Academic Years is not. This uses an exact grant rather than the
        // default teacher role, because the default role deliberately covers all of
        // Academics and would pass this check vacuously.
        $granted = ['level', 'subjects', 'classrooms'];

        $this->actingAsWithExact(
            collect($granted)
                ->flatMap(fn (string $pageKey): array => CapabilityCatalog::accessKeysFor('academics', $pageKey))
                ->all()
        );

        $teacher = PermissionRegistry::permissionsFor(User::whereKey($this->userId)->firstOrFail());

        $reachable = [];

        foreach (array_merge($granted, ['academic_years']) as $pageKey) {
            $reachable[$pageKey] = PermissionRegistry::isGrantedAny(
                $teacher,
                CapabilityCatalog::accessKeysFor('academics', $pageKey),
            );
        }

        $this->assertTrue($reachable['level'], 'A teacher should reach the Level page');
        $this->assertTrue($reachable['subjects'], 'A teacher should reach the Subjects page');
        $this->assertTrue($reachable['classrooms'], 'A teacher should reach the Classrooms page');
        $this->assertFalse($reachable['academic_years'], 'A teacher must not reach Academic Years');
    }

    public function test_the_setup_and_structure_hub_lists_only_reachable_pages(): void
    {
        // The same three-page grant as the test above, so the category really does
        // have children the teacher may not open.
        $granted = ['level', 'subjects', 'classrooms'];

        $this->actingAsWithExact(
            collect($granted)
                ->flatMap(fn (string $pageKey): array => CapabilityCatalog::accessKeysFor('academics', $pageKey))
                ->all()
        );

        // What the hub renders is exactly this list, so it is checked directly.
        $hubPages = app(SetupStructureHub::class)->getCategoryPages();

        // Tabs identify their page by class, so the granted pages are matched
        // through the catalogue's own class map rather than by guessing at slugs.
        $classFor = array_map(
            fn (array $map): string => $map[1],
            array_filter(
                CapabilityCatalog::classMap(),
                fn (array $map): bool => $map[0] === 'academics',
            ),
        );

        $offeredKeys = collect($hubPages)
            ->map(fn (array $tab): ?string => isset($tab['class']) ? ($classFor[$tab['class']] ?? null) : null)
            ->filter()
            ->all();

        $this->assertEqualsCanonicalizing(
            $granted,
            $offeredKeys,
            'The hub should offer exactly the pages the teacher may open',
        );

        // Opening the hub sends the teacher to a page they may actually use, and
        // never to one they may not.
        $hub = $this->tenantGet('/workspace/academics-setup-structure');

        $hub->assertRedirect();

        // Tab URLs are stored as trimmed paths, so both sides are normalised the
        // same way before being compared.
        $this->assertContains(
            trim((string) parse_url($hub->headers->get('Location'), PHP_URL_PATH), '/'),
            collect($hubPages)
                ->map(fn (array $tab): string => trim((string) parse_url($tab['url'], PHP_URL_PATH), '/'))
                ->all(),
            'The hub must send the teacher to one of the pages they may open',
        );

        // And the page they land on offers nothing they lack.
        $landing = $this->get($hub->headers->get('Location'));
        $landing->assertOk();

        $html = $landing->getContent();

        foreach (CapabilityCatalog::accessKeysFor('academics', 'academic_years') as $key) {
            $this->assertStringNotContainsString($key, $html);
        }

        $this->assertStringNotContainsString(
            '/workspace/academics-academic-years',
            $html,
            'No link to the withheld Academic Years page may be rendered',
        );
    }

    public function test_a_teacher_never_sees_publish_to_student_portal(): void
    {
        $this->actingAsRole('teaching_staff');

        $html = $this->sidebarHtml();

        $this->assertStringNotContainsString(
            'Publish to Student Portal',
            $html,
            'A teacher must not be offered the student-portal publishing page',
        );

        $this->assertStringNotContainsString('publish-to-student-portal', $html);

        // The rest of that category stays available: the teacher keeps the whole
        // Reports & Academic Publishing area, it is only the publishing page
        // inside it that is withheld.
        $this->assertStringContainsString(
            'Reports &amp; Academic Publishing',
            $html,
            'A teacher keeps the Reports & Academic Publishing category',
        );
    }

    public function test_a_teacher_without_export_never_sees_an_export_button(): void
    {
        // A teacher who may read students but not take them away. The Export
        // button must be absent, not merely refuse when pressed.
        $this->actingAsWithExact([
            CapabilityCatalog::pagePermissionKey('students', 'students', 'view'),
        ]);

        $this->assertStringContainsString(
            'Students',
            $this->sidebarHtml(),
            'The teacher can read the student directory',
        );

        $page = $this->tenantGet('/'.ltrim(parse_url(StudentResource::getUrl('index'), PHP_URL_PATH), '/'));

        // Either the table renders without the button, or the page is refused
        // outright. What must never happen is an Export button being offered.
        if ($page->status() === 200) {
            $this->assertStringNotContainsString(
                'Export',
                $page->getContent(),
                'A teacher without the export capability must not be offered an Export button',
            );
        } else {
            $this->assertSame(403, $page->status());
        }
    }

    public function test_a_teacher_with_export_does_see_the_export_button(): void
    {
        // The other half of the check: the button must actually appear when it is
        // allowed, otherwise "hidden" would be indistinguishable from "broken".
        $this->actingAsWithExact([
            CapabilityCatalog::pagePermissionKey('students', 'students', 'view'),
            CapabilityCatalog::pagePermissionKey('students', 'students', 'export'),
        ]);

        $this->tenantGet('/'.ltrim(parse_url(StudentResource::getUrl('index'), PHP_URL_PATH), '/'))
            ->assertOk()
            ->assertSee('Export');
    }

    /**
     * Give the signed-in account exactly these permissions and nothing else.
     *
     * @param  array<int, string>  $permissions
     */
    private function actingAsWithExact(array $permissions): void
    {
        $this->actingAsRole('teaching_staff');

        CustomRole::whereKey($this->roleId)->update(['permissions' => $permissions]);

        $this->refreshApplication();
        $this->useMysql();

        $this->actingAs(User::whereKey($this->userId)->firstOrFail());
    }
}
