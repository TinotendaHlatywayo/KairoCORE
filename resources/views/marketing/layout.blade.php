<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if (config('services.google.site_verification') || env('GOOGLE_SITE_VERIFICATION'))
        <meta name="google-site-verification" content="{{ config('services.google.site_verification') ?: env('GOOGLE_SITE_VERIFICATION') }}">
    @endif
    @if (config('services.bing.site_verification') || env('BING_SITE_VERIFICATION'))
        <meta name="msvalidate.01" content="{{ config('services.bing.site_verification') ?: env('BING_SITE_VERIFICATION') }}">
    @else
        <meta name="msvalidate.01" content="73BA1442529B66591DF90CB78368BC43">
    @endif
    @php
        $metaTitle = trim((string) $__env->yieldContent('meta_title'));
        if ($metaTitle === '') {
            $metaTitle = trim((string) $__env->yieldContent('title', platform_name().' - School Management System'));
            $seoTitleOverride = marketing_content('meta_title');
            if ($seoTitleOverride !== '') {
                $metaTitle = $seoTitleOverride;
            }
        }
        $metaDescription = trim((string) $__env->yieldContent('meta_description', platform_seo_description()));
        $seoDescriptionOverride = marketing_content('meta_description');
        if ($seoDescriptionOverride !== '') {
            $metaDescription = $seoDescriptionOverride;
        }
        $canonicalUrl = url()->current();
        $seoImageOverride = trim((string) $__env->yieldContent('seo_image'));
        $schemas = [
            platform_website_schema(),
            platform_organization_schema(),
            platform_webpage_schema($canonicalUrl, $metaTitle, $metaDescription),
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
        'seoRobots' => $seoRobots,
        'seoType' => 'website',
        'seoJsonLd' => $schemas,
    ])
    @stack('seo_jsonld')
    <link rel="icon" href="{{ platform_favicon_url() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/marketing.css'])
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
<body class="antialiased bg-white text-slate-800 flex flex-col min-h-screen selection:bg-[#D94B39] selection:text-white">
    <nav class="sticky top-0 z-50 bg-white/95 dark:bg-[#0B1120]/95 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800/80 transition-colors duration-200"
         x-data="{
             open: false,
             featuresOpen: false,
             isDark: document.documentElement.classList.contains('dark'),
             init() {
                 var self = this;
                 if (window.matchMedia) {
                     var mq = window.matchMedia('(prefers-color-scheme: dark)');
                     var handler = function(e) {
                         if (!localStorage.getItem('theme_manual')) {
                             if (e.matches) {
                                 document.documentElement.classList.add('dark');
                                 self.isDark = true;
                             } else {
                                 document.documentElement.classList.remove('dark');
                                 self.isDark = false;
                             }
                         }
                     };
                     if (mq.addEventListener) {
                         mq.addEventListener('change', handler);
                     } else if (mq.addListener) {
                         mq.addListener(handler);
                     }
                 }
                 window.addEventListener('theme-toggle-sync', function(e) {
                     self.isDark = (e.detail === 'dark');
                 });
             },
             toggleTheme() {
                 var nextDark = !this.isDark;
                 this.isDark = nextDark;
                 if (nextDark) {
                     document.documentElement.classList.add('dark');
                     localStorage.setItem('theme', 'dark');
                 } else {
                     document.documentElement.classList.remove('dark');
                     localStorage.setItem('theme', 'light');
                 }
                 localStorage.setItem('theme_manual', 'true');
                 window.dispatchEvent(new CustomEvent('theme-toggle-sync', { detail: nextDark ? 'dark' : 'light' }));
             }
         }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                {{-- Brand Logo --}}
                <div class="flex items-center gap-6">
                    <a href="{{ route('marketing.home') }}" class="flex items-center gap-2 font-bold text-lg active:scale-95 transition-transform">
                        <img src="{{ marketing_image('nav_logo', 'images/kairo-core-logo.webp') }}" alt="{{ platform_name() }} logo" width="480" height="268" class="h-11 w-auto dark:brightness-110">
                    </a>

                    {{-- Desktop Navigation Links --}}
                    <div class="hidden lg:flex lg:items-center lg:gap-2">
                        {{-- Home --}}
                        <a href="{{ route('marketing.home') }}"
                           class="nav-interactive {{ request()->routeIs('marketing.home') ? 'active-page' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                           @if(request()->routeIs('marketing.home')) aria-current="page" @endif>
                            <span>{{ __('Home') }}</span>
                            @if(request()->routeIs('marketing.home'))
                                <span class="current-dot" aria-hidden="true"></span>
                            @endif
                        </a>

                        {{-- Features Dropdown --}}
                        <div class="relative" x-on:mouseenter="featuresOpen=true" x-on:mouseleave="featuresOpen=false" @click.away="featuresOpen=false">
                            <button type="button"
                                    class="nav-interactive {{ request()->routeIs('marketing.features') ? 'active-page' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                                    @click="featuresOpen=!featuresOpen"
                                    :aria-expanded="featuresOpen"
                                    aria-haspopup="true"
                                    @if(request()->routeIs('marketing.features')) aria-current="page" @endif>
                                <span>{{ __('Features') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" :class="featuresOpen ? 'rotate-180 text-[#D94B39]' : ''" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                                </svg>
                                @if(request()->routeIs('marketing.features'))
                                    <span class="current-dot" aria-hidden="true"></span>
                                @endif
                            </button>

                            {{-- Enhanced Features Banner Mega-Menu --}}
                            <div x-show="featuresOpen" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                 x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                                 class="absolute left-0 top-full mt-2 w-[58rem] max-w-[96vw] rounded-2xl border border-slate-200/90 dark:border-slate-800 bg-white/98 dark:bg-[#0F172A]/98 shadow-2xl backdrop-blur-xl ring-1 ring-black/5 z-50 overflow-hidden">

                                <div class="p-6">
                                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 dark:border-slate-800/80">
                                        <div>
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-[#D94B39] dark:text-[#EF5F4D]">{{ __('Platform Ecosystem') }}</h4>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('Explore every integrated department and module') }}</p>
                                        </div>
                                        <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-[#D94B39] dark:text-[#EF5F4D] hover:underline active:scale-95 transition-transform">
                                            <span>{{ __('View all features') }}</span>
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                        </a>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        {{-- 1. Core --}}
                                        <a href="{{ route('marketing.features', ['category' => 'core']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5Zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Core') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Admissions, Students, Academics') }}</p>
                                            </div>
                                        </a>

                                        {{-- 2. Finance & Fees --}}
                                        <a href="{{ route('marketing.features', ['category' => 'finance']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-emerald-500/10 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Finance & Fees') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Fees, Billing, Invoicing, Accounting') }}</p>
                                            </div>
                                        </a>

                                        {{-- 3. People --}}
                                        <a href="{{ route('marketing.features', ['category' => 'people']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-purple-500/10 text-purple-600 dark:bg-purple-500/20 dark:text-purple-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('People') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('HR, Payroll, Staff') }}</p>
                                            </div>
                                        </a>

                                        {{-- 4. Health & Wellness --}}
                                        <a href="{{ route('marketing.features', ['category' => 'health']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Health & Wellness') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Clinic, Records, Follow-ups') }}</p>
                                            </div>
                                        </a>

                                        {{-- 5. Inventory & Procurement --}}
                                        <a href="{{ route('marketing.features', ['category' => 'procurement']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-amber-500/10 text-amber-600 dark:bg-amber-500/20 dark:text-amber-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Inventory & Procurement') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Stock, Requisitions, Assets') }}</p>
                                            </div>
                                        </a>

                                        {{-- 6. Communications --}}
                                        <a href="{{ route('marketing.features', ['category' => 'communications']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-teal-500/10 text-teal-600 dark:bg-teal-500/20 dark:text-teal-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.455a13.36 13.36 0 0 1-1.07-3.053m2.732-1.166a13.36 13.36 0 0 0 2.732-1.166m-2.732 1.166c.253-.962.584-1.892.985-2.783.247-.55.06-1.21-.463-1.511l-.657-.38c-.551-.318-1.26-.117-1.527.455a13.36 13.36 0 0 0-1.07 3.053m0 0a13.35 13.35 0 0 0-2.732-1.166m2.732 1.166c.253.962.584 1.892.985 2.783"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Communications') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Announcements, Email or WhatsApp Alerts') }}</p>
                                            </div>
                                        </a>

                                        {{-- 7. Academics & Exams --}}
                                        <a href="{{ route('marketing.features', ['category' => 'academics']) }}" class="feature-menu-card group">
                                            <div class="menu-icon bg-indigo-500/10 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-400">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-slate-900 dark:text-white transition-colors">{{ __('Academics & Exams') }}</p>
                                                    <svg class="arrow-hint h-3.5 w-3.5 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                                </div>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1">{{ __('Timetables, Results, Reports') }}</p>
                                            </div>
                                        </a>

                                        {{-- 8. All Features --}}
                                        <a href="{{ route('marketing.features') }}" class="feature-menu-card group bg-gradient-to-br from-[#D94B39]/5 to-[#EF5F4D]/10 dark:from-[#D94B39]/15 dark:to-[#EF5F4D]/5 border-[#D94B39]/20 dark:border-[#D94B39]/30">
                                            <div class="menu-icon bg-[#D94B39] text-white shadow-md shadow-[#D94B39]/30">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5">
                                                    <p class="text-sm font-bold text-[#D94B39] dark:text-[#EF5F4D]">{{ __('All Features') }}</p>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-extrabold uppercase bg-[#D94B39] text-white">Full</span>
                                                </div>
                                                <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5 line-clamp-1">{{ __('Explore full feature set') }}</p>
                                            </div>
                                        </a>
                                    </div>
                                </div>

                                {{-- Features Banner Bottom Strip --}}
                                <div class="px-6 py-3.5 bg-slate-50 dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">{{ __('Cloud-based, multi-language system built for modern schools.') }}</span>
                                    <a href="{{ route('register') }}" class="font-bold text-[#D94B39] dark:text-[#EF5F4D] hover:underline flex items-center gap-1">
                                        <span>{{ __('Start free registration') }}</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    </a>
                                </div>
                            </div>
                        </div>

                        {{-- Blog --}}
                        <a href="{{ route('marketing.blog') }}"
                           class="nav-interactive {{ request()->routeIs('marketing.blog*') ? 'active-page' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                           @if(request()->routeIs('marketing.blog*')) aria-current="page" @endif>
                            <span>{{ __('Blog') }}</span>
                            @if(request()->routeIs('marketing.blog*'))
                                <span class="current-dot" aria-hidden="true"></span>
                            @endif
                        </a>

                        {{-- About --}}
                        <a href="{{ route('marketing.about') }}"
                           class="nav-interactive {{ request()->routeIs('marketing.about') ? 'active-page' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                           @if(request()->routeIs('marketing.about')) aria-current="page" @endif>
                            <span>{{ __('About') }}</span>
                            @if(request()->routeIs('marketing.about'))
                                <span class="current-dot" aria-hidden="true"></span>
                            @endif
                        </a>

                        {{-- Contact --}}
                        <a href="{{ route('marketing.contact') }}"
                           class="nav-interactive {{ request()->routeIs('marketing.contact') ? 'active-page' : 'text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60' }}"
                           @if(request()->routeIs('marketing.contact')) aria-current="page" @endif>
                            <span>{{ __('Contact') }}</span>
                            @if(request()->routeIs('marketing.contact'))
                                <span class="current-dot" aria-hidden="true"></span>
                            @endif
                        </a>
                    </div>
                </div>

                {{-- Right Side Actions: Theme Toggle, Language Switcher, Platform Login, Register --}}
                <div class="flex items-center gap-2 sm:gap-3">
                    {{-- Interactive Dark/Light Theme Toggle --}}
                    <button type="button"
                            @click="toggleTheme()"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 text-slate-700 dark:text-slate-200 shadow-sm hover:bg-slate-50 dark:hover:bg-slate-700 transition-all duration-200 active:scale-90"
                            :title="isDark ? '{{ __('Switch to Light mode') }}' : '{{ __('Switch to Dark mode') }}'"
                            aria-label="{{ __('Toggle color scheme') }}">
                        {{-- Sun Icon for Dark Mode (switch to light) --}}
                        <svg x-show="isDark" x-cloak class="h-4.5 w-4.5 text-amber-400 rotate-0 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
                        </svg>
                        {{-- Moon Icon for Light Mode (switch to dark) --}}
                        <svg x-show="!isDark" class="h-4.5 w-4.5 text-slate-700 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                        </svg>
                    </button>

                    {{-- Language Selector --}}
                    <div class="relative" x-data="{ langOpen: false }">
                        <button @click="langOpen=!langOpen" @click.away="langOpen=false" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 dark:border-slate-700/80 bg-white dark:bg-slate-800/80 px-2.5 py-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition active:scale-95 shadow-sm" aria-label="{{ __('Change language') }}" aria-haspopup="true" :aria-expanded="langOpen">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.66 0 3-4.03 3-9s-1.34-9-3-9m0 18c-1.66 0-3-4.03-3-9s1.34-9 3-9m-9 9a9 9 0 019-9"/></svg>
                            <span class="uppercase tracking-wide text-xs">{{ app()->getLocale() }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="langOpen" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                             x-transition:leave-end="opacity-0 -translate-y-1 scale-95"
                             class="absolute right-0 top-full mt-2 w-48 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 shadow-xl ring-1 ring-black/5 py-1 z-50 overflow-hidden">
                            @foreach(supported_locales() as $code => $label)
                            <a href="{{ route('marketing.language', $code) }}" class="flex items-center justify-between px-3.5 py-2 text-sm transition-colors {{ app()->getLocale() === $code ? 'bg-[#D94B39]/10 font-bold text-[#D94B39] dark:text-[#EF5F4D]' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800' }}">
                                <span>{{ $label }}</span>
                                @if(app()->getLocale() === $code)
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#D94B39] dark:text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    </div>

                    {{-- Login: School or Platform --}}
                    <div class="relative hidden sm:block" x-data="{ loginOpen: false }" @click.outside="loginOpen = false">
                        <button type="button"
                                @click="loginOpen = !loginOpen"
                                :aria-expanded="loginOpen"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition active:scale-95">
                            {{ __('Login') }}
                            <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="loginOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                        </button>
                        <div x-show="loginOpen" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100"
                             x-transition:leave-end="opacity-0 -translate-y-1"
                             class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-[#0F172A] shadow-xl shadow-slate-900/10">
                            <a href="{{ route('school.login') }}" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D]">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('School Login') }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Use your school’s short code') }}</span>
                                </span>
                            </a>
                            <a href="{{ url('/platform/login') }}" class="flex items-start gap-3 border-t border-slate-100 dark:border-slate-800 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition">
                                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-[#1F2E43] dark:bg-slate-700 text-white">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                </span>
                                <span>
                                    <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ __('Platform Login') }}</span>
                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Kairo CORE administrators') }}</span>
                                </span>
                            </a>
                        </div>
                    </div>

                    {{-- Register School CTA --}}
                    <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-1.5 rounded-xl bg-[#D94B39] hover:bg-[#c14332] px-4 py-2 text-sm font-bold text-white shadow-md shadow-[#D94B39]/25 transition active:scale-95">
                        <span>{{ __('Register School') }}</span>
                    </a>

                    {{-- Mobile menu button --}}
                    <button @click="open=!open" class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800 transition active:scale-90" aria-label="Toggle menu">
                        <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Mobile Drawer Menu --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="lg:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-[#0B1120]/95 backdrop-blur-lg">
            <div class="space-y-1.5 px-4 pb-4 pt-3">
                <a href="{{ route('marketing.home') }}"
                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold transition active:scale-95 {{ request()->routeIs('marketing.home') ? 'bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D] border-l-4 border-[#D94B39]' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                    <span>{{ __('Home') }}</span>
                    @if(request()->routeIs('marketing.home'))
                        <span class="current-dot"></span>
                    @endif
                </a>
                <a href="{{ route('marketing.features') }}"
                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold transition active:scale-95 {{ request()->routeIs('marketing.features') ? 'bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D] border-l-4 border-[#D94B39]' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                    <span>{{ __('Features') }}</span>
                    @if(request()->routeIs('marketing.features'))
                        <span class="current-dot"></span>
                    @endif
                </a>
                <a href="{{ route('marketing.blog') }}"
                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold transition active:scale-95 {{ request()->routeIs('marketing.blog*') ? 'bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D] border-l-4 border-[#D94B39]' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                    <span>{{ __('Blog') }}</span>
                    @if(request()->routeIs('marketing.blog*'))
                        <span class="current-dot"></span>
                    @endif
                </a>
                <a href="{{ route('marketing.about') }}"
                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold transition active:scale-95 {{ request()->routeIs('marketing.about') ? 'bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D] border-l-4 border-[#D94B39]' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                    <span>{{ __('About') }}</span>
                    @if(request()->routeIs('marketing.about'))
                        <span class="current-dot"></span>
                    @endif
                </a>
                <a href="{{ route('marketing.contact') }}"
                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold transition active:scale-95 {{ request()->routeIs('marketing.contact') ? 'bg-[#D94B39]/10 text-[#D94B39] dark:text-[#EF5F4D] border-l-4 border-[#D94B39]' : 'text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60' }}">
                    <span>{{ __('Contact') }}</span>
                    @if(request()->routeIs('marketing.contact'))
                        <span class="current-dot"></span>
                    @endif
                </a>

                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Appearance') }}</span>
                    <button type="button" @click="toggleTheme()" class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-bold border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 active:scale-95">
                        <span x-show="!isDark">{{ __('Dark Mode') }}</span>
                        <span x-show="isDark">{{ __('Light Mode') }}</span>
                    </button>
                </div>

                <a href="{{ route('school.login') }}" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <span>{{ __('School Login') }}</span>
                    <span class="text-xs font-medium text-slate-400">{{ __('Short code') }}</span>
                </a>
                <a href="{{ url('/platform/login') }}" class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-base font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800/60">
                    <span>{{ __('Platform Login') }}</span>
                </a>
                <a href="{{ route('register') }}" class="mt-2 block rounded-xl bg-[#D94B39] px-4 py-3 text-center text-base font-bold text-white shadow-md shadow-[#D94B39]/25">{{ __('Register School') }}</a>
            </div>
        </div>
    </nav>
    <main class="flex-1">
        @yield('content')
    </main>
    <footer class="bg-[#1F2E43] text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-3">
                    <img src="{{ marketing_image('footer_logo', 'images/kairocore-logo-white.webp') }}" alt="{{ platform_name() }} logo" width="480" height="268" class="h-12 w-auto" loading="lazy">
                    <p class="text-sm text-slate-200">{{ marketing_content('footer_tagline') }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">{{ __('Product') }}</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('marketing.features') }}" class="hover:text-white">{{ __('Features') }}</a></li>
                        <li><a href="{{ route('marketing.school-fees') }}" class="hover:text-white">{{ __('Fees & Finance') }}</a></li>
                        <li><a href="{{ route('marketing.admissions') }}" class="hover:text-white">{{ __('Admissions') }}</a></li>
                        <li><a href="{{ route('marketing.security') }}" class="hover:text-white">{{ __('Security') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">{{ __('Company') }}</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('marketing.about') }}" class="hover:text-white">{{ __('About') }}</a></li>
                        <li><a href="{{ route('marketing.blog') }}" class="hover:text-white">{{ __('Blog') }}</a></li>
                        <li><a href="{{ route('marketing.contact') }}" class="hover:text-white">{{ __('Contact') }}</a></li>
                        <li><a href="{{ route('platform.terms') }}" class="hover:text-white">{{ __('Terms') }}</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">{{ __('Resources') }}</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('help.index') }}" class="hover:text-white">{{ __('Help Centre') }}</a></li>
                        <li><a href="{{ route('marketing.help.enroll') }}" class="hover:text-white">{{ __('How to enroll a student') }}</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">{{ __('Book a demo') }}</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-white/20 pt-6">
                <p class="text-sm text-slate-200 text-center">&copy; {{ date('Y') }} {{ platform_name() }}. {{ __('All rights reserved.') }}</p>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
