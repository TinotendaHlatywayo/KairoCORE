<?php

namespace Modules\Finance\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class FinanceDocumentTemplate extends Model
{
    use BelongsToTenant;

    protected $table = 'finance_document_templates';

    protected $fillable = [
        'school_id',
        'document_type',
        'name',
        'design_theme',
        'is_active',
        'layout_config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'layout_config' => 'array',
    ];

    protected static function booted()
    {
        parent::booted();

        static::saved(function (FinanceDocumentTemplate $template) {
            if ($template->is_active) {
                static::query()
                    ->where('school_id', $template->school_id)
                    ->where('document_type', $template->document_type)
                    ->where('id', '!=', $template->id)
                    ->update(['is_active' => false]);
            }
        });
    }

    public static array $documentTypes = [
        'invoice' => 'Student Invoice',
        'receipt' => 'Payment Receipt',
        'statement' => 'Account Statement',
    ];

    public static array $themes = [
        'classic_line' => '1. Classic Academic (Navy Rules)',
        'modern_grid' => '2. Modern Blue (High Contrast)',
        'elegant_editorial' => '3. Elegant Serif (Maroon Accents)',
        'minimal_compact' => '4. Minimalist Compact (No Colour — Black & White)',
        'royal_crest' => '5. Royal Crest (Navy & Gold)',
        'corporate_banker' => '6. Corporate Banker (Deep Navy, Professional)',
        'university_oxford' => '7. University Scholastic (Oxford Serif)',
        'swiss_minimal' => '8. Swiss Grid (Ultra-Minimal, Monochrome)',
        'boutique_sage' => '9. Boutique Sage (Soft & Warm)',
        'executive_navy' => '10. Executive Night (Dark & Bold)',
    ];

    public static array $themeDefaults = [
        'classic_line' => [
            'header_color' => '#1e3a8a',
            'accent_color' => '#1e3a8a',
            'table_header_bg' => '#1e3a8a',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'classic',
        ],
        'modern_grid' => [
            'header_color' => '#1d4ed8',
            'accent_color' => '#2563eb',
            'table_header_bg' => '#1d4ed8',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'modern',
        ],
        'elegant_editorial' => [
            'header_color' => '#7f1d1d',
            'accent_color' => '#7f1d1d',
            'table_header_bg' => '#7f1d1d',
            'font_family' => "'Times New Roman', Times, serif",
            'structure' => 'editorial',
        ],
        'minimal_compact' => [
            'header_color' => '#111827',
            'accent_color' => '#111827',
            'table_header_bg' => '#f3f4f6',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'compact',
        ],
        'royal_crest' => [
            'header_color' => '#1e3a8a',
            'accent_color' => '#fbbf24',
            'table_header_bg' => '#1e3a8a',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'crest',
        ],
        'corporate_banker' => [
            'header_color' => '#0f2a5c',
            'accent_color' => '#0f2a5c',
            'table_header_bg' => '#0f2a5c',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'banker',
        ],
        'university_oxford' => [
            'header_color' => '#16335e',
            'accent_color' => '#9a7b1e',
            'table_header_bg' => '#16335e',
            'font_family' => 'Georgia, serif',
            'structure' => 'scholastic',
        ],
        'swiss_minimal' => [
            'header_color' => '#111827',
            'accent_color' => '#0f766e',
            'table_header_bg' => '#111827',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'swiss',
            'mono' => true,
        ],
        'boutique_sage' => [
            'header_color' => '#4a7c59',
            'accent_color' => '#b3562f',
            'table_header_bg' => '#4a7c59',
            'font_family' => 'Georgia, serif',
            'structure' => 'boutique',
        ],
        'executive_navy' => [
            'header_color' => '#0b1f3a',
            'accent_color' => '#d4af37',
            'table_header_bg' => '#0b1f3a',
            'font_family' => 'Helvetica, sans-serif',
            'structure' => 'executive',
        ],
    ];

    /**
     * Structural layout variants applied per theme so the five presets do not
     * only differ by colour but also by the arrangement of the header, title
     * and tables on the printed document.
     */
    public static array $structures = [
        'classic' => 'Classic — Centered identity, right-aligned title, ruled header',
        'modern' => 'Modern — Left identity, full-width coloured title band',
        'editorial' => 'Editorial — Serif letter-spaced centred title, thin rules',
        'compact' => 'Compact — Tight single-line header, minimal rules',
        'crest' => 'Crest — Framed centred emblem with double border',
        'banker' => 'Banker — Left identity & right refs, full-width navy title bar',
        'scholastic' => 'Scholastic — Centred double-ruled serif letterhead with gold accents',
        'swiss' => 'Swiss — Pure grid, thin rules, generous whitespace, monochrome',
        'boutique' => 'Boutique — Centred soft sage card with warm rounded corners',
        'executive' => 'Executive — Inverted dark navy header block with gold rule',
    ];

    public static array $fonts = [
        'Helvetica, sans-serif' => 'Helvetica (Default)',
        'Arial, Helvetica, sans-serif' => 'Arial',
        'Arial Black, sans-serif' => 'Arial Black',
        'Verdana, sans-serif' => 'Verdana',
        'Trebuchet MS, sans-serif' => 'Trebuchet MS',
        'Georgia, serif' => 'Georgia',
        'Times New Roman, Times, serif' => 'Times New Roman',
        'Courier New, Courier, monospace' => 'Courier New (Monospace)',
        'Comic Sans MS, cursive' => 'Comic Sans MS',
        'Impact, sans-serif' => 'Impact',
        'DejaVu Sans, sans-serif' => 'DejaVu Sans',
        'DejaVu Serif, serif' => 'DejaVu Serif',
        'DejaVu Sans Mono, monospace' => 'DejaVu Sans Mono',
    ];

    /**
     * Per-section layout defaults. `{header_color}`, `{accent_color}` and
     * `{table_header_bg}` are resolved from the active theme's flat colours.
     */
    public static array $sectionDefaults = [
        'header' => [
            'show_logo' => true,
            'logo' => '',
            'logo_position' => 'center',
            'logo_size' => 78,
            'show_school_name' => true,
            'school_name_font_size' => 22,
            'school_name_color' => '{header_color}',
            'school_name_bold' => true,
            'school_name_italic' => false,
            'show_motto' => true,
            'motto_font_size' => 12,
            'motto_color' => '#4b5563',
            'motto_italic' => true,
            'show_contact' => true,
            'contact_font_size' => 9,
            'contact_color' => '#4b5563',
        ],
        'title' => [
            'font_size' => 18,
            'color' => '{accent_color}',
            'bold' => true,
            'italic' => false,
            'extra_text' => '',
        ],
        'metadata' => [
            'font_size' => 10,
            'color' => '#374151',
            'bold' => false,
            'italic' => false,
        ],
        'table' => [
            'font_size' => 11,
            'header_bg' => '{table_header_bg}',
            'header_color' => '#ffffff',
            'header_bold' => true,
            'body_color' => '#1f2937',
        ],
        'instructions' => [
            'show' => true,
            'font_size' => 9,
            'color' => '#4b5563',
        ],
        'footer' => [
            'show_signatures' => true,
            'show_qr' => true,
            'qr_size' => 70,
            'qr_position' => 'right',
            'font_size' => 10,
            'color' => '#4b5563',
            'extra_text' => '',
        ],
    ];

    public static array $logoPositions = [
        'left' => 'Left of the school name',
        'center' => 'Centered above the school name',
        'right' => 'Right of the school name',
    ];

    /**
     * Merge the theme presets with the per-template flat layout overrides.
     * Null / empty overrides (unset form state) fall back to the theme preset.
     */
    public function resolveConfig(): array
    {
        $defaults = self::$themeDefaults[$this->design_theme] ?? self::$themeDefaults['classic_line'];

        $overrides = collect($this->layout_config ?? [])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();

        return array_merge($defaults, $overrides);
    }

    /**
     * Resolve the full per-section layout by merging theme-derived defaults
     * with the per-section user overrides stored in layout_config.
     */
    public function resolveSections(): array
    {
        return self::sectionsFor($this->design_theme ?? 'classic_line', $this->layout_config ?? [], $this->resolveConfig());
    }

    public static function sectionsFor(string $designTheme, array $layoutConfig, ?array $flatColors = null): array
    {
        $flatColors ??= self::$themeDefaults[$designTheme] ?? self::$themeDefaults['classic_line'];

        $sections = [];
        foreach (self::$sectionDefaults as $section => $defaults) {
            $resolved = [];
            foreach ($defaults as $field => $value) {
                if (is_string($value) && str_starts_with($value, '{') && str_ends_with($value, '}')) {
                    $resolved[$field] = $flatColors[trim($value, '{}')] ?? $value;
                } else {
                    $resolved[$field] = $value;
                }
            }
            if (isset($layoutConfig[$section]) && is_array($layoutConfig[$section])) {
                $overrides = array_filter(
                    $layoutConfig[$section],
                    fn ($value) => $value !== null && $value !== ''
                );
                $resolved = array_merge($resolved, $overrides);
            }
            $sections[$section] = $resolved;
        }

        return $sections;
    }

    /**
     * Resolve the active template for a school + document type, falling back to
     * the default preset so documents always render with a theme.
     */
    public static function resolveFor(int $schoolId, string $documentType): self
    {
        $template = static::where('school_id', $schoolId)
            ->where('document_type', $documentType)
            ->where('is_active', true)
            ->first();

        if ($template) {
            return $template;
        }

        $fallback = new static;
        $fallback->school_id = $schoolId;
        $fallback->document_type = $documentType;
        $fallback->name = 'Default';
        $fallback->design_theme = 'classic_line';
        $fallback->is_active = true;
        $fallback->layout_config = [];

        return $fallback;
    }
}
