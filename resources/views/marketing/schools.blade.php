@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: __('Schools using :platform', ['platform' => platform_name()]).' - '.platform_name())
@section('meta_description', marketing_content('meta_description') ?: __('Browse the schools and colleges using :platform. Visit their official websites for admissions, news, events and contact details.', ['platform' => platform_name()]))

@push('styles')
@include('marketing.partials.animations')
@endpush

@section('content')
<section class="relative overflow-hidden bg-white pb-16 pt-14 lg:pt-20 dark:bg-[#0B1120]">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 right-1/4 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
        <div class="absolute top-40 -left-20 h-80 w-80 rounded-full bg-[#1F2E43]/5 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Our Schools') }}</p>
            <h1 class="reveal reveal-d1 mt-4 text-4xl font-extrabold tracking-tight text-slate-900 leading-[1.08] sm:text-5xl dark:text-white">{{ __('Schools using :platform', ['platform' => platform_name()]) }}</h1>
            <p class="reveal reveal-d2 mt-5 text-lg leading-8 text-slate-600 dark:text-slate-300">{{ __('Every school below has its own official website powered by :platform. Visit a school to explore its admissions, news, events and contact details.', ['platform' => platform_name()]) }}</p>
        </div>

        @if($schools->isEmpty())
            <div class="reveal reveal-d3 mx-auto mt-14 max-w-xl rounded-3xl border border-dashed border-slate-300 p-12 text-center dark:border-slate-700">
                <p class="text-lg font-semibold text-slate-700 dark:text-slate-300">{{ __('No public school websites yet — check back soon.') }}</p>
            </div>
        @else
            <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($schools as $i => $entry)
                    @php
                        $school = $entry['school'];
                        $website = rtrim((string) $entry['url'], '/');
                        $host = preg_replace('~^https?://~i', '', $website);
                        $location = trim(implode(', ', array_filter([$school->region, $school->country])));
                        $type = $school->institution_type ? ucfirst((string) $school->institution_type) : null;
                    @endphp

                    <article class="reveal card-lift group flex flex-col overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm transition-all dark:border-slate-800 dark:bg-[#0F172A]" style="transition-delay: {{ ($i % 3) * 0.08 }}s">
                        <div class="flex items-center gap-4 border-b border-slate-100 p-6 dark:border-slate-800">
                            @if($entry['logo'])
                                <img src="{{ $entry['logo'] }}" alt="{{ $school->name }} logo" width="56" height="56" class="h-14 w-14 shrink-0 rounded-2xl border border-slate-200 bg-white object-contain p-1.5 dark:border-slate-700" loading="lazy" decoding="async">
                            @else
                                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-[#1F2E43] text-xl font-extrabold text-white dark:bg-[#D94B39]">{{ mb_substr($school->name, 0, 1) }}</span>
                            @endif
                            <div class="min-w-0">
                                <h2 class="truncate text-lg font-bold text-slate-900 dark:text-white">{{ $school->name }}</h2>
                                <p class="mt-0.5 truncate text-sm text-slate-500 dark:text-slate-400">{{ $host }}</p>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-6">
                            @if($school->motto)
                                <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $school->motto }}</p>
                            @else
                                <p class="text-sm leading-6 text-slate-600 dark:text-slate-300">{{ __('Official school website with admissions, news, events and contact details.') }}</p>
                            @endif

                            <div class="mt-4 flex flex-wrap gap-2">
                                @if($type)
                                    <span class="inline-flex items-center rounded-full bg-[#1F2E43]/6 px-2.5 py-1 text-xs font-semibold text-[#1F2E43] dark:bg-white/10 dark:text-slate-200">{{ $type }}</span>
                                @endif
                                @if($location)
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $location }}</span>
                                @endif
                            </div>

                            <div class="mt-auto pt-6">
                                <a href="{{ $website }}" class="inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-4 py-2.5 text-sm font-bold text-white shadow-md shadow-[#D94B39]/20 transition-colors hover:bg-[#c14332]">
                                    {{ __('Visit school website') }}
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="reveal mt-16 rounded-3xl bg-[#1F2E43] px-6 py-10 text-center sm:px-10">
            <h2 class="text-2xl font-extrabold tracking-tight text-white sm:text-3xl">{{ __('Want your school listed here?') }}</h2>
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-6 text-slate-300">{{ __('Register your school on :platform and get a professional website, admissions portal and school management system under your own subdomain.', ['platform' => platform_name()]) }}</p>
            <a href="{{ route('register') }}" class="mt-6 inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#EF5F4D]/25 transition-colors hover:bg-[#D94B39]">{{ __('Register your school') }}</a>
        </div>
    </div>
</section>
@endsection
