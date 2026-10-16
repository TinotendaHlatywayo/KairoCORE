<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\PlatformSettingsPage;
use App\Jobs\ConvertWebsiteMediaToWebp;
use App\Models\User;
use App\Rules\MaxVideoDuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;
use Modules\SaaS\Models\PlatformSetting;
use Tests\TestCase;

/**
 * Website media pipeline: image/GIF/video uploads in Platform Settings →
 * Website Content convert to WebP (animated for GIF/video) in the
 * background. Runs against the real dev database like
 * TempPlatformSettingsRenderTest; every test restores what it touches.
 */
class WebsiteMediaWebpConversionTest extends TestCase
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

    /** Render a real H.264 MP4 fixture of $seconds with ffmpeg. */
    private function makeVideo(float $seconds): string
    {
        $path = sys_get_temp_dir().'/sms-clip-'.uniqid().'.mp4';
        $output = [];
        $code = 1;
        exec(
            sprintf(
                'ffmpeg -y -v error -f lavfi -i testsrc=duration=%s:size=320x240:rate=25 -c:v libx264 -pix_fmt yuv420p %s 2>&1',
                $seconds,
                escapeshellarg($path)
            ),
            $output,
            $code
        );
        $this->assertSame(0, $code, 'ffmpeg fixture failed: '.implode("\n", $output));

        return $path;
    }

    public function test_video_upload_keeps_public_value_and_queues_conversion(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/original-hero.jpg']
        );
        $original = $hero->value;
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.marketing_hero_image', [UploadedFile::fake()->create('hero-clip.mp4', 512, 'video/mp4')])
            ->call('save');

        $hero->refresh();
        $pending = PlatformSetting::where('group', 'marketing')
            ->where('key', 'hero_image_source')
            ->first();
        $pendingPath = (string) ($pending?->value);

        Queue::assertPushed(
            ConvertWebsiteMediaToWebp::class,
            fn (ConvertWebsiteMediaToWebp $job) => $job->key === 'hero_image'
                && str_ends_with($job->path, '.mp4')
                && $job->sourceKey === 'hero_image_source'
        );

        $publicUntouched = $hero->value === $original;
        $pendingOk = $pending !== null && Storage::disk('public')->exists($pendingPath);

        // Restore the database and remove the upload this test created.
        $hero->update(['value' => $original]);
        $pending?->delete();
        if ($pendingPath !== '') {
            Storage::disk('public')->delete($pendingPath);
        }

        $this->assertTrue($publicUntouched, 'hero_image changed while the video was still queued');
        $this->assertTrue($pendingOk, 'pending-video marker missing or file not stored');
    }

    public function test_conversion_job_produces_animated_webp_and_cleans_up(): void
    {
        $this->actingAsPlatformAdmin();

        $disk = Storage::disk('public');
        $path = 'platform/website/job-clip-'.uniqid().'.mp4';
        $disk->put($path, file_get_contents($this->makeVideo(2.0)));

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/before-video.jpg']
        );
        $original = $hero->value;
        PlatformSetting::updateOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image_source'],
            ['value' => $path]
        );

        (new ConvertWebsiteMediaToWebp('marketing', 'hero_image', $path, 30, 'hero_image_source'))->handle();

        $webp = preg_replace('/\.[^.]+$/', '.webp', $path);
        $webpExists = $disk->exists($webp);
        $animated = $webpExists && str_contains((string) $disk->get($webp), 'ANIM');
        $hero->refresh();
        $pointedAtWebp = $hero->value === $webp;
        $pendingGone = ! PlatformSetting::where('group', 'marketing')
            ->where('key', 'hero_image_source')
            ->exists();
        $sourceDeleted = ! $disk->exists($path);

        // Restore the database and remove fixture files.
        $hero->update(['value' => $original]);
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();
        $disk->delete([$path, $webp]);

        $this->assertTrue($webpExists, 'webp was not created');
        $this->assertTrue($animated, 'webp is not animated (ANIM chunk missing)');
        $this->assertTrue($pointedAtWebp, 'hero_image does not point at the webp');
        $this->assertTrue($pendingGone, 'pending-video marker row survived the job');
        $this->assertTrue($sourceDeleted, 'source video was not deleted');
    }

    public function test_still_image_job_converts_to_static_webp(): void
    {
        $this->actingAsPlatformAdmin();

        $disk = Storage::disk('public');
        $image = UploadedFile::fake()->image('still.png', 120, 90);
        $path = 'platform/website/job-still-'.uniqid().'.png';
        $disk->put($path, (string) file_get_contents($image->getRealPath()));

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/before-still.jpg']
        );
        $original = $hero->value;
        $hero->update(['value' => $path]);

        (new ConvertWebsiteMediaToWebp('marketing', 'hero_image', $path, 30))->handle();

        $webp = preg_replace('/\.[^.]+$/', '.webp', $path);
        $webpExists = $disk->exists($webp);
        $static = $webpExists && ! str_contains((string) $disk->get($webp), 'ANIM');
        $hero->refresh();
        $pointedAtWebp = $hero->value === $webp;
        $sourceDeleted = ! $disk->exists($path);

        // Restore the database and remove fixture files.
        $hero->update(['value' => $original]);
        $disk->delete([$path, $webp]);

        $this->assertTrue($webpExists, 'webp was not created from the still image');
        $this->assertTrue($static, 'still-image webp should not be animated');
        $this->assertTrue($pointedAtWebp, 'hero_image does not point at the webp');
        $this->assertTrue($sourceDeleted, 'source image was not deleted after conversion');
    }

    public function test_fps_setting_is_passed_to_queued_conversion(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $fpsRow = PlatformSetting::where('group', 'media')->where('key', 'video_fps')->first();
        $fpsOriginal = $fpsRow?->value;
        PlatformSetting::set('media', 'video_fps', '10');

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/fps-hero.jpg']
        );
        $original = $hero->value;
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.marketing_hero_image', [UploadedFile::fake()->create('fps-clip.mp4', 256, 'video/mp4')])
            ->call('save');

        $pending = PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->first();
        $pendingPath = (string) ($pending?->value);

        Queue::assertPushed(
            ConvertWebsiteMediaToWebp::class,
            fn (ConvertWebsiteMediaToWebp $job) => $job->fps === 10
        );

        // Restore the database and remove the upload this test created.
        if ($fpsRow) {
            $fpsRow->update(['value' => $fpsOriginal]);
        } else {
            PlatformSetting::where('group', 'media')->where('key', 'video_fps')->delete();
        }
        $hero->update(['value' => $original]);
        $pending?->delete();
        if ($pendingPath !== '') {
            Storage::disk('public')->delete($pendingPath);
        }

        $this->addToAssertionCount(1); // Queue::assertPushed above
    }

    public function test_videos_over_eight_seconds_fail_validation(): void
    {
        $tooLong = new UploadedFile($this->makeVideo(9.0), 'long.mp4', 'video/mp4', null, true);
        $short = new UploadedFile($this->makeVideo(2.0), 'short.mp4', 'video/mp4', null, true);

        $longResult = Validator::make(
            ['media' => [$tooLong]],
            ['media.*' => [new MaxVideoDuration(8)]]
        );
        $shortResult = Validator::make(
            ['media' => [$short]],
            ['media.*' => [new MaxVideoDuration(8)]]
        );

        $this->assertTrue($longResult->fails(), 'a 9-second video must be rejected');
        $this->assertTrue($shortResult->passes(), 'a 2-second video must be accepted');

        @unlink($tooLong->getRealPath());
        @unlink($short->getRealPath());
    }

    public function test_trim_start_allows_a_longer_source_video(): void
    {
        $tenSeconds = new UploadedFile($this->makeVideo(10.0), 'ten.mp4', 'video/mp4', null, true);

        $withoutTrim = Validator::make(
            ['media' => [$tenSeconds]],
            ['media.*' => [new MaxVideoDuration(8)]]
        );
        $withTrim = Validator::make(
            ['media' => [$tenSeconds]],
            ['media.*' => [new MaxVideoDuration(8, 2.0)]]
        );

        $this->assertTrue($withoutTrim->fails(), 'a 10-second video must be rejected without a trim start');
        $this->assertTrue($withTrim->passes(), 'a 10-second video must be accepted when trim start 2 yields an 8-second clip');

        @unlink($tenSeconds->getRealPath());
    }

    public function test_trim_start_still_rejects_videos_the_window_cannot_cover(): void
    {
        $tenSeconds = new UploadedFile($this->makeVideo(10.0), 'ten.mp4', 'video/mp4', null, true);

        $result = Validator::make(
            ['media' => [$tenSeconds]],
            ['media.*' => [new MaxVideoDuration(8, 1.0)]]
        );

        $this->assertTrue($result->fails(), 'trim start 1 on a 10-second video needs 9 seconds — must be rejected');

        @unlink($tenSeconds->getRealPath());
    }

    public function test_conversion_job_honours_trim_start(): void
    {
        $this->actingAsPlatformAdmin();

        $disk = Storage::disk('public');
        $path = 'platform/website/trim-clip-'.uniqid().'.mp4';
        $disk->put($path, file_get_contents($this->makeVideo(10.0)));

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/before-trim.jpg']
        );
        $original = $hero->value;
        PlatformSetting::updateOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image_source'],
            ['value' => $path]
        );

        (new ConvertWebsiteMediaToWebp('marketing', 'hero_image', $path, 30, 'hero_image_source', null, 'image', 2.0))->handle();

        $webp = preg_replace('/\.[^.]+$/', '.webp', $path);
        $webpExists = $disk->exists($webp);
        $animated = $webpExists && str_contains((string) $disk->get($webp), 'ANIM');
        $hero->refresh();
        $pointedAtWebp = $hero->value === $webp;
        $sourceDeleted = ! $disk->exists($path);

        // Restore the database and remove fixture files.
        $hero->update(['value' => $original]);
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();
        $disk->delete([$path, $webp]);

        $this->assertTrue($webpExists, 'webp was not created from the trimmed window');
        $this->assertTrue($animated, 'trimmed webp is not animated');
        $this->assertTrue($pointedAtWebp, 'hero_image does not point at the trimmed webp');
        $this->assertTrue($sourceDeleted, 'source video was not deleted after trimmed conversion');
    }

    public function test_save_passes_trim_start_to_queued_job_and_persists_it(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/trim-save-hero.jpg']
        );
        $original = $hero->value;
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_trim_start')->delete();

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.marketing_hero_image', [UploadedFile::fake()->create('trim-save-clip.mp4', 512, 'video/mp4')])
            ->set('data.marketing_hero_image_trim_start', '2')
            ->call('save');

        $pending = PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->first();
        $pendingPath = (string) ($pending?->value);
        $trimPersisted = PlatformSetting::where('group', 'marketing')
            ->where('key', 'hero_image_trim_start')
            ->value('value');

        Queue::assertPushed(
            ConvertWebsiteMediaToWebp::class,
            fn (ConvertWebsiteMediaToWebp $job) => $job->key === 'hero_image'
                && str_ends_with($job->path, '.mp4')
                && $job->trimStart === 2.0
        );

        // Restore the database and remove the upload this test created.
        $hero->update(['value' => $original]);
        $pending?->delete();
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_trim_start')->delete();
        if ($pendingPath !== '') {
            Storage::disk('public')->delete($pendingPath);
        }

        $this->assertSame('2', (string) $trimPersisted, 'trim start was not persisted as a setting');
    }

    public function test_no_edit_save_does_not_requeue_and_pending_video_redisplays(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $hero = PlatformSetting::firstOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image'],
            ['value' => 'platform/website/idempotent-hero.jpg']
        );
        $original = $hero->value;
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();

        Livewire::test(PlatformSettingsPage::class)
            ->set('data.marketing_hero_image', [UploadedFile::fake()->create('once-clip.mp4', 256, 'video/mp4')])
            ->call('save');

        Queue::assertPushed(ConvertWebsiteMediaToWebp::class, 1);

        $pending = PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->first();
        $pendingPath = (string) ($pending?->value);

        // A fresh page load must re-display the pending video, and saving
        // it again unchanged must not queue a second conversion.
        $reloaded = Livewire::test(PlatformSettingsPage::class);
        $boxValue = $reloaded->get('data.marketing_hero_image');
        $reloaded->call('save');

        Queue::assertPushed(ConvertWebsiteMediaToWebp::class, 1);
        $hero->refresh();
        $publicUntouched = $hero->value === $original;
        $redisplaysPending = is_array($boxValue)
            && in_array($pendingPath, array_values($boxValue), true)
            && $pendingPath !== '';

        // Restore the database and remove the upload this test created.
        $hero->update(['value' => $original]);
        $pending?->delete();
        if ($pendingPath !== '') {
            Storage::disk('public')->delete($pendingPath);
        }

        $this->assertTrue($publicUntouched, 'hero_image changed while the video was pending');
        $this->assertTrue($redisplaysPending, 'reloaded page did not re-display the pending video');
    }
}
