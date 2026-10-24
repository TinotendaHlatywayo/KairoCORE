<?php

namespace Tests\Feature;

use App\Support\UploadReason;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Upload rejections must always carry the reason and a real number
 * ("larger than this site's 64 MB limit — try something smaller than 64 MB")
 * instead of the framework's bare "must be a file" / "failed to upload".
 */
class UploadReasonTest extends TestCase
{
    public function test_limit_is_the_smallest_configured_ceiling(): void
    {
        config(['uploads.max_megabytes' => 1]);

        $this->assertSame(1, UploadReason::limitMegabytes());
    }

    public function test_limit_honours_livewire_rules(): void
    {
        config(['uploads.max_megabytes' => 999999]);
        config(['livewire.temporary_file_upload.rules' => ['required', 'file', 'max:1024']]);

        $this->assertSame(1, UploadReason::limitMegabytes());
    }

    public function test_php_ini_sizes_are_parsed(): void
    {
        $this->assertSame(64.0, UploadReason::toMegabytes('64M'));
        $this->assertSame(1.0, UploadReason::toMegabytes('1024K'));
        $this->assertSame(1024.0, UploadReason::toMegabytes('1G'));
        $this->assertSame(2.0, UploadReason::toMegabytes('2097152'));
        $this->assertSame(0.0, UploadReason::toMegabytes('-1'));
        $this->assertSame(0.0, UploadReason::toMegabytes(''));
    }

    public function test_sizes_are_formatted_for_humans(): void
    {
        $this->assertSame('2 MB', UploadReason::formatMegabytes(2));
        $this->assertSame('1.5 MB', UploadReason::formatMegabytes(1.5));
        $this->assertSame('64 MB', UploadReason::formatMegabytes(64));

        $this->assertSame('2 MB', UploadReason::formatKilobytes(2048));
        $this->assertSame('1.5 MB', UploadReason::formatKilobytes(1536));
        $this->assertSame('0.5 MB', UploadReason::formatKilobytes(512));
    }

    public function test_php_size_rejection_reports_the_limit(): void
    {
        $path = $this->tempFile();
        $file = new UploadedFile($path, 'archive.zip', 'application/zip', UPLOAD_ERR_INI_SIZE, true);

        $validator = Validator::make(['files' => [$file]], ['files.*' => 'file']);
        $this->assertTrue($validator->fails());

        $message = $validator->errors()->first('files.0');

        $this->assertStringContainsString('failed to upload', $message);
        $this->assertStringContainsString("larger than this site's ".UploadReason::limitPhrase(), $message);
        $this->assertStringContainsString('Try something smaller than '.UploadReason::limitPhrase(), $message);
    }

    public function test_partial_upload_reports_a_dropped_connection(): void
    {
        $file = new UploadedFile($this->tempFile(), 'notes.pdf', 'application/pdf', UPLOAD_ERR_PARTIAL, true);

        $validator = Validator::make(['files' => [$file]], ['files.*' => 'file']);
        $this->assertTrue($validator->fails());

        $this->assertStringContainsString('connection dropped halfway', $validator->errors()->first('files.0'));
    }

    public function test_missing_file_reports_that_nothing_was_selected(): void
    {
        $file = new UploadedFile($this->tempFile(), '', 'application/octet-stream', UPLOAD_ERR_NO_FILE, true);

        $validator = Validator::make(['files' => [$file]], ['files.*' => 'file']);
        $this->assertTrue($validator->fails());

        $this->assertStringContainsString('no file was selected', $validator->errors()->first('files.0'));
    }

    public function test_field_max_size_reports_megabytes_not_kilobytes(): void
    {
        $file = new UploadedFile($this->tempFile('3M'), 'portrait.jpg', 'image/jpeg', null, true);

        $validator = Validator::make(['photo' => $file], ['photo' => 'max:2048']);
        $this->assertTrue($validator->fails());

        $message = $validator->errors()->first('photo');

        $this->assertStringContainsString('The photo is 3 MB', $message);
        $this->assertStringContainsString("this field's 2 MB maximum", $message);
        $this->assertStringContainsString('Try something smaller than 2 MB', $message);
        $this->assertStringNotContainsString('kilobytes', $message);
    }

    public function test_livewire_attribute_names_read_as_this_file(): void
    {
        $file = new UploadedFile($this->tempFile('3M'), 'notes.pdf', 'application/pdf', null, true);

        $validator = Validator::make(['files' => [$file]], ['files.*' => 'max:1024']);
        $this->assertTrue($validator->fails());

        $message = $validator->errors()->first('files.0');

        $this->assertStringStartsWith('This file is 3 MB', $message);
        $this->assertStringContainsString('1 MB maximum', $message);
    }

    public function test_non_file_failures_keep_the_stock_message(): void
    {
        $fileValidator = Validator::make(['photo' => 'not-a-file'], ['photo' => 'file']);
        $this->assertTrue($fileValidator->fails());
        $this->assertSame('The photo field must be a file.', $fileValidator->errors()->first('photo'));

        $numberValidator = Validator::make(['qty' => 9], ['qty' => 'numeric|max:5']);
        $this->assertTrue($numberValidator->fails());
        $this->assertSame('The qty field must not be greater than 5.', $numberValidator->errors()->first('qty'));
    }

    public function test_livewire_upload_message_quotes_the_limit(): void
    {
        $message = trans('validation.uploaded', ['attribute' => 'photo']);

        $this->assertStringContainsString('The photo could not be uploaded', $message);
        $this->assertStringContainsString("larger than this site's ".UploadReason::limitPhrase(), $message);
        $this->assertStringContainsString('Try something smaller than '.UploadReason::limitPhrase(), $message);
        $this->assertStringNotContainsString(':attribute', $message);
    }

    public function test_throwable_reasons_are_flattened(): void
    {
        $validation = ValidationException::withMessages(['photo' => 'The photo is too large.']);
        $this->assertSame('The photo is too large.', UploadReason::forThrowable($validation));

        $this->assertSame(
            'Disk full while writing the upload.',
            UploadReason::forThrowable(new \RuntimeException('Disk full while writing the upload.'))
        );

        $this->assertSame(
            UploadReason::genericMessage(),
            UploadReason::forThrowable(new \RuntimeException('The given data was invalid.'))
        );
    }

    public function test_error_codes_explain_themselves(): void
    {
        $this->assertStringContainsString('connection dropped', UploadReason::forErrorCode(UPLOAD_ERR_PARTIAL));
        $this->assertStringContainsString('no file was selected', UploadReason::forErrorCode(UPLOAD_ERR_NO_FILE));
        $this->assertStringContainsString(
            'larger than this site\'s '.UploadReason::limitPhrase(),
            UploadReason::forErrorCode(UPLOAD_ERR_INI_SIZE)
        );
        $this->assertSame(UploadReason::genericMessage(), UploadReason::forErrorCode(-1));
    }

    /**
     * A real temp file of the requested size (default 3 MB) so size rules
     * measure something meaningful.
     */
    private function tempFile(string $size = '3M'): string
    {
        $path = tempnam(sys_get_temp_dir(), 'upload-reason');
        file_put_contents($path, str_repeat('a', $size === '3M' ? 3 * 1024 * 1024 : 16));

        return $path;
    }
}
