<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\SystemSetting;
use Tests\TestCase;

/**
 * Verifies the two core multi-tenancy guarantees:
 *
 * 1. Guests hitting a protected workspace page are redirected to the LOGIN
 *    page of their own subdomain (never a broken cross-host 404).
 * 2. Settings (e.g. language) written by one tenant are INVISIBLE to every
 *    other tenant AND to the central platform website.
 */
class TenantIsolationAndAuthRedirectTest extends TestCase
{
    private ?School $schoolA = null;

    private ?School $schoolB = null;

    private array $createdSchoolIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->schoolA = $this->ensureSchool('iso-test-a', 'Isolation Test A');
        $this->schoolB = $this->ensureSchool('iso-test-b', 'Isolation Test B');

        // Clean slate for the two tenants under test.
        SystemSetting::withoutTenantScope()
            ->whereIn('school_id', [$this->schoolA->id, $this->schoolB->id])
            ->whereIn('group', ['preferences', 'branding'])
            ->delete();

        Cache::flush();
    }

    protected function tearDown(): void
    {
        SystemSetting::withoutTenantScope()
            ->whereIn('school_id', array_filter($this->createdSchoolIds))
            ->delete();

        School::withTrashed()
            ->whereIn('id', array_filter($this->createdSchoolIds))
            ->forceDelete();

        parent::tearDown();
    }

    public function test_guest_visiting_workspace_settings_page_is_redirected_to_login(): void
    {
        $response = $this->get('https://iso-test-a.lvh.me/workspace/system-settings-page');

        $response->assertStatus(302);
        $response->assertRedirect('https://iso-test-a.lvh.me/workspace/login');
    }

    public function test_guest_stays_on_their_own_subdomain_when_redirected(): void
    {
        $response = $this->get('https://iso-test-b.lvh.me/workspace');

        $response->assertStatus(302);
        $this->assertStringStartsWith(
            'https://iso-test-b.lvh.me/',
            (string) $response->headers->get('Location'),
            'Guests must be redirected to a login page on the SAME tenant subdomain.'
        );
    }

    public function test_setting_written_by_one_tenant_is_invisible_to_other_tenants(): void
    {
        // Admin of tenant A changes the system language to French.
        $this->actingAsTenant($this->schoolA);
        SystemSetting::set('preferences', 'default_language', 'fr');

        // Tenant B still resolves its own default (English), never A's value.
        $this->actingAsTenant($this->schoolB);
        $this->assertSame(
            'en',
            SystemSetting::get('preferences', 'default_language', 'en'),
            'Tenant B must not see the language changed by tenant A.'
        );

        // A still sees its own change.
        $this->actingAsTenant($this->schoolA);
        $this->assertSame('fr', SystemSetting::get('preferences', 'default_language', 'en'));

        // Overwriting in B does not leak back into A either.
        $this->actingAsTenant($this->schoolB);
        SystemSetting::set('preferences', 'default_language', 'sw');
        $this->actingAsTenant($this->schoolA);
        $this->assertSame('fr', SystemSetting::get('preferences', 'default_language', 'en'));
    }

    public function test_platform_website_never_reads_or_writes_tenant_settings(): void
    {
        $this->actingAsTenant($this->schoolA);
        SystemSetting::set('branding', 'theme', 'obsidian_gold');

        // Central platform host (lvh.me): no tenant bound.
        $this->withoutTenant();

        $this->assertSame(
            'emerald_heritage',
            SystemSetting::get('branding', 'theme', 'emerald_heritage'),
            'The platform site must never read another tenant\'s setting.'
        );

        $this->expectException(\RuntimeException::class);
        SystemSetting::set('branding', 'theme', 'digital_cobalt');
    }

    public function test_set_scopes_the_write_to_the_active_tenant_row(): void
    {
        $this->actingAsTenant($this->schoolA);
        SystemSetting::set('preferences', 'default_language', 'pt');

        // Writing the same group/key from tenant B must UPDATE B's row,
        // leaving A's row untouched (no shared/global row).
        $this->actingAsTenant($this->schoolB);
        SystemSetting::set('preferences', 'default_language', 'es');

        $rows = SystemSetting::withoutTenantScope()
            ->where('group', 'preferences')
            ->where('key', 'default_language')
            ->whereIn('school_id', [$this->schoolA->id, $this->schoolB->id])
            ->get();

        $this->assertCount(2, $rows, 'Each tenant must own its own setting row.');
        $this->assertSame('pt', $rows->firstWhere('school_id', $this->schoolA->id)->value);
        $this->assertSame('es', $rows->firstWhere('school_id', $this->schoolB->id)->value);
    }

    private function ensureSchool(string $subdomain, string $name): School
    {
        $school = School::withTrashed()->where('subdomain', $subdomain)->first();

        if (! $school) {
            $school = School::create(['name' => $name, 'subdomain' => $subdomain, 'status' => 'active']);
        } elseif ($school->trashed()) {
            $school->restore();
        }

        $this->createdSchoolIds[] = $school->id;

        return $school;
    }
}
