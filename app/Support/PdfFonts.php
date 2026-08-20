<?php

namespace App\Support;

use Dompdf\Dompdf;

/**
 * Registers the fonts available on this server with DomPDF so that the
 * "Document Font" choice on finance documents is actually applied in the
 * printed PDF (DomPDF only ships with a handful of core fonts by default).
 *
 * The registration is idempotent: after the first run DomPDF stores the fonts
 * and their metric files in storage/fonts and every later call is a no-op.
 */
class PdfFonts
{
    protected const SOURCE_DIR = '/usr/share/fonts/truetype/msttcorefonts';

    /**
     * font-family (as it appears in CSS) => [normal, bold, italic, bold_italic] TTFs.
     */
    public const FAMILIES = [
        'arial' => [
            'normal' => 'Arial.ttf',
            'bold' => 'Arial_Bold.ttf',
            'italic' => 'Arial_Italic.ttf',
            'bold_italic' => 'Arial_Bold_Italic.ttf',
        ],
        'arial black' => [
            'normal' => 'Arial_Black.ttf',
            'bold' => 'Arial_Black.ttf',
        ],
        'verdana' => [
            'normal' => 'Verdana.ttf',
            'bold' => 'Verdana_Bold.ttf',
            'italic' => 'Verdana_Italic.ttf',
            'bold_italic' => 'Verdana_Bold_Italic.ttf',
        ],
        'times new roman' => [
            'normal' => 'Times_New_Roman.ttf',
            'bold' => 'Times_New_Roman_Bold.ttf',
            'italic' => 'Times_New_Roman_Italic.ttf',
            'bold_italic' => 'Times_New_Roman_Bold_Italic.ttf',
        ],
        'georgia' => [
            'normal' => 'Georgia.ttf',
            'bold' => 'Georgia_Bold.ttf',
            'italic' => 'Georgia_Italic.ttf',
            'bold_italic' => 'Georgia_Bold_Italic.ttf',
        ],
        'trebuchet ms' => [
            'normal' => 'Trebuchet_MS.ttf',
            'bold' => 'Trebuchet_MS_Bold.ttf',
            'italic' => 'Trebuchet_MS_Italic.ttf',
            'bold_italic' => 'Trebuchet_MS_Bold_Italic.ttf',
        ],
        'courier new' => [
            'normal' => 'Courier_New.ttf',
            'bold' => 'Courier_New_Bold.ttf',
            'italic' => 'Courier_New_Italic.ttf',
            'bold_italic' => 'Courier_New_Bold_Italic.ttf',
        ],
        'comic sans ms' => [
            'normal' => 'Comic_Sans_MS.ttf',
            'bold' => 'Comic_Sans_MS_Bold.ttf',
        ],
        'impact' => [
            'normal' => 'Impact.ttf',
            'bold' => 'Impact.ttf',
        ],
    ];

    public static function register(Dompdf $dompdf): void
    {
        if (! is_dir(self::SOURCE_DIR)) {
            return;
        }

        $fontDir = $dompdf->getOptions()->getFontDir();
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        // DomPDF's file:// protocol is constrained to its chroot, so the TTFs
        // must live inside the project (storage/fonts). Copy them once here and
        // register from the local copies.
        $localDir = $fontDir.'/msttcorefonts';
        if (! is_dir($localDir)) {
            @mkdir($localDir, 0775, true);
        }

        $fontMetrics = $dompdf->getFontMetrics();

        foreach (self::FAMILIES as $family => $variants) {
            foreach ($variants as $style => $file) {
                $source = self::SOURCE_DIR.'/'.$file;
                if (! is_file($source)) {
                    continue;
                }

                $local = $localDir.'/'.$file;
                if (! is_file($local)) {
                    @copy($source, $local);
                }
                if (! is_file($local)) {
                    continue;
                }

                $fontMetrics->registerFont([
                    'family' => $family,
                    'style' => in_array($style, ['italic', 'bold_italic'], true) ? 'italic' : 'normal',
                    'weight' => in_array($style, ['bold', 'bold_italic'], true) ? 'bold' : 'normal',
                ], 'file://'.$local);
            }
        }

        $fontMetrics->saveFontFamilies();
    }
}
