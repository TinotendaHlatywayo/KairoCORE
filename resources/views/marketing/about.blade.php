@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - About')
@section('meta_description', marketing_content('meta_description') ?: __('KairoCORE is a modern, all-in-one school management system built for African schools, admissions, academics, finance, HR and communication in one platform.'))

@push('styles')
@include('marketing.partials.animations')
@endpush

@section('content')
{{-- HERO --}}
<section class="relative overflow-hidden bg-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 right-0 h-96 w-96 rounded-full bg-[#D94B39]/8 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 pb-20 pt-14 sm:px-6 lg:px-8 lg:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_content('about_badge') }}</p>
                <h1 class="reveal reveal-d1 mt-4 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 leading-[1.08]">{{ marketing_content('about_title') }}</h1>
                <p class="reveal reveal-d2 mt-6 text-lg leading-8 text-slate-600">{{ marketing_content('about_intro') }}</p>
                <div class="reveal reveal-d3 mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_content('cta_primary_label') }}</a>
                    <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 px-6 py-3 text-sm font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-colors">{{ marketing_content('features_cta_secondary_label') }}</a>
                </div>
            </div>
            <div class="reveal reveal-d2 relative">
                <div class="relative aspect-[4/3] w-full overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 shadow-2xl shadow-slate-900/10">
                    <img src="{{ marketing_image('about_image') }}" alt="{{ __('Diverse students learning in a modern classroom') }}" class="h-full w-full object-cover" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/25 via-transparent to-transparent"></div>
                </div>
                <div class="float-slow absolute -bottom-5 -left-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:-left-8">
                    <p class="text-xs font-semibold text-slate-500">{{ marketing_content('about_founded_label') }}</p>
                    <p class="text-lg font-extrabold text-slate-900">{{ marketing_content('about_founded_value') }}</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- MISSION / VISION / BUILD --}}
<section class="bg-white py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @php
                $pillars = marketing_merged_list('about_pillars');
            @endphp
            @foreach($pillars as $i => $p)
            <div class="reveal card-lift rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" style="transition-delay: {{ $i * 0.1 }}s">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#1F2E43] text-white">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $p['icon'] ?? '' }}"/></svg>
                </div>
                <h2 class="mt-4 text-lg font-bold text-slate-900">{{ $p['title'] ?? '' }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $p['desc'] ?? '' }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- WHY CHOOSE US --}}
<section class="bg-slate-50 py-16 sm:py-20">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_content('about_why_badge') }}</p>
            <h2 class="reveal reveal-d1 mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl">{{ marketing_content('about_why_title') }}</h2>
        </div>
        <ul class="mt-10 grid gap-4 sm:grid-cols-2">
            @php
                $points = marketing_merged_list('about_why_points');
            @endphp
            @foreach ($points as $i => $point)
                <li class="reveal flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" style="transition-delay: {{ $i * 0.07 }}s">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#EF5F4D]/12 text-[#EF5F4D]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    </span>
                    <span class="text-sm text-slate-700">{{ $point['text'] ?? '' }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- STORY / NUMBERS --}}
<section class="bg-[#1F2E43] py-16 sm:py-20">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-center">
            <div class="reveal">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#EF5F4D]">{{ marketing_content('about_story_badge') }}</p>
                <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ marketing_content('about_story_title') }}</h2>
                <p class="mt-4 text-base leading-7 text-slate-300">{{ marketing_content('about_story_p1') }}</p>
                <p class="mt-4 text-base leading-7 text-slate-300">{{ marketing_content('about_story_p2') }}</p>
            </div>
            <div class="reveal reveal-d2 grid grid-cols-2 gap-4">
                @php
                    $stats = marketing_merged_list('about_stats');
                @endphp
                @foreach($stats as $st)
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5 text-center backdrop-blur">
                    <p class="text-3xl font-extrabold text-white" data-countup="{{ $st['value'] ?? '0' }}" data-suffix="{{ $st['suffix'] ?? '' }}">0</p>
                    <p class="mt-1 text-xs font-medium text-slate-400">{{ $st['label'] ?? '' }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="relative overflow-hidden bg-gradient-to-br from-[#1F2E43] via-[#253a55] to-[#1F2E43] py-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -bottom-32 left-1/4 h-80 w-80 rounded-full bg-[#D94B39]/15 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="reveal text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ marketing_content('about_cta_title') }}</h2>
        <p class="reveal reveal-d1 mt-4 text-lg text-slate-300">{{ marketing_content('about_cta_subtitle') }}</p>
        <div class="reveal reveal-d2 mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#EF5F4D]/30 hover:bg-[#D94B39]">{{ marketing_content('cta_primary_label') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/25 px-7 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ marketing_content('features_cta_secondary_label') }}</a>
        </div>
    </div>
</section>

@endsection
