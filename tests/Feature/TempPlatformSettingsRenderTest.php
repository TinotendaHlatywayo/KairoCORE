<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Filament\App\Pages\SystemSettingsPage;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Modules\SaaS\Models\PlatformSetting;
use Tests\TestCase;

/**
 * Upload persistence regressions for Platform Settings (Website Content /
 * SaaS Branding) and System Settings (tenant favicon). Runs against the real
 * dev database like PanelRenderTest; every test restores the values it touches.
 */
class TempPlatformSettingsRenderTest extends TestCase
{
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
    }

    private function actingAsPlatformAdmin(): User
    {
        $user = User::withoutTenantScope()->where('email', 'tinhlats09@gmail.com')->firstOrFail();
        $this->actingAs($user);

        return $user;
    }

    public function test_platform_settings_page_renders(): void
    {
        $this->actingAsPlatformAdmin();

        $response = $this->get('https://lvh.me/platform/platform-settings-page');

        if ($response->getStatusCode() !== 200) {
            $content = $response->getContent();
            $message = preg_match('/<h1[^>]*>(.*?)<\/h1>|class="exception-message"[^>]*>(.*?)</s', $content, $m) ? strip_tags(implode(' ', $m)) : substr(strip_tags($content), 0, 800);
            fwrite(STDERR, "\nSTATUS: {$response->getStatusCode()}\nMESSAGE: {$message}\n");
        }

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_hero_image_upload_persists_to_settings(): void
    {
        $this->actingAsPlatformAdmin();

        $setting = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => '']
        );
        $original = $setting->value;
        $disk = Storage::disk('public');

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.marketing_hero_image', [UploadedFile::fake()->image('hero.jpg', 640, 480)])
            ->call('save');

        $setting->refresh();
        $stored = $setting->value;
        $exists = $stored && ! str_starts_with($stored, 'http') && $disk->exists($stored);

        $resolved = $stored ? marketing_resolve_image($stored) : '';

        // Restore the original value so the real site stays untouched.
        $setting->update(['value' => $original]);
        if ($exists) {
            $disk->delete($stored);
        }

        $this->assertNotSame('', (string) $stored, 'hero_image was not saved');
        $this->assertNotSame($original, $stored, 'hero_image did not change');
        $this->assertTrue($exists, 'uploaded file missing on public disk; stored=' . var_export($stored, true));
        $this->assertStringContainsString($stored, $resolved, 'uploaded path does not resolve to a public URL');
    }

    public function test_untouched_settings_page_save_does_not_wipe_images(): void
    {
        $this->actingAsPlatformAdmin();

        $hero = PlatformSetting::firstOrCreate(['group' => 'marketing', 'key' => 'hero_image'], ['value' => '']);
        $logo = PlatformSetting::firstOrCreate(['group' => 'branding', 'key' => 'platform_logo'], ['value' => '']);
        $favicon = PlatformSetting::firstOrCreate(['group' => 'branding', 'key' => 'platform_favicon'], ['value' => '']);

        $heroBefore = $hero->value;
        $logoBefore = $logo->value;
        $faviconBefore = $favicon->value;

        // A fresh page load + Save with no edits must not blank any image.
        Livewire::test(PlatformSettingsPage::class)->call('save');

        $hero->refresh();
        $logo->refresh();
        $favicon->refresh();

        $failures = [];
        if ($hero->value !== $heroBefore) {
            $failures[] = 'hero_image: ' . var_export($heroBefore, true) . ' -> ' . var_export($hero->value, true);
        }
        if ($logo->value !== $logoBefore) {
            $failures[] = 'platform_logo: ' . var_export($logoBefore, true) . ' -> ' . var_export($logo->value, true);
        }
        if ($favicon->value !== $faviconBefore) {
            $failures[] = 'platform_favicon: ' . var_export($faviconBefore, true) . ' -> ' . var_export($favicon->value, true);
        }

        $this->assertSame([], $failures, 'no-edit save wiped image settings: ' . implode('; ', $failures));
    }

    public function test_platform_favicon_upload_persists_and_keeps_logo(): void
    {
        $this->actingAsPlatformAdmin();

        $favicon = PlatformSetting::firstOrCreate(['group' => 'branding', 'key' => 'platform_favicon'], ['value' => '']);
        $logo = PlatformSetting::firstOrCreate(['group' => 'branding', 'key' => 'platform_logo'], ['value' => '']);
        $originalFavicon = $favicon->value;
        $logoBefore = $logo->value;
        $disk = Storage::disk('public');

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.branding_platform_favicon', [UploadedFile::fake()->image('favicon.png', 64, 64)])
            ->call('save');

        $favicon->refresh();
        $logo->refresh();
        $stored = $favicon->value;
        $exists = $stored && $disk->exists($stored);

        // Restore the original values so the real site stays untouched.
        $favicon->update(['value' => $originalFavicon]);
        if ($exists) {
            $disk->delete($stored);
        }

        $this->assertTrue($exists, 'favicon file missing on public disk; stored=' . var_export($stored, true));
        $this->assertSame($logoBefore, $logo->value, 'platform_logo was wiped by the favicon save');
    }

    public function test_system_settings_favicon_upload_persists(): void
    {
        $school = School::find(3);
        $this->actingAsPlatformAdmin();
        $this->actingAsTenant($school);
        $this->withSession(['current_tenant' => $school]);

        $favicon = \Modules\Admin\Models\SystemSetting::firstOrCreate(
            ['school_id' => 3, 'group' => 'branding', 'key' => 'favicon_path'],
            ['value' => '']
        );
        $original = $favicon->value;
        $disk = Storage::disk('public');

        Livewire::test(SystemSettingsPage::class)
            ->set('data.branding_favicon_path', [UploadedFile::fake()->image('favicon.png', 64, 64)])
            ->call('save');

        $favicon->refresh();
        $stored = $favicon->value;
        $exists = is_string($stored) && $stored !== '' && $disk->exists($stored);

        // Save again with no upload: the stored path must survive.
        Livewire::test(SystemSettingsPage::class)->call('save');
        $favicon->refresh();
        $secondSave = $favicon->value;

        // Restore the original value so the real site stays untouched.
        $favicon->update(['value' => $original]);
        if ($exists) {
            $disk->delete($stored);
        }

        $this->assertTrue($exists, 'favicon file missing on public disk; stored=' . var_export($stored, true));
        $this->assertSame($stored, $secondSave, 'second no-edit save wiped the uploaded favicon');
    }

    public function test_features_repeater_image_upload_persists(): void
    {
        $this->actingAsPlatformAdmin();

        $setting = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'features_list'],
            ['value' => '']
        );
        $original = $setting->value;
        $disk = Storage::disk('public');

        $page = Livewire::test(PlatformSettingsPage::class);
        $list = $page->get('data.marketing_features_list');

        if (! is_array($list) || $list === []) {
            $this->markTestSkipped('features_list state unavailable');
            return;
        }

        $itemKey = array_key_first($list);
        $before = $page->get('data.marketing_features_list.' . $itemKey . '.image');

        $page->set(
            'data.marketing_features_list.' . $itemKey . '.image',
            [UploadedFile::fake()->image('feature.jpg', 640, 480)]
        );
        $page->call('save');

        $setting->refresh();
        $storedList = json_decode((string) $setting->value, true);
        $originalList = json_decode((string) $original, true);

        $originalImages = is_array($originalList)
            ? array_values(array_filter(array_column($originalList, 'image')))
            : [];
        $storedImages = is_array($storedList)
            ? array_values(array_filter(array_column($storedList, 'image')))
            : [];
        $newImages = array_values(array_diff($storedImages, $originalImages));
        $stored = $newImages[0] ?? null;
        $exists = is_string($stored) && str_starts_with($stored, 'platform/website/') && $disk->exists($stored);

        // Restore the original value so the real site stays untouched.
        if ($original !== null && $original !== '') {
            $setting->update(['value' => $original]);
        } else {
            $setting->delete();
        }
        if ($exists) {
            $disk->delete($stored);
        }

        $this->assertTrue(
            $exists,
            'feature image not stored; item=' . var_export($stored, true) . ' before=' . var_export($before, true)
        );
    }
}
