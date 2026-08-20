<!DOCTYPE html>
@php
    use Modules\CMS\Services\CmsTemplateService;

    $primary   = CmsTemplateService::safeHex($website->color_primary, '#1e3a8a');
    $secondary = CmsTemplateService::safeHex($website->color_secondary, '#0284c7');
    $accent    = CmsTemplateService::safeHex($website->color_accent, '#f59e0b');
    $bg        = CmsTemplateService::safeHex($website->color_background, '#ffffff');
    $textColor = CmsTemplateService::safeHex($website->color_text, '#0f172a');
    $cardBg    = CmsTemplateService::safeHex($website->color_card_bg, '#f8fafc');
    $onPrimary = CmsTemplateService::getAdaptiveTextColor($primary, '#ffffff', '#0f172a');

    $radiusKey = CmsTemplateService::safeToken($website->design_radius, CmsTemplateService::RADIUS_SCALE, 'md');
    $shadowKey = CmsTemplateService::safeToken($website->design_shadow, CmsTemplateService::SHADOW_SCALE, 'md');
    $containerKey = CmsTemplateService::safeToken($website->design_container, CmsTemplateService::CONTAINER_SCALE, 'wide');
    $buttonKey = CmsTemplateService::safeToken($website->design_button_style, CmsTemplateService::BUTTON_STYLES, 'pill');

    $fontPrimary = $website->font_primary ?: 'Inter';
    $fontSecondary = $website->font_secondary ?: 'Outfit';
    $templateKey = CmsTemplateService::canonicalTemplate($page->page_template ?? ($website->active_template ?: 'heritage-editorial'));

    $siteTokens = [
        '--sc-primary: ' . $primary,
        '--sc-secondary: ' . $secondary,
        '--sc-accent: ' . $accent,
        '--sc-bg: ' . $bg,
        '--sc-surface: ' . $cardBg,
        '--sc-text: ' . $textColor,
        '--sc-ink: ' . $onPrimary,
        '--sc-border: rgba(15,23,42,0.1)',
        '--sc-radius: ' . CmsTemplateService::RADIUS_SCALE[$radiusKey],
        '--sc-radius-btn: ' . CmsTemplateService::BUTTON_STYLES[$buttonKey],
        '--sc-shadow: ' . CmsTemplateService::SHADOW_SCALE[$shadowKey],
        '--sc-shadow-lg: ' . CmsTemplateService::SHADOW_SCALE[$shadowKey === 'xl' ? 'xl' : ($shadowKey === 'lg' ? 'lg' : 'lg')],
        '--sc-container: ' . ($containerKey === 'full' ? 'none' : '80rem'),
        "--sc-font-sans: '{$fontPrimary}', ui-sans-serif, system-ui, sans-serif",
        "--sc-font-display: '{$fontSecondary}', ui-sans-serif, system-ui, sans-serif",
    ];

    // ── SEO / meta chain ──
    $pageTitle = $page->seo_title ?: $page->title;
    $titleSuffix = trim((string) $website->seo_title_suffix);
    $fullTitle = $titleSuffix !== '' ? $pageTitle . ' ' . $titleSuffix : $pageTitle . ' | ' . $school->name;
    $metaDescription = trim((string) ($page->seo_description ?: $website->seo_global_description));
    if ($metaDescription === '' && $school->motto) {
        $metaDescription = trim((string) $school->motto);
    }
    $canonical = $page->canonical_url ?: url()->current();
    $ogImage = $website->seo_og_image ?: ($school->logo_path ?? null);
    if ($ogImage && ! preg_match('~^https?://~i', $ogImage)) {
        $ogImage = asset('storage/' . $ogImage);
    }
    $ogImage = $ogImage ?: asset('images/School_repository_cover.jpeg');

    $favicon = $website->favicon_path ?: ($school->logo_path ?? null);
    $faviconHref = $favicon
        ? (preg_match('~^https?://~i', $favicon) ? $favicon : asset('storage/' . $favicon))
        : 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="22" fill="' . $primary . '"/><text x="50" y="68" font-size="52" font-family="Arial, sans-serif" font-weight="700" text-anchor="middle" fill="' . $onPrimary . '">' . e(mb_substr($school->name, 0, 1)) . '</text></svg>');

    // ── Fonts: only the active pair (identity CSS handles styling) ──
    $fontFamilies = [$fontPrimary, $fontSecondary];
    $fontsUrl = CmsTemplateService::googleFontsUrl($fontFamilies, '400;500;600;700;800');

    // ── Navigation (group children under their parent for dropdowns) ──
    $navChildren = collect($navigationPages)->filter(fn ($p) => ! empty($p->parent_slug))->groupBy('parent_slug');
    $navTop = collect($navigationPages)->filter(fn ($p) => empty($p->parent_slug));

    // ── Announcement banner ──
    $announcement = collect((array) ($website->announcement_banner ?? []));
    $announceText = (string) ($announcement['text'] ?? $announcement['message'] ?? '');
    $announceEnabled = (bool) ($announcement['enabled'] ?? $announcement['show'] ?? ($announceText !== ''));

    $motionState = (($website->enable_animations ?? true) !== false) ? 'on' : 'off';
@endphp
<html lang="{{ app()->getLocale() ?: 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $fullTitle }}</title>

    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ $canonical }}">
    @php
        $robots = collect();
        if (! empty($page->noindex)) $robots->push('noindex');
        if (! empty($page->nofollow)) $robots->push('nofollow');
        $robotsMeta = $robots->isNotEmpty() ? $robots->implode(', ') : '';
    @endphp
    @if($robotsMeta !== '')
        <meta name="robots" content="{{ $robotsMeta }}">
    @endif
    <link rel="icon" href="{{ $faviconHref }}">

    @if($metaDescription !== '')
        <meta property="og:description" content="{{ $metaDescription }}">
    @endif
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $school->name }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $ogImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $fullTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImage }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $fontsUrl }}">

    {!! $schemaMarkup !!}

    {!! $website->custom_head ?? '' !!}

    @include('modules.cms.site.design-system')

    @if($website->custom_css)
        <style>{!! $website->custom_css !!}</style>
    @endif
</head>
<body>
<a href="#main-content" class="sc-skip">{{ __('Skip to main content') }}</a>

<div class="sc-site" data-sc-template="{{ $templateKey }}" data-sc-motion="{{ $motionState }}" style="{{ implode('; ', $siteTokens) }};">

    @if($announceEnabled && $announceText !== '')
        <div class="sc-announce" role="status">{{ $announceText }}</div>
    @endif

    {{-- Sticky header --}}
    <header class="sc-nav" x-data="{ menu: false }" @click.outside="menu = false" @keydown.escape.window="menu = false">
        <div class="sc-container sc-nav-inner">

            <a href="/" class="sc-brand" aria-label="{{ $school->name }}">
                @if(! empty($website->logo_light_path ?? $school->logo_path))
                    <img class="sc-brand-logo" src="{{ asset('storage/' . ($website->logo_light_path ?? $school->logo_path)) }}" alt="" width="44" height="44">
                @endif
                <span class="sc-brand-name">{{ $school->name }}</span>
            </a>

            <nav aria-label="{{ __('Primary') }}">
                <ul class="sc-nav-menu">
                    @foreach($navTop as $navPage)
                        @continue($navPage->hide_from_nav ?? false)
                        @continue(! ($navPage->is_published ?? true))

                        @php
                            $children = $navChildren[$navPage->slug] ?? collect();
                            $children = $children->where('hide_from_nav', false)->where('is_published', true)->take(6);
                            $active = $navPage->id === $page->id;
                        @endphp

                        <li class="sc-nav-item {{ $children->isNotEmpty() ? 'has-children' : '' }}">
                            <a href="{{ $navPage->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $navPage->slug]) }}"
                               class="sc-nav-link {{ $active ? 'is-active' : '' }}"
                               @if($children->isNotEmpty()) aria-haspopup="true" aria-expanded="false" @endif>
                                {{ $navPage->title }}
                                @if($children->isNotEmpty())<span class="sc-caret" aria-hidden="true">&#9662;</span>@endif
                            </a>

                            @if($children->isNotEmpty())
                                <div class="sc-nav-dropdown">
                                    @foreach($children as $child)
                                        <a class="sc-nav-dropdown-link" href="{{ $child->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $child->slug]) }}">
                                            {{ $child->title }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>

            <div class="sc-nav-actions">
                <span class="sc-nav-divider" aria-hidden="true"></span>
                <a href="/login" class="sc-btn sc-btn-ghost sc-btn-sm sc-nav-login">{{ __('Log In') }}</a>
                <a href="/apply-online" class="sc-btn sc-btn-primary sc-btn-sm">
                    <span>{{ __('Apply Online') }}</span>
                    <span class="sc-btn-arrow" aria-hidden="true">→</span>
                </a>
                <button type="button" class="sc-hamburger" @click="menu = !menu" :aria-expanded="menu ? 'true' : 'false'" aria-controls="sc-mobile-menu" aria-label="{{ __('Toggle menu') }}">
                    <svg x-show="!menu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                    <svg x-show="menu" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
        </div>

        <div id="sc-mobile-menu" class="sc-mobile-panel" x-cloak x-show="menu"
             x-transition:enter="sc-transition sc-transition-enter"
             x-transition:enter-start="sc-transition-start"
             x-transition:enter-end="sc-transition-end"
             x-transition:leave="sc-transition sc-transition-leave"
             x-transition:leave-start="sc-transition-end"
             x-transition:leave-end="sc-transition-start">
            <nav aria-label="{{ __('Mobile') }}">
                <ul class="sc-mobile-links">
                    @foreach($navigationPages as $navPage)
                        @continue($navPage->hide_from_nav ?? false)
                        @continue(! ($navPage->is_published ?? true))
                        <li>
                            <a href="{{ $navPage->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $navPage->slug]) }}"
                               class="sc-mobile-link {{ $navPage->id === $page->id ? 'is-active' : '' }}">
                                {{ $navPage->title }}
                                @if($childrenOfMobile = ($navChildren[$navPage->slug] ?? collect())->where('hide_from_nav', false)->where('is_published', true))
                                    @if($childrenOfMobile->isNotEmpty())
                                        <span aria-hidden="true">▸</span>
                                    @endif
                                @endif
                            </a>
                            @php
                                $mobileKids = ($navChildren[$navPage->slug] ?? collect())->where('hide_from_nav', false)->where('is_published', true);
                            @endphp
                            @if($mobileKids->isNotEmpty())
                                <ul style="list-style: none; padding-left: 1rem;">
                                    @foreach($mobileKids as $kid)
                                        <li>
                                            <a href="{{ $kid->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $kid->slug]) }}" class="sc-mobile-link">{{ $kid->title }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
                <div class="sc-mobile-actions">
                    <a href="/login" class="sc-btn sc-btn-surface">{{ __('Log In') }}</a>
                    <a href="/apply-online" class="sc-btn sc-btn-primary">{{ __('Apply Online') }}</a>
                </div>
            </nav>
        </div>
    </header>

    {{-- Application submitted confirmation --}}
    @if(session('application_ref'))
        @php
            $successTitle = \Modules\Admin\Models\SystemSetting::get('admission', 'success_title', 'Application Submitted!');
            $successMessage = \Modules\Admin\Models\SystemSetting::get('admission', 'success_message', 'Your online application has been submitted successfully! Save your tracking reference below to monitor your application status.');
        @endphp
        <div class="sc-announce" x-data="{ show: true }" x-show="show" x-cloak style="display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap;">
            <span><strong>{{ $successTitle }}</strong> {{ $successMessage }}</span>
            <span style="font-family: monospace; font-weight: 800; letter-spacing: 0.08em;">{{ session('application_ref') }}</span>
            <button type="button" @click="show = false" aria-label="{{ __('Dismiss') }}" style="background: none; border: none; color: inherit; cursor: pointer; font-weight: 800;">&#10005;</button>
        </div>
    @endif

    <main id="main-content" class="sc-main">
        @foreach($blocks as $block)
            @continue($block['styles']['hidden'] ?? false)

            @include('modules.cms.sections.preview-block', [
                'block' => $block,
                'stats' => $stats,
                'news' => $news,
                'events' => $events,
                'staff' => $staff,
                'page' => $page,
                'school' => $school,
                'isStudioPreview' => false,
                'theme' => [
                    'template' => $templateKey,
                    'primary' => $primary,
                    'secondary' => $secondary,
                    'accent' => $accent,
                    'background' => $bg,
                    'text' => $textColor,
                    'cardBg' => $cardBg,
                    'fontPrimary' => $fontPrimary,
                    'fontSecondary' => $fontSecondary,
                    'container' => CmsTemplateService::CONTAINER_SCALE[$containerKey],
                ],
            ])
        @endforeach
    </main>

    {{-- Footer --}}
    @php
        $footerLinks = $website->footer_menu ?? null;
        $socialLinks = collect((array) ($website->social_links ?? []));
        $socialIcons = [
            'facebook' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M13.5 21v-7h2.4l.4-3h-2.8V9.1c0-.9.3-1.5 1.6-1.5h1.3V4.9c-.3 0-1.1-.1-2-.1-2.1 0-3.6 1.3-3.6 3.7V11H8v3h2.8v7h2.7z"/></svg>',
            'x' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-4.9-6.4L6.4 22H3.3l7.3-8.3L1.2 2h6.5l4.4 5.9L18.9 2zm-1.1 18h1.7L6.9 3.8H5.1L17.8 20z"/></svg>',
            'twitter' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3l-4.9-6.4L6.4 22H3.3l7.3-8.3L1.2 2h6.5l4.4 5.9L18.9 2zm-1.1 18h1.7L6.9 3.8H5.1L17.8 20z"/></svg>',
            'instagram' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2.5" y="2.5" width="19" height="19" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.6" cy="6.4" r="1.1" fill="currentColor" stroke="none"/></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.12 1 2.5 1s2.48 1.12 2.48 2.5zM.5 8h4v15h-4V8zm7.5 0h3.8v2.05h.05c.53-1 1.83-2.05 3.77-2.05 4.03 0 4.78 2.65 4.78 6.1V23h-4v-7.5c0-1.8-.03-4.1-2.5-4.1-2.5 0-2.89 1.96-2.89 3.98V23h-4V8z"/></svg>',
            'whatsapp' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.4A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-2.9.8.8-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.6-6.1c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-2.5-1.5 6.4 6.4 0 0 1-1.6-2.6c-.1-.2 0-.4.1-.5l.5-.5c.1-.2.2-.3.3-.4.1-.2 0-.3 0-.5-.1-.2-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1s.9 2.5 1.1 2.7c.1.2 1.8 2.8 4.4 3.9 1.6.7 2.3.8 3 .7.6-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.2-.3-.2-.6-.3z"/></svg>',
            'youtube' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M23 7.2s-.2-1.5-.9-2.1c-.9-.9-1.9-.9-2.4-.9C16.6 4 12 4 12 4s-4.6 0-7.7.2c-.5 0-1.5 0-2.4.9-.7.6-.9 2.1-.9 2.1S.8 9 .8 10.8v1.7C.8 14.3 1 16.2 1 16.2s.2 1.5.9 2.1c.9.9 2 .9 2.5 1 1.8.2 7.6.2 7.6.2s4.6 0 7.7-.3c.5 0 1.5 0 2.4-.9.7-.6.9-2.1.9-2.1s.2-1.9.2-3.7v-1.7c0-1.8-.2-3.6-.2-3.6zM9.8 15.3V8.7l6.4 3.3-6.4 3.3z"/></svg>',
        ];
    @endphp
    <footer class="sc-footer">
        <div class="sc-container">
            <div class="sc-footer-grid">
                <div>
                    <a href="/" class="sc-footer-brand">
                        @if(! empty($website->logo_light_path ?? $school->logo_path))
                            <img class="sc-brand-logo" src="{{ asset('storage/' . ($website->logo_light_path ?? $school->logo_path)) }}" alt="" width="40" height="40">
                        @endif
                        {{ $school->name }}
                    </a>
                    <p class="sc-footer-about">{{ $school->motto ?: ($website->seo_global_description ?: __('A modern institution committed to academic excellence and character development.')) }}</p>
                    @if($socialLinks->isNotEmpty())
                        <div class="sc-social">
                            @foreach($socialLinks as $network => $url)
                                @if(! is_string($url) || $url === '') @continue @endif
                                <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" aria-label="{{ is_string($network) ? $network : __('Social profile') }}">
                                    {!! $socialIcons[strtolower((string) $network)] ?? mb_substr((string) $network, 0, 1) !!}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h4>{{ __('Explore') }}</h4>
                    <ul class="sc-footer-links">
                        @if(is_array($footerLinks) && count($footerLinks))
                            @foreach($footerLinks as $link)
                                @if(! is_array($link) || empty($link['url'])) @continue @endif
                                <li><a href="{{ $link['url'] }}">{{ $link['label'] ?? $link['url'] }}</a></li>
                            @endforeach
                        @else
                            @foreach($navTop->take(6) as $navPage)
                                <li><a href="{{ $navPage->is_homepage ? route('tenant.home') : route('cms-render', ['slug' => $navPage->slug]) }}">{{ $navPage->title }}</a></li>
                            @endforeach
                        @endif
                    </ul>
                </div>

                <div>
                    <h4>{{ __('Contact') }}</h4>
                    <ul class="sc-footer-links">
                        @if($school->physical_address)
                            <li><a href="#" style="pointer-events: none;">{{ $school->physical_address }}</a></li>
                        @endif
                        @if($school->phone_number)
                            <li><a href="tel:{{ preg_replace('/\s+/', '', (string) $school->phone_number) }}">{{ $school->phone_number }}</a></li>
                        @endif
                        @if($school->email_address)
                            <li><a href="mailto:{{ $school->email_address }}">{{ $school->email_address }}</a></li>
                        @endif
                    </ul>
                </div>

                <div>
                    <h4>{{ __('Admissions') }}</h4>
                    <ul class="sc-footer-links">
                        <li><a href="/apply-online">{{ __('Apply Online') }}</a></li>
                        <li><a href="/login">{{ __('Parent / Student Portal') }}</a></li>
                        <li><a href="/sitemap.xml">{{ __('Sitemap') }}</a></li>
                    </ul>
                </div>
            </div>

            <div class="sc-footer-bottom">
                <span>&copy; {{ date('Y') }} {{ $school->name }}. {{ __('All rights reserved.') }}</span>
                <span>{{ __('Powered by') }} <a href="/platform" style="font-weight: 700;">SchoolCore</a></span>
            </div>
        </div>
    </footer>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
@include('modules.cms.site.motion')

@if($website->custom_js)
    <script>{!! $website->custom_js !!}</script>
@endif
</body>
</html>
