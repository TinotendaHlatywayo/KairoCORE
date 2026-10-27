@extends('help-centre.layout')

@section('title', __('Help Centre').' — '.platform_name())
@section('meta_description', __('Step-by-step guides for '.platform_name().': getting started, academics, students, admissions, fees, HR, report cards, permissions and troubleshooting.'))

@section('help-body')
<header class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-white via-white to-[#EF5F4D]/[0.06] p-6 sm:p-8">
    <div class="grid items-center gap-8 sm:grid-cols-[1fr_auto]">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Help Centre') }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ __('How can we help?') }}</h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ __('Verified, step-by-step guides for every part of '.platform_name().' — written from the product itself, so every button and menu we mention really exists.') }}</p>

            <form action="{{ route('help.search') }}" method="GET" role="search" class="mt-6 max-w-xl">
                <label for="hero-search" class="sr-only">{{ __('Search help articles') }}</label>
                <div class="relative">
                    <input
                        id="hero-search"
                        type="search"
                        name="q"
                        placeholder="{{ __('Search guides — fees, report cards, staff import…') }}"
                        class="w-full rounded-2xl border border-slate-200 bg-white py-3.5 pl-12 pr-32 text-sm text-slate-900 shadow-sm focus:border-[#EF5F4D] focus:outline-none focus:ring-2 focus:ring-[#EF5F4D]/20"
                    >
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                    <button type="submit" class="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-xl bg-[#EF5F4D] px-4 py-2 text-sm font-bold text-white transition hover:bg-[#D94B39]">{{ __('Search') }}</button>
                </div>
            </form>

            <div class="mt-4 flex flex-wrap gap-2 text-xs font-semibold">
                <span class="py-1 text-slate-400">{{ __('Popular:') }}</span>
                @foreach ([
                    'getting-started' => __('Getting started'),
                    'finance' => __('Fees & payments'),
                    'hr' => __('Staff import'),
                    'troubleshooting' => __('Troubleshooting'),
                ] as $quickKey => $quickLabel)
                    @if (collect($categories)->contains('key', $quickKey))
                        <a href="{{ route('help.category', $quickKey) }}" class="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-600 transition hover:border-[#EF5F4D]/50 hover:text-[#D94B39]">{{ $quickLabel }}</a>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Decorative life-buoy illustration: the classic "help" mark,
             drawn inline so the hero ships with zero external assets. --}}
        <div class="hidden sm:block" aria-hidden="true">
            <svg viewBox="0 0 220 180" class="h-40 w-52 text-[#EF5F4D]" fill="none">
                <circle cx="110" cy="90" r="62" stroke="currentColor" stroke-width="10" opacity="0.18"/>
                <circle cx="110" cy="90" r="62" stroke="currentColor" stroke-width="3.5"/>
                <circle cx="110" cy="90" r="30" stroke="currentColor" stroke-width="3.5"/>
                <path d="M110 28v32M110 120v32M48 90h32M140 90h32" stroke="currentColor" stroke-width="8" stroke-linecap="round" opacity="0.55"/>
                <rect x="150" y="22" width="54" height="34" rx="8" fill="#fff" stroke="#E2E8F0" stroke-width="2"/>
                <path d="M158 34h38M158 41h26" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"/>
                <rect x="12" y="116" width="58" height="36" rx="8" fill="#fff" stroke="#E2E8F0" stroke-width="2"/>
                <path d="M20 128h42M20 135h30" stroke="#94A3B8" stroke-width="3" stroke-linecap="round"/>
                <circle cx="42" cy="42" r="7" fill="currentColor" opacity="0.35"/>
                <circle cx="184" cy="140" r="5" fill="currentColor" opacity="0.3"/>
            </svg>
        </div>
    </div>
</header>

<h2 class="mb-4 mt-10 text-lg font-bold text-slate-900">{{ __('Browse by topic') }}</h2>
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    @foreach ($categories as $cat)
        <a href="{{ route('help.category', $cat['key']) }}"
           class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-[#EF5F4D]/40 hover:shadow-md">
            <div class="flex items-start justify-between gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[#EF5F4D]/10 text-[#D94B39] transition group-hover:bg-[#EF5F4D] group-hover:text-white">
                    @include('help-centre.partials.category-icon', ['categoryKey' => $cat['key'], 'svgClass' => 'h-5 w-5'])
                </span>
                <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $cat['count'] }} {{ $cat['count'] === 1 ? __('guide') : __('guides') }}</span>
            </div>
            <h3 class="mt-3 text-base font-bold text-slate-900 group-hover:text-[#D94B39]">{{ $cat['label'] }}</h3>
            <p class="mt-1.5 text-sm text-slate-600">{{ $cat['description'] }}</p>
        </a>
    @endforeach
</div>

<h2 class="mb-4 mt-10 text-lg font-bold text-slate-900">{{ __('All guides') }}</h2>
<ul class="divide-y divide-slate-100 rounded-2xl border border-slate-200 bg-white shadow-sm">
    @foreach ($articles as $article)
        <li>
            <a href="{{ $article->url() }}" class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-slate-50">
                <div class="min-w-0">
                    <p class="truncate font-semibold text-slate-900">{{ $article->title }}</p>
                    @if ($article->summary)
                        <p class="mt-0.5 truncate text-sm text-slate-500">{{ $article->summary }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 items-center gap-3 text-xs font-medium text-slate-400">
                    <span>{{ $article->readMinutes() }} {{ __('min read') }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-500">
                        {{ \App\Support\HelpCentre\HelpCentreRepository::CATEGORY_LABELS[$article->category] ?? $article->category }}
                    </span>
                </div>
            </a>
        </li>
    @endforeach
</ul>
@endsection
