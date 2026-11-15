@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', marketing_content('features_title') ?: __('Features').' - '.platform_name().' | School Management System Zimbabwe')
@section('seo_image', asset('images/KairoCORE_Features_thumbnail.png'))
@section('meta_description', marketing_content('features_subtitle') ?: __('Explore Kairo CORE features for admissions, academics, fees, attendance, HR and report cards — the best school management software for schools in Zimbabwe.'))

@push('styles')
@vite('resources/css/kairo-v2/features.css')
@endpush
@push('styles')
<style>body.kairo-v2 section { padding-top: 24px; }</style>
@endpush

@section('content')
@php
    $icons = [
        'admissions' => 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z',
        'academics' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        'students' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'finance' => 'M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33',
        'hr' => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
        'attendance' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'communications' => 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z',
        'library' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        'health' => 'M21 7.5V18a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 18V7.5m18 0A2.25 2.25 0 0 0 18.75 5.25H5.25A2.25 2.25 0 0 0 3 7.5m18 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L4.07 9.659A2.25 2.25 0 0 1 3 7.743V7.5',
        'inventory' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'exams' => 'M16.5 8.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v8.25A2.25 2.25 0 0 0 6 16.5h2.25m8.25-8.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-7.5A2.25 2.25 0 0 1 8.25 18v-1.5m8.25-8.25h-6a2.25 2.25 0 0 0-2.25 2.25v6',
        'lms' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
        'boarding' => 'M3 21h18M5 21V7l7-4 7 4v14M9 9h2m-2 4h2m4-4h2m-2 4h2',
        'reports' => 'M4 20V10m6 10V4m6 16v-7m5 7H3',
        'cms' => 'M4 5h16M4 10h16M4 15h10M4 20h10',
    ];
    $categoryMeta = [
        'admin' => [__('Administration'), '#ef6050'],
        'academic' => [__('Academic'), '#16a34a'],
        'campus' => [__('Campus life'), '#10b981'],
    ];
    $features = marketing_merged_list('features_list');
    $featureCounts = ['all' => count($features)];
    foreach ($features as $feature) {
        $featureCategory = $feature['cat'] ?? 'admin';
        $featureCounts[$featureCategory] = ($featureCounts[$featureCategory] ?? 0) + 1;
    }
@endphp

<header class="fh" id="hero">
    <div class="fiw" id="fi"></div>
    <div class="w">
        <div class="fhc">
            <span class="pill"><i>{{ marketing_live_stats()['modules'] }}+</i> {{ __('modules in one connected platform') }}</span>
            <h1>{{ marketing_content('features_title') }}</h1>
            <p>{{ marketing_content('features_subtitle') }}</p>
            <div class="cta">
                <a class="btn o beg" href="{{ route('marketing.contact') }}#contact-form">
                    {{ marketing_content('features_cta_primary_label') ?: __('Book a demo') }}
                    <i class="ar">→</i>
                </a>
                <a class="btn g" href="#grid">
                    {{ __('Explore modules') }}
                    <i class="ar">↓</i>
                </a>
            </div>
        </div>
    </div>
</header>

<div class="mq">
    <div id="mq">
        @for ($i = 0; $i < 2; $i++)
            @foreach ($features as $feature)
                <span>{{ $feature['name'] ?? '' }}</span>
            @endforeach
        @endfor
    </div>
</div>

<section class="fs" id="grid">
    <div class="w">
        <div class="fbar" role="search">
            <div class="fch" aria-label="{{ __('Filter features by category') }}">
                <span class="ind" aria-hidden="true"></span>
                <button class="fc on" type="button" data-feature-filter="all">{{ __('All features') }} <small>{{ $featureCounts['all'] }}</small></button>
                @foreach ($categoryMeta as $key => [$label])
                    <button class="fc" type="button" data-feature-filter="{{ $key }}">{{ $label }} <small>{{ $featureCounts[$key] ?? 0 }}</small></button>
                @endforeach
            </div>
            <label class="sr">
                <span class="sr-only">{{ __('Search features') }}</span>
                <input type="search" id="featureSearch" placeholder="{{ __('Search features') }}">
            </label>
        </div>
        <p class="fcount" id="cnt" aria-live="polite" data-all-label="{{ __('Showing all :count modules') }}" data-filtered-label="{{ __('Showing :shown of :total modules') }}">{{ __('Showing all :count modules', ['count' => count($features)]) }}</p>
        <div class="mgrid" id="mgrid">
            @foreach ($features as $feature)
                @php
                    [$categoryLabel, $color] = $categoryMeta[$feature['cat'] ?? 'admin'] ?? ['Platform', '#3b82f6'];
                @endphp
                <article class="mc rv" id="{{ $feature['key'] ?? '' }}" data-feature-card data-category="{{ $feature['cat'] ?? 'admin' }}" data-name="{{ $feature['name'] ?? '' }}" data-detail="{{ $feature['detail'] ?? '' }}" data-sub1="{{ $feature['sub1'] ?? '' }}" data-sub2="{{ $feature['sub2'] ?? '' }}" data-sub3="{{ $feature['sub3'] ?? '' }}" data-search="{{ strtolower(($feature['name'] ?? '').' '.($feature['desc'] ?? '').' '.($feature['detail'] ?? '').' '.($feature['sub1'] ?? '').' '.($feature['sub2'] ?? '').' '.($feature['sub3'] ?? '')) }}" style="--c:{{ $color }}">
                    <div class="mv">
                        <span class="cg">{{ $categoryLabel }}</span>
                        <span class="wm">
                            <svg width="150" height="150" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$feature['key'] ?? ''] ?? '' }}"/></svg>
                        </span>
                        <div class="mi2">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icons[$feature['key'] ?? ''] ?? '' }}"/></svg>
                        </div>
                        <i class="bars"><b></b><b></b><b></b></i>
                    </div>
                    <div class="mb">
                        <h3>{{ $feature['name'] ?? '' }}</h3>
                        <p>{{ $feature['desc'] ?? '' }}</p>
                        @if(!empty($feature['sub1']) || !empty($feature['sub2']) || !empty($feature['sub3']))
                            <ul>
                                @foreach ([$feature['sub1'] ?? null, $feature['sub2'] ?? null, $feature['sub3'] ?? null] as $sub)
                                    @if($sub)
                                        <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5 9-11"/></svg>{{ $sub }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                        <button class="btn g sm" type="button" data-open-feature>{{ __('Explore feature') }} <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg></button>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="em" hidden id="em">
            <p>{{ __('No module matches that search. Try a word like fees, library or attendance.') }}</p>
            <button class="btn g sm" type="button" data-feature-reset>{{ __('Show all features') }}</button>
        </div>
    </div>
</section>

<div class="md" id="featureModal" aria-hidden="true">
    <div class="mp" role="dialog" aria-modal="true" aria-labelledby="featureModalTitle">
        <button class="ib mx" type="button" data-close-feature aria-label="{{ __('Close') }}">✕</button>
        <div class="mv" aria-hidden="true"></div>
        <div class="mb">
            <h3 id="featureModalTitle"></h3>
            <p id="featureModalDetail"></p>
            <ul id="featureModalPoints"></ul>
            <div class="mact">
                <a class="btn o" href="{{ route('marketing.contact') }}#contact-form">{{ __('Book a demo for this module') }}</a>
                <button class="btn g" type="button" data-close-feature>{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

<div class="ct rv" id="start">
    <h2>{{ marketing_content('features_cta_title') ?: __('Not sure where to start?') }}</h2>
    <p>{{ marketing_content('features_cta_subtitle') ?: __('Book a personal demo and we will show you the modules that matter most to your school.') }}</p>
    <div class="cta">
        <a class="btn o" href="{{ route('marketing.contact') }}#contact-form">{{ marketing_content('features_cta_primary_label') ?: __('Book a demo') }} →</a>
        <a class="btn g" href="{{ route('marketing.contact') }}">{{ marketing_content('features_cta_secondary_label') ?: __('Contact us') }}</a>
    </div>
</div>
@endsection
