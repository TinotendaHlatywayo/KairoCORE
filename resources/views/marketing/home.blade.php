@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - School Management System')
@section('meta_description', marketing_content('meta_description') ?: __('Modern school management system for admissions, academics, fees, attendance, HR, communications and more.'))

@push('styles')
@include('marketing.partials.animations')
@endpush

@section('content')
@if(marketing_content('announcement'))
<div class="bg-[#1F2E43] text-white text-center text-xs sm:text-sm py-2 px-4">
    <span>{{ marketing_content('announcement') }}</span>
</div>
@endif

{{-- HERO --}}
<section class="relative overflow-hidden bg-white">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 -left-32 h-96 w-96 rounded-full bg-[#D94B39]/8 blur-3xl"></div>
        <div class="absolute top-40 -right-20 h-80 w-80 rounded-full bg-[#1F2E43]/6 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 pb-20 pt-14 sm:px-6 lg:px-8 lg:pt-20">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <div>
                <div class="reveal inline-flex items-center gap-2 rounded-full border border-[#D94B39]/20 bg-[#D94B39]/6 px-3.5 py-1.5 text-xs font-semibold text-[#B93A2B]">
                    <span class="h-1.5 w-1.5 rounded-full bg-[#D94B39] pulse-ring"></span>
                    {{ marketing_content('hero_badge') }}
                </div>
                <h1 class="reveal reveal-d1 mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-[1.08]">
                    {{ marketing_content('hero_title') }}
                </h1>
                <p class="reveal reveal-d2 mt-6 max-w-xl text-base sm:text-lg leading-8 text-slate-600">{{ marketing_content('hero_subtitle') }}</p>
                <div class="reveal reveal-d3 mt-8 flex flex-wrap gap-3">
                    <a href="{{ marketing_content('hero_primary_url') ?: route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_content('hero_primary_label') }}</a>
                    <a href="{{ marketing_content('hero_secondary_url') ?: route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-200 px-6 py-3 text-sm font-bold text-slate-700 hover:border-slate-300 hover:bg-slate-50 transition-colors">{{ marketing_content('hero_secondary_label') }}</a>
                </div>
                <div class="reveal reveal-d4 mt-8 flex flex-wrap gap-x-7 gap-y-2.5 text-sm font-medium text-slate-600">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ marketing_content('hero_trust_1') }}</span>
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ marketing_content('hero_trust_2') }}</span>
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-green-500"></span>{{ marketing_content('hero_trust_3') }}</span>
                </div>
            </div>
            <div class="reveal reveal-d2 relative">
                <div class="relative aspect-[4/3] w-full overflow-hidden rounded-3xl border border-slate-200 bg-slate-100 shadow-2xl shadow-slate-900/10">
                    <img src="{{ marketing_image('hero_image') }}" alt="{{ __('Diverse students in crisp uniforms learning in a bright modern classroom') }}" class="h-full w-full object-cover" loading="eager" fetchpriority="high">
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900/25 via-transparent to-transparent"></div>
                </div>
                <div class="float-slow absolute -bottom-5 -left-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:-left-8">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-50 text-green-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-900">{{ marketing_content('hero_stat_1_label') }}</p>
                            <p class="text-sm font-bold text-green-600">{{ marketing_content('hero_stat_1_value') }}</p>
                        </div>
                    </div>
                </div>
                <div class="float-med absolute -top-4 -right-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl sm:-right-6">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#D94B39]/10 text-[#D94B39]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-900">{{ marketing_content('hero_stat_2_label') }}</p>
                            <p class="text-sm font-bold text-slate-900">{{ marketing_content('hero_stat_2_value') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- STATS MARQUEE --}}
<section class="border-y border-slate-100 bg-slate-50/60 py-8 marquee-wrap overflow-hidden">
    <div class="flex w-max marquee-track gap-14 px-6">
        @for($i = 0; $i < 2; $i++)
        <div class="flex shrink-0 items-center gap-14">
            @foreach (marketing_merged_list('home_stats') as $stat)
            <div class="flex items-center gap-3"><span class="text-2xl font-extrabold text-[#1F2E43]" data-countup="{{ $stat['value'] ?? '0' }}" data-suffix="{{ $stat['suffix'] ?? '' }}">0</span><span class="text-sm font-medium text-slate-500">{{ $stat['label'] ?? '' }}</span></div>
            <div class="h-1 w-1 rounded-full bg-slate-300"></div>
            @endforeach
        </div>
        @endfor
    </div>
</section>

{{-- MODULES --}}
<section class="bg-white py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Platform') }}</p>
            <h2 class="reveal reveal-d1 mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">{{ marketing_content('modules_title') }}</h2>
            <p class="reveal reveal-d2 mt-4 text-lg text-slate-600">{{ marketing_content('modules_subtitle') }}</p>
        </div>
        @php
            $modules = marketing_merged_list('home_modules');
        @endphp
        <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $i => $module)
                <div class="reveal card-lift group rounded-2xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#1F2E43] text-white transition-colors group-hover:bg-[#D94B39]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $module['icon'] ?? '' }}"/></svg>
                    </div>
                    <h3 class="mt-5 text-base font-bold text-slate-900">{{ $module['name'] ?? '' }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $module['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <div class="reveal mt-12 text-center">
            <a href="{{ route('marketing.features') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#1F2E43] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#1F2E43]/20 hover:bg-[#2a3d5a]">{{ marketing_content('modules_cta_label') }} <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/></svg></a>
        </div>
    </div>
</section>

{{-- BENEFITS --}}
<section class="relative overflow-hidden bg-[#1F2E43] py-20 sm:py-24">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-40 right-0 h-96 w-96 rounded-full bg-[#D94B39]/10 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#EF5F4D]">{{ __('Benefits') }}</p>
            <h2 class="reveal reveal-d1 mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ marketing_content('benefits_title') }}</h2>
            <p class="reveal reveal-d2 mt-4 text-lg text-slate-300">{{ marketing_content('benefits_subtitle') }}</p>
        </div>
        @php
            $benefits = marketing_merged_list('home_benefits');
        @endphp
        <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($benefits as $i => $benefit)
                <div class="reveal card-lift rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-[#EF5F4D]/15 text-[#EF5F4D]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefit['icon'] ?? '' }}"/></svg>
                    </div>
                    <h3 class="mt-5 text-base font-bold text-white">{{ $benefit['name'] ?? '' }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-300">{{ $benefit['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- STEPS --}}
<section class="bg-white py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Get started') }}</p>
            <h2 class="reveal reveal-d1 mt-3 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl lg:text-5xl">{{ marketing_content('steps_title') }}</h2>
            <p class="reveal reveal-d2 mt-4 text-lg text-slate-600">{{ marketing_content('steps_subtitle') }}</p>
        </div>
        <div class="relative mt-16">
            <div class="absolute left-1/2 top-0 hidden h-full w-0.5 step-line opacity-20 md:block" aria-hidden="true"></div>
            <div class="grid gap-10 md:grid-cols-3">
                @php
                    $steps = marketing_merged_list('home_steps');
                @endphp
                @foreach($steps as $i => $step)
                <div class="reveal relative text-center md:pt-2" style="transition-delay: {{ $i * 0.12 }}s">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[#1F2E43] text-2xl font-extrabold text-white shadow-xl shadow-[#1F2E43]/20 relative z-10">{{ $i + 1 }}</div>
                    <h3 class="mt-5 text-lg font-bold text-slate-900">{{ $step['title'] ?? '' }}</h3>
                    <p class="mt-2 text-sm leading-6 text-slate-600 max-w-xs mx-auto">{{ $step['desc'] ?? '' }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- CTA --}}
<section class="relative overflow-hidden bg-gradient-to-br from-[#1F2E43] via-[#253a55] to-[#1F2E43] py-20 sm:py-24">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -bottom-32 left-1/4 h-80 w-80 rounded-full bg-[#D94B39]/15 blur-3xl"></div>
        <div class="absolute -top-24 right-1/4 h-64 w-64 rounded-full bg-white/5 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="reveal text-3xl font-extrabold tracking-tight text-white sm:text-4xl lg:text-5xl">{{ marketing_content('cta_title') }}</h2>
        <p class="reveal reveal-d1 mt-5 text-lg text-slate-300">{{ marketing_content('cta_subtitle') }}</p>
        <div class="reveal reveal-d2 mt-9 flex flex-wrap justify-center gap-3">
            <a href="{{ marketing_content('cta_primary_url') ?: route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#EF5F4D]/30 hover:bg-[#D94B39]">{{ marketing_content('cta_primary_label') }}</a>
            <a href="{{ marketing_content('cta_secondary_url') ?: route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/25 px-7 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ marketing_content('cta_secondary_label') }}</a>
        </div>
    </div>
</section>
@endsection
