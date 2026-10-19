@extends('marketing.layout')

{{-- Shared chrome for every /docs page: search box + category sidebar. --}}
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

    <div class="grid gap-10 lg:grid-cols-[260px_1fr]">
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
                           class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ ! isset($category) ? 'bg-[#EF5F4D]/10 text-[#D94B39]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            {{ __('All articles') }}
                        </a>
                    </li>
                    @foreach ($categories as $cat)
                        <li>
                            <a href="{{ route('help.category', $cat['key']) }}"
                               class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ ($category ?? null) === $cat['key'] ? 'bg-[#EF5F4D]/10 text-[#D94B39]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                                {{ $cat['label'] }}
                                <span class="ml-1 text-xs text-slate-400">{{ $cat['count'] }}</span>
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
