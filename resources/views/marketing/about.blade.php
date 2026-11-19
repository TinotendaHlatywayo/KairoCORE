@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', marketing_text('About Kairo CORE').' | School Management System in Zimbabwe')
@section('seo_image', platform_public_asset_url('images/KairoCORE_About_thumbnail.png'))
@section('seo_schema_image', platform_public_asset_url('images/KairoCORE_About_thumbnail.png'))
@section('seo_image_alt', marketing_text('About Kairo CORE — school management software for schools in Zimbabwe'))
@section('meta_description', marketing_text('Learn about Kairo CORE, a Zimbabwean-built school management system for schools in Zimbabwe and Southern Africa: admissions, academics, fees, attendance, HR, communication and more.'))

@push('styles')
@vite('resources/css/kairo-v2/about.css')
@endpush
@push('styles')
<style>body.kairo-v2 section { padding-top: 0px !important; }</style>
@endpush

@section('content')
<header class="ah">
    <div class="w">
        <div>
            <div class="ey">{{ marketing_content('about_badge') ?: marketing_text('About us') }}</div>
            <h1>{{ marketing_content('about_title') }}</h1>
            <p>{{ marketing_content('about_intro') }}</p>
            <div class="cta">
                <a class="btn o beg" href="{{ route('marketing.contact') }}#contact-form">
                    {{ marketing_text('Book a demo') }}
                    <i class="ar">→</i>
                </a>
                <a class="btn g" href="{{ route('marketing.contact') }}">
                    {{ marketing_text('Contact us') }}
                </a>
            </div>
        </div>
        <div class="av">
            <div class="slot">
                <img src="{{ marketing_image('about_image') }}" alt="{{ marketing_text('School team using Kairo CORE') }}" loading="lazy">
            </div>
            <div class="fd2">
                {{ marketing_content('about_founded_label') ?: marketing_text('Founded in') }}
                <b>{{ marketing_content('about_founded_value') ?: 'Harare, Zimbabwe' }}</b>
            </div>
        </div>
    </div>
</header>

<section class="pl">
    <div class="w">
        <div class="bt" id="bt">
            @foreach (marketing_merged_list('home_modules') as $i => $module)
                <div class="bc rv" style="--c:{{ ['#3b82f6','#16a34a','#ef6050','#10b981','#6366f1','#0ea5a4','#f59e0b','#8b5cf6','#d946ef'][$i % 9] }}">
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
    </div>
</section>

<section class="xp" id="problems">
    <div class="w">
        <div class="hd rv">
            <div class="ey">{{ marketing_content('about_problems_badge') }}</div>
            <h2>{{ marketing_content('about_problems_title') }}</h2>
            <p>{{ marketing_content('about_problems_subtitle') }}</p>
        </div>
        <div class="pst" id="pst">
            @foreach (marketing_merged_list('about_problems') as $i => $problem)
                <article class="xps rv">
                    <div class="xpb">
                        <small><span class="xi"></span>{{ marketing_text('The problem') }}</small>
                        <h3>{{ $problem['problem'] ?? '' }}</h3>
                        <p>{{ $problem['detail'] ?? '' }}</p>
                    </div>
                    <div class="xsl">
                        <small><span class="xi"></span>{{ marketing_text('How Kairo CORE fixes it') }}</small>
                        <p>{{ $problem['solution'] ?? '' }}</p>
                        @php
                            $points = is_array($problem['points'] ?? null)
                                ? $problem['points']
                                : preg_split('/\r\n|\r|\n/', (string) ($problem['points'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
                        @endphp
                        @if(!empty($points))
                            <div class="xex" id="problem-details-{{ $i }}">
                                <div>
                                    <ul>
                                        @foreach ($points as $point)
                                            <li>{{ $point }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        @endif
                        <div class="xft">
                            <div class="mc">
                                @foreach (($problem['links'] ?? []) as $link)
                                    <a href="{{ route('marketing.features') }}#{{ $link['key'] ?? $link[0] ?? '' }}">{{ $link['label'] ?? $link[1] ?? '' }}</a>
                                @endforeach
                            </div>
                            <button class="more" type="button" aria-expanded="false" aria-controls="problem-details-{{ $i }}" data-more-label="{{ marketing_text('See everything it does') }}" data-less-label="{{ marketing_text('Show less') }}" aria-label="{{ marketing_text('See everything it does') }}">
                                <span>{{ marketing_text('See everything it does') }}</span>
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="xw rv">
            <span class="xi"></span>
            <div>
                <h3>{{ marketing_content('about_make_yours_title') }}</h3>
                <p>{{ marketing_content('about_make_yours_text') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="xp xmd" id="modules">
    <div class="w">
        <div class="hd rv">
            <div class="ey">{{ marketing_content('about_platform_badge') }}</div>
            <h2>{{ marketing_text(':count+ modules. One connected system.', ['count' => marketing_live_stats()['modules']]) }}</h2>
            <p>{{ marketing_content('about_platform_subtitle') }}</p>
        </div>
        <div class="xg" id="xg">
            @php
                $groups = [
                    [marketing_text('Administration'), '#ef6050', ['admissions', 'finance', 'hr', 'inventory', 'reports', 'cms']],
                    [marketing_text('Academic'), '#3b82f6', ['academics', 'students', 'exams', 'lms', 'library']],
                    [marketing_text('Campus life'), '#10b981', ['attendance', 'communications', 'health', 'boarding']],
                ];
                $featureMap = collect(marketing_merged_list('features_list'))->keyBy('key');
            @endphp
            @foreach ($groups as [$name, $color, $keys])
                <div class="xc rv" style="--c:{{ $color }}">
                    <h3>{{ $name }}<i>{{ count($keys) }}</i></h3>
                    <ul>
                        @foreach ($keys as $key)
                            @if(isset($featureMap[$key]))
                                <li><a href="{{ route('marketing.features') }}#{{ $key }}"><span></span>{{ $featureMap[$key]['name'] }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="ys">
    <div class="w">
        <div class="hd rv">
            <div class="ey">{{ marketing_content('about_why_badge') ?: marketing_text('Why Kairo CORE') }}</div>
            <h2>{{ marketing_content('about_why_title') ?: marketing_text('Why schools choose Kairo CORE') }}</h2>
        </div>
        <div class="ckg" id="ckg">
            @foreach (marketing_merged_list('home_benefits') as $benefit)
                <div class="bc rv" style="--c:#16a34a">
                    <span class="wm"></span>
                    <div class="ic">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $benefit['icon'] ?? '' }}"/></svg>
                    </div>
                    <h3>{{ $benefit['name'] ?? '' }}</h3>
                    <p>{{ $benefit['desc'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <div class="ckg mt-4">
            @foreach (marketing_merged_list('about_why_points') as $point)
                <div class="ck rv">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5 9-10"/></svg>
                    {{ $point['text'] ?? '' }}
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="wy sy" id="story" style="--bld:url('{{ marketing_image('about_image') }}')">
    <div class="w">
        <div class="rv">
            <div class="ey">{{ marketing_content('about_story_badge') ?: marketing_text('Our story') }}</div>
            <h2>{{ marketing_content('about_story_title') ?: marketing_text('Built in Zimbabwe, for schools everywhere') }}</h2>
            <p>{{ marketing_content('about_story_p1') }}</p>
            <p>{{ marketing_content('about_story_p2') }}</p>
        </div>
        <div class="bn st">
            @php
                $liveStats = marketing_live_stats();
                $liveAboutStats = [$liveStats['schools'], $liveStats['students'], $liveStats['languages'], $liveStats['modules']];
                $aboutStats = marketing_merged_list('about_stats');
            @endphp
            @foreach ($aboutStats as $index => $stat)
                <div class="rv">
                    <b data-n="{{ $liveAboutStats[$index] ?? '0' }}" data-s="{{ $stat['suffix'] ?? '' }}">0</b>
                    <span>{{ $stat['label'] ?? '' }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

<div class="ct rv" id="contact">
    <h2>{{ marketing_content('about_cta_title') ?: marketing_text('Want to see how Kairo CORE fits your school?') }}</h2>
    <p>{{ marketing_content('about_cta_subtitle') ?: marketing_text('Book a personal demo and we will walk you through it, module by module.') }}</p>
    <div class="cta">
        <a class="btn o" href="{{ route('marketing.contact') }}#contact-form">{{ marketing_text('Book a demo') }} →</a>
    </div>
</div>
@endsection
