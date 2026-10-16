<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects website media uploads whose video is too long for the animated
 * WebP clip. The clip is always `$maxSeconds` long, starting at `$trimStart`
 * seconds into the source, so a longer source is allowed as long as the
 * trim window still fits inside it. Static images pass untouched. When
 * ffprobe is not reachable (e.g. inside the app container) the check is
 * skipped — the conversion job trims to the limit anyway.
 */
class MaxVideoDuration implements ValidationRule
{
    public function __construct(
        protected float $maxSeconds = 8.0,
        protected float $trimStart = 0.0,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $path = $value->getRealPath();
        if (! is_string($path) || ! is_file($path)) {
            return;
        }

        $ext = strtolower(pathinfo($value->getClientOriginalName(), PATHINFO_EXTENSION));
        if (! in_array($ext, ['mp4', 'mov', 'm4v', 'webm', 'avi', 'mpg', 'mpeg', 'mkv', '3gp', 'mts', 'wmv', 'flv', 'ogv', 'gif'], true)) {
            return;
        }

        $seconds = $this->probeSeconds($path);
        if ($seconds === null) {
            return;
        }

        $trimStart = max(0.0, $this->trimStart);

        // A trim start at or past the end of the video yields zero frames:
        // ffmpeg still exits 0 and writes a tiny, blank WebP, which renders
        // as an empty image slot. Reject it here with a real explanation.
        if ($trimStart >= $seconds - 0.1) {
            $fail(strtr('The trim start (:trim s) is at or past the end of this :got-second video, so there is nothing left to show. Lower the trim start so it starts before the video ends.', [
                ':trim' => rtrim(rtrim(number_format($trimStart, 1), '0'), '.'),
                ':got' => number_format($seconds, 1),
            ]));

            return;
        }

        $limit = $this->maxSeconds + $trimStart;

        // Half a second of tolerance: "8 second" clips exported from
        // editors often come out as 8.0–8.3 s of container duration.
        if ($seconds <= $limit + 0.3) {
            return;
        }

        $friendlyLimit = rtrim(rtrim(number_format($limit, 1), '0'), '.');

        if ($trimStart > 0) {
            $fail(strtr('This video is :got seconds long. With the trim start set to :trim seconds, the longest clip it can yield is :limit seconds — shorten the video, or lower the trim start.', [
                ':got' => number_format($seconds, 1),
                ':trim' => rtrim(rtrim(number_format($trimStart, 1), '0'), '.'),
                ':limit' => $friendlyLimit,
            ]));

            return;
        }

        $fail(strtr('This video is :got seconds long. Website videos play as a :limit-second clip — shorten the video, or set this slot\'s "Video trim start" field to keep a chosen :limit-second section of a longer video.', [
            ':got' => number_format($seconds, 1),
            ':limit' => $friendlyLimit,
        ]));
    }

    protected function probeSeconds(string $path): ?float
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
}
