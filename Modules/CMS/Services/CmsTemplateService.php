<?php

namespace Modules\CMS\Services;

use Modules\CMS\Models\CmsWebsite;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\EventCalendar;
use Modules\HR\Models\Employee;

/**
 * CmsTemplateService
 *
 * Central source of truth for:
 *  - 10 Award-Winning, Agency-Grade Starter Templates
 *  - Full Default Multi-Page Block Schemas for each template
 *  - 50+ Curated Google Fonts & Font Pairings
 *  - WCAG Contrast Engine (ensures NO light/white text on light backgrounds)
 *  - Dynamic Block Data Resolution from Kairo CORE modules
 */
class CmsTemplateService
{
    public const RADIUS_SCALE = [
        'none' => '0px',
        'sm' => '6px',
        'md' => '12px',
        'lg' => '20px',
        'xl' => '32px',
        'full' => '9999px',
    ];

    public const SHADOW_SCALE = [
        'none' => 'none',
        'sm' => '0 1px 3px rgba(15,23,42,0.08)',
        'md' => '0 10px 30px -5px rgba(15,23,42,0.08), 0 4px 12px -2px rgba(15,23,42,0.04)',
        'lg' => '0 20px 40px -15px rgba(15,23,42,0.12), 0 8px 16px -4px rgba(15,23,42,0.06)',
        'xl' => '0 30px 60px -12px rgba(15,23,42,0.25)',
    ];

    public const CONTAINER_SCALE = [
        'boxed' => 'max-w-5xl',
        'wide' => 'max-w-7xl',
        'full' => 'max-w-none',
    ];

    public const BUTTON_STYLES = [
        'pill' => 'rounded-full',
        'rounded' => 'rounded-xl',
        'soft' => 'rounded-2xl',
        'square' => 'rounded-none',
    ];

    public const ANIMATIONS = [
        'none', 'fade-in', 'fade-up', 'fade-down', 'slide-left', 'slide-right', 'zoom-in', 'bounce-subtle',
    ];

    public const IMAGE_POSITIONS = [
        'top', 'center', 'bottom', 'left', 'right',
    ];

    /**
     * Rich-text editor font catalog: display name => Google Fonts CSS2 spec
     * (or null for system fonts). Used by the toolbar dropdown, the admin
     * editor font loader, and the public-site renderer so any font chosen in
     * the editor is guaranteed to render everywhere.
     */
    public const RICH_FONTS = [
        // System fonts (always available, no import needed)
        'Arial' => null,
        'Verdana' => null,
        'Tahoma' => null,
        'Trebuchet MS' => null,
        'Segoe UI' => null,
        'Georgia' => null,
        'Times New Roman' => null,
        'Palatino Linotype' => null,
        'Courier New' => null,
        'Lucida Console' => null,
        'Impact' => null,
        'Comic Sans MS' => null,

        // Modern Sans-Serif
        'Inter' => 'Inter:wght@400;500;600;700;800',
        'Roboto' => 'Roboto:wght@400;500;700',
        'Open Sans' => 'Open+Sans:wght@400;600;700',
        'Lato' => 'Lato:wght@400;700',
        'Montserrat' => 'Montserrat:wght@400;500;600;700;800',
        'Poppins' => 'Poppins:wght@400;500;600;700',
        'Raleway' => 'Raleway:wght@400;500;700',
        'Nunito' => 'Nunito:wght@400;600;700;800',
        'Plus Jakarta Sans' => 'Plus+Jakarta+Sans:wght@400;500;600;700',
        'Lexend' => 'Lexend:wght@400;600',
        'Outfit' => 'Outfit:wght@400;500;700',
        'Sora' => 'Sora:wght@400;600;700',
        'Oswald' => 'Oswald:wght@400;500;600;700',
        'Bebas Neue' => 'Bebas+Neue',
        'Bungee' => 'Bungee',

        // Classic & Editorial Serif
        'Playfair Display' => 'Playfair+Display:ital,wght@0,400;0,600;0,700;1,400',
        'Merriweather' => 'Merriweather:wght@400;700',
        'Lora' => 'Lora:wght@400;500;700',
        'Cinzel' => 'Cinzel:wght@400;700',
        'EB Garamond' => 'EB+Garamond:wght@400;600',
        'PT Serif' => 'PT+Serif:wght@400;700',
        'Bodoni Moda' => 'Bodoni+Moda:wght@400;600;700',
        'Libre Baskerville' => 'Libre+Baskerville:wght@400;700',
        'Crimson Text' => 'Crimson+Text:wght@400;600',
        'Cormorant Garamond' => 'Cormorant+Garamond:wght@400;600',
        'DM Serif Display' => 'DM+Serif+Display',

        // Script / Cursive
        'Dancing Script' => 'Dancing+Script:wght@400;500;600;700',
        'Great Vibes' => 'Great+Vibes',
        'Sacramento' => 'Sacramento',
        'Alex Brush' => 'Alex+Brush',
        'Satisfy' => 'Satisfy',
        'Parisienne' => 'Parisienne',
        'Allura' => 'Allura',
        'Cookie' => 'Cookie',
        'Caveat' => 'Caveat:wght@400;600;700',
        'Permanent Marker' => 'Permanent+Marker',
        'Architects Daughter' => 'Architects+Daughter',
        'Marck Script' => 'Marck+Script',

        // Monospace & Hand-drawn Display
        'Courier Prime' => 'Courier+Prime:wght@400;700',
        'Fira Code' => 'Fira+Code:wght@400;600',
        'Special Elite' => 'Special+Elite',
        'Fredoka' => 'Fredoka:wght@400;600',
    ];

    public static function availableFonts(): array
    {
        return [
            // Modern Sans-Serif
            'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
            'Raleway', 'Nunito', 'Ubuntu', 'Work Sans', 'Plus Jakarta Sans',
            'Outfit', 'DM Sans', 'Manrope', 'Lexend', 'Sora', 'Space Grotesk',
            'Urbanist', 'Quicksand', 'Comfortaa', 'Figtree',

            // Classic & Editorial Serif
            'Playfair Display', 'Merriweather', 'Lora', 'PT Serif', 'Cinzel',
            'Cormorant Garamond', 'EB Garamond', 'Bodoni Moda', 'Spectral',
            'DM Serif Display', 'Fraunces', 'Bitter', 'Prata', 'Newsreader',

            // Display & Accent
            'Oswald', 'Bebas Neue', 'Syne', 'Clash Display', 'Cabinet Grotesk',
            'Righteous', 'Archivo Black', 'Rubik',

            // Monospace
            'Fira Code', 'JetBrains Mono', 'Space Mono', 'Source Code Pro',

            // Primary School / Friendly
            'Fredoka', 'Sniglet', 'Baloo 2', 'Bubblegum Sans', 'Patrick Hand',
        ];
    }

    public static function availableFontsByCategory(): array
    {
        return [
            'Modern Sans-Serif' => [
                'Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins',
                'Raleway', 'Nunito', 'Ubuntu', 'Work Sans', 'Plus Jakarta Sans',
                'Outfit', 'DM Sans', 'Manrope', 'Lexend', 'Sora', 'Space Grotesk',
                'Urbanist', 'Quicksand', 'Comfortaa', 'Figtree',
            ],
            'Editorial & Serif' => [
                'Playfair Display', 'Merriweather', 'Lora', 'PT Serif', 'Cinzel',
                'Cormorant Garamond', 'EB Garamond', 'Bodoni Moda', 'Spectral',
                'DM Serif Display', 'Fraunces', 'Bitter', 'Prata',
            ],
            'Display & High Impact' => [
                'Oswald', 'Bebas Neue', 'Syne', 'Righteous', 'Archivo Black', 'Rubik',
            ],
            'Monospace' => [
                'Fira Code', 'JetBrains Mono', 'Space Mono', 'Source Code Pro',
            ],
            'Friendly / Primary' => [
                'Fredoka', 'Sniglet', 'Baloo 2', 'Bubblegum Sans', 'Patrick Hand',
            ],
        ];
    }

    /**
     * WCAG Relative Luminance calculation.
     * Guarantees contrast: returns dark text for light backgrounds (#1f2937)
     * and light text for dark backgrounds (#ffffff).
     */
    public static function isDarkColor(string $hexColor): bool
    {
        $hexColor = ltrim($hexColor, '#');
        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hexColor)) {
            return false;
        }
        $r = hexdec(substr($hexColor, 0, 2)) / 255;
        $g = hexdec(substr($hexColor, 2, 2)) / 255;
        $b = hexdec(substr($hexColor, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        $luminance = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

        return $luminance < 0.5;
    }

    public static function getAdaptiveTextColor(string $bgColor, string $lightText = '#ffffff', string $darkText = '#1f2937'): string
    {
        return self::isDarkColor($bgColor) ? $lightText : $darkText;
    }

    /**
     * The ten active system templates (new hyphenated keys).
     *
     * @return array<string, array{name: string, subtitle: string, description: string, fonts: array, palette: array, design: array}>
     */
    public static function getTemplates(): array
    {
        $templates = [];
        foreach (ComponentRegistry::SYSTEM_TEMPLATES as $key => $tpl) {
            $templates[$key] = [
                'name' => $tpl['name'],
                'subtitle' => $tpl['subtitle'],
                'description' => $tpl['description'],
                'fonts' => $tpl['fonts'],
                'palette' => $tpl['palette'],
                'design' => $tpl['design'],
            ];
        }

        return $templates;
    }

    /**
     * Legacy token-only template keys -> the new system template keys.
     */
    public static function LEGACY_TEMPLATE_MAP(): array
    {
        return [
            'modern_international' => 'heritage-editorial',
            'minimal_academic' => 'minimalist-academic',
            'warm_christian' => 'community-warm',
            'stem_academy' => 'cinematic-immersive',
            'kindergarten' => 'modern-vibrant',
        ];
    }

    /**
     * Normalise any stored template key (legacy or new) to a current one.
     */
    public static function canonicalTemplate(?string $key): string
    {
        if ($key === null || $key === '') {
            return 'heritage-editorial';
        }

        $map = self::LEGACY_TEMPLATE_MAP();

        return $map[$key] ?? (isset(self::getTemplates()[$key]) ? $key : 'heritage-editorial');
    }

    /**
     * Resolve the effective template for a page.
     *
     * `page_template` historically stores TWO kinds of values:
     *  - real template keys ("coastal-fresh") = deliberate per-page theme override
     *  - page LAYOUT ids ("home_2", "about_2") from pageLayoutsFor()
     *
     * Layout ids are NOT templates — they only describe block structure, so the
     * theme must fall back to the site-wide active template instead of the
     * hard-coded heritage fallback (which made every page render identically).
     */
    public static function resolvePageTemplate(?string $stored, ?string $siteDefault = null): string
    {
        if ($stored !== null && $stored !== '') {
            if (isset(self::getTemplates()[$stored])) {
                return $stored;
            }

            $legacyMap = self::LEGACY_TEMPLATE_MAP();
            if (isset($legacyMap[$stored])) {
                return $legacyMap[$stored];
            }
        }

        return self::canonicalTemplate($siteDefault);
    }

    /**
     * Resolve the effective theme for a page using the dedicated page_theme column.
     *
     * Resolution order:
     *  1. page_theme (explicit per-page override)
     *  2. page_template (legacy — only if it's a valid template key)
     *  3. site-wide active_template
     *  4. heritage-editorial (hard fallback)
     */
    public static function resolvePageTheme(?string $pageTheme, ?string $pageTemplate, ?string $siteDefault = null): string
    {
        // 1. Explicit per-page theme override
        if ($pageTheme !== null && $pageTheme !== '') {
            if (isset(self::getTemplates()[$pageTheme])) {
                return $pageTheme;
            }
            $legacyMap = self::LEGACY_TEMPLATE_MAP();
            if (isset($legacyMap[$pageTheme])) {
                return $legacyMap[$pageTheme];
            }
        }

        // 2. Legacy page_template (if it's a real template key, not a layout ID)
        return self::resolvePageTemplate($pageTemplate, $siteDefault);
    }

    /**
     * Produce a fresh, fully-styled starter block of a given type. Used when
     * seeding a new page layout, building templates from the live site, and by
     * the design studio's block importer (deep per-block mixing).
     */
    public static function starterBlock(string $type): array
    {
        $block = [
            'id' => uniqid('blk_'),
            'type' => $type,
            'title' => __('Section Highlight Heading'),
            'description' => __('Detailed descriptive text for this section goes here.'),
            'styles' => [
                'bg_style' => 'solid', 'bg_color' => '', 'bg_gradient_end' => '#f1f5f9',
                'text_color' => '', 'title_color' => '', 'padding_top' => 'py-16', 'padding_bottom' => 'py-16',
                'font_family' => '', 'title_font' => '',
                'font_size' => 16, 'title_size' => 36, 'line_height' => '', 'text_align' => 'text-center',
                'container' => 'default', 'animate' => 'fade-up', 'bg_image_opacity' => 1,
                'offset_x' => 0, 'offset_y' => 0, 'hidden' => false,
            ],
        ];

        match ($type) {
            'hero' => [
                $block['cta_text'] = 'Apply For Admission',
                $block['cta_url'] = '/apply-online',
                $block['secondary_cta_text'] = 'Contact Us',
                $block['secondary_cta_url'] = '/contact',
                $block['image_url'] = ComponentRegistry::placeholderUrl('campus-exterior'),
                $block['layout'] = 'image-right',
                $block['image_fit'] = 'cover',
                $block['image_position'] = 'center',
            ],
            'principal_welcome' => [
                $block['title'] = 'Welcome to Our Institution',
                $block['principal_name'] = 'Dr. Sefan Salvador',
                $block['principal_title'] = 'Executive Principal',
                $block['description'] = 'It is my privilege to welcome you to our school. We nurture moral courage and analytical capability.',
                $block['image_url'] = ComponentRegistry::placeholderUrl('staff-silhouette'),
            ],
            'about_section' => [
                $block['title'] = 'Empowering Curiosity & Ethical Growth',
                $block['mission'] = 'To nurture principled, high-achieving leaders.',
                $block['vision'] = 'Setting benchmarks in modern education.',
                $block['image_url'] = ComponentRegistry::placeholderUrl('campus-quad'),
            ],
            'academics_grid' => [
                $block['title'] = 'Comprehensive Educational Faculties',
                $block['items'] = [
                    ['title' => __('Sciences & STEM'), 'desc' => 'Robotics, biology, and chemistry labs.'],
                    ['title' => __('Humanities & Arts'), 'desc' => 'Literature, history, design, and drama.'],
                    ['title' => __('Athletics & Sports'), 'desc' => 'Swimming, football, and track events.'],
                ],
            ],
            'features_grid' => $block['features'] = [
                ['title' => __('Modern Science Labs'), 'desc' => 'Equipped with precision research apparatus.'],
                ['title' => __('Champion Sports Fields'), 'desc' => 'Nurturing physical coordination & athletics.'],
                ['title' => __('Certified Educators'), 'desc' => 'Fostering analytical thinking and innovation.'],
            ],
            'faq_accordion' => $block['faqs'] = [
                ['q' => 'What is the application deadline?', 'a' => 'Applications are reviewed on a rolling basis per academic term.'],
                ['q' => 'Are boarding hostel facilities available?', 'a' => 'Yes, our residential halls feature 24/7 warden supervision.'],
            ],
            'gallery' => [
                $block['columns'] = 3,
                $block['images'] = [
                    ['url' => ComponentRegistry::placeholderUrl('library'), 'caption' => 'Campus Library'],
                    ['url' => ComponentRegistry::placeholderUrl('science-lab'), 'caption' => 'Science Exhibition'],
                    ['url' => ComponentRegistry::placeholderUrl('sports-field'), 'caption' => 'Sports Day'],
                ],
            ],
            'testimonials' => $block['testimonials'] = [
                ['quote' => 'The faculty genuinely transformed my child\'s academic confidence.', 'name' => 'Mrs. Chikafu', 'role' => 'Parent, Grade 6'],
                ['quote' => 'Kairo CORE provided the foundation I needed for university success.', 'name' => 'T. Mangwiro', 'role' => 'Alumnus, Class of 2022'],
            ],
            'logo_cloud' => $block['logos'] = [
                ['name' => 'Cambridge Assessment', 'logo_url' => ''],
                ['name' => 'Ministry of Education', 'logo_url' => ''],
                ['name' => 'STEM Council', 'logo_url' => ''],
            ],
            'contact_map' => [
                $block['title'] = 'Visit & Contact Us',
                $block['description'] = 'Talk with our admissions team or arrange a visit to campus.',
                $block['address'] = 'Your campus address',
                $block['phone'] = '+263 000 000 000',
                $block['email'] = 'admissions@your-school.edu',
                $block['map_url'] = '',
            ],
            'cta_banner' => [
                $block['title'] = 'Give your child an exceptional start.',
                $block['description'] = 'Applications are open for the next academic year.',
                $block['cta_text'] = 'Start an application',
                $block['cta_url'] = '/apply-online',
            ],
            'video_embed' => [
                $block['title'] = 'See our campus in action',
                $block['video_url'] = '',
            ],
            default => null,
        };

        // Merge registry defaults (variant + per-prop defaults) so starter
        // blocks and cross-template borrows stay schema-consistent.
        return ComponentRegistry::normalizeBlock($block);
    }

    /** Five structural starters for each of Home, About, Admissions, News and Contact. */
    public static function pageLayoutsFor(string $slug, bool $isHomepage = false): array
    {
        $kind = $isHomepage || in_array($slug, ['home', 'index'], true) ? 'home'
            : (str_contains($slug, 'admission') || str_contains($slug, 'apply') ? 'admission'
            : (str_contains($slug, 'news') ? 'news'
            : (str_contains($slug, 'contact') ? 'contact' : 'about')));

        $sets = [
            'home' => [
                ['hero', 'principal_welcome', 'features_grid', 'gallery', 'cta_banner'],
                ['hero', 'statistics', 'about_section', 'academics_grid', 'cta_banner'],
                ['hero', 'features_grid', 'testimonials', 'dynamic_news', 'cta_banner'],
                ['hero', 'about_section', 'gallery', 'principal_welcome', 'cta_banner'],
                ['hero', 'statistics', 'academics_grid', 'testimonials', 'cta_banner'],
            ],
            'about' => [
                ['hero', 'about_section', 'principal_welcome', 'gallery'],
                ['hero', 'about_section', 'features_grid', 'testimonials'],
                ['hero', 'principal_welcome', 'statistics', 'gallery'],
                ['hero', 'about_section', 'academics_grid', 'cta_banner'],
                ['hero', 'gallery', 'testimonials', 'cta_banner'],
            ],
            'admission' => [
                ['hero', 'admissions_block', 'faq_accordion', 'cta_banner'],
                ['hero', 'about_section', 'admissions_block', 'testimonials'],
                ['hero', 'features_grid', 'admissions_block', 'faq_accordion'],
                ['hero', 'gallery', 'admissions_block', 'cta_banner'],
                ['hero', 'statistics', 'admissions_block', 'contact_map'],
            ],
            'news' => [
                ['hero', 'dynamic_news', 'events_calendar', 'cta_banner'],
                ['hero', 'events_calendar', 'dynamic_news', 'gallery'],
                ['hero', 'dynamic_news', 'gallery', 'testimonials'],
                ['hero', 'events_calendar', 'features_grid', 'cta_banner'],
                ['hero', 'dynamic_news', 'events_calendar', 'contact_map'],
            ],
            'contact' => [
                ['hero', 'contact_map', 'faq_accordion'],
                ['hero', 'contact_map', 'cta_banner', 'gallery'],
                ['hero', 'principal_welcome', 'contact_map', 'faq_accordion'],
                ['hero', 'contact_map', 'testimonials', 'cta_banner'],
                ['hero', 'about_section', 'contact_map', 'faq_accordion'],
            ],
        ];

        return collect($sets[$kind])->mapWithKeys(fn (array $blocks, int $index) => [
            $kind.'_'.($index + 1) => ['name' => ucfirst($kind).' layout '.($index + 1), 'blocks' => $blocks],
        ])->all();
    }

    public static function googleFontsUrl(array $families, string $weights = '300;400;500;600;700;800;900'): string
    {
        $families = array_unique(array_filter($families));
        if (empty($families)) {
            return '';
        }

        $formatted = array_map(function (string $f) use ($weights) {
            return 'family='.str_replace(' ', '+', trim($f)).':wght@'.$weights;
        }, $families);

        return 'https://fonts.googleapis.com/css2?'.implode('&', $formatted).'&display=swap';
    }

    /** Google Fonts CSS2 URL that loads every Google-hosted rich-text editor font. */
    public static function richFontsUrl(): string
    {
        $families = [];
        foreach (self::RICH_FONTS as $import) {
            if ($import !== null) {
                $families[] = 'family='.$import;
            }
        }

        return $families
            ? 'https://fonts.googleapis.com/css2?'.implode('&', $families).'&display=swap'
            : '';
    }

    /** A safe CSS token bridge for integrations that render a website outside the Studio. */
    public static function generateThemeCss(array $colors, array $design): string
    {
        $primary = self::safeHex($colors['primary'] ?? null, '#1e3a8a');
        $secondary = self::safeHex($colors['secondary'] ?? null, '#0284c7');
        $accent = self::safeHex($colors['accent'] ?? null, '#f59e0b');
        $background = self::safeHex($colors['bg'] ?? null, '#ffffff');
        $text = self::safeHex($colors['text'] ?? null, '#0f172a');
        $card = self::safeHex($colors['cardBg'] ?? null, '#f8fafc');
        $radius = in_array($design['radius'] ?? '', self::RADIUS_SCALE, true) ? $design['radius'] : self::RADIUS_SCALE['md'];
        $shadow = in_array($design['shadow'] ?? '', self::SHADOW_SCALE, true) ? $design['shadow'] : self::SHADOW_SCALE['md'];

        return ":root{--theme-primary:{$primary};--theme-secondary:{$secondary};--theme-accent:{$accent};--theme-bg:{$background};--theme-text:{$text};--theme-card-bg:{$card};--theme-radius:{$radius};--theme-shadow:{$shadow};}";
    }

    public static function safeHex(?string $value, string $default = '#000000'): string
    {
        if ($value && preg_match('/^#[0-9a-fA-F]{3,8}$/', $value)) {
            return $value;
        }

        return $default;
    }

    public static function safeToken(?string $value, array $allowed, string $default): string
    {
        return in_array($value, array_keys($allowed), true) ? $value : $default;
    }

    public static function safeAnimation(?string $value): string
    {
        return in_array($value, self::ANIMATIONS, true) ? $value : 'none';
    }

    /**
     * Translate a legacy Tailwind spacing class list (e.g. "py-16", "pt-8 pb-8",
     * "p-4") into a CSS length for one side. Used to keep blocks authored
     * before the design-system migration rendering correctly without Tailwind.
     * Returns '' when no applicable rule exists.
     */
    public static function tailwindSpacing(string $classes, string $side): string
    {
        if (! in_array($side, ['top', 'bottom', 'left', 'right'], true)) {
            $side = 'top';
        }

        $hits = [];
        foreach (preg_split('/\s+/', trim($classes)) as $token) {
            if (! preg_match('/^p(y|x|t|b|l|r)?-(\d+)$/', $token, $m)) {
                continue;
            }
            $rem = ((int) $m[2]) * 0.25;
            $applies = match ($token[1] ?? '') {
                't' => ['top'],
                'b' => ['bottom'],
                'l' => ['left'],
                'r' => ['right'],
                'y' => ['top', 'bottom'],
                'x' => ['left', 'right'],
                default => ['top', 'bottom', 'left', 'right'],
            };
            foreach ($applies as $apply) {
                if (! array_key_exists($apply, $hits)) {
                    $hits[$apply] = $rem;
                }
            }
        }

        return isset($hits[$side]) ? $hits[$side].'rem' : '';
    }

    /** Map a legacy Tailwind font-size utility (e.g. "text-4xl") to a CSS length. */
    public static function tailwindFontSize(string $class): string
    {
        $map = [
            'text-xs' => '0.75rem', 'text-sm' => '0.875rem', 'text-base' => '1rem',
            'text-lg' => '1.125rem', 'text-xl' => '1.25rem', 'text-2xl' => '1.5rem',
            'text-3xl' => '1.875rem', 'text-4xl' => '2.25rem', 'text-5xl' => '3rem',
            'text-6xl' => '3.75rem', 'text-7xl' => '4.5rem', 'text-8xl' => '6rem',
        ];

        return $map[$class] ?? '';
    }

    /** Map a legacy Tailwind container class to a design-system container key. */
    public static function containerToKey(string $class): string
    {
        return match ($class) {
            'max-w-5xl', 'max-w-6xl' => 'boxed',
            'max-w-7xl' => 'wide',
            'max-w-none' => 'full',
            default => 'wide',
        };
    }

    /** Map a legacy Tailwind align utility to a text-align value. */
    public static function alignToKey(?string $class): string
    {
        return match ($class) {
            'text-left' => 'left',
            'text-right' => 'right',
            'text-justify' => 'justify',
            default => 'center',
        };
    }

    /** Render a content field as safe rich text (bold, italic, lists, links, line breaks). */
    public static function richText(?string $value): string
    {
        return self::sanitizeRichText($value ?? '');
    }

    /**
     * Sanitize rich-text HTML stored by the content editors. Everything outside a
     * tiny allowlist (headings, emphasis, lists, links, line breaks) is stripped;
     * all other text is escaped. Defense-in-depth on output, so no editor or
     * storage path can introduce active markup.
     */
    public static function sanitizeRichText(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        // 1. Drop dangerous containers AND their content entirely.
        $html = preg_replace(
            '#<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|meta|link|base|svg|math)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $html
        );
        $html = preg_replace(
            '#<\s*(script|style|iframe|object|embed|form|input|button|textarea|select|meta|link|base|svg|math)\b[^>]*/?>#i',
            '',
            $html
        );

        // 2. Remove HTML comments and on* attribute injection carried inside tags.
        $html = preg_replace('#<!--.*?-->#s', '', $html);
        $html = preg_replace('#\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $html);

        $allowed = [
            'p', 'div', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'strike',
            'sub', 'sup', 'span', 'font', 'ul', 'ol', 'li', 'a', 'blockquote',
            'code', 'h3', 'h4', 'h5',
        ];

        // 3. Tokenize into tags and text; escape text, keep only allowed tags.
        $html = preg_replace_callback(
            '#(<[^>]+>)|([^<]+)#',
            fn ($m) => ! empty($m[1])
                ? self::sanitizeRichTag($m[1], $allowed)
                : e($m[2]),
            $html
        );

        return trim($html);
    }

    private static function sanitizeRichTag(string $tag, array $allowed): string
    {
        if (! preg_match('#^</?\s*([a-zA-Z0-9]+)#', $tag, $mm)) {
            return '';
        }
        $name = strtolower($mm[1]);
        if (! in_array($name, $allowed, true)) {
            return '';
        }

        $closing = str_starts_with($tag, '</');
        if ($closing) {
            return '</'.($name === 'font' ? 'span' : $name).'>';
        }

        $styles = [];
        $attrs = '';

        // Only <a> may carry attributes, and only a safe href / target.
        if ($name === 'a' && preg_match('#\bhref\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $tag, $hm)) {
            $href = trim($hm[2] ?? $hm[3] ?? $hm[4] ?? '', '"\'');
            if (self::isSafeRichUrl($href)) {
                $attrs = ' href="'.e($href).'"';
                if (stripos($tag, 'target="_blank"') !== false) {
                    $attrs .= ' target="_blank" rel="noopener noreferrer"';
                }
            }
        }

        // Inline style on span / p / div — rebuilt from a CSS-property allowlist.
        if (in_array($name, ['span', 'p', 'div', 'blockquote'], true) && preg_match('#\bstyle\s*=\s*("([^"]*)"|\'([^\']*)\')#i', $tag, $sm)) {
            $css = self::sanitizeRichStyle($sm[2] ?? $sm[3] ?? '');
            if ($css !== '') {
                $styles[] = $css;
            }
        }

        // Deprecated align attribute produced by some execCommand flows.
        if (in_array($name, ['p', 'div', 'blockquote'], true) && preg_match('#\balign\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $tag, $am)) {
            $val = strtolower(trim($am[2] ?? $am[3] ?? $am[4] ?? '', '"\''));
            if (in_array($val, ['left', 'center', 'right', 'justify'], true)) {
                $styles[] = 'text-align:'.$val;
            }
        }

        // Legacy <font face/size/color> — normalise to an inline-styled <span>.
        if ($name === 'font') {
            $name = 'span';
            if (preg_match('#\bface\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $tag, $fm)) {
                $face = html_entity_decode(trim($fm[2] ?? $fm[3] ?? $fm[4] ?? '', '"\''));
                if (preg_match('~^[a-zA-Z0-9\s,.\'"-]{1,80}$~', $face)) {
                    $styles[] = 'font-family:'.trim($face);
                }
            }
            if (preg_match('#\bsize\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $tag, $gm)) {
                $size = trim($gm[2] ?? $gm[3] ?? $gm[4] ?? '', '"\'');
                if (preg_match('~^(\d{1,2}(\.\d+)?(px|pt|em|rem|%)|xx-small|x-small|small|medium|large|x-large|xx-large|xxx-large)$~i', $size)) {
                    $styles[] = 'font-size:'.strtolower($size);
                } elseif (preg_match('~^[1-7]$~', $size)) {
                    $map = ['1' => 'xx-small', '2' => 'small', '3' => 'medium', '4' => 'large', '5' => 'x-large', '6' => 'xx-large', '7' => 'xxx-large'];
                    $styles[] = 'font-size:'.$map[$size];
                }
            }
            if (preg_match('#\bcolor\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))#i', $tag, $cm)) {
                $color = self::sanitizeRichColor(trim($cm[2] ?? $cm[3] ?? $cm[4] ?? '', '"\''));
                if ($color !== '') {
                    $styles[] = 'color:'.$color;
                }
            }
        }

        if ($styles) {
            $attrs .= ' style="'.e(implode(';', $styles).';').'"';
        }

        $selfClose = str_ends_with(trim($tag), '/>');

        return '<'.$name.$attrs.($selfClose || $name === 'br' ? ' />' : '>');
    }

    /** Rebuild a style="" value keeping only allowed CSS properties and safe values. */
    private static function sanitizeRichStyle(string $style): string
    {
        // Decode entities BEFORE splitting: browser innerHTML escapes `"` in quoted
        // font-family values as &quot;, and the `;` inside the entity would otherwise
        // corrupt the declaration split below.
        $style = html_entity_decode($style, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $rules = [];
        foreach (explode(';', $style) as $decl) {
            $decl = trim($decl);
            if ($decl === '' || ! str_contains($decl, ':')) {
                continue;
            }
            [$prop, $val] = array_map('trim', explode(':', $decl, 2));
            $prop = strtolower($prop);
            $val = self::sanitizeRichStyleValue($prop, $val);
            if ($val !== null) {
                $rules[] = $prop.':'.$val;
            }
        }

        return implode(';', $rules);
    }

    private static function sanitizeRichStyleValue(string $prop, string $val): ?string
    {
        switch ($prop) {
            case 'color':
            case 'background-color':
                $color = self::sanitizeRichColor($val);

                return $color !== '' ? $color : null;
            case 'font-family':
                // Browsers serialize `font-family: "Dancing Script"` in innerHTML as
                // `&quot;Dancing Script&quot;`; decode entities before the allowlist check
                // so quoted multi-word families survive round-tripping.
                $decoded = html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                return preg_match('~^[a-zA-Z0-9\s,.\'"-]{1,80}$~', $decoded) ? trim($decoded) : null;
            case 'font-size':
                $v = strtolower(trim($val));
                if (preg_match('~^\d{1,2}(\.\d+)?(px|pt|em|rem|%)$~', $v) && (float) $v >= 8 && (float) $v <= 96) {
                    return $v;
                }

                return in_array($v, ['xx-small', 'x-small', 'small', 'medium', 'large', 'x-large', 'xx-large', 'xxx-large'], true) ? $v : null;
            case 'font-weight':
                $v = strtolower(trim($val));

                return in_array($v, ['normal', 'bold', 'bolder', 'lighter', '100', '200', '300', '400', '500', '600', '700', '800', '900'], true) ? $v : null;
            case 'font-style':
                $v = strtolower(trim($val));

                return in_array($v, ['normal', 'italic', 'oblique'], true) ? $v : null;
            case 'text-decoration':
                $v = strtolower(trim($val));
                $parts = preg_split('#\s+#', $v);
                $parts = array_filter($parts, fn ($p) => in_array($p, ['none', 'underline', 'line-through'], true));
                $parts = array_values($parts);

                return $parts ? implode(' ', $parts) : null;
            case 'text-align':
                $v = strtolower(trim($val));

                return in_array($v, ['left', 'center', 'right', 'justify'], true) ? $v : null;
            case 'vertical-align':
                $v = strtolower(trim($val));

                return in_array($v, ['sub', 'super'], true) ? $v : null;
            default:
                return null;
        }
    }

    private static function sanitizeRichColor(string $color): string
    {
        $color = trim($color);
        if (preg_match('~^#[0-9a-f]{3,8}$~i', $color)) {
            return strtolower($color);
        }
        if (preg_match('~^(rgb|rgba|hsl|hsla)\([\d\s,./%]+\)$~i', $color)) {
            return strtolower($color);
        }
        if (preg_match('~^[a-z]{3,20}$~i', $color) && in_array(strtolower($color), [
            'black', 'white', 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink',
            'brown', 'gray', 'grey', 'silver', 'gold', 'navy', 'teal', 'maroon', 'lime',
            'olive', 'aqua', 'fuchsia', 'indigo', 'violet', 'magenta', 'crimson', 'cyan',
            'darkred', 'darkblue', 'darkgreen', 'darkorange', 'darkgray', 'darkgrey',
            'lightgray', 'lightgrey', 'lightblue', 'lightgreen', 'lightpink', 'lightyellow',
            'lightcoral', 'firebrick', 'tomato', 'salmon', 'coral', 'khaki', 'chocolate',
            'sienna', 'tan', 'wheat', 'ivory', 'honeydew', 'lavender', 'mistyrose',
            'aliceblue', 'whitesmoke', 'seashell', 'linen', 'gainsboro',
        ], true)) {
            return strtolower($color);
        }

        return '';
    }

    private static function isSafeRichUrl(string $url): bool
    {
        if ($url === '' || $url === '#') {
            return true;
        }
        if (preg_match('~^(https?:|mailto:|tel:|/|\.|#)~i', $url)) {
            return true;
        }

        return false;
    }

    public static function resolveDynamicBlockData(string $type, int $schoolId): array
    {
        try {
            switch ($type) {
                case 'news_feed':
                    if (class_exists(Announcement::class)) {
                        return Announcement::where('school_id', $schoolId)
                            ->latest()->take(6)->get()->toArray();
                    }

                    return [];

                case 'events_calendar':
                    if (class_exists(EventCalendar::class)) {
                        return EventCalendar::where('school_id', $schoolId)
                            ->where('start_time', '>=', now()->subDay())
                            ->orderBy('start_time', 'asc')->take(6)->get()->toArray();
                    }

                    return [];

                case 'staff_directory':
                    if (class_exists(Employee::class)) {
                        return Employee::where('school_id', $schoolId)
                            ->where('status', 'active')
                            ->take(12)->get()->toArray();
                    }

                    return [];

                default:
                    return [];
            }
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }
}
