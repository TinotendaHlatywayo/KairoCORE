<?php

namespace App\Services;

use App\Filament\App\Pages\Auth\EditProfile;
use App\Models\User;
use App\Notifications\ProfilePhotoRejectedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Shared server-side validation and storage for ID-card style profile photos
 * (students and staff). The client already crops the image to a passport-like
 * aspect ratio and passes it here as a JPEG data URL; this service re-verifies
 * everything with GD so no malformed or low-quality payload can ever persist.
 */
class ProfilePhotoService
{
    /** Minimum short-edge in pixels for a usable ID-card photo. */
    public const MIN_DIMENSION = 300;

    /** Maximum stored long-edge in pixels (keep cards lightweight). */
    public const MAX_DIMENSION = 1200;

    /**
     * Accepted portrait aspect ratios (width/height). Includes the classic
     * 1:1 square, 3:4, 4:5 and 2:3 passport proportions.
     */
    public const ACCEPTED_ASPECT_RATIOS = [1.0, 0.75, 0.8, 0.6667];

    /** How close the photo aspect ratio must be to an accepted ratio. */
    public const ASPECT_TOLERANCE = 0.04;

    /**
     * Validate a base64 data URL and store it as a re-encoded JPEG.
     *
     * @param  string  $dataUrl  data:image/...;base64, payload
     * @param  string  $directory  storage sub-directory (e.g. "student-photos")
     * @return array{path: ?string, error: ?string}
     */
    public function storeFromDataUrl(string $dataUrl, string $directory): array
    {
        if (! preg_match('/^data:image\/(jpeg|png|webp);base64,/', $dataUrl)) {
            return [null, __('Invalid image format. Please use JPG, PNG or WebP.')];
        }

        $payload = preg_replace('/^data:image\/[a-z]+;base64,/', '', $dataUrl);
        $binary = base64_decode($payload, true);

        if ($binary === false || $binary === '') {
            return [null, __('The image data could not be decoded.')];
        }

        if (strlen($binary) > 3 * 1024 * 1024) {
            return [null, __('The image is too large. Maximum file size is 3MB.')];
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return [null, __('The file is not a valid image.')];
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < self::MIN_DIMENSION || $height < self::MIN_DIMENSION) {
            imagedestroy($source);

            return [null, __('This photo is too small / low quality. Use a photo of at least '.self::MIN_DIMENSION.'x'.self::MIN_DIMENSION.' pixels.')];
        }

        $ratio = $width / $height;
        $match = null;
        foreach (self::ACCEPTED_ASPECT_RATIOS as $accepted) {
            if (abs($ratio - $accepted) <= self::ASPECT_TOLERANCE) {
                $match = $accepted;
                break;
            }
        }

        if ($match === null) {
            imagedestroy($source);

            return [null, __('This photo is not a passport-style portrait. Please crop it to a 1:1, 3:4, 4:5 or 2:3 ratio.')];
        }

        $canvas = $source;
        $max = max($width, $height);
        if ($max > self::MAX_DIMENSION) {
            $scale = self::MAX_DIMENSION / $max;
            $newWidth = (int) round($width * $scale);
            $newHeight = (int) round($height * $scale);
            $canvas = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($source);
        }

        ob_start();
        imagejpeg($canvas, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($canvas);

        if ($jpeg === false || $jpeg === '') {
            return [null, __('The image could not be processed.')];
        }

        $filename = $directory.'/'.Str::uuid().'.jpg';

        Storage::disk('public')->put($filename, $jpeg);

        return [$filename, null];
    }

    /**
     * Delete a stored photo file if it exists on the public disk.
     */
    public function deleteStored(string $path): void
    {
        $path = ltrim((string) $path, '/');
        if ($path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Admin removes / rejects a profile photo: deletes the stored file, clears
     * the photo column and records who rejected it and why (so the portal can
     * show the user why their photo is gone and prompt them to re-upload).
     *
     * @param  Model  $record  Student or Employee
     */
    public function rejectPhoto($record, ?string $reason, string $photoColumn): void
    {
        $old = $record->{$photoColumn};

        if ($old) {
            $this->deleteStored($old);
        }

        $record->update([
            $photoColumn => null,
            'photo_rejected_reason' => trim((string) $reason) ?: null,
            'photo_rejected_by' => auth()->id(),
            'photo_rejected_at' => now(),
        ]);
    }

    /**
     * Remove an employee photo and notify the linked staff member.
     */
    public static function removeEmployeePhoto($employee, ?string $reason): void
    {
        $service = app(self::class);

        $service->rejectPhoto($employee, $reason, 'avatar_path');

        if ($employee->user_id) {
            $user = User::find($employee->user_id);
            if ($user) {
                $user->notify(new ProfilePhotoRejectedNotification(
                    subject: __('Your profile photo was removed'),
                    reason: trim((string) $reason) ?: null,
                    url: EditProfile::getUrl(),
                ));
            }
        }
    }
}
