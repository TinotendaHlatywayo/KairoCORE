@php
    use Modules\CMS\Services\CmsTemplateService;
    use Modules\CMS\Services\ComponentRegistry;

    // Normalize against the registry: ensures a valid `variant` and merged
    // per-prop defaults for every block before it reaches a section partial.
    $block = ComponentRegistry::normalizeBlock($block);

    $theme = $theme ?? [];

    // ── Resolved site tokens (from the renderer or the studio canvas) ──
    $primary      = CmsTemplateService::safeHex($theme['primary'] ?? null, '#1e3a8a');
    $secondary    = CmsTemplateService::safeHex($theme['secondary'] ?? null, '#0284c7');
    $accent       = CmsTemplateService::safeHex($theme['accent'] ?? null, '#f59e0b');
    $background   = CmsTemplateService::safeHex($theme['background'] ?? null, '#ffffff');
    $textColor    = CmsTemplateService::safeHex($theme['text'] ?? null, '#0f172a');
    $cardBg       = CmsTemplateService::safeHex($theme['cardBg'] ?? null, '#f8fafc');
    $fontPrimary  = $theme['fontPrimary'] ?? 'Inter';
    $fontSecondary= $theme['fontSecondary'] ?? 'Outfit';
    $onPrimary    = CmsTemplateService::getAdaptiveTextColor($primary, '#ffffff', '#0f172a');
    $templateKey  = CmsTemplateService::canonicalTemplate($theme['template'] ?? null);
    $siteContainerClass = $theme['container'] ?? 'max-w-7xl';

    // ── Section background ──
    $styleBgStyle   = $block['styles']['bg_style'] ?? 'solid';
    $styleBgColorRaw = $block['styles']['bg_color'] ?? '';
    $bgInherit      = ($styleBgColorRaw === '' || $styleBgColorRaw === $background);
    $styleBgColor   = $bgInherit ? 'var(--sc-bg)' : CmsTemplateService::safeHex($styleBgColorRaw, '#ffffff');
    $styleBgEnd     = CmsTemplateService::safeHex($block['styles']['bg_gradient_end'] ?? null, '#f1f5f9');
    $styleBgCss     = $styleBgStyle === 'gradient'
        ? 'background: linear-gradient(135deg, '.$styleBgColor.' 0%, '.$styleBgEnd.' 100%);'
        : 'background-color: '.$styleBgColor.';';

    $bgImage   = $block['styles']['bg_image_url'] ?? null;
    $bgOpacity = max(0, min(1, (float) ($block['styles']['bg_image_opacity'] ?? 1)));

    // ── Section text colour (adaptive when a custom bg colour is set) ──
    $textRaw = $block['styles']['text_color'] ?? '';
    $bgIsDefault = ($styleBgColorRaw === '' || $styleBgColorRaw === $background);
    if ($bgIsDefault) {
        $sectionText = ($textRaw === '' || $textRaw === $textColor)
            ? 'var(--sc-text)'
            : CmsTemplateService::safeHex($textRaw, 'var(--sc-text)');
    } else {
        $sectionText = ($textRaw === '' || $textRaw === $textColor)
            ? CmsTemplateService::getAdaptiveTextColor($styleBgColorRaw, '#ffffff', '#0f172a')
            : CmsTemplateService::safeHex($textRaw, CmsTemplateService::getAdaptiveTextColor($styleBgColorRaw, '#ffffff', '#0f172a'));
    }

    // ── Title / body typography overrides ──
    $titleFontRaw = $block['styles']['title_font'] ?? '';
    $titleFontCss = ($titleFontRaw === '' || $titleFontRaw === $fontSecondary)
        ? 'var(--sc-font-heading, var(--sc-font-display))'
        : "'".$titleFontRaw."', sans-serif";

    $titleColorRaw = $block['styles']['title_color'] ?? '';
    if ($titleColorRaw !== '') {
        $titleColorCss = 'color: '.CmsTemplateService::safeHex($titleColorRaw, 'var(--sc-text)').'; ';
    } elseif (! $bgIsDefault) {
        $titleColorCss = 'color: '.CmsTemplateService::getAdaptiveTextColor($styleBgColorRaw, '#ffffff', '#0f172a').'; ';
    } else {
        $titleColorCss = '';
    }

    $titleSizeRaw = $block['styles']['title_size'] ?? 36;
    $titleSizeCss = is_numeric($titleSizeRaw)
        ? (int) $titleSizeRaw.'px'
        : CmsTemplateService::tailwindFontSize($titleSizeRaw);

    $titleStyle = 'font-family: '.$titleFontCss.'; '.$titleColorCss
        .($titleSizeCss !== '' ? 'font-size: '.$titleSizeCss.';' : '');

    $bodyFontRaw = $block['styles']['font_family'] ?? '';
    $bodyFontCss = ($bodyFontRaw === '' || $bodyFontRaw === $fontPrimary)
        ? 'var(--sc-font-sans)'
        : "'".$bodyFontRaw."', sans-serif";

    $fontSizeRaw = $block['styles']['font_size'] ?? 16;
    $fontSizeCss = is_numeric($fontSizeRaw)
        ? (int) $fontSizeRaw.'px'
        : CmsTemplateService::tailwindFontSize($fontSizeRaw);

    $lineHeightCss = ! empty($block['styles']['line_height']) ? 'line-height: '.$block['styles']['line_height'].';' : '';

    // ── Alignment / padding / offset ──
    $alignKey = CmsTemplateService::alignToKey($block['styles']['text_align'] ?? 'text-center');
    $alignClass = 'sc-text-'.$alignKey;

    $padTop = CmsTemplateService::tailwindSpacing($block['styles']['padding_top'] ?? 'py-16', 'top') ?: '4rem';
    $padBot = CmsTemplateService::tailwindSpacing($block['styles']['padding_bottom'] ?? 'py-16', 'bottom') ?: '4rem';

    $offX = (int) ($block['styles']['offset_x'] ?? 0);
    $offY = (int) ($block['styles']['offset_y'] ?? 0);
    $offsetCss = ($offX !== 0 || $offY !== 0) ? '; position: relative; top: '.$offY.'px; left: '.$offX.'px' : '';

    // ── Section photo (content image) controls → CSS custom properties ──
    $imgFits    = ['cover' => 'cover', 'contain' => 'contain'];
    $imgPositions = ['center' => 'center', 'top' => 'top', 'bottom' => 'bottom', 'left' => 'left', 'right' => 'right'];
    $imgRatios  = ['auto' => 'auto', '16 / 9' => '16 / 9', '4 / 3' => '4 / 3', '1 / 1' => '1 / 1', '3 / 4' => '3 / 4'];
    $imgWidths  = ['none' => 'none', '70%' => '70%', '85%' => '85%'];
    $imgRadii   = ['0px' => '0px', '12px' => '12px', '24px' => '24px', '999px' => '999px'];

    $imgVars = '';
    if (isset($block['styles']['image_fit'], $imgFits[$block['styles']['image_fit']])) {
        $imgVars .= ' --sc-img-fit: '.$imgFits[$block['styles']['image_fit']].';';
    }
    if (isset($block['styles']['image_position'], $imgPositions[$block['styles']['image_position']])) {
        $imgVars .= ' --sc-img-pos: '.$imgPositions[$block['styles']['image_position']].';';
    }
    if (isset($block['styles']['image_ratio'], $imgRatios[$block['styles']['image_ratio']])) {
        $imgVars .= ' --sc-img-ratio: '.$imgRatios[$block['styles']['image_ratio']].';';
    }
    if (isset($block['styles']['image_width'], $imgWidths[$block['styles']['image_width']])) {
        $imgVars .= ' --sc-img-maxw: '.$imgWidths[$block['styles']['image_width']].';';
    }
    $imgRadius = isset($block['styles']['image_radius'], $imgRadii[$block['styles']['image_radius']])
        ? $imgRadii[$block['styles']['image_radius']]
        : '';

    // ── Gallery tile controls (styles.gallery_*) → CSS custom properties ──
    if (isset($block['styles']['gallery_fit'], $imgFits[$block['styles']['gallery_fit']])) {
        $imgVars .= ' --sc-gallery-fit: '.$imgFits[$block['styles']['gallery_fit']].';';
    }
    if (isset($block['styles']['gallery_position'], $imgPositions[$block['styles']['gallery_position']])) {
        $imgVars .= ' --sc-gallery-pos: '.$imgPositions[$block['styles']['gallery_position']].';';
    }
    $galleryRatio = isset($block['styles']['gallery_ratio'], $imgRatios[$block['styles']['gallery_ratio']])
        ? $imgRatios[$block['styles']['gallery_ratio']]
        : '';
    $galleryRadius = isset($block['styles']['gallery_radius'], $imgRadii[$block['styles']['gallery_radius']])
        ? $imgRadii[$block['styles']['gallery_radius']]
        : '';

    // ── Collection card image controls (styles.card_image_*) → CSS custom properties ──
    if (isset($block['styles']['card_image_fit'], $imgFits[$block['styles']['card_image_fit']])) {
        $imgVars .= ' --sc-card-fit: '.$imgFits[$block['styles']['card_image_fit']].';';
    }
    if (isset($block['styles']['card_image_position'], $imgPositions[$block['styles']['card_image_position']])) {
        $imgVars .= ' --sc-card-pos: '.$imgPositions[$block['styles']['card_image_position']].';';
    }
    if (isset($block['styles']['card_image_ratio'], $imgRatios[$block['styles']['card_image_ratio']])) {
        $imgVars .= ' --sc-card-ratio: '.$imgRatios[$block['styles']['card_image_ratio']].';';
    }
    $cardImageRadius = isset($block['styles']['card_image_radius'], $imgRadii[$block['styles']['card_image_radius']])
        ? $imgRadii[$block['styles']['card_image_radius']]
        : '';

    $containerKey = $block['styles']['container'] ?? 'default';
    $containerClass = 'sc-container';
    if (in_array($containerKey, ['boxed', 'wide', 'full'], true)) {
        if ($containerKey === 'boxed') {
            $containerClass = 'sc-container sc-container-boxed';
        } elseif ($containerKey === 'full') {
            $containerClass = 'sc-container sc-container-full';
        }
    } else {
        $siteKey = CmsTemplateService::containerToKey($siteContainerClass);
        if ($siteKey === 'full') {
            $containerClass = 'sc-container sc-container-full';
        } elseif ($siteKey === 'boxed') {
            $containerClass = 'sc-container sc-container-boxed';
        }
    }

    // ── Entrance animation (legacy names → design-system reveal) ──
    $animRaw = CmsTemplateService::safeAnimation($block['styles']['animate'] ?? 'none');
    $animMap = [
        'fade-in' => 'fade', 'fade-up' => 'up', 'fade-down' => 'fade',
        'slide-left' => 'left', 'slide-right' => 'right', 'zoom-in' => 'zoom',
        'bounce-subtle' => 'bounce-subtle',
    ];
    $anim = $animRaw !== 'none' ? ($animMap[$animRaw] ?? 'up') : null;
    $animAttr = $anim ? ' data-sc-reveal="'.$anim.'"' : '';

    $sectionStyle = 'style="'.$styleBgCss.' color: '.$sectionText.'; text-align: '.$alignKey
        .'; font-family: '.$bodyFontCss
        .($fontSizeCss !== '' ? '; font-size: '.$fontSizeCss : '')
        .'; '.$lineHeightCss.' padding-top: '.$padTop.'; padding-bottom: '.$padBot.$offsetCss.$imgVars.';"';

    $v = [
        'primary' => $primary,
        'secondary' => $secondary,
        'accent' => $accent,
        'bg' => $background,
        'text' => $sectionText,
        'cardBg' => $cardBg,
        'onPrimary' => $onPrimary,
        'fontPrimary' => $fontPrimary,
        'fontSecondary' => $fontSecondary,
        'titleStyle' => $titleStyle,
        'alignClass' => $alignClass,
        'align' => $alignKey,
        'containerClass' => $containerClass,
        'bgImage' => $bgImage,
        'bgOpacity' => $bgOpacity,
        'anim' => $anim,
        'imgRadius' => $imgRadius,
        'galleryRatio' => $galleryRatio,
        'galleryRadius' => $galleryRadius,
        'cardImageRadius' => $cardImageRadius,
    ];

    $rich = fn ($value) => CmsTemplateService::richText($value ?? '');
@endphp

<section class="sc-section" {!! $animAttr !!} {!! $sectionStyle !!}>

    @if($bgImage)
        <div class="sc-section-bg" role="presentation"
             style="background-image: url('{{ e($bgImage) }}'); opacity: {{ $bgOpacity }};"></div>
    @endif

    <div class="{{ $containerClass }} sc-section-content">

        @if($block['type'] === 'hero')
            @include('modules.cms.sections.hero', ['block' => $block, 'v' => $v, 'rich' => $rich, 'theme' => $theme])

        @elseif($block['type'] === 'principal_welcome')
            @include('modules.cms.sections.principal-welcome', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'about_section')
            @include('modules.cms.sections.about-section', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'academics_grid')
            @include('modules.cms.sections.academics-grid', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'statistics')
            @include('modules.cms.sections.statistics', ['block' => $block, 'v' => $v, 'stats' => $stats ?? []])

        @elseif($block['type'] === 'dynamic_news')
            @include('modules.cms.sections.dynamic-news', ['block' => $block, 'v' => $v, 'news' => $news ?? []])

        @elseif($block['type'] === 'events_calendar')
            @include('modules.cms.sections.events-calendar', ['block' => $block, 'v' => $v, 'events' => $events ?? []])

        @elseif($block['type'] === 'features_grid')
            @include('modules.cms.sections.features-grid', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'gallery')
            @include('modules.cms.sections.gallery', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'testimonials')
            @include('modules.cms.sections.testimonials', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'team_directory')
            @include('modules.cms.sections.team-directory', ['block' => $block, 'v' => $v, 'staff' => $staff ?? []])

        @elseif($block['type'] === 'faq_accordion')
            @include('modules.cms.sections.faq-accordion', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'contact_map')
            @include('modules.cms.sections.contact-map', [
                'block' => $block, 'v' => $v, 'rich' => $rich,
                'school' => $school ?? null, 'page' => $page ?? null,
                'isStudioPreview' => $isStudioPreview ?? false,
            ])

        @elseif($block['type'] === 'cta_banner')
            @include('modules.cms.sections.cta-banner', ['block' => $block, 'v' => $v, 'rich' => $rich])

        @elseif($block['type'] === 'video_embed')
            @include('modules.cms.sections.video-embed', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'logo_cloud')
            @include('modules.cms.sections.logo-cloud', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'divider')
            <hr class="sc-divider">

        @elseif($block['type'] === 'admissions_block')
            @include('modules.cms.sections.admissions-form', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'cylinder_carousel')
            @include('modules.cms.sections.cylinder-carousel', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'cinematic_scroll')
            @include('modules.cms.sections.cinematic-scroll', ['block' => $block, 'v' => $v, 'theme' => $theme, 'stats' => $stats ?? []])

        @elseif($block['type'] === 'orbit_gallery')
            @include('modules.cms.sections.orbit-gallery', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'coverflow_carousel')
            @include('modules.cms.sections.coverflow-carousel', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'marquee_ticker')
            @include('modules.cms.sections.marquee-ticker', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'kinetic_reveal_heading')
            @include('modules.cms.sections.kinetic-reveal-heading', ['block' => $block, 'v' => $v])

        @elseif($block['type'] === 'scroll_highlight_text')
            @include('modules.cms.sections.scroll-highlight-text', ['block' => $block])

        @elseif($block['type'] === 'depth_text')
            @include('modules.cms.sections.depth-text', ['block' => $block])

        @endif
    </div>
</section>
