@php
    use Modules\Admin\Models\SystemSetting;

    $schoolId = current_tenant()?->id;
    
    // Core Defaults
    $theme = 'emerald_heritage';
    $fontFamily = 'inter';
    $logoHeight = '32px';
    $logoOpacity = '1.0';
    $bgOpacity = '0.08';
    $bgScaling = 'cover';
    
    $bgUrl = asset('images/School_repository_cover.jpeg');
    $logoUrl = asset('images/Transparant Logo.png');
    if (!file_exists(public_path('images/Transparant Logo.png')) && file_exists(public_path('images/Transparent Logo.png'))) {
        $logoUrl = asset('images/Transparent Logo.png');
    }

    if ($schoolId) {
        $themeSetting = SystemSetting::get('branding', 'theme');
        $theme = !empty($themeSetting) ? $themeSetting : 'emerald_heritage';

        $fontSetting = SystemSetting::get('branding', 'font_family');
        $fontFamily = !empty($fontSetting) ? $fontSetting : 'inter';

        $heightSetting = SystemSetting::get('branding', 'logo_height');
        $logoHeight = !empty($heightSetting) ? $heightSetting : '32px';

        $opacitySetting = SystemSetting::get('branding', 'logo_opacity');
        $logoOpacity = !empty($opacitySetting) ? $opacitySetting : '1.0';

        $bgOpacitySetting = SystemSetting::get('branding', 'background_opacity');
        $bgOpacity = !empty($bgOpacitySetting) ? $bgOpacitySetting : '0.08';

        $scalingSetting = SystemSetting::get('branding', 'background_scaling');
        $bgScaling = !empty($scalingSetting) ? $scalingSetting : 'cover';

        $customLogo = SystemSetting::get('branding', 'logo_path');
        if (!empty($customLogo)) {
            $logoUrl = asset('storage/' . $customLogo);
        }
        $customBg = SystemSetting::get('branding', 'bg_path');
        if (!empty($customBg)) {
            $bgUrl = asset('storage/' . $customBg);
        }
    }

    // High-Contrast Theme Gradients & Border Shadows
    $themes = [
        'emerald_heritage' => ['primary' => '#15803d', 'accent' => '#eab308', 'glow' => 'rgba(21, 128, 61, 0.12)'],
        'digital_cobalt' => ['primary' => '#3b82f6', 'accent' => '#8b5cf6', 'glow' => 'rgba(59, 130, 246, 0.12)'],
        'obsidian_gold' => ['primary' => '#18181b', 'accent' => '#d4af37', 'glow' => 'rgba(24, 24, 27, 0.12)'],
        'crimson_academy' => ['primary' => '#991b1b', 'accent' => '#475569', 'glow' => 'rgba(153, 27, 27, 0.12)'],
        'ocean_breeze' => ['primary' => '#0d9488', 'accent' => '#06b6d4', 'glow' => 'rgba(13, 148, 136, 0.12)'],
        'forest_pine' => ['primary' => '#166534', 'accent' => '#22c55e', 'glow' => 'rgba(22, 101, 52, 0.12)'],
        'sunset_amber' => ['primary' => '#ea580c', 'accent' => '#f97316', 'glow' => 'rgba(234, 88, 12, 0.12)'],
        'royal_purple' => ['primary' => '#6b21a8', 'accent' => '#a855f7', 'glow' => 'rgba(107, 33, 168, 0.12)'],
        'steel_slate' => ['primary' => '#475569', 'accent' => '#3b82f6', 'glow' => 'rgba(71, 85, 105, 0.12)'],
        'rosewood' => ['primary' => '#9f1239', 'accent' => '#f43f5e', 'glow' => 'rgba(159, 18, 57, 0.12)'],
        'dev_choice_1' => ['primary' => '#4f46e5', 'accent' => '#06b6d4', 'glow' => 'rgba(79, 70, 229, 0.12)'],
        'dev_choice_2' => ['primary' => '#c026d3', 'accent' => '#a855f7', 'glow' => 'rgba(192, 38, 211, 0.12)'],
        'dev_choice_3' => ['primary' => '#0891b2', 'accent' => '#10b981', 'glow' => 'rgba(8, 145, 178, 0.12)']
    ];

    $colors = $themes[$theme] ?? $themes['emerald_heritage'];
    $primaryColor = $colors['primary'];
    $accentColor = $colors['accent'];
    $shadowColor = $colors['glow'];

    // Typography Fonts Mapping for All 40 Select Options
    $fonts = [
        'inter' => ['css' => '"Inter", sans-serif', 'import' => 'Inter:wght@400;700'],
        'roboto' => ['css' => '"Roboto", sans-serif', 'import' => 'Roboto:wght@400;700'],
        'plus_jakarta' => ['css' => '"Plus Jakarta Sans", sans-serif', 'import' => 'Plus+Jakarta+Sans:wght@400;700'],
        'playfair' => ['css' => '"Playfair Display", serif', 'import' => 'Playfair+Display:ital,wght@0,400;0,700'],
        'outfit' => ['css' => '"Outfit", sans-serif', 'import' => 'Outfit:wght@400;700'],
        'montserrat' => ['css' => '"Montserrat", sans-serif', 'import' => 'Montserrat:wght@400;700'],
        'poppins' => ['css' => '"Poppins", sans-serif', 'import' => 'Poppins:wght@400;700'],
        'lexend' => ['css' => '"Lexend", sans-serif', 'import' => 'Lexend:wght@400;600'],
        'nunito' => ['css' => '"Nunito", sans-serif', 'import' => 'Nunito:wght@400;700'],
        'oswald' => ['css' => '"Oswald", sans-serif', 'import' => 'Oswald:wght@400;700'],
        'syncopate' => ['css' => '"Syncopate", sans-serif', 'import' => 'Syncopate:wght@400;700'],
        'merriweather' => ['css' => '"Merriweather", serif', 'import' => 'Merriweather:wght@400;700'],
        'lora' => ['css' => '"Lora", serif', 'import' => 'Lora:wght@400;700'],
        'cinzel' => ['css' => '"Cinzel", serif', 'import' => 'Cinzel:wght@400;700'],
        'crimson' => ['css' => '"Crimson Text", serif', 'import' => 'Crimson+Text:wght@400;700'],
        'eb_garamond' => ['css' => '"EB Garamond", serif', 'import' => 'EB+Garamond:wght@400;700'],
        'arvo' => ['css' => '"Arvo", serif', 'import' => 'Arvo:wght@400;700'],
        'pt_serif' => ['css' => '"PT Serif", serif', 'import' => 'PT+Serif:wght@400;700'],
        'libre_baskerville' => ['css' => '"Libre Baskerville", serif', 'import' => 'Libre+Baskerville:wght@400;700'],
        'bodoni_moda' => ['css' => '"Bodoni Moda", serif', 'import' => 'Bodoni+Moda:wght@400;700'],
        'great_vibes' => ['css' => '"Great Vibes", cursive', 'import' => 'Great+Vibes'],
        'dancing_script' => ['css' => '"Dancing Script", cursive', 'import' => 'Dancing+Script:wght@400;700'],
        'sacramento' => ['css' => '"Sacramento", cursive', 'import' => 'Sacramento'],
        'alex_brush' => ['css' => '"Alex Brush", cursive', 'import' => 'Alex+Brush'],
        'satisfy' => ['css' => '"Satisfy", cursive', 'import' => 'Satisfy'],
        'parisienne' => ['css' => '"Parisienne", cursive', 'import' => 'Parisienne'],
        'allura' => ['css' => '"Allura", cursive', 'import' => 'Allura'],
        'monsieur' => ['css' => '"Monsieur La Doulaise", cursive', 'import' => 'Monsieur+La+Doulaise'],
        'cookie' => ['css' => '"Cookie", cursive', 'import' => 'Cookie'],
        'marck_script' => ['css' => '"Marck Script", cursive', 'import' => 'Marck+Script'],
        'unifraktur_maguntia' => ['css' => '"UnifrakturMaguntia", serif', 'import' => 'UnifrakturMaguntia'],
        'courier_prime' => ['css' => '"Courier Prime", monospace', 'import' => 'Courier+Prime:wght@400;700'],
        'special_elite' => ['css' => '"Special Elite", display', 'import' => 'Special+Elite'],
        'caveat' => ['css' => '"Caveat", cursive', 'import' => 'Caveat:wght@400;700'],
        'architects_daughter' => ['css' => '"Architects Daughter", cursive', 'import' => 'Architects+Daughter'],
        'fredericka' => ['css' => '"Fredericka the Great", display', 'import' => 'Fredericka+the+Great'],
        'bungee' => ['css' => '"Bungee", sans-serif', 'import' => 'Bungee'],
        'permanent_marker' => ['css' => '"Permanent Marker", cursive', 'import' => 'Permanent+Marker'],
        'creepster' => ['css' => '"Creepster", display', 'import' => 'Creepster'],
        'rye' => ['css' => '"Rye", display', 'import' => 'Rye']
    ];

    $fontSelected = $fonts[$fontFamily] ?? $fonts['inter'];
    $fontFamilyCss = $fontSelected['css'];
    $fontImportUrl = 'https://fonts.googleapis.com/css2?family=' . $fontSelected['import'] . '&display=swap';

    $fontImportsList = [
        'Inter:wght@400;700', 'Roboto:wght@400;700', 'Plus+Jakarta+Sans:wght@400;700',
        'Playfair+Display:ital,wght@0,400;0,700', 'Outfit:wght@400;700', 'Montserrat:wght@400;700',
        'Poppins:wght@400;700', 'Lexend:wght@400;600', 'Nunito:wght@400;700', 'Oswald:wght@400;700',
        'Syncopate:wght@400;700', 'Merriweather:wght@400;700', 'Lora:wght@400;700', 'Cinzel:wght@400;700',
        'Crimson+Text:wght@400;700', 'EB+Garamond:wght@400;700', 'Arvo:wght@400;700', 'PT+Serif:wght@400;700',
        'Libre+Baskerville:wght@400;700', 'Bodoni+Moda:wght@400;700', 'Great+Vibes', 'Dancing+Script:wght@400;700',
        'Sacramento', 'Alex+Brush', 'Satisfy', 'Parisienne', 'Allura', 'Monsieur+La+Doulaise', 'Cookie',
        'Marck+Script', 'UnifrakturMaguntia', 'Courier+Prime:wght@400;700', 'Special+Elite', 'Caveat:wght@400;700',
        'Architects+Daughter', 'Fredericka+the+Great', 'Bungee', 'Permanent+Marker', 'Creepster', 'Rye'
    ];
@endphp

<!-- Dynamic Fonts Sync preconnections -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link id="schoolcore-active-font-link" rel="stylesheet" href="{{ $fontImportUrl }}">

<!-- Parallel chunk loaders for settings context -->
@if(request()->is('*/system-settings') || request()->is('*/system-settings-page'))
    @php
        $fontChunks = array_chunk($fontImportsList, 10);
        foreach ($fontChunks as $chunk) {
            $chunkUrl = 'https://fonts.googleapis.com/css2?family=' . implode('&family=', $chunk) . '&display=swap';
            echo '<link rel="stylesheet" href="' . $chunkUrl . '" media="all">';
        }
    @endphp
@endif

<!-- Dynamic styles block rendered via echo using standard PHP concatenation, keeping IDE problems clean -->
@php
    echo '<style>
        :root {
            --theme-primary: ' . $primaryColor . ';
            --theme-accent: ' . $accentColor . ';
            --theme-glow: ' . $shadowColor . ';
            --theme-font: ' . $fontFamilyCss . ';
            --theme-radius: 16px;
        }

        /* Enforce typography style globally */
        body, h1, h2, h3, h4, h5, h6, button, input, select, textarea, .fi-sidebar-item {
            font-family: var(--theme-font) !important;
        }

        /* Rich-text editor content must keep its inline font-family (B, I, colors, fonts).
           `p` and `span` are excluded from the !important override above so inline
           `font-family` styles chosen in the editor actually render. */
        .wcm-rt-editor, .wcm-rt-editor * {
            font-family: inherit;
        }

        /* Single clean chevron for studio selects: strip Tailwind/Filament background
           chevrons and native arrows so only one SVG indicator shows. The rich-text
           toolbar uses its own custom arrow (`.wcm-rt-select`), so it is excluded. */
        .wcm-shell select:not(.wcm-rt-select),
        .studio-shell select:not(.wcm-rt-select),
        select.wcm-input,
        select.studio-select-field {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2716%27 height=%2716%27 viewBox=%270 0 20 20%27 fill=%27none%27 stroke=%27%2364748b%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27%3E%3Cpath d=%27M6 9l4 4 4-4%27/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.65rem center !important;
            background-size: 1rem !important;
            padding-right: 2.4rem !important;
        }

        /* Dynamic Watermark Background applied to the active tenant workspace only */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: url("' . $bgUrl . '");
            background-repeat: no-repeat;
            background-position: center center;
            background-size: ' . $bgScaling . ';
            opacity: ' . $bgOpacity . ';
            pointer-events: none;
        }

        /* Card Edge Glow & Animation Transitions */
        .schoolcore-glowing-card {
            border-radius: var(--theme-radius) !important;
            border: 1px solid rgba(229, 231, 235, 0.4) !important;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05), var(--theme-glow) !important;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .dark .schoolcore-glowing-card {
            border: 1px solid rgba(156, 163, 175, 0.15) !important;
            box-shadow: 0 4px 25px -2px rgba(0, 0, 0, 0.4), var(--theme-glow) !important;
        }

        .schoolcore-glowing-card:hover {
            transform: translateY(-4px) scale(1.008);
            border-color: var(--theme-primary) !important;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.08), var(--theme-glow) !important;
        }

        /* ── Sidebar Item Hover Animations ── */
    .fi-sidebar-item {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    
    .fi-sidebar-item:hover {
        transform: translateX(4px) !important;
        background: color-mix(in srgb, var(--theme-primary) 6%, transparent) !important;
    }
    
    .fi-sidebar-item a {
        transition: all 0.2s ease !important;
    }
    
    .fi-sidebar-item:hover a {
        color: var(--theme-primary) !important;
    }
    
    /* ── Navigation Group Toggle Animation ── */
    .fi-sidebar-group-label {
        transition: all 0.25s ease !important;
    }
    
    .fi-sidebar-group-label:hover {
        color: var(--theme-primary) !important;
    }
    
    .fi-sidebar-group .fi-sidebar-group-items {
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    
    /* ── Scrollbar for Sidebar ── */
    .fi-sidebar-nav::-webkit-scrollbar {
        width: 4px;
    }
    
    .fi-sidebar-nav::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .fi-sidebar-nav::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 8px;
    }
    
    .fi-sidebar-nav::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
    </style>';
@endphp

<!-- Dynamic HTML5 Data Bridge containing encoded fonts catalog. Bypasses standard JSON script tag compiler errors -->
<div id="schoolcore-fonts-data-bridge" 
     data-fonts-catalog="<?php echo htmlspecialchars(json_encode($fonts), ENT_QUOTES, 'UTF-8'); ?>"
     data-selected-font="<?php echo htmlspecialchars(json_encode($fontFamily), ENT_QUOTES, 'UTF-8'); ?>"
     style="display: none;"></div>

<!-- Theme marker so CSS can target per-theme animations (e.g. developer's choice moving-gradient buttons) -->
<script>
    document.documentElement.setAttribute('data-sc-theme', '<?php echo e($theme); ?>');
</script>

<!-- Dynamic Zero-Refresh Javascript Stylist Listener -->
<script>
    window.addEventListener('theme-updated', function(event) {
        const data = Array.isArray(event.detail) ? event.detail[0] : event.detail;
        if (!data) return;

        const themeMap = {
            emerald_heritage: { primary: '#15803d', accent: '#eab308', glow: 'rgba(21, 128, 61, 0.12)' },
            digital_cobalt: { primary: '#3b82f6', accent: '#8b5cf6', glow: 'rgba(59, 130, 246, 0.12)' },
            obsidian_gold: { primary: '#18181b', accent: '#d4af37', glow: 'rgba(24, 24, 27, 0.12)' },
            crimson_academy: { primary: '#991b1b', accent: '#475569', glow: 'rgba(153, 27, 27, 0.12)' },
            ocean_breeze: { primary: '#0d9488', accent: '#06b6d4', glow: 'rgba(13, 148, 136, 0.12)' },
            forest_pine: { primary: '#166534', accent: '#22c55e', glow: 'rgba(22, 101, 52, 0.12)' },
            sunset_amber: { primary: '#ea580c', accent: '#f97316', glow: 'rgba(234, 88, 12, 0.12)' },
            royal_purple: { primary: '#6b21a8', accent: '#a855f7', glow: 'rgba(107, 33, 168, 0.12)' },
            steel_slate: { primary: '#475569', accent: '#3b82f6', glow: 'rgba(71, 85, 105, 0.12)' },
            rosewood: { primary: '#9f1239', accent: '#f43f5e', glow: 'rgba(159, 18, 57, 0.12)' },
            dev_choice_1: { primary: '#4f46e5', accent: '#06b6d4', glow: 'rgba(79, 70, 229, 0.12)' },
            dev_choice_2: { primary: '#c026d3', accent: '#a855f7', glow: 'rgba(192, 38, 211, 0.12)' },
            dev_choice_3: { primary: '#0891b2', accent: '#10b981', glow: 'rgba(8, 145, 178, 0.12)' }
        };

        const bridge = document.getElementById('schoolcore-fonts-data-bridge');
        const fontMap = bridge ? JSON.parse(bridge.getAttribute('data-fonts-catalog')) : {};

        // Keep the top bar logo within ~40px no matter what is stored.
        const capLogoHeight = (v) => {
            const n = parseInt(v, 10);
            if (isNaN(n)) return '32px';
            return Math.min(Math.max(n, 24), 40) + 'px';
        };

        const colors = themeMap[data.theme] || themeMap.emerald_heritage;
        document.documentElement.style.setProperty('--theme-primary', colors.primary);
        document.documentElement.style.setProperty('--theme-accent', colors.accent);
        document.documentElement.style.setProperty('--theme-glow', colors.glow);
        document.documentElement.setAttribute('data-sc-theme', data.theme);

        const fontSelected = fontMap[data.font_family] || fontMap.inter;
        document.documentElement.style.setProperty('--theme-font', fontSelected.css);

        const fontLinkId = 'dynamic-google-font-element-live-' + data.font_family;
        if (!document.getElementById(fontLinkId)) {
            const link = document.createElement('link');
            link.id = fontLinkId;
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/css2?family=' + fontSelected.import + '&display=swap';
            document.head.appendChild(link);
        }

        let liveOverrideBlock = document.getElementById('dynamic-styles-live-theme-override');
        if (!liveOverrideBlock) {
            liveOverrideBlock = document.createElement('style');
            liveOverrideBlock.id = 'dynamic-styles-live-theme-override';
            document.head.appendChild(liveOverrideBlock);
        }

        liveOverrideBlock.innerHTML = `
            body::before {
                background-image: url('${data.bg_url}') !important;
                background-size: ${data.background_scaling} !important;
                opacity: ${data.background_opacity} !important;
            }
            .fi-logo {
                height: ${capLogoHeight(data.logo_height)} !important;
                opacity: ${data.logo_opacity} !important;
                width: ${capLogoHeight(data.logo_height)} !important;
            }
        `;
    });

    window.addEventListener('reload-page-after-save', function() {
        setTimeout(function() {
            window.location.reload();
        }, 800);
    });
</script>