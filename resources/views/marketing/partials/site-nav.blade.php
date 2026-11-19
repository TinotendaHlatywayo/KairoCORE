@php
    $navLinks = [
        ['label' => marketing_text('Home'), 'url' => route('marketing.home'), 'active' => request()->routeIs('marketing.home')],
        ['label' => marketing_text('Features'), 'url' => route('marketing.features'), 'active' => request()->routeIs('marketing.features')],
        ['label' => marketing_text('Blog'), 'url' => route('marketing.blog'), 'active' => request()->routeIs('marketing.blog*')],
        ['label' => marketing_text('About'), 'url' => route('marketing.about'), 'active' => request()->routeIs('marketing.about')],
        ['label' => marketing_text('Contact'), 'url' => route('marketing.contact'), 'active' => request()->routeIs('marketing.contact')],
    ];
    $logo = marketing_image('nav_logo', 'images/kairo-core-logo.webp');
    $logoDark = marketing_image('footer_logo', 'images/kairocore-logo-white.webp');
    $localeFlags = ['en' => '🇬🇧', 'sn' => '🇿🇼', 'sw' => '🇹🇿', 'fr' => '🇫🇷', 'pt' => '🇵🇹', 'es' => '🇪🇸'];
@endphp

<header class="kf-nav" id="kfNav">
    <div class="kf-nav-bar">
        <div class="kf-wrap kf-nav-inner">
            <a href="{{ route('marketing.home') }}" class="kf-logo" aria-label="{{ platform_name() }} home">
                <img src="{{ $logo }}" alt="{{ platform_name() }} logo" class="kf-logo-light" width="480" height="268">
                <img src="{{ $logoDark }}" alt="{{ platform_name() }} logo" class="kf-logo-dark" width="480" height="268">
            </a>
            <div class="kf-links" role="navigation" aria-label="{{ marketing_text('Primary navigation') }}">
            @foreach ($navLinks as $link)
                @if($link['label'] === marketing_text('Features'))
                    <a href="{{ $link['url'] }}" id="ft" class="kf-feature-trigger @if($link['active']) is-active @endif" aria-expanded="false" aria-haspopup="true" aria-controls="mm" @if($link['active']) aria-current="page" @endif>
                        {{ $link['label'] }}
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </a>
                @else
                    <a href="{{ $link['url'] }}" @class(['is-active' => $link['active']]) @if($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
                @endif
            @endforeach
        </div>

        <div class="kf-actions">
            <button type="button" class="kf-theme-toggle" data-kf-theme aria-label="{{ marketing_text('Toggle dark mode') }}" title="{{ marketing_text('Toggle dark mode') }}">
                <span class="kf-theme-track">
                    <span class="kf-theme-thumb">
                        <svg class="kf-moon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg class="kf-sun" hidden width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"/></svg>
                    </span>
                </span>
            </button>

            <details class="kf-menu kf-lang-desktop">
                <summary class="kf-icon-btn" aria-label="{{ marketing_text('Change language') }}" title="{{ marketing_text('Change language') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/></svg>
                    <span class="kf-lang-code">{{ strtoupper(app()->getLocale()) }}</span>
                </summary>
                <div class="kf-menu-panel kf-lang-panel">
                    @foreach (supported_locales() as $code => $label)
                        <a href="{{ route('marketing.language', ['locale' => $code]) }}" data-switch-locale="{{ $code }}" @if(app()->getLocale() === $code) aria-current="true" @endif>
                            <span class="kf-lang-flag">{{ $localeFlags[$code] ?? '🌐' }}</span>
                            <span class="kf-lang-label">{{ $label }}</span>
                            <span class="kf-lang-code">{{ strtoupper($code) }}</span>
                        </a>
                    @endforeach
                </div>
            </details>

            <details class="kf-menu kf-login-desktop">
                <summary class="kf-btn kf-btn-ghost"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>{{ marketing_text('Login') }}</summary>
                <div class="kf-menu-panel kf-login-panel">
                    <a href="{{ route('school.login') }}">
                        <span class="kf-login-icon">🏫</span>
                        <span>{{ marketing_text('School Login') }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="{{ url('/platform/login') }}">
                        <span class="kf-login-icon">⚙️</span>
                        <span>{{ marketing_text('Platform Login') }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </details>

            <a href="{{ route('register') }}" class="kf-btn kf-btn-primary">{{ marketing_text('Register School') }}</a>

            <button type="button" class="kf-icon-btn kf-burger" id="kfBurger" aria-label="{{ marketing_text('Open menu') }}" aria-controls="kfMobile" aria-expanded="false">
                <svg class="kf-burger-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                <svg class="kf-burger-close" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>
            </button>
        </div>
        </div>
    </div>

    <div class="kf-mobile" id="kfMobile" aria-label="{{ marketing_text('Mobile navigation') }}">
        <div class="kf-mobile-inner">
            <nav class="kf-mobile-links" aria-label="{{ marketing_text('Mobile navigation') }}">
                @foreach ($navLinks as $link)
                    <a href="{{ $link['url'] }}" @class(['is-active' => $link['active']]) @if($link['active']) aria-current="page" @endif>
                        <span>{{ $link['label'] }}</span>
                        <span class="kf-mobile-arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach
            </nav>

            <div class="kf-mobile-section">
                <span class="kf-mobile-label">{{ marketing_text('Language') }}</span>
                <div class="kf-mobile-langs">
                    @foreach (supported_locales() as $code => $label)
                        <a href="{{ route('marketing.language', ['locale' => $code]) }}" data-switch-locale="{{ $code }}" @if(app()->getLocale() === $code) aria-current="true" @endif>
                            <span class="kf-mobile-lang-name"><span aria-hidden="true">{{ $localeFlags[$code] ?? '🌐' }}</span>{{ $label }}</span>
                            <span class="kf-mobile-code">{{ strtoupper($code) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="kf-mobile-section">
                <span class="kf-mobile-label">{{ marketing_text('Account') }}</span>
                <div class="kf-mobile-links">
                    <a href="{{ route('school.login') }}"><span>{{ marketing_text('School Login') }}</span><span class="kf-mobile-arrow" aria-hidden="true">→</span></a>
                    <a href="{{ url('/platform/login') }}"><span>{{ marketing_text('Platform Login') }}</span><span class="kf-mobile-arrow" aria-hidden="true">→</span></a>
                </div>
                <a href="{{ route('register') }}" class="kf-mobile-cta">{{ marketing_text('Register School') }} <span aria-hidden="true">→</span></a>
            </div>
        </div>
    </div>
</header>
<div class="kf-mobile-scrim" id="kfMobileScrim" aria-hidden="true" hidden></div>
@php
    $ecosystem = [
        ['admissions', marketing_text('Core'), marketing_text('Admissions, Students, Academics'), '#3b82f6', 'M4.26 10.147a60.436 60.436 0 0 0-.491 6.347A48.627 48.627 0 0 1 12 20.904a48.627 48.627 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.905 59.905 0 0 1 12 3.493a59.902 59.902 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5'],
        ['finance', marketing_text('Finance & Fees'), marketing_text('Fees, Billing, Invoicing, Accounting'), '#ef6050', 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
        ['hr', marketing_text('People'), marketing_text('HR, Payroll, Staff'), '#8b5cf6', 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z'],
        ['health', marketing_text('Health & Wellness'), marketing_text('Clinic, Records, Follow-ups'), '#f43f5e', 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z'],
        ['inventory', marketing_text('Inventory & Procurement'), marketing_text('Stock, Requisitions, Assets'), '#f59e0b', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
        ['communications', marketing_text('Communications'), marketing_text('Announcements, Email or WhatsApp alerts'), '#10b981', 'M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z'],
        ['exams', marketing_text('Academics & Exams'), marketing_text('Timetables, Results, Reports'), '#0284c7', 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25'],
        ['grid', marketing_text('All Features'), marketing_text('Explore the full feature set'), '#ef6050', 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z'],
    ];
@endphp
<div class="kf-mm-backdrop" id="mb2"></div>
<div class="kf-mm" id="mm" role="region" aria-label="{{ marketing_text('Platform modules') }}" aria-hidden="true">
    <div class="kf-mm-head">
        <div>
            <span class="kf-mm-eyebrow">{{ marketing_text('Platform ecosystem') }}</span>
            <small>{{ marketing_text('Explore every integrated department and module') }}</small>
        </div>
        <a class="kf-mm-viewall" href="{{ route('marketing.features') }}#grid" data-g="grid">{{ marketing_text('View all features') }} <span aria-hidden="true">→</span></a>
    </div>
    <div class="kf-mm-grid">
        @foreach ($ecosystem as [$key, $title, $desc, $color, $icon])
            <a class="kf-mm-item @if($key === 'grid') is-all @endif" href="{{ route('marketing.features') }}#{{ $key }}" data-g="{{ $key }}" style="--c:{{ $color }}">
                <span class="kf-mm-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"/></svg>
                </span>
                <span class="kf-mm-copy">
                    <b>{{ $title }} @if($key === 'grid')<i class="kf-mm-badge">{{ marketing_text('FULL') }}</i>@endif</b>
                    <small>{{ $desc }}</small>
                </span>
                <span class="kf-mm-arrow" aria-hidden="true">→</span>
            </a>
        @endforeach
    </div>
    <div class="kf-mm-foot">
        <span>{{ marketing_text('Cloud-based, multi-language system built for modern schools.') }}</span>
        <a href="{{ route('marketing.contact') }}#contact-form">{{ marketing_text('Book a demo') }} <span aria-hidden="true">→</span></a>
    </div>
</div>

<script>
document.addEventListener('click', function (e) {
    var link = e.target.closest('[data-switch-locale]');
    if (!link) return;
    e.preventDefault();
    var url = link.getAttribute('href');
    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    }).then(function () {
        window.location.reload();
    }).catch(function () {
        window.location.reload();
    });
});
</script>
