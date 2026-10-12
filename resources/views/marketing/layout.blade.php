<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $metaTitle = trim((string) $__env->yieldContent('meta_title'));
        if ($metaTitle === '') {
            $metaTitle = trim((string) $__env->yieldContent('title', platform_name().' - School Management System'));
        }
        $metaDescription = trim((string) $__env->yieldContent('meta_description', platform_seo_description()));
    @endphp
    @include('partials.seo', [
        'seoTitle' => $metaTitle,
        'seoDescription' => $metaDescription,
        'seoCanonical' => url()->current(),
        'seoImage' => platform_og_image_url(),
        'seoRobots' => 'index, follow',
        'seoType' => 'website',
        'seoJsonLd' => [platform_website_schema(), platform_organization_schema()],
    ])
    <link rel="icon" href="{{ platform_favicon_url() }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&family=plus-jakarta-sans:400,600,700,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --bg: #FFFFFF;
            --bg-alt: #F3F5F8;
            --navy: #1F2E43;
            --text: #1F2E43;
            --text-2: #444B57;
            --text-muted: #667085;
            --coral: #EF5F4D;
            --coral-strong: #D94B39;
            --coral-text: #B93A2B;
            --border: #E2E8F0;
        }
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; color: var(--text); }
        h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', system-ui, -apple-system, 'Segoe UI', sans-serif; }
        @media (prefers-reduced-motion: reduce) {
            * { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; scroll-behavior: auto !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased bg-white text-slate-800 flex flex-col min-h-screen">
    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200/70" x-data="{ open: false, featuresOpen: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="{{ route('marketing.home') }}" class="flex items-center gap-2 font-bold text-lg">
                        <img src="{{ asset('images/kairo-core-logo.png') }}" alt="{{ platform_name() }} logo" class="h-9 w-auto" loading="lazy">
                    </a>
                    <div class="hidden lg:flex lg:items-center lg:gap-6">
                        <a href="{{ route('marketing.home') }}" class="text-sm font-medium {{ request()->routeIs('marketing.home') ? 'text-slate-900' : 'text-slate-700' }} hover:text-slate-900 transition-colors">{{ __('Home') }}</a>
                        <div class="relative" x-on:mouseenter="featuresOpen=true" x-on:mouseleave="featuresOpen=false" @click.away="featuresOpen=false">
                            <button class="flex items-center gap-1 text-sm font-medium {{ request()->routeIs('marketing.features') ? 'text-slate-900' : 'text-slate-700' }} hover:text-slate-900 transition-colors">{{ __('Features') }} <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg></button>
                            <div x-show="featuresOpen" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-1" class="absolute left-0 top-full mt-2 w-[48rem] max-w-[90vw] rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
                                    <div class="space-y-3">
                                        <a href="{{ route('marketing.features', ['category' => 'core']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Core</p><p class="text-xs text-slate-600">Admissions, Students, Academics</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'finance']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Finance &amp; Fees</p><p class="text-xs text-slate-600">Fees, Billing, Invoicing, Accounting</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'people']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">People</p><p class="text-xs text-slate-600">HR, Payroll, Staff</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'health']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Health &amp; Wellness</p><p class="text-xs text-slate-600">Clinic, Records, Follow-ups</p></a>
                                    </div>
                                    <div class="space-y-3">
                                        <a href="{{ route('marketing.features', ['category' => 'procurement']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Inventory &amp; Procurement</p><p class="text-xs text-slate-600">Stock, Requisitions, Assets</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'communications']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Communications</p><p class="text-xs text-slate-600">Announcements, Email or WhatsApp Alerts</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'academics']) }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">Academics &amp; Exams</p><p class="text-xs text-slate-600">Timetables, Results, Reports</p></a>
                                        <a href="{{ route('marketing.features') }}" class="block rounded-lg p-3 hover:bg-slate-50 transition-colors"><p class="text-sm font-semibold">All Features</p><p class="text-xs text-slate-600">Explore full feature set</p></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('marketing.about') }}" class="text-sm font-medium {{ request()->routeIs('marketing.about') ? 'text-slate-900' : 'text-slate-700' }} hover:text-slate-900 transition-colors">{{ __('About') }}</a>
                        <a href="{{ route('marketing.contact') }}" class="text-sm font-medium {{ request()->routeIs('marketing.contact') ? 'text-slate-900' : 'text-slate-700' }} hover:text-slate-900 transition-colors">{{ __('Contact') }}</a>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url('/platform') }}" class="hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('Platform Login') }}</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">{{ __('Register School') }}</a>
                    <button @click="open=!open" class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200" aria-label="Toggle menu">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-1" class="lg:hidden border-t border-slate-200">
            <div class="space-y-1 px-4 pb-3 pt-2">
                <a href="{{ route('marketing.home') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('marketing.home') ? 'text-slate-900 bg-slate-50' : 'text-slate-700' }} hover:bg-slate-50">{{ __('Home') }}</a>
                <a href="{{ route('marketing.features') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('marketing.features') ? 'text-slate-900 bg-slate-50' : 'text-slate-700' }} hover:bg-slate-50">{{ __('Features') }}</a>
                <a href="{{ route('marketing.about') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('marketing.about') ? 'text-slate-900 bg-slate-50' : 'text-slate-700' }} hover:bg-slate-50">{{ __('About') }}</a>
                <a href="{{ route('marketing.contact') }}" class="block rounded-lg px-3 py-2 text-base font-medium {{ request()->routeIs('marketing.contact') ? 'text-slate-900 bg-slate-50' : 'text-slate-700' }} hover:bg-slate-50">{{ __('Contact') }}</a>
                <a href="{{ url('/platform') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('Platform Login') }}</a>
                <a href="{{ route('register') }}" class="mt-1 block rounded-lg bg-slate-900 px-3 py-2 text-center text-base font-semibold text-white">{{ __('Register School') }}</a>
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
                    <img src="{{ asset('images/kairo-core-logo.png') }}" alt="{{ platform_name() }} logo" class="h-10 w-auto" loading="lazy">
                    <p class="text-sm text-slate-200">Cloud-based school management platform built for schools in Zimbabwe.</p>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">Product</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('marketing.features') }}" class="hover:text-white">Features</a></li>
                        <li><a href="{{ route('marketing.features') }}" class="hover:text-white">Modules</a></li>
                        <li><a href="{{ route('marketing.home') }}" class="hover:text-white">Solutions</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">Company</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('marketing.about') }}" class="hover:text-white">About</a></li>
                        <li><a href="{{ route('marketing.contact') }}" class="hover:text-white">Contact</a></li>
                        <li><a href="{{ route('platform.terms') }}" class="hover:text-white">Terms</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold mb-3">Resources</h3>
                    <ul class="space-y-2 text-sm text-slate-200">
                        <li><a href="{{ route('marketing.home') }}" class="hover:text-white">Resources</a></li>
                        <li><a href="{{ url('/platform') }}" class="hover:text-white">Live demo</a></li>
                        <li><a href="{{ route('register') }}" class="hover:text-white">Book a demo</a></li>
                    </ul>
                </div>
            </div>
            <div class="mt-8 border-t border-white/20 pt-6">
                <p class="text-sm text-slate-200 text-center">&copy; {{ date('Y') }} {{ platform_name() }}. All rights reserved.</p>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
