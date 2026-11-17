<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        // Google ownership is already proven by the DNS TXT record. Keeping
        // the same token in the head as well makes URL-prefix properties
        // verify too, so Search Console never drops the property.
        $googleVerification = config('services.google.site_verification') ?: env('GOOGLE_SITE_VERIFICATION', 'vWazvLJpCIkzWl6XgC037RcvgEMng_Y5b9QOyQuV6yY');
    @endphp
    @if ($googleVerification)
        <meta name="google-site-verification" content="{{ $googleVerification }}">
    @endif
    @if (config('services.bing.site_verification') || env('BING_SITE_VERIFICATION'))
        <meta name="msvalidate.01" content="{{ config('services.bing.site_verification') ?: env('BING_SITE_VERIFICATION') }}">
    @else
        <meta name="msvalidate.01" content="73BA1442529B66591DF90CB78368BC43">
    @endif
    @php
        // Page-specific titles are what make each URL rank for its own
        // intent. The global marketing title is only a fallback for pages
        // that do not declare one; it must never overwrite /about or /features.
        $metaTitle = trim((string) $__env->yieldContent('meta_title'));
        if ($metaTitle === '') {
            $metaTitle = trim((string) $__env->yieldContent('title'));
        }
        if ($metaTitle === '') {
            $metaTitle = marketing_content('meta_title') ?: platform_name().' - School Management System';
        }

        $metaDescription = trim((string) $__env->yieldContent('meta_description'));
        if ($metaDescription === '') {
            $metaDescription = marketing_content('meta_description') ?: platform_seo_description();
        }

        $canonicalUrl = url()->current();
        $seoImageOverride = trim((string) $__env->yieldContent('seo_image'));
        $seoImageAlt = trim((string) $__env->yieldContent('seo_image_alt')) ?: platform_name().' school management system';
        $seoSchemaImage = trim((string) $__env->yieldContent('seo_schema_image'))
            ?: $seoImageOverride
            ?: platform_og_image_url();
        $seoImageSecondary = trim((string) $__env->yieldContent('seo_image_secondary'));

        $schemas = [
            platform_website_schema(),
            platform_organization_schema(),
            platform_webpage_schema($canonicalUrl, $metaTitle, $metaDescription, $seoSchemaImage),
        ];
        if (isset($seoJsonLd) && is_array($seoJsonLd)) {
            $schemas = array_merge($schemas, $seoJsonLd);
        }
    @endphp
    @php
        $seoRobots = trim((string) $__env->yieldContent('seo_robots'));
        if ($seoRobots === '') {
            $seoRobots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
        }
    @endphp
    @include('partials.seo', [
        'seoTitle' => $metaTitle,
        'seoDescription' => $metaDescription,
        'seoCanonical' => $canonicalUrl,
        'seoImage' => $seoImageOverride !== '' ? $seoImageOverride : platform_og_image_url(),
        'seoImageAlt' => $seoImageAlt,
        'seoImageSecondary' => $seoImageSecondary,
        'seoRobots' => $seoRobots,
        'seoType' => 'website',
        'seoJsonLd' => $schemas,
    ])
    @stack('seo_jsonld')
    <link rel="icon" href="{{ platform_favicon_url() }}">
    <meta name="theme-color" content="#EF5F4D">
    <link rel="alternate" type="text/plain" href="{{ url('/llms.txt') }}" title="Kairo CORE information for AI assistants">
    <link rel="alternate" type="text/plain" href="{{ url('/llms-full.txt') }}" title="Kairo CORE help guides for AI assistants">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800&family=inter:400,500,600,700,800&family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/marketing.css', 'resources/css/kairo-site.css', 'resources/js/kairo-site.js'])
    <script>
        (function () {
            var userTheme = localStorage.getItem('theme');
            var systemDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (userTheme === 'dark' || (!userTheme && systemDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #FFFFFF;
            --bg-alt: #F8FAFC;
            --navy: #1F2E43;
            --text: #1F2E43;
            --text-2: #444B57;
            --text-muted: #667085;
            --coral: #EF5F4D;
            --coral-strong: #D94B39;
            --coral-text: #B93A2B;
            --border: #E2E8F0;
        }
        html.dark {
            --bg: #0B1120;
            --bg-alt: #0F172A;
            --navy: #0B1120;
            --text: #F8FAFC;
            --text-2: #CBD5E1;
            --text-muted: #94A3B8;
            --coral: #EF5F4D;
            --coral-strong: #D94B39;
            --coral-text: #FCA5A5;
            --border: #1E293B;
            color-scheme: dark;
        }
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; color: var(--text); background-color: var(--bg); transition: background-color .25s ease, color .25s ease; }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }

        /* Navigation Interactive States */
        .nav-interactive {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.5rem 0.875rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
            text-decoration: none;
            user-select: none;
        }
        .nav-interactive:hover {
            transform: translateY(-1px);
        }
        .nav-interactive:active {
            transform: translateY(1px) scale(0.96);
            transition-duration: 0.08s;
        }
        .nav-interactive.active-page {
            color: #D94B39;
            background: rgba(217, 75, 57, 0.08);
            box-shadow: inset 0 0 0 1px rgba(217, 75, 57, 0.18);
        }
        html.dark .nav-interactive.active-page {
            color: #EF5F4D;
            background: rgba(239, 95, 77, 0.14);
            box-shadow: inset 0 0 0 1px rgba(239, 95, 77, 0.3);
        }
        .nav-interactive .current-dot {
            width: 0.375rem;
            height: 0.375rem;
            border-radius: 9999px;
            background-color: #D94B39;
            box-shadow: 0 0 8px rgba(217, 75, 57, 0.6);
            animation: pulseDot 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        html.dark .nav-interactive .current-dot {
            background-color: #EF5F4D;
            box-shadow: 0 0 8px rgba(239, 95, 77, 0.8);
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Features Dropdown Menu Item */
        .feature-menu-card {
            display: flex;
            align-items: flex-start;
            gap: 0.875rem;
            padding: 0.875rem;
            border-radius: 1rem;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
        }
        .feature-menu-card:hover {
            background: #F8FAFC;
            border-color: #E2E8F0;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px -4px rgba(31, 46, 67, 0.08);
        }
        html.dark .feature-menu-card:hover {
            background: #1E293B;
            border-color: #334155;
            box-shadow: 0 6px 20px -4px rgba(0, 0, 0, 0.4);
        }
        .feature-menu-card:active {
            transform: scale(0.98);
            transition-duration: 0.08s;
        }
        .feature-menu-card .menu-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.625rem;
            height: 2.625rem;
            border-radius: 0.75rem;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }
        .feature-menu-card:hover .menu-icon {
            transform: scale(1.1) rotate(2deg);
        }
        .feature-menu-card .arrow-hint {
            opacity: 0;
            transform: translateX(-4px);
            transition: all 0.2s ease;
        }
        .feature-menu-card:hover .arrow-hint {
            opacity: 1;
            transform: translateX(0);
        }

        /* Dark Mode Utility Classes for Whole Marketing Area */
        html.dark body { background-color: #0B1120; color: #F1F5F9; }
        html.dark nav { background-color: rgba(11, 17, 32, 0.92) !important; border-color: rgba(30, 41, 59, 0.8) !important; }
        html.dark .bg-white { background-color: #0F172A !important; }
        html.dark .bg-slate-50, html.dark .bg-slate-50\/60 { background-color: #0B1120 !important; }
        html.dark .bg-slate-100 { background-color: #1E293B !important; }
        html.dark .text-slate-900 { color: #F8FAFC !important; }
        html.dark .text-slate-800 { color: #F1F5F9 !important; }
        html.dark .text-slate-700 { color: #CBD5E1 !important; }
        html.dark .text-slate-600 { color: #94A3B8 !important; }
        html.dark .text-slate-500 { color: #64748B !important; }
        html.dark .text-slate-400 { color: #64748B !important; }
        html.dark .border-slate-200, html.dark .border-slate-200\/80, html.dark .border-slate-200\/70, html.dark .border-slate-100 { border-color: #1E293B !important; }
        html.dark .border-slate-300 { border-color: #334155 !important; }
        html.dark .card-lift { background-color: #0F172A !important; border-color: #1E293B !important; }
        html.dark .card-lift:hover { box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.5) !important; }
        html.dark .shadow-sm { box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.3) !important; }
        html.dark .shadow-md { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.4) !important; }
        html.dark .shadow-xl { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5) !important; }
        html.dark .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7) !important; }

        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; scroll-behavior: auto !important; }
        }
    </style>
    @stack('styles')
</head>
@php($v2Page = trim((string) $__env->yieldContent('body_class')) !== '')
<body class="antialiased bg-white text-slate-800 flex flex-col min-h-screen selection:bg-[#D94B39] selection:text-white @yield('body_class')">
    <div class="kf-scroll-progress" id="pg"></div>
    @include('marketing.partials.site-nav')
    <main class="flex-1">
        @yield('content')
    </main>
    @include('marketing.partials.site-footer')
    @stack('scripts')
</body>
</html>
