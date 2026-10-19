<?php

namespace Tests\Feature;

use App\Models\School;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\SystemSetting;
use Tests\TestCase;

/**
 * Email branding contract:
 *  - tenant emails show the school's logo (dedicated email logo → workspace
 *    School Logo → platform-uploaded school logo → KairoCORE fallback),
 *  - system/platform emails always show the KairoCORE logo,
 *  - every branded email renders through the shared x-email-layout shell
 *    (which emails/brand.blade.php also uses).
 *
 * Runs against the real dev database (like SchoolForceDeletePurgeTest);
 * every row/file it creates is unique and removed by the assertions/tearDown.
 */
class EmailBrandingAndTemplatesTest extends TestCase
{
    private string $uniq;

    private ?int $schoolId = null;

    /** @var list<string> absolute paths of files created for logo existence checks */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.env', 'local');
        config()->set('database.default', 'mysql');
        config()->set('database.connections.mysql.database', 'schoolcore');
        config()->set('database.connections.mysql.host', '127.0.0.1');
        config()->set('database.connections.mysql.port', '3306');
        config()->set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        config()->set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');

        $this->uniq = 'ebrand-'.uniqid();
    }

    protected function tearDown(): void
    {
        if ($this->schoolId !== null) {
            SystemSetting::withoutTenantScope()->where('school_id', $this->schoolId)->delete();
            DB::table('schools')->where('id', $this->schoolId)->delete();
            $this->schoolId = null;
        }

        foreach ($this->files as $file) {
            @unlink($file);
        }
        $this->files = [];

        parent::tearDown();
    }

    /** Create a real file under public/storage and return its storage-relative path. */
    private function makeLogo(string $name): string
    {
        $relative = 'email-branding-test/'.$this->uniq.'/'.$name;
        $absolute = public_path('storage/'.$relative);

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        file_put_contents($absolute, 'png-bytes');
        $this->files[] = $absolute;

        return $relative;
    }

    private function makeSchool(?string $logoPath = null): School
    {
        $school = School::create([
            'name' => 'Email Branding School '.$this->uniq,
            'subdomain' => $this->uniq,
            'logo_path' => $logoPath,
        ]);

        $this->schoolId = $school->id;

        return $school;
    }

    public function test_tenant_email_logo_resolves_school_chain_then_platform(): void
    {
        $schoolLogo = $this->makeLogo('school-logo.png');
        $school = $this->makeSchool($schoolLogo);

        // 1. No settings → the platform-uploaded school logo (schools.logo_path).
        $this->assertSame(asset('storage/'.$schoolLogo), email_branding($school)['logo_url']);

        // 2. Workspace System Settings → School Logo (group branding) wins over schools.logo_path.
        $workspaceLogo = $this->makeLogo('workspace-logo.png');
        SystemSetting::withoutTenantScope()->updateOrCreate(
            ['school_id' => $school->id, 'group' => 'branding', 'key' => 'logo_path'],
            ['value' => $workspaceLogo],
        );
        $this->assertSame(asset('storage/'.$workspaceLogo), email_branding($school)['logo_url']);

        // 3. Dedicated email logo (group email) wins over the workspace logo.
        $emailLogo = $this->makeLogo('email-logo.png');
        SystemSetting::withoutTenantScope()->updateOrCreate(
            ['school_id' => $school->id, 'group' => 'email', 'key' => 'logo_path'],
            ['value' => $emailLogo],
        );
        $this->assertSame(asset('storage/'.$emailLogo), email_branding($school)['logo_url']);

        // 4. Nothing school-specific → platform identity (same as a system email).
        $school->forceFill(['logo_path' => null])->save();
        SystemSetting::withoutTenantScope()->where('school_id', $school->id)->delete();
        $this->assertSame(email_branding(null)['logo_url'], email_branding($school)['logo_url']);
    }

    public function test_email_layout_uses_school_logo_for_tenant_and_platform_logo_for_system(): void
    {
        $schoolLogo = $this->makeLogo('component-school-logo.png');
        $school = $this->makeSchool($schoolLogo);

        $tenant = Blade::render(
            '<x-email-layout :school="$school" heading="Hello there">Body copy</x-email-layout>',
            ['school' => $school],
        );
        $this->assertStringContainsString(asset('storage/'.$schoolLogo), $tenant);
        $this->assertStringContainsString('Hello there', $tenant);
        $this->assertStringContainsString('Body copy', $tenant);

        $system = Blade::render(
            '<x-email-layout heading="System note">Body copy</x-email-layout>',
            [],
        );
        $this->assertSame(email_branding(null)['logo_url'], $this->extractLogoSrc($system));
        $this->assertStringContainsString('System note', $system);
        $this->assertStringNotContainsString(asset('storage/'.$schoolLogo), $system);
    }

    public function test_brand_shell_renders_heading_action_and_school_logo(): void
    {
        $schoolLogo = $this->makeLogo('brand-shell-logo.png');
        $school = $this->makeSchool($schoolLogo);

        $html = view('emails.brand', brand_email_view_data([
            'heading' => 'Registration awaiting approval',
            'greeting' => 'Hello approver,',
            'introLines' => ['Line one.'],
            'actionUrl' => 'https://example.test/review',
            'actionText' => 'Review registration',
            'outroLines' => ['Thanks.'],
            'signature' => 'The Team',
        ], $school))->render();

        $this->assertStringContainsString('Registration awaiting approval', $html);
        $this->assertStringContainsString('Hello approver,', $html);
        $this->assertStringContainsString('https://example.test/review', $html);
        $this->assertStringContainsString('Review registration', $html);
        $this->assertStringContainsString(asset('storage/'.$schoolLogo), $html);
    }

    private function extractLogoSrc(string $html): ?string
    {
        return preg_match('/<img src="([^"]+)"/', $html, $m) ? $m[1] : null;
    }
}
