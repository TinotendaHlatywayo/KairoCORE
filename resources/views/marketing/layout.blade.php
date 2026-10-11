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
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="antialiased bg-white text-slate-800 flex flex-col min-h-screen">
    <nav class="sticky top-0 z-50 bg-white/90 backdrop-blur border-b border-slate-200/70" x-data="{ open: false, featuresOpen: false }">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-6">
                    <a href="{{ route('marketing.home') }}" class="flex items-center gap-2 font-bold text-lg">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-white text-sm">KC</span>
                        <span>{{ platform_name() }}</span>
                    </a>
                    <div class="hidden lg:flex lg:items-center lg:gap-6">
                        <a href="{{ route('marketing.home') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">{{ __('Home') }}</a>
                        <div class="relative" x-on:mouseenter="featuresOpen=true" x-on:mouseleave="featuresOpen=false">
                            <button class="flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-slate-900">{{ __('Features') }} <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg></button>
                            <div x-show="featuresOpen" x-cloak class="absolute left-0 top-full mt-2 w-[48rem] rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5">
                                <div class="grid grid-cols-2 gap-6 p-6">
                                    <div class="space-y-3">
                                        <a href="{{ route('marketing.features', ['category' => 'core']) }}" class="block rounded-lg p-3 hover:bg-slate-50"><p class="text-sm font-semibold">Core</p><p class="text-xs text-slate-600">Admissions, Students, Academics</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'finance']) }}" class="block rounded-lg p-3 hover:bg-slate-50"><p class="text-sm font-semibold">Finance</p><p class="text-xs text-slate-600">Fees, Billing, Invoicing</p></a>
                                        <a href="{{ route('marketing.features', ['category' => 'people']) }}" class="block rounded-lg p-3 hover:bg-slate-50"><p class="text-sm font-semibold">People</p><p class="text-xs text-slate-600">HR, Payroll, Staff</p></a>
                                    </div>
                                    <div class="space-y-3">
                                        <a href="{{ route('marketing.features') }}" class="block rounded-lg p-3 hover:bg-slate-50"><p class="text-sm font-semibold">All Features</p><p class="text-xs text-slate-600">Explore full feature set</p></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ route('marketing.about') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">{{ __('About') }}</a>
                        <a href="{{ route('marketing.contact') }}" class="text-sm font-medium text-slate-700 hover:text-slate-900">{{ __('Contact') }}</a>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ url('/platform') }}" class="hidden sm:inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-100">{{ __('Platform Login') }}</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">{{ __('Register School') }}</a>
                    <button @click="open=!open" class="lg:hidden inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        <div x-show="open" x-cloak class="lg:hidden border-t border-slate-200">
            <div class="space-y-1 px-4 pb-3 pt-2">
                <a href="{{ route('marketing.home') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('Home') }}</a>
                <a href="{{ route('marketing.features') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('Features') }}</a>
                <a href="{{ route('marketing.about') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('About') }}</a>
                <a href="{{ route('marketing.contact') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('Contact') }}</a>
                <a href="{{ url('/platform') }}" class="block rounded-lg px-3 py-2 text-base font-medium text-slate-700 hover:bg-slate-50">{{ __('Platform Login') }}</a>
                <a href="{{ route('register') }}" class="mt-1 block rounded-lg bg-slate-900 px-3 py-2 text-center text-base font-semibold text-white">{{ __('Register School') }}</a>
            </div>
        </div>
    </nav>
    <main class="flex-1">
        @yield('content')
    </main>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} {{ platform_name() }}. All rights reserved.</p>
                <div class="flex gap-6 text-sm text-slate-600">
                    <a href="{{ route('terms.platform') }}" class="hover:text-slate-900">{{ __('Terms') }}</a>
                    <a href="{{ route('marketing.contact') }}" class="hover:text-slate-900">{{ __('Contact') }}</a>
                </div>
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
