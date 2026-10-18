<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\SaaS\Models\PlatformSetting;

/**
   Converts website uploads (photos, GIFs or videos) to optimized web media.
   Videos produce a Desktop MP4, Mobile MP4, and Poster WebP.
   Stills convert to WebP. Preserves legacy animated WebP / still images.
 */
class ConvertWebsiteMediaToWebp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    public const ANIMATED_EXTENSIONS = [
        'mp4', 'mov', 'm4v', 'webm', 'avi', 'mpg', 'mpeg', 'mkv',
        '3gp', 'mts', 'wmv', 'flv', 'ogv', 'gif',
    ];

    public const STILL_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'bmp', 'tif', 'tiff', 'avif', 'jfif',
    ];

    public const MAX_SOURCE_SECONDS = 8;

    public function __construct(
        public string $group,
        public string $key,
        public string $path,
        public int $fps = 30,
        public ?string $sourceKey = null,
        public ?string $itemKey = null,
        public string $field = 'image',
        public float $trimStart = 0.0,
    ) {}

    public static function isAnimatedMediaPath(string $path): bool
    {
        $ext = self::pathExtension($path);

        return $ext !== null && in_array($ext, self::ANIMATED_EXTENSIONS, true);
    }

    public static function isStillImagePath(string $path): bool
    {
        $ext = self::pathExtension($path);

        return $ext !== null && in_array($ext, self::STILL_EXTENSIONS, true);
    }

    protected static function pathExtension(string $path): ?string
    {
        if (str_contains($path, '://')) {
            return null;
        }

        $ext = strtolower(pathinfo((string) parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));

        return $ext !== '' ? $ext : null;
    }

    public static function outputLooksLikeWebp(string $path, bool $animated): bool
    {
        if (! is_file($path) || filesize($path) < 64) {
            return false;
        }

        $header = (string) @file_get_contents($path, false, null, 0, 12);
        if (strlen($header) < 12 || substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WEBP') {
            return false;
        }

        if (! $animated) {
            return true;
        }

        $size = filesize($path);
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }

        try {
            fseek($handle, 12);
            $found = false;

            while (ftell($handle) + 8 <= $size) {
                $chunkHeader = fread($handle, 8);
                if (strlen($chunkHeader) < 8) {
                    break;
                }

                $id = substr($chunkHeader, 0, 4);
                $length = unpack('V', substr($chunkHeader, 4, 4))[1];

                if ($id === 'ANIM') {
                    $found = true;
                    break;
                }

                $skip = $length + ($length % 2);
                if (fseek($handle, $skip, SEEK_CUR) !== 0) {
                    break;
                }
            }

            return $found;
        } finally {
            fclose($handle);
        }
    }

    public static function outputLooksLikeValidVideo(string $path): bool
    {
        if (! is_file($path) || filesize($path) < 1024) {
            return false;
        }
        $binary = trim((string) @shell_exec('command -v ffprobe 2>/dev/null'));
        if ($binary === '') {
            return true;
        }

        $output = @shell_exec(
            escapeshellarg($binary)
            .' -v error -select_streams v:0 -show_entries stream=duration,nb_frames -of default=nw=1:nk=1 '
            .escapeshellarg($path)
            .' 2>/dev/null'
        );

        return is_string($output) && trim($output) !== '';
    }

    protected static function probeDurationSeconds(string $path): ?float
    {
        $binary = trim((string) @shell_exec('command -v ffprobe 2>/dev/null'));
        if ($binary === '') {
            return null;
        }

        $output = @shell_exec(
            escapeshellarg($binary)
            .' -v error -show_entries format=duration -of default=nw=1:nk=1 '
            .escapeshellarg($path)
            .' 2>/dev/null'
        );

        if (! is_string($output)) {
            return null;
        }

        $seconds = (float) trim($output);

        return $seconds > 0 ? $seconds : null;
    }

    public function handle(): void
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($this->path)) {
            Log::warning('Media conversion skipped: source file missing.', [
                'group' => $this->group,
                'key' => $this->key,
                'path' => $this->path,
            ]);
            $this->dropPendingMarker();

            return;
        }

        $ext = self::pathExtension($this->path);
        if ($ext === 'webp') {
            return;
        }

        $source = $disk->path($this->path);
        $baseRel = preg_replace('/\.[^.]+$/', '', $this->path);
        $isStill = self::isStillImagePath($this->path);

        $seek = '';
        if ($this->trimStart > 0) {
            $duration = self::probeDurationSeconds($source);
            $maxStart = $duration !== null ? max(0.0, $duration - 0.5) : null;
            if ($maxStart === null || $this->trimStart <= $maxStart) {
                $seek = '-ss '.escapeshellarg(sprintf('%.3F', $this->trimStart)).' ';
            }
        }

        if ($isStill) {
            $targetRel = $baseRel.'.webp';
            $target = $disk->path($targetRel);
            $command = sprintf(
                'nice -n 19 ffmpeg -y -v error -i %s -frames:v 1 -c:v libwebp -q:v 82 -compression_level 6 -pix_fmt yuva420p %s 2>&1',
                escapeshellarg($source),
                escapeshellarg($target)
            );

            $output = [];
            $exitCode = 1;
            try {
                exec($command, $output, $exitCode);
            } catch (\Throwable $e) {}

            if ($exitCode !== 0 || ! self::outputLooksLikeWebp($target, false)) {
                if (is_file($target)) { @unlink($target); }
                return;
            }

            $this->persistResult($targetRel);
            $disk->delete($this->path);
            return;
        }

        // If GIF, treat as animated WebP for backward compatibility
        if ($ext === 'gif') {
            $targetRel = $baseRel.'.webp';
            $target = $disk->path($targetRel);
            $fps = in_array($this->fps, [10, 20, 30], true) ? $this->fps : 30;
            $command = sprintf(
                'nice -n 19 ffmpeg -y -v error %s-t %d -i %s -vf %s -c:v libwebp -q:v 75 -compression_level 6 -preset default -loop 0 -pix_fmt yuva420p -an %s 2>&1',
                $seek,
                self::MAX_SOURCE_SECONDS,
                escapeshellarg($source),
                escapeshellarg("fps={$fps},scale=min(1920\\,iw):-2"),
                escapeshellarg($target)
            );
            $output = [];
            $exitCode = 1;
            try {
                exec($command, $output, $exitCode);
            } catch (\Throwable $e) {}

            if ($exitCode !== 0 || ! self::outputLooksLikeWebp($target, true)) {
                if (is_file($target)) { @unlink($target); }
                return;
            }

            $this->persistResult($targetRel);
            $disk->delete($this->path);
            return;
        }

        // Video upload pipeline: Desktop MP4, Mobile MP4, Poster WebP
        $desktopRel = $baseRel.'-720.mp4';
        $mobileRel = $baseRel.'-480.mp4';
        $posterRel = $baseRel.'-poster.webp';

        $desktopPath = $disk->path($desktopRel);
        $mobilePath = $disk->path($mobileRel);
        $posterPath = $disk->path($posterRel);

        $fps = in_array($this->fps, [10, 20, 30], true) ? $this->fps : 30;

        $cmdDesktop = sprintf(
            'nice -n 19 ffmpeg -y -v error %s-t %d -i %s -an -vf "scale=\'min(1280,iw)\':-2,fps=%d" -c:v libx264 -profile:v high -pix_fmt yuv420p -crf 28 -preset slow -movflags +faststart %s 2>&1',
            $seek,
            self::MAX_SOURCE_SECONDS,
            escapeshellarg($source),
            $fps,
            escapeshellarg($desktopPath)
        );

        $cmdMobile = sprintf(
            'nice -n 19 ffmpeg -y -v error %s-t %d -i %s -an -vf "scale=\'min(720,iw)\':-2,fps=24" -c:v libx264 -profile:v high -pix_fmt yuv420p -crf 32 -preset slow -movflags +faststart %s 2>&1',
            $seek,
            self::MAX_SOURCE_SECONDS,
            escapeshellarg($source),
            escapeshellarg($mobilePath)
        );

        $cmdPoster = sprintf(
            'nice -n 19 ffmpeg -y -v error %s-i %s -frames:v 1 -vf "scale=\'min(1280,iw)\':-2" -c:v libwebp -q:v 80 %s 2>&1',
            $seek,
            escapeshellarg($source),
            escapeshellarg($posterPath)
        );

        $exitCode = 0;
        foreach ([$cmdDesktop, $cmdMobile, $cmdPoster] as $cmd) {
            $out = [];
            $code = 0;
            exec($cmd, $out, $code);
            if ($code !== 0) {
                $exitCode = $code;
            }
        }

        if ($exitCode !== 0 || ! self::outputLooksLikeValidVideo($desktopPath) || ! self::outputLooksLikeValidVideo($mobilePath) || ! is_file($posterPath)) {
            Log::error('Media conversion failed; keeping original.', ['group' => $this->group, 'key' => $this->key, 'path' => $this->path]);
            foreach ([$desktopPath, $mobilePath, $posterPath] as $p) {
                if (is_file($p)) { @unlink($p); }
            }
            if ($this->job && ! $this->job instanceof \Illuminate\Queue\Jobs\SyncJob && $this->job->attempts() < $this->tries) {
                $this->release(60);
            }
            return;
        }

        $package = json_encode([
            'type' => 'video',
            'desktop' => $desktopRel,
            'mobile' => $mobileRel,
            'poster' => $posterRel,
        ]);

        $this->persistResult($package);
        $disk->delete($this->path);
    }

    protected function persistResult(string $value): void
    {
        if ($this->itemKey !== null && $this->itemKey !== '') {
            $this->writeListItem($value);
        } else {
            PlatformSetting::updateOrCreate(
                [
                    'group' => $this->group,
                    'key' => $this->key,
                ],
                [
                    'value' => $value,
                ]
            );

            if ($this->sourceKey !== null && $this->sourceKey !== '') {
                PlatformSetting::where('group', $this->group)
                    ->where('key', $this->sourceKey)
                    ->where('value', $this->path)
                    ->delete();
            }
        }
    }

    protected function writeListItem(string $value): void
    {
        $setting = PlatformSetting::where('group', $this->group)
            ->where('key', $this->key)
            ->first();

        if (! $setting) {
            Log::warning('Media conversion: settings row disappeared.', [
                'group' => $this->group,
                'key' => $this->key,
            ]);

            return;
        }

        $list = json_decode((string) $setting->value, true);
        if (! is_array($list)) {
            $list = [];
        }

        $target = null;
        foreach ($list as $i => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (($item['image_source'] ?? null) === $this->path
                || ($item['image'] ?? null) === $this->path) {
                $target = $i;
                break;
            }
        }

        if ($target === null) {
            Log::warning('Media conversion: feature item not found in list.', [
                'group' => $this->group,
                'key' => $this->key,
                'path' => $this->path,
            ]);

            return;
        }

        $list[$target][$this->field] = $value;
        unset($list[$target]['image_source']);
        $setting->update(['value' => json_encode($list)]);
    }

    protected function dropPendingMarker(): void
    {
        if ($this->sourceKey !== null && $this->sourceKey !== '') {
            PlatformSetting::where('group', $this->group)
                ->where('key', $this->sourceKey)
                ->where('value', $this->path)
                ->delete();
        }
    }
}
