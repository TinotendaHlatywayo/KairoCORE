<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Rejects website media uploads whose video runs longer than the limit
 * (8 seconds by default). Static images pass untouched. When ffprobe is
 * not reachable (e.g. inside the app container) the check is skipped —
 * the conversion job trims the source to the limit anyway.
 */
class MaxVideoDuration implements ValidationRule
{
    public function __construct(protected float $maxSeconds = 8.0) {}

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

        // Half a second of tolerance: "8 second" clips exported from
        // editors often come out as 8.0–8.3 s of container duration.
        if ($seconds > $this->maxSeconds + 0.3) {
            $fail(sprintf(
                'The video may not be longer than %s seconds — this one is %s seconds.',
                rtrim(rtrim(number_format($this->maxSeconds, 1), '0'), '.'),
                number_format($seconds, 1)
            ));
        }
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
