<?php

namespace Tests\Feature;

use App\Services\ImageConverter;
use Filament\Forms\ComponentContainer;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Components\ComponentManager;
use Illuminate\Support\Facades\Storage;
use Livewire\Component as LivewireComponent;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Tests\TestCase;

/**
 * Server-side WebP conversion for uploaded images: unit coverage of the
 * converter's skip/convert rules plus a functional check that Filament's
 * storage callback (wrapped in AppServiceProvider) persists new uploads as
 * WebP. Runs against faked filesystems only - no database touched.
 */
class ImageConverterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('This PHP build lacks the GD WebP encoder.');
        }

        config()->set('filament.default_filesystem_disk', 'public');
        Storage::fake('public');
    }

    private function jpegContents(int $width = 12, int $height = 8): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 12, 96, 180));
        ob_start();
        imagejpeg($image, null, 90);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    private function pngContentsWithTransparentCorner(): string
    {
        $image = imagecreatetruecolor(8, 8);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 7, 7, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagesetpixel($image, 4, 4, imagecolorallocatealpha($image, 255, 0, 0, 0));
        ob_start();
        imagepng($image);
        $binary = (string) ob_get_clean();
        imagedestroy($image);

        return $binary;
    }

    public function test_it_converts_a_stored_jpeg_to_webp_and_keeps_dimensions(): void
    {
        Storage::disk('public')->put('photos/pic.jpg', $this->jpegContents(12, 8));

        $converted = ImageConverter::convert('photos/pic.jpg');

        $this->assertSame('photos/pic.webp', $converted);
        $this->assertFalse(Storage::disk('public')->exists('photos/pic.jpg'));
        $this->assertTrue(Storage::disk('public')->exists('photos/pic.webp'));

        $size = getimagesize(Storage::disk('public')->path($converted));
        $this->assertSame(IMAGETYPE_WEBP, $size[2]);
        $this->assertSame(12, $size[0]);
        $this->assertSame(8, $size[1]);
    }

    public function test_it_converts_png_and_preserves_transparency(): void
    {
        Storage::disk('public')->put('images/transparent.png', $this->pngContentsWithTransparentCorner());

        $converted = ImageConverter::convert('images/transparent.png');

        $this->assertSame('images/transparent.webp', $converted);

        $decoded = imagecreatefromstring((string) Storage::disk('public')->get($converted));
        $this->assertNotFalse($decoded);
        $this->assertGreaterThan(0, imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha']);
        imagedestroy($decoded);
    }

    public function test_it_leaves_unsupported_types_urls_and_missing_files_untouched(): void
    {
        $this->assertSame('files/anim.gif', ImageConverter::convert('files/anim.gif'));
        $this->assertSame('files/doc.pdf', ImageConverter::convert('files/doc.pdf'));
        $this->assertSame('files/missing.jpg', ImageConverter::convert('files/missing.jpg'));
        $this->assertSame('files/photo.svg', ImageConverter::convert('files/photo.svg'));
        $this->assertSame(
            'https://cdn.example.com/pic.jpg',
            ImageConverter::convert('https://cdn.example.com/pic.jpg'),
        );
    }

    public function test_it_skips_evidence_email_and_favicon_paths(): void
    {
        $skipped = [
            'saas_receipts_proofs/receipt.jpg',
            'student_receipts_proofs/receipt.jpg',
            'saas/manual-receipts/receipt.png',
            'assessment-uploads/answer.jpg',
            'homework_submissions/work.jpg',
            'application-docs/form.jpg',
            'admission-docs/id.jpg',
            'hr/leaves/letter.jpg',
            'school/email-branding/logo.png',
            'platform/branding/favicon.png',
        ];

        foreach ($skipped as $path) {
            $contents = str_ends_with($path, 'png') ? $this->pngContentsWithTransparentCorner() : $this->jpegContents();
            Storage::disk('public')->put($path, $contents);

            $this->assertSame($path, ImageConverter::convert($path));
            $this->assertTrue(Storage::disk('public')->exists($path));
        }
    }

    public function test_filament_storage_callback_is_registered_and_persists_new_uploads_as_webp(): void
    {
        $manager = ComponentManager::resolve();
        $configurations = (new \ReflectionProperty($manager, 'importantConfigurations'))->getValue($manager);
        $this->assertArrayHasKey(BaseFileUpload::class, $configurations);

        Storage::fake('tmp-for-tests');
        Storage::disk('tmp-for-tests')->put('livewire-tmp/photo.jpg', $this->jpegContents());

        $livewire = new class extends LivewireComponent implements HasForms
        {
            use InteractsWithForms;
        };

        $field = FileUpload::make('attachment')->directory('test-uploads');
        $field->container(ComponentContainer::make($livewire));
        $wrapper = (new \ReflectionProperty(BaseFileUpload::class, 'saveUploadedFileUsing'))->getValue($field);

        $temporary = new TemporaryUploadedFile('photo.jpg', 'tmp-for-tests');
        $stored = $field->evaluate($wrapper, ['file' => $temporary]);

        $this->assertIsString($stored);
        $this->assertStringEndsWith('.webp', $stored);
        $this->assertTrue(Storage::disk('public')->exists($stored));
        $this->assertSame(IMAGETYPE_WEBP, getimagesize(Storage::disk('public')->path($stored))[2]);
    }

    public function test_conversion_is_idempotent_for_already_converted_files(): void
    {
        Storage::disk('public')->put('photos/once.jpg', $this->jpegContents());

        $once = ImageConverter::convert('photos/once.jpg');
        $twice = ImageConverter::convert($once);

        $this->assertSame($once, $twice);
        $this->assertTrue(Storage::disk('public')->exists($once));
    }
}
