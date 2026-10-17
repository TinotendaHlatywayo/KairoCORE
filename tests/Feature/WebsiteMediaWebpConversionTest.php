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
        // The admin panel only accepts super administrators (school_id = null);
        // database restores may re-home the fixture account under a school, so
        // fall back to any active super administrator.
        $user = User::withoutTenantScope()
                ->where('email', 'tinhlats09@gmail.com')
                ->whereNull('school_id')
                ->first()
            ?? User::withoutTenantScope()
                ->whereNull('school_id')
                ->where('account_status', 'active')
                ->orderBy('id')
                ->firstOrFail();

        $this->actingAs($user);

        return $user;
    }

    /**
     * Ensure a marketing hero_image row exists for the test to assert
     * against, remembering whether it pre-existed. When it did not, the row
     * is a placeholder this test owns and restoreHeroSetting() removes it —
     * otherwise the fixture would leave a dangling pointer on the live site.
     *
     * @return array{0: PlatformSetting, 1: string|null} [row, original value or null if created here]
     */
    private function seedHeroSetting(): array
    {
        $existing = PlatformSetting::where('group', 'marketing')->where('key', 'hero_image')->first();

        if ($existing !== null) {
            return [$existing, (string) $existing->value];
        }

        $row = PlatformSetting::create([
            'group' => 'marketing',
            'key' => 'hero_image',
            'value' => 'platform/website/original-hero.jpg',
        ]);

        return [$row, null];
    }

    /** Restore a pre-existing hero value, or delete a row this test created. */
    private function restoreHeroSetting(PlatformSetting $hero, ?string $original): void
    {
        if ($original === null) {
            $hero->delete();

            return;
        }

        $hero->update(['value' => $original]);
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

        [$hero, $original] = $this->seedHeroSetting();
        $valueBefore = (string) $hero->value;
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

        $publicUntouched = $hero->value === $valueBefore;
        $pendingOk = $pending !== null && Storage::disk('public')->exists($pendingPath);

        // Restore the database and remove the upload this test created.
        $this->restoreHeroSetting($hero, $original);
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

        [$hero, $original] = $this->seedHeroSetting();
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
        $this->restoreHeroSetting($hero, $original);
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

        [$hero, $original] = $this->seedHeroSetting();
        $hero->update(['value' => $path]);

        (new ConvertWebsiteMediaToWebp('marketing', 'hero_image', $path, 30))->handle();

        $webp = preg_replace('/\.[^.]+$/', '.webp', $path);
        $webpExists = $disk->exists($webp);
        $static = $webpExists && ! str_contains((string) $disk->get($webp), 'ANIM');
        $hero->refresh();
        $pointedAtWebp = $hero->value === $webp;
        $sourceDeleted = ! $disk->exists($path);

        // Restore the database and remove fixture files.
        $this->restoreHeroSetting($hero, $original);
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

        [$hero, $original] = $this->seedHeroSetting();
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
        $this->restoreHeroSetting($hero, $original);
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

    public function test_trim_start_at_or_past_video_end_fails_validation(): void
    {
        $twoSeconds = new UploadedFile($this->makeVideo(2.0), 'two.mp4', 'video/mp4', null, true);

        $atEnd = Validator::make(
            ['media' => [$twoSeconds]],
            ['media.*' => [new MaxVideoDuration(8, 2.0)]]
        );
        $pastEnd = Validator::make(
            ['media' => [$twoSeconds]],
            ['media.*' => [new MaxVideoDuration(8, 5.0)]]
        );
        $inside = Validator::make(
            ['media' => [$twoSeconds]],
            ['media.*' => [new MaxVideoDuration(8, 1.5)]]
        );

        $this->assertTrue($atEnd->fails(), 'trim start at the end of a 2-second video must be rejected');
        $this->assertTrue($pastEnd->fails(), 'trim start past the end of a 2-second video must be rejected');
        $this->assertTrue($inside->passes(), 'trim start 1.5 on a 2-second video must be accepted');

        @unlink($twoSeconds->getRealPath());
    }

    public function test_conversion_job_clamps_out_of_range_trim_and_still_produces_animation(): void
    {
        $this->actingAsPlatformAdmin();

        $disk = Storage::disk('public');
        $path = 'platform/website/clamp-clip-'.uniqid().'.mp4';
        $disk->put($path, file_get_contents($this->makeVideo(2.0)));

        [$hero, $original] = $this->seedHeroSetting();
        PlatformSetting::updateOrCreate(
            ['group' => 'marketing', 'key' => 'hero_image_source'],
            ['value' => $path]
        );

        // Trim start 5s on a 2-second video: the job must fall back to
        // converting from the start rather than writing a blank WebP.
        (new ConvertWebsiteMediaToWebp('marketing', 'hero_image', $path, 30, 'hero_image_source', null, 'image', 5.0))->handle();

        $webp = preg_replace('/\.[^.]+$/', '.webp', $path);
        $valid = ConvertWebsiteMediaToWebp::outputLooksLikeWebp($disk->path($webp), true);
        $hero->refresh();
        $pointedAtWebp = $hero->value === $webp;
        $sourceDeleted = ! $disk->exists($path);

        // Restore the database and remove fixture files.
        $this->restoreHeroSetting($hero, $original);
        PlatformSetting::where('group', 'marketing')->where('key', 'hero_image_source')->delete();
        $disk->delete([$path, $webp]);

        $this->assertTrue($valid, 'clamped conversion did not produce a real animated WebP');
        $this->assertTrue($pointedAtWebp, 'hero_image does not point at the clamped webp');
        $this->assertTrue($sourceDeleted, 'source video was not deleted after the clamped conversion');
    }

    public function test_output_validation_rejects_blank_and_still_files(): void
    {
        $disk = Storage::disk('public');

        // The exact artifact ffmpeg leaves when it encodes zero frames.
        $blank = 'platform/website/blank-'.uniqid().'.webp';
        $disk->put($blank, hex2bin('00000000f8ffffff'));

        $still = 'platform/website/still-'.uniqid().'.webp';
        $source = 'platform/website/still-src-'.uniqid().'.png';
        $image = UploadedFile::fake()->image('probe.png', 80, 60);
        $disk->put($source, (string) file_get_contents($image->getRealPath()));
        (new ConvertWebsiteMediaToWebp('marketing', 'validation_probe', $source, 30))->handle();
        $stillPath = preg_replace('/\.[^.]+$/', '.webp', $source);

        $blankRejected = ! ConvertWebsiteMediaToWebp::outputLooksLikeWebp($disk->path($blank), true);
        $stillAccepted = ConvertWebsiteMediaToWebp::outputLooksLikeWebp($disk->path($stillPath), false);
        $stillNotAnimated = ! ConvertWebsiteMediaToWebp::outputLooksLikeWebp($disk->path($stillPath), true);
        $missingRejected = ! ConvertWebsiteMediaToWebp::outputLooksLikeWebp($disk->path('platform/website/nope.webp'), false);

        // The still job persists its result under the probe key — remove it.
        PlatformSetting::where('group', 'marketing')->where('key', 'validation_probe')->delete();
        $disk->delete([$blank, $source, $stillPath]);

        $this->assertTrue($blankRejected, 'the 8-byte blank artifact must be rejected');
        $this->assertTrue($stillAccepted, 'a real still WebP must pass the non-animated check');
        $this->assertTrue($stillNotAnimated, 'a still WebP must fail the animated check');
        $this->assertTrue($missingRejected, 'a missing file must be rejected');
    }

    public function test_conversion_job_honours_trim_start(): void
    {
        $this->actingAsPlatformAdmin();

        $disk = Storage::disk('public');
        $path = 'platform/website/trim-clip-'.uniqid().'.mp4';
        $disk->put($path, file_get_contents($this->makeVideo(10.0)));

        [$hero, $original] = $this->seedHeroSetting();
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
        $this->restoreHeroSetting($hero, $original);
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

        [$hero, $original] = $this->seedHeroSetting();
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
        $this->restoreHeroSetting($hero, $original);
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

        [$hero, $original] = $this->seedHeroSetting();
        $valueBefore = (string) $hero->value;
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
        $publicUntouched = $hero->value === $valueBefore;
        $redisplaysPending = is_array($boxValue)
            && in_array($pendingPath, array_values($boxValue), true)
            && $pendingPath !== '';

        // Restore the database and remove the upload this test created.
        $this->restoreHeroSetting($hero, $original);
        $pending?->delete();
        if ($pendingPath !== '') {
            Storage::disk('public')->delete($pendingPath);
        }

        $this->assertTrue($publicUntouched, 'hero_image changed while the video was pending');
        $this->assertTrue($redisplaysPending, 'reloaded page did not re-display the pending video');
    }

    /**
     * The browser's file-upload blade calls $wire.getFormUploadedFiles(path)
     * (state watch / pre-save scan). A save dehydrates a feature-card image
     * to a plain string path and echoes it back into $this->data without
     * re-wrapping, so that next call reached Filament's foreach()-over-state
     * with a string and 500'd. Reproduces the real sequence: upload → save →
     * blade call.
     */
    public function test_get_form_uploaded_files_wraps_string_feature_image_state(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $stored = PlatformSetting::where('group', 'marketing')->where('key', 'features_list')->first();
        $originalList = $stored?->value;

        $component = Livewire::test(PlatformSettingsPage::class);

        $list = $component->get('data.marketing_features_list');
        $this->assertIsArray($list);
        $itemKey = array_key_first($list);
        $statePath = 'data.marketing_features_list.'.$itemKey.'.image';

        $component
            ->set($statePath, [UploadedFile::fake()->create('echo-clip.mp4', 256, 'video/mp4')])
            ->call('save');

        $pushedPath = null;
        Queue::assertPushed(
            ConvertWebsiteMediaToWebp::class,
            function (ConvertWebsiteMediaToWebp $job) use (&$pushedPath): bool {
                $pushedPath = $job->path;

                return $job->key === 'features_list';
            }
        );

        // Post-save echo: the dehydrated single-file state is a bare string.
        $itemKey = array_key_first($component->get('data.marketing_features_list'));
        $statePath = 'data.marketing_features_list.'.$itemKey.'.image';
        $echoed = $component->get($statePath);
        $this->assertIsString($echoed, 'expected the save to echo a dehydrated string path back into data');

        // The blade's call must not throw, and it must leave an array state.
        $component->call('getFormUploadedFiles', $statePath);

        $wrapped = $component->get($statePath);
        $this->assertIsArray($wrapped, 'string feature image state was not wrapped into an array');
        $this->assertContains($echoed, array_values($wrapped));

        // Restore the stored list and remove the upload this test created.
        if ($stored) {
            $stored->update(['value' => $originalList]);
        }
        if ($pushedPath !== null) {
            Storage::disk('public')->delete($pushedPath);
        }
    }

    /**
     * A feature-card video upload must save, queue conversion with the card's
     * trim start, and remember the pending upload under image_source while the
     * public list keeps serving the previous image.
     */
    public function test_feature_card_video_saves_and_queues_conversion(): void
    {
        Queue::fake();
        $this->actingAsPlatformAdmin();

        $stored = PlatformSetting::where('group', 'marketing')->where('key', 'features_list')->first();
        $originalList = $stored?->value;

        $component = Livewire::test(PlatformSettingsPage::class);
        $list = $component->get('data.marketing_features_list');
        $itemKey = array_key_first($list);
        $statePath = 'data.marketing_features_list.'.$itemKey.'.image';

        $component
            ->set($statePath, [UploadedFile::fake()->create('feature-clip.mp4', 256, 'video/mp4')])
            ->set('data.marketing_features_list.'.$itemKey.'.trim_start', '4')
            ->call('save');

        $pushedJob = null;
        Queue::assertPushed(
            ConvertWebsiteMediaToWebp::class,
            function (ConvertWebsiteMediaToWebp $job) use (&$pushedJob): bool {
                $pushedJob = $job;

                return $job->key === 'features_list'
                    && str_ends_with($job->path, '.mp4')
                    && $job->trimStart === 4.0;
            }
        );

        $saved = json_decode(
            (string) PlatformSetting::where('group', 'marketing')->where('key', 'features_list')->value('value'),
            true
        );
        $savedItem = $saved[$itemKey] ?? $saved[array_key_first($saved)] ?? null;
        $pendingRecorded = is_array($savedItem)
            && str_ends_with((string) ($savedItem['image_source'] ?? ''), '.mp4');
        $publicImageKept = is_array($savedItem)
            && ($savedItem['image'] ?? null) !== ($savedItem['image_source'] ?? null);

        // Restore the stored list and remove the upload this test created.
        if ($stored) {
            $stored->update(['value' => $originalList]);
        }
        if ($pushedJob !== null) {
            Storage::disk('public')->delete($pushedJob->path);
        }

        $this->assertTrue($pendingRecorded, 'pending feature video was not recorded under image_source');
        $this->assertTrue($publicImageKept, 'public feature image was not kept while the video converts');
    }
}
