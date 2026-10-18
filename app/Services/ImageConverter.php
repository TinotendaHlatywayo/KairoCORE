<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Server-side JPEG/PNG -> WebP conversion for freshly stored uploads, so the
 * files every page renders are smaller with zero client-side work.
 *
 * Conversion runs only against paths handed over by a store operation in the
 * current request (wrapped Filament storage callback / manual ->store()
 * sites). Every failure path returns the original path unchanged - an upload
 * must never break because of this service - and when the GD WebP encoder is
 * unavailable (image not rebuilt yet) conversion is skipped silently.
 */
final class ImageConverter
{
    /** Matches the ffmpeg background pipeline's WebP quality. */
    public const QUALITY = 82;

    /**
     * Upload targets whose files must keep their original format: payment
     * receipts and assessment/homework/leave/admission documents are evidence
     * records, and email branding must stay PNG/JPEG because Outlook does not
     * render WebP inside <img>.
     *
     * @var list<string>
     */
    private const SKIP_DIRECTORIES = [
        'saas_receipts_proofs',
        'student_receipts_proofs',
        'saas/manual-receipts',
        'assessment-uploads',
        'homework_submissions',
        'application-docs',
        'admission-docs',
        'hr/leaves',
        'email-branding',
    ];

    /** Raster formats GD can decode and this app actually serves as photos. */
    private const CONVERTIBLE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    private const CONVERTIBLE_IMAGETYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG];

    /** Above this pixel count decoding would exceed the 256M memory limit. */
    private const MAX_PIXELS = 32000000;

    /**
     * Convert a stored image to WebP and return its (possibly renamed) path.
     * Returns $path unchanged whenever conversion is not possible or not safe.
     */
    public static function convert(string $path, string $disk = 'public'): string
    {
        try {
            if ($path === '' || ! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
                return $path;
            }

            if (Str::contains($path, '://')) {
                return $path;
            }

            if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::CONVERTIBLE_EXTENSIONS, true)) {
                return $path;
            }

            if (Str::contains(Str::lower($path), 'favicon')) {
                return $path;
            }

            foreach (self::SKIP_DIRECTORIES as $directory) {
                if (str_contains($path, $directory)) {
                    return $path;
                }
            }

            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                return $path;
            }

            $absolutePath = $storage->path($path);
            $info = @getimagesize($absolutePath);

            if ($info === false || ! in_array($info[2] ?? 0, self::CONVERTIBLE_IMAGETYPES, true)) {
                return $path;
            }

            if (($info[0] * $info[1]) > self::MAX_PIXELS) {
                return $path;
            }

            $image = @imagecreatefromstring((string) $storage->get($path));

            if ($image === false) {
                return $path;
            }

            if ($info[2] === IMAGETYPE_JPEG) {
                $image = self::applyExifOrientation($image, $absolutePath);
            }

            if ($info[2] === IMAGETYPE_PNG) {
                imagealphablending($image, false);
                imagesavealpha($image, true);
            }

            ob_start();
            $encoded = @imagewebp($image, null, self::QUALITY);
            $webp = (string) ob_get_clean();
            imagedestroy($image);

            if (! $encoded || $webp === '') {
                return $path;
            }

            $convertedPath = preg_replace('/\.[^.]+$/', '.webp', $path);

            if (! is_string($convertedPath) || $convertedPath === $path) {
                return $path;
            }

            $storage->put($convertedPath, $webp);
            $storage->delete($path);

            return $convertedPath;
        } catch (Throwable) {
            return $path;
        }
    }

    /**
     * Bake the JPEG's EXIF orientation into the pixels before re-encoding -
     * imagecreatefromjpeg discards the orientation tag, so phone photos would
     * otherwise appear rotated after conversion. Mirrored modes (5/7) are
     * intentionally left untouched rather than guessed.
     */
    private static function applyExifOrientation(\GdImage $image, string $absolutePath): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($absolutePath);

        switch ((int) ($exif['Orientation'] ?? 1)) {
            case 2:
                imageflip($image, IMG_FLIP_HORIZONTAL);
                break;
            case 3:
            case 6:
            case 8:
                $angle = match ((int) ($exif['Orientation'] ?? 1)) {
                    3 => 180,
                    6 => -90,
                    default => 90,
                };
                $rotated = imagerotate($image, $angle, 0);

                if ($rotated !== false) {
                    imagedestroy($image);
                    $image = $rotated;
                }
                break;
        }

        return $image;
    }
}
