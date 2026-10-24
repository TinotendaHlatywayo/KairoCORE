<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Turns a failed upload into a sentence a person can act on.
 *
 * Everywhere the app (or Livewire, or Filament) would otherwise say "upload
 * failed", this helper answers with the actual reason and a concrete number:
 * which PHP/proxy limit rejected the file, what the field allows, or that the
 * connection dropped — always phrased as "try something smaller than X MB".
 *
 * Uploads are governed by four ceilings, and the smallest one wins:
 *
 *   1. PHP's upload_max_filesize / post_max_size  (docker/php/uploads.ini)
 *   2. Livewire's temporary_file_upload rules    (config/livewire.php)
 *   3. A per-request proxy/CDN cap such as Cloudflare's 100 MB body limit
 *      (UPLOAD_MAX_MB, see config/uploads.php)
 *   4. The field's own ->maxSize() rule          (validated separately)
 *
 * limitMegabytes() reports ceiling 1-3 so that generic messages never promise
 * a size the server will actually refuse.
 */
class UploadReason
{
    /**
     * Used only if every configured ceiling is unusable (all empty or -1).
     */
    public const FALLBACK_LIMIT_MB = 64;

    /**
     * Install the message plumbing:
     *
     *  - replacers for the `file` and `max` rules, so a rejected file reports
     *    the real limit instead of "must be a file" / "must not be greater
     *    than 2048 kilobytes";
     *  - a runtime `validation.uploaded` line carrying the real limit, which
     *    is what Livewire shows when the upload request never produced a JSON
     *    error body (413 at the proxy, dropped connection, PHP fatal).
     *
     * Call once per request, during boot.
     */
    public static function register(): void
    {
        ValidatorFacade::replacer('file', self::replacerFile(...));
        ValidatorFacade::replacer('max', self::replacerMax(...));

        // Laravel short-circuits an invalid UploadedFile to the `uploaded`
        // rule (before `file` or `max` ever run), so this is where a rejected
        // upload is actually reported.
        ValidatorFacade::replacer('uploaded', self::replacerUploaded(...));

        // Livewire builds its "failed to upload" text with a bare trans() call
        // (no validator, no :limit placeholder), so the number has to live in
        // the translated line itself. Warm the group first: addLines() marks
        // the group as loaded, and a group that was never read from disk would
        // then serve only this one key for the rest of the request.
        $fallback = (string) (config('app.fallback_locale') ?: 'en');
        $translator = app('translator');
        $translator->get('validation.required', [], $fallback);
        $translator->addLines(['validation.uploaded' => self::uploadedMessage()], $fallback);
    }

    /**
     * The line Livewire throws for a failed upload request (attribute filled
     * in by Livewire's _uploadErrored()).
     */
    public static function uploadedMessage(): string
    {
        $limit = self::limitPhrase();

        return "The :attribute could not be uploaded — almost always because the file is larger than this site's {$limit} limit, or the connection dropped before it finished. Try something smaller than {$limit}.";
    }

    /**
     * Sentence for failures where nothing better can be determined.
     */
    public static function genericMessage(): string
    {
        $limit = self::limitPhrase();

        return "The upload could not be completed — almost always a file larger than this site's {$limit} limit, or a connection that dropped mid-transfer. Try something smaller than {$limit}.";
    }

    /**
     * Best-effort reason for a throwable raised while handling an upload.
     * Validation errors are flattened to their first message; vague framework
     * messages ("The given data was invalid.") are replaced by the limit-aware
     * fallback.
     */
    public static function forThrowable(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            foreach ($e->errors() as $messages) {
                foreach ((array) $messages as $message) {
                    if (trim((string) $message) !== '') {
                        return trim((string) $message);
                    }
                }
            }
        }

        $message = trim($e->getMessage());

        if ($message === '' || preg_match('/^(the given data was invalid|internal server error|server error|an exception occurred|something went wrong)/i', $message)) {
            return self::genericMessage();
        }

        return $message;
    }

    /**
     * Smallest live ceiling in megabytes (PHP ini + Livewire rules +
     * config/uploads.php).
     */
    public static function limitMegabytes(): int
    {
        $limits = [];

        foreach (['upload_max_filesize', 'post_max_size'] as $ini) {
            $mb = self::toMegabytes((string) ini_get($ini));
            if ($mb > 0) {
                $limits[] = (int) floor($mb);
            }
        }

        foreach ((array) config('livewire.temporary_file_upload.rules', []) as $rule) {
            if (is_string($rule) && preg_match('/^max:(\d+)$/i', $rule, $matches)) {
                $limits[] = (int) ceil(((int) $matches[1]) / 1024);
            }
        }

        $configured = (int) config('uploads.max_megabytes', 0);
        if ($configured > 0) {
            $limits[] = $configured;
        }

        return $limits === [] ? self::FALLBACK_LIMIT_MB : max(1, min($limits));
    }

    public static function limitPhrase(): string
    {
        return self::formatMegabytes((float) self::limitMegabytes());
    }

    /**
     * "64M" / "1024K" / "1G" / "2097152" (bytes) → megabytes. Unusable values
     * ("-1", empty, unparsable) return 0 so the caller can ignore them.
     */
    public static function toMegabytes(string $size): float
    {
        $size = trim($size);

        if ($size === '' || $size === '-1') {
            return 0.0;
        }

        $value = (float) $size;
        if ($value <= 0) {
            return 0.0;
        }

        return match (strtolower(substr($size, -1))) {
            'g' => $value * 1024,
            'm' => $value,
            'k' => $value / 1024,
            default => $value / 1024 / 1024,
        };
    }

    /**
     * Human size with no needless decimals: 2, 1.5, 64.
     */
    public static function formatMegabytes(float $megabytes): string
    {
        $rounded = round($megabytes, 1);

        if ((float) $rounded === (float) (int) $rounded) {
            return ((int) $rounded).' MB';
        }

        return rtrim(rtrim(number_format($rounded, 1, '.', ''), '0'), '.').' MB';
    }

    /**
     * kilobytes (what the `max` rule measures files in) → "2 MB".
     */
    public static function formatKilobytes(float $kilobytes): string
    {
        return self::formatMegabytes($kilobytes / 1024);
    }

    /**
     * Reason for one of PHP's UPLOAD_ERR_* codes.
     *
     * @param  string  $subject  already-phrased subject, e.g. "The photo" or "This file"
     */
    public static function forErrorCode(int $code, string $subject = 'This file'): string
    {
        $limit = self::limitPhrase();

        return match ($code) {
            UPLOAD_ERR_INI_SIZE => "{$subject} failed to upload — it is larger than this site's {$limit} limit. Try something smaller than {$limit}.",
            UPLOAD_ERR_FORM_SIZE => "{$subject} failed to upload — the whole request was larger than this site's {$limit} limit. Try something smaller than {$limit}.",
            UPLOAD_ERR_PARTIAL => "{$subject} failed to upload — the connection dropped halfway through, so only part of it arrived. Upload it again.",
            UPLOAD_ERR_NO_FILE => "{$subject} was not uploaded — no file was selected. Choose a file and try again.",
            UPLOAD_ERR_NO_TMP_DIR => "{$subject} failed to upload — the server has no temporary upload space. Ask an administrator to check PHP's upload_tmp_dir.",
            UPLOAD_ERR_CANT_WRITE => "{$subject} failed to upload — the server could not write the temporary file. Ask an administrator to check disk space and permissions.",
            UPLOAD_ERR_EXTENSION => "{$subject} failed to upload — a PHP security extension blocked it. Ask an administrator to check the PHP configuration.",
            default => self::genericMessage(),
        };
    }

    /**
     * `uploaded` rule: Laravel raises it for any UploadFile PHP rejected, so
     * this is the common path for "the file never really arrived". Name the
     * exact cause instead of leaving the generic line in place.
     */
    protected static function replacerUploaded(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        $file = self::rejectedFile($validator, $attribute);

        if ($file instanceof UploadedFile && $file->getError() !== UPLOAD_ERR_OK) {
            return self::forErrorCode($file->getError(), self::subject($validator, $attribute));
        }

        // Nothing on the payload carries an error: the request itself never
        // completed (a 413 at the proxy, a dropped connection). Keep the
        // limit-aware line, which already names both possibilities.
        return $message;
    }

    /**
     * `file` rule: a failed upload almost always means a PHP-side rejection,
     * which the stock message ("must be a file") hides completely.
     */
    protected static function replacerFile(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        $file = self::rejectedFile($validator, $attribute);

        if (! $file instanceof UploadedFile) {
            // Not an upload error: a non-file value where a file was expected.
            return $message;
        }

        return self::forErrorCode($file->getError(), self::subject($validator, $attribute));
    }

    /**
     * `max` rule: for files the stock message talks in raw kilobytes. Report
     * both the actual size and the limit in megabytes instead. Non-file
     * attributes keep the stock `:max` replacement.
     */
    protected static function replacerMax(string $message, string $attribute, string $rule, array $parameters, Validator $validator): string
    {
        $kilobytes = (float) ($parameters[0] ?? 0);
        $value = data_get($validator->getData(), $attribute);

        if (! $value instanceof File) {
            return str_replace(':max', (string) ($parameters[0] ?? ''), $message);
        }

        $limit = self::formatKilobytes($kilobytes);
        $subject = self::subject($validator, $attribute);

        $actual = null;
        try {
            $bytes = $value->getSize();
            if (is_numeric($bytes) && $bytes > 0) {
                $actual = self::formatMegabytes(((float) $bytes) / 1048576);
            }
        } catch (\Throwable) {
            $actual = null;
        }

        return $actual !== null
            ? "{$subject} is {$actual} — larger than this field's {$limit} maximum. Try something smaller than {$limit}."
            : "{$subject} is larger than this field's {$limit} maximum. Try something smaller than {$limit}.";
    }

    /**
     * The file PHP rejected, if there is one: prefer the failing attribute,
     * then any upload in the payload that carries an error code.
     */
    protected static function rejectedFile(Validator $validator, string $attribute): ?UploadedFile
    {
        $value = data_get($validator->getData(), $attribute);

        if ($value instanceof UploadedFile && $value->getError() !== UPLOAD_ERR_OK) {
            return $value;
        }

        foreach (Arr::dot($validator->getData()) as $candidate) {
            if ($candidate instanceof UploadedFile && $candidate->getError() !== UPLOAD_ERR_OK) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Displayable subject. Livewire validates against its internal "files"
     * attribute, which would read as "The files.0" — say "This file" instead.
     */
    protected static function subject(Validator $validator, string $attribute): string
    {
        $display = trim($validator->getDisplayableAttribute($attribute));

        if ($display === '' || preg_match('/^files(\.\*|\.\d+)?$/i', $display)) {
            return 'This file';
        }

        return 'The '.$display;
    }
}
