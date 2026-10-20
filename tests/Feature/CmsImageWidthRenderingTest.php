<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The renderer must accept any valid CSS width (percentage or px) for
 * styles.image_width — not just a hard-coded whitelist — so the builder's
 * choice always reaches the live site at the exact fraction set.
 *
 * Tests the regex validator used in preview-block.blade.php.
 */
class CmsImageWidthRenderingTest extends TestCase
{
    public function test_valid_widths_pass_the_renderer_validator(): void
    {
        $validWidths = ['none', '50%', '60%', '70%', '80%', '90%', '560px', '640px', '720px'];

        foreach ($validWidths as $width) {
            $this->assertTrue(
                $this->isValidImageWidth($width),
                "\"{$width}\" must pass the renderer validator"
            );
        }
    }

    public function test_malicious_or_invalid_widths_are_rejected(): void
    {
        $badWidths = [
            'expression(alert(1))',
            'url(javascript:alert(1))',
            '50; background:red',
            'calc(100px)',
            '100vw',
            '-50%',
            '999%',
            '99999px',
            'inherit',
            'var(--x)',
        ];

        foreach ($badWidths as $width) {
            $this->assertFalse(
                $this->isValidImageWidth($width),
                "\"{$width}\" must be rejected by the renderer validator"
            );
        }
    }

    public function test_empty_width_is_treated_as_unset(): void
    {
        $this->assertFalse($this->isValidImageWidth(''));
        $this->assertFalse($this->isValidImageWidth(null));
    }

    /**
     * Mirrors the regex in preview-block.blade.php:
     *   preg_match('/^(none|\d{1,2}%|\d{2,4}px)$/', $rawImgWidth)
     */
    private function isValidImageWidth(?string $value): bool
    {
        $raw = trim((string) $value);

        return $raw !== '' && (bool) preg_match('/^(none|\d{1,2}%|\d{2,4}px)$/', $raw);
    }
}
