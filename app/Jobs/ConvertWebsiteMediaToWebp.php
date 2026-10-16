<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\SaaS\Models\PlatformSetting;

/**
 * Converts a website upload (photo, GIF or short video) to WebP on the
 * public disk — GIFs and videos become animated WebPs at the configured
 * frame rate — then points the owning PlatformSetting at the result and
 * deletes the source file. Dispatched from Platform Settings → Website
 * Content and executed by the existing queue:work cron, so the admin
 * never waits on ffmpeg.
 */
class ConvertWebsiteMediaToWebp implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 15;

    /** Extensions rendered as an animated WebP (videos + GIF). */
    public const ANIMATED_EXTENSIONS = [
        'mp4', 'mov', 'm4v', 'webm', 'avi', 'mpg', 'mpeg', 'mkv',
        '3gp', 'mts', 'wmv', 'flv', 'ogv', 'gif',
    ];

    /** Extensions converted to a single (static) WebP image. */
    public const STILL_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'bmp', 'tif', 'tiff', 'avif', 'jfif',
    ];

    /** Hard cap applied by ffmpeg even when validation could not run. */
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

    /**
     * Is $path a real WebP file (RIFF/WEBP header)? When $animated, it must
     * also carry the ANIM chunk so a frame-less or still result can never be
     * published as a video clip.
     */
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

        // Scan the RIFF chunks for the ANIM marker (animated WebP only).
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

                // Chunks are padded to even sizes.
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

    /** Container duration in seconds via ffprobe, or null when unavailable. */
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
            Log::warning('WebP conversion skipped: source file missing.', [
                'group' => $this->group,
                'key' => $this->key,
                'path' => $this->path,
            ]);
            $this->dropPendingMarker();

            return;
        }

        if (self::pathExtension($this->path) === 'webp') {
            return;
        }

        $source = $disk->path($this->path);
        $webpRelative = preg_replace('/\.[^.]+$/', '', $this->path).'.webp';
        $target = $disk->path($webpRelative);

        $fps = in_array($this->fps, [10, 20, 30], true) ? $this->fps : 30;

        // The clip is always MAX_SOURCE_SECONDS long, starting at trimStart
        // into the source. -ss before -i seeks in the (fast) input stream.
        //
        // A trim start at or past the end of the video makes ffmpeg emit zero
        // frames while still exiting 0 and leaving a blank (frame-less) file —
        // which renders as an empty image slot on the site. Clamp the seek into
        // the source's real duration so a clip is always produced.
        $animated = self::isAnimatedMediaPath($this->path);
        $seek = '';

        if ($this->trimStart > 0 && $animated) {
            $duration = self::probeDurationSeconds($source);
            $maxStart = $duration !== null ? max(0.0, $duration - 0.5) : null;

            if ($maxStart === null) {
                // Cannot measure the source here; trust the validated upload
                // and let the output check below catch an empty result.
                $seek = '-ss '.escapeshellarg(sprintf('%.3F', $this->trimStart)).' ';
            } elseif ($this->trimStart <= $maxStart) {
                $seek = '-ss '.escapeshellarg(sprintf('%.3F', $this->trimStart)).' ';
            } else {
                Log::warning('WebP conversion: trim start is past the end of the video; converting from the start instead.', [
                    'group' => $this->group,
                    'key' => $this->key,
                    'path' => $this->path,
                    'trim_start' => $this->trimStart,
                    'duration' => $duration,
                ]);
            }
        }

        $command = $animated
            ? sprintf(
                'ffmpeg -y -v error %s-t %d -i %s -vf %s -c:v libwebp -q:v 75 -compression_level 6 -preset default -loop 0 -pix_fmt yuva420p -an %s 2>&1',
                $seek,
                self::MAX_SOURCE_SECONDS,
                escapeshellarg($source),
                escapeshellarg("fps={$fps},scale=min(1920\\,iw):-2"),
                escapeshellarg($target)
            )
            : sprintf(
                'ffmpeg -y -v error -i %s -frames:v 1 -c:v libwebp -q:v 82 -compression_level 6 -pix_fmt yuva420p %s 2>&1',
                escapeshellarg($source),
                escapeshellarg($target)
            );

        $output = [];
        $exitCode = 1;

        try {
            exec($command, $output, $exitCode);
        } catch (\Throwable $e) {
            $output[] = $e->getMessage();
        }

        // ffmpeg exits 0 even when it encoded nothing (e.g. a seek past the
        // end of the source), leaving a blank, unrenderable file. Require a
        // real WebP — RIFF/WEBP header, and the ANIM chunk for videos/GIF —
        // before pointing the setting at the result and deleting the source.
        if ($exitCode !== 0 || ! self::outputLooksLikeWebp($target, $animated)) {
            Log::error('WebP conversion failed; keeping the original upload.', [
                'group' => $this->group,
                'key' => $this->key,
                'path' => $this->path,
                'exit_code' => $exitCode,
                'animated' => $animated,
                'output' => implode("\n", $output),
            ]);

            // Remove the blank/partial artifact so it cannot be served.
            if (is_file($target)) {
                @unlink($target);
            }

            return;
        }

        if ($this->itemKey !== null && $this->itemKey !== '') {
            $this->writeListItem($webpRelative);
        } else {
            PlatformSetting::updateOrCreate(
                [
                    'group' => $this->group,
                    'key' => $this->key,
                ],
                [
                    'value' => $webpRelative,
                ]
            );

            // Remove the pending-video marker only when it still points at
            // the file this job consumed (a newer upload may have replaced it).
            if ($this->sourceKey !== null && $this->sourceKey !== '') {
                PlatformSetting::where('group', $this->group)
                    ->where('key', $this->sourceKey)
                    ->where('value', $this->path)
                    ->delete();
            }
        }

        $disk->delete($this->path);
    }

    /** Point a features-card image at the converted WebP inside its JSON list. */
    protected function writeListItem(string $webpRelative): void
    {
        $setting = PlatformSetting::where('group', $this->group)
            ->where('key', $this->key)
            ->first();

        if (! $setting) {
            Log::warning('WebP conversion: settings row disappeared.', [
                'group' => $this->group,
                'key' => $this->key,
            ]);

            return;
        }

        $list = json_decode((string) $setting->value, true);
        if (! is_array($list)) {
            $list = [];
        }

        // Match by the media this job consumed: pending videos live under
        // "image_source", still photos under "image". (Hidden form fields
        // never reach the persisted list, so structural item keys are not
        // available here — the path itself identifies the card.)
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
            Log::warning('WebP conversion: feature item not found in list.', [
                'group' => $this->group,
                'key' => $this->key,
                'path' => $this->path,
            ]);

            return;
        }

        $list[$target][$this->field] = $webpRelative;
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
