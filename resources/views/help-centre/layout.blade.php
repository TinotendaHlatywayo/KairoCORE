@extends('marketing.layout')

{{-- Shared chrome for every /docs page: search box, category sidebar, and the
     full stylesheet for article bodies (callouts, tables, TOC) — the site has
     no typography plugin, so help articles carry their own scoped styles. --}}
@push('styles')
<style>
    /* ── Article body typography (plain CSS: this block never passes
         through the Tailwind build, so no @apply here) ─────────────── */
    .help-article { color: #334155; line-height: 1.7; }
    .help-article > * + * { margin-top: 1.25rem; }
    .help-article h1 { margin: 0 0 .5rem; font-size: 1.875rem; font-weight: 800; letter-spacing: -0.02em; color: #0f172a; }
    .help-article h2 { margin: 2.5rem 0 .75rem; padding-bottom: .5rem; border-bottom: 1px solid #f1f5f9; font-size: 1.5rem; font-weight: 700; letter-spacing: -0.01em; color: #0f172a; scroll-margin-top: 6rem; }
    .help-article h3 { margin: 2rem 0 .5rem; font-size: 1.125rem; font-weight: 700; color: #0f172a; scroll-margin-top: 6rem; }
    .help-article h4 { margin: 1.5rem 0 .5rem; font-size: 1rem; font-weight: 700; color: #0f172a; scroll-margin-top: 6rem; }
    .help-article p { color: #334155; }
    .help-article a { color: #b23a2b; font-weight: 500; text-decoration: underline; text-decoration-color: rgba(239,95,77,.35); text-underline-offset: 2px; }
    .help-article a:hover { text-decoration-color: #d94b39; }
    .help-article strong { color: #0f172a; font-weight: 700; }
    .help-article ul { margin: 1.25rem 0; padding-left: 1.5rem; list-style: disc; }
    .help-article ol { margin: 1.25rem 0; padding-left: 1.5rem; list-style: decimal; }
    .help-article li + li { margin-top: .5rem; }
    .help-article li::marker { color: #ef5f4d; font-weight: 700; }
    .help-article ul ul, .help-article ol ol { margin-top: .5rem; }
    .help-article blockquote { margin: 1.5rem 0; border-left: 4px solid rgba(239,95,77,.45); background: #f8fafc; padding: .75rem 1rem; color: #475569; font-style: italic; }
    .help-article hr { margin: 2rem 0; border: 0; border-top: 1px solid #e2e8f0; }
    .help-article code { background: #f1f5f9; border-radius: .375rem; padding: .125rem .375rem; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: .875em; font-weight: 500; color: #0f172a; }
    .help-article pre { overflow-x: auto; margin: 1.5rem 0; background: #0f172a; border-radius: 1rem; padding: 1rem; font-size: .875rem; line-height: 1.6; color: #f1f5f9; }
    .help-article pre code { background: transparent; padding: 0; color: inherit; }

    /* ── Tables ──────────────────────────────────────────────────────── */
    .help-article .table-wrap { overflow-x: auto; margin: 1.5rem 0; border: 1px solid #e2e8f0; border-radius: 1rem; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
    .help-article table { width: 100%; border-collapse: collapse; font-size: .9375rem; text-align: left; }
    .help-article thead th { background: #f8fafc; padding: .7rem 1rem; font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #64748b; }
    .help-article tbody td { border-top: 1px solid #f1f5f9; padding: .7rem 1rem; vertical-align: top; color: #334155; }
    .help-article tbody tr:hover { background: rgba(248, 250, 252, .8); }
    .help-article tbody tr:first-child td { border-top: 0; }

    /* ── Callouts (:::tip / note / warning / danger / success) ───────── */
    .help-callout { margin: 1.5rem 0; overflow: hidden; border: 1px solid #e2e8f0; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
    .help-callout__head { display: flex; flex-wrap: wrap; align-items: center; column-gap: .5rem; row-gap: .25rem; padding: 1rem 1.25rem 0; }
    .help-callout__icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }
    .help-callout__label { margin: 0; font-size: .6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; }
    .help-callout__title { margin: 0; font-size: .875rem; font-weight: 700; color: #0f172a; }
    .help-callout__body { padding: .25rem 1.25rem 1rem; font-size: .9375rem; line-height: 1.65; color: #334155; }
    .help-callout__body > * + * { margin-top: .75rem; }
    .help-callout__body ul { margin: .5rem 0; padding-left: 1.25rem; list-style: disc; }
    .help-callout__body ol { margin: .5rem 0; padding-left: 1.25rem; list-style: decimal; }
    .help-callout__body li + li { margin-top: .25rem; }
    .help-callout__body p { margin: 0; }

    .help-callout--tip, .help-callout--important, .help-callout--success { border-color: #a7f3d0; background: rgba(236, 253, 245, .7); }
    .help-callout--tip .help-callout__icon, .help-callout--tip .help-callout__label,
    .help-callout--important .help-callout__icon, .help-callout--important .help-callout__label,
    .help-callout--success .help-callout__icon, .help-callout--success .help-callout__label { color: #059669; }

    .help-callout--note, .help-callout--info { border-color: #bae6fd; background: rgba(240, 249, 255, .7); }
    .help-callout--note .help-callout__icon, .help-callout--note .help-callout__label,
    .help-callout--info .help-callout__icon, .help-callout--info .help-callout__label { color: #0284c7; }

    .help-callout--warning, .help-callout--caution { border-color: #fde68a; background: rgba(255, 251, 235, .8); }
    .help-callout--warning .help-callout__icon, .help-callout--warning .help-callout__label,
    .help-callout--caution .help-callout__icon, .help-callout--caution .help-callout__label { color: #d97706; }

    .help-callout--danger, .help-callout--error { border-color: #fecdd3; background: rgba(255, 241, 242, .7); }
    .help-callout--danger .help-callout__icon, .help-callout--danger .help-callout__label,
    .help-callout--error .help-callout__icon, .help-callout--error .help-callout__label { color: #e11d48; }

    /* ── Table of contents rail ──────────────────────────────────────── */
    .help-toc { border-left: 2px solid #f1f5f9; padding-left: 1rem; }
    .help-toc a { display: block; padding: .25rem 0; font-size: .875rem; line-height: 1.4; color: #64748b; transition: color .15s ease; }
    .help-toc a:hover { color: #d94b39; }
    .help-toc a.is-active { color: #b23a2b; font-weight: 600; }
    .help-toc .help-toc__sub a { padding-left: .75rem; font-size: .8125rem; }
</style>
@endpush

@section('content')
<section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <nav aria-label="Breadcrumb" class="mb-6 text-sm text-slate-500">
        <ol class="flex flex-wrap items-center gap-2">
            <li><a href="{{ route('marketing.home') }}" class="hover:text-[#D94B39]">{{ __('Home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li><a href="{{ route('help.index') }}" class="hover:text-[#D94B39]">{{ __('Help Centre') }}</a></li>
            @yield('breadcrumbs')
        </ol>
    </nav>

    <div class="grid gap-10 lg:grid-cols-[264px_1fr]">
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <form action="{{ route('help.search') }}" method="GET" role="search" class="mb-6">
                <label for="help-search" class="sr-only">{{ __('Search help articles') }}</label>
                <div class="relative">
                    <input
                        id="help-search"
                        type="search"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="{{ __('Search help…') }}"
                        class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-[#EF5F4D] focus:outline-none focus:ring-2 focus:ring-[#EF5F4D]/20"
                    >
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
                </div>
            </form>

            <nav aria-label="{{ __('Help categories') }}">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.15em] text-slate-400">{{ __('Categories') }}</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('help.index') }}"
                           class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ ! isset($category) ? 'bg-[#EF5F4D]/10 text-[#D94B39]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            @include('help-centre.partials.category-icon', ['categoryKey' => 'all', 'svgClass' => 'h-4 w-4 shrink-0 opacity-70'])
                            {{ __('All articles') }}
                        </a>
                    </li>
                    @foreach ($categories as $cat)
                        <li>
                            <a href="{{ route('help.category', $cat['key']) }}"
                               class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition {{ ($category ?? null) === $cat['key'] ? 'bg-[#EF5F4D]/10 text-[#D94B39]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                @include('help-centre.partials.category-icon', ['categoryKey' => $cat['key'], 'svgClass' => 'h-4 w-4 shrink-0 opacity-70'])
                                <span class="truncate">{{ $cat['label'] }}</span>
                                <span class="ml-auto text-xs text-slate-400">{{ $cat['count'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        <div class="min-w-0">
            @yield('help-body')
        </div>
    </div>
</section>
@endsection
