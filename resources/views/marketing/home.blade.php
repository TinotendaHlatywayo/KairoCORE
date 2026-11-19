@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', platform_name().' | A Modern School Management System')
@section('meta_description', marketing_content('meta_description') ?: platform_seo_description())
@section('seo_image', platform_public_asset_url('images/KairoCORE_link_preview.png'))
@section('seo_image_secondary', platform_public_asset_url('images/KairoCORE_Home_final_thumbnail.png'))
@section('seo_schema_image', platform_public_asset_url('images/KairoCORE_Home_final_thumbnail.png'))
@section('seo_image_alt', marketing_text('Kairo CORE school management system — smarter school management for a brighter future'))

@push('styles')
@vite('resources/css/kairo-v2/home.css')
@endpush

@section('content')
@php
    $colors = ['#3b82f6', '#16a34a', '#ef6050', '#10b981', '#6366f1', '#0ea5a4', '#f59e0b', '#8b5cf6', '#d946ef'];
    $marquee = marketing_merged_list('home_audiences');
@endphp

@if(marketing_content('announcement'))
<div class="bg-[#1F2E43] text-white text-center text-xs sm:text-sm py-2 px-4">
    <span>{{ marketing_content('announcement') }}</span>
</div>
@endif

<header class="hero" style="--hero:url('{{ marketing_image('hero_image') }}')">
    <div class="pic sh" id="ps"></div>
    <div class="gs" id="gs">
        @php
            $uptimeStat = [];
            preg_match('/^([0-9.]+)(.*)$/u', marketing_content('hero_stat_3_value'), $uptimeStat);
            $supportStat = [];
            preg_match('/^([0-9.]+)(.*)$/u', marketing_content('hero_stat_4_value'), $supportStat);
        @endphp
        <div class="fk">
            <b data-n="{{ marketing_content('hero_stat_1_value') }}">0</b>
            <small><span class="dt"></span> {{ marketing_content('hero_stat_1_label') }}</small>
        </div>
        <div class="fk">
            <b data-n="{{ $uptimeStat[1] ?? '99.9' }}" data-s="{{ $uptimeStat[2] ?? '%' }}">0</b>
            <small><span class="dt"></span> {{ marketing_content('hero_stat_3_label') }}</small>
        </div>
        <div class="fk">
            <b data-n="{{ marketing_content('hero_stat_2_value') }}">0</b>
            <small><span class="dt"></span> {{ marketing_content('hero_stat_2_label') }}</small>
        </div>
        <div class="fk">
            <b data-n="{{ $supportStat[1] ?? '24' }}" data-s="{{ $supportStat[2] ?? '/7' }}">0</b>
            <small><span class="dt"></span> {{ marketing_content('hero_stat_4_label') }}</small>
        </div>
    </div>
    <div class="hc">
            <span class="pill"><i>{{ marketing_text('NEW') }}</i> {{ marketing_content('hero_badge') }}</span>
            @php
    $heroTitleWords = preg_split('/\s+/', (string) marketing_content('hero_title'));
@endphp
<h1>@foreach($heroTitleWords as $wi => $w)@if(strcasecmp($w, 'powerful') === 0)<span class="wd gr" style="--i:{{ $wi }}">{{ e($w) }}</span>@else<span class="wd" style="--i:{{ $wi }}">{{ e($w) }}</span>@endif @endforeach</h1>
            <p>{{ str_replace('—', ',', marketing_content('hero_subtitle')) }}</p>
            <div class="cta">
                <a class="btn o beg" href="{{ marketing_content('hero_primary_url') ?: route('marketing.contact').'#contact-form' }}">
                    {{ marketing_content('hero_primary_label') ?: marketing_text('Book a demo') }}
                    <i class="ar">→</i>
                </a>
                <a class="btn g" href="{{ marketing_content('hero_secondary_url') ?: route('marketing.features') }}">
                    {{ marketing_content('hero_secondary_label') ?: marketing_text('Explore features') }}
                </a>
            </div>
        </div>
    </div>
</header>

<div class="mq" style="margin-top: 50px;">
    <div id="mq">
        @for ($i = 0; $i < 6; $i++)
            @foreach ($marquee as $item)
                <span>{{ $item['label'] ?? '' }}</span>
            @endforeach
        @endfor
    </div>
</div>

<section id="features">
    <div class="w">
        <div class="hd rv">
            <div class="ey">{{ marketing_content('home_modules_badge') }}</div>
            <h2>{{ marketing_content('modules_title') }}</h2>
            <p>{{ marketing_content('modules_subtitle') }}</p>
        </div>
        <div class="bt" id="bt">
            @foreach (marketing_merged_list('home_modules') as $i => $module)
                <div class="bc rv" style="--c:{{ $colors[$i % count($colors)] }}">
                    <span class="wm">
                        <svg width="170" height="170" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.1" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $module['icon'] ?? '' }}"/></svg>
                    </span>
                    <div class="ic">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $module['icon'] ?? '' }}"/></svg>
                    </div>
                    <h3>{{ $module['name'] ?? '' }}</h3>
                    <p>{{ $module['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <div class="reveal mt-12 text-center">
            <a href="{{ route('marketing.features') }}" class="btn o beg">{{ marketing_content('modules_cta_label') ?: marketing_text('See every feature') }} <i class="ar">→</i></a>
        </div>
    </div>
</section>

<section class="wy" id="why" style="--bld:url('{{ marketing_image('why_image', marketing_image('hero_image')) }}')">
    <div class="w">
        <div class="rv">
            <div class="ey">{{ marketing_content('home_why_badge') }}</div>
            <h2>{{ marketing_content('benefits_title') }}</h2>
            <p>{{ marketing_content('benefits_subtitle') }}</p>
            <a class="btn o beg" href="#contact">
                {{ marketing_content('hero_primary_label') ?: marketing_text('Book a Demo') }}
                <i class="ar">→</i>
            </a>
        </div>
        <div class="bn" id="bn">
            @foreach (marketing_merged_list('home_benefits') as $benefit)
                <div class="rv">
                    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $benefit['icon'] ?? '' }}"/></svg>
                    <b>{{ $benefit['name'] ?? '' }}</b>
                    <span>{{ $benefit['desc'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section id="start">
    <div class="w">
        <div class="hd rv">
            <div class="ey">{{ marketing_content('home_steps_badge') }}</div>
            <h2>{{ marketing_content('steps_title') }}</h2>
            <p>{{ marketing_content('steps_subtitle') }}</p>
        </div>
        <div class="sp3 rv">
            @foreach (marketing_merged_list('home_steps') as $i => $step)
                <div class="stp">
                    <b>{{ $i + 1 }}</b>
                    <span class="si">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </span>
                    <h3>{{ $step['title'] ?? '' }}</h3>
                    <p>{{ $step['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <div class="tr rv">
            <div class="ti">
                <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3 3 9 3 12 0v-5"/></svg>
            </div>
            <div>
                <span class="op">{{ marketing_content('home_training_badge') }}</span>
                <h3>{{ marketing_content('home_training_title') }}</h3>
                <p>{{ marketing_content('home_training_desc') }}</p>
            </div>
            <div class="tc">
                <span>{{ marketing_text('Hands-on sessions') }}</span>
                <span>{{ marketing_text('Video tutorials') }}</span>
                <span>{{ marketing_text('Help centre') }}</span>
            </div>
        </div>
    </div>
</section>

@include('marketing.partials.faq-chat')

<div class="ct rv" id="contact" style="--bld:url('{{ marketing_image('cta_image', marketing_image('hero_image')) }}')">
    <h2>{{ marketing_content('cta_title') }}</h2>
    <p>{{ marketing_content('cta_subtitle') }}</p>
        <form class="fm" method="POST" action="{{ route('marketing.contact.submit') }}" data-contact-form>
            @csrf
            <input type="hidden" name="topic" value="{{ marketing_text('Booking a demo') }}">
            <input type="hidden" name="message" value="{{ marketing_text('Please contact me to arrange a Kairo CORE demo.') }}">
        <div class="fl">
            <label for="f-name">{{ marketing_text('Your name') }}</label>
            <input autocomplete="name" id="f-name" name="name" placeholder="{{ marketing_text('Your name') }}" required>
        </div>
        <div class="fl">
            <label for="f-school">{{ marketing_text('School name') }}</label>
            <input id="f-school" name="school" placeholder="{{ marketing_text('School name') }}" required>
        </div>
        <div class="fl" style="grid-column:1/-1">
            <label for="f-email">{{ marketing_text('Work email') }}</label>
            <input autocomplete="email" id="f-email" name="email" placeholder="{{ marketing_text('Work email') }}" required type="email">
        </div>
        <button class="btn o" style="grid-column:1/-1;justify-content:center" type="submit">
            {{ marketing_text('Book a demo') }} →
        </button>
        @if($errors->any())
            <p class="kerr" role="alert">{{ $errors->first() }}</p>
        @elseif(session('success_message'))
            <p class="ok" role="status">{{ session('success_message') }}</p>
        @endif
    </form>
</div>

@endsection
