<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', platform_name() . ' - ' . __('Multi-Tenant School Management Platform'))</title>
    <meta name="description" content="{{ __(platform_name() . ' is a multi-tenant school management platform with admissions, academics, fees, attendance, communication, and a customizable public website for every school.') }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ platform_name() }}">
    <meta property="og:title" content="{{ platform_name() }} - {{ __('Multi-Tenant School Management Platform') }}">
    <meta property="og:description" content="{{ __('Run your entire school administration from a single, secure platform.') }}">
    <link rel="icon" href="{{ platform_favicon_url() }}">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <style>
        :root {
            --sc-indigo: #5b4fe9;
            --sc-violet: #7c6cf0;
            --sc-deep: #4338ca;
            --sc-cyan: #06b6d4;
            --sc-navy: #0b1033;
            --sc-ink: #0f172a;
            --sc-slate: #475569;
            --sc-muted: #64748b;
            --sc-border: rgba(15, 23, 42, 0.08);
            --sc-radius: 1.1rem;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            font-size: 1rem;
            line-height: 1.6;
            color: var(--sc-ink);
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            min-height: 100dvh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        a { color: inherit; }

        .sc-container {
            width: 100%;
            max-width: 1140px;
            margin-inline: auto;
            padding-inline: 1.25rem;
        }

        /* ---------- Nav ---------- */
        .sc-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(255, 255, 255, 0.85);
            -webkit-backdrop-filter: blur(18px) saturate(160%);
            backdrop-filter: blur(18px) saturate(160%);
            border-bottom: 1px solid var(--sc-border);
        }
        .sc-nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding-block: 0.8rem;
        }
        .sc-nav-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            text-decoration: none;
            color: var(--sc-ink);
            font-weight: 800;
            font-size: 1.1rem;
            letter-spacing: -0.01em;
            white-space: nowrap;
        }
        .sc-nav-logo {
            width: 2.4rem; height: 2.4rem; border-radius: 0.8rem;
            display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, #5b4fe9 0%, #06b6d4 100%);
            box-shadow: 0 8px 20px -8px rgba(91, 79, 233, 0.7);
        }
        .sc-nav-logo svg { width: 46%; height: 46%; color: #fff; }
        .sc-nav-links {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        .sc-nav-link {
            color: var(--sc-slate);
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            padding: 0.4rem 0.85rem;
            border-radius: 0.6rem;
            transition: background 0.15s ease, color 0.15s ease;
        }
        .sc-nav-link:hover { color: var(--sc-indigo); background: rgba(91, 79, 233, 0.08); }
        .sc-lang-wrap { position: relative; }
        .sc-lang-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            cursor: pointer;
            border: none;
            background: transparent;
            font-family: inherit;
        }
        .sc-lang-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 0.45rem);
            min-width: 11rem;
            background: #fff;
            border: 1px solid var(--sc-border);
            border-radius: 0.75rem;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.14);
            overflow: hidden;
            z-index: 60;
            padding: 0.3rem;
        }
        .sc-lang-item {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.5rem 0.7rem;
            font-size: 0.88rem;
            font-weight: 500;
            color: #334155;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: background 0.12s ease;
        }
        .sc-lang-item:hover { background: #f1f5f9; color: var(--sc-indigo); }
        .sc-lang-item.is-active { background: #eef2ff; font-weight: 700; }
        .sc-nav-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 2.5rem; height: 2.5rem;
            border: 1.5px solid var(--sc-border);
            border-radius: 0.7rem;
            background: #fff;
            cursor: pointer;
            color: var(--sc-ink);
        }
        .sc-nav-toggle svg { width: 1.25rem; height: 1.25rem; }

        /* ---------- Buttons ---------- */
        .sc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            font-weight: 700;
            font-size: 0.9rem;
            text-decoration: none;
            border-radius: 0.8rem;
            padding: 0.55rem 1.2rem;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.15s ease, border-color 0.15s ease;
        }
        .sc-btn:hover { transform: translateY(-1px); }
        .sc-btn-lg { font-size: 1rem; padding: 0.75rem 1.6rem; border-radius: 0.9rem; }
        .sc-btn-block { width: 100%; }
        .sc-btn-primary {
            background-image: linear-gradient(135deg, #5b4fe9 0%, #7c6cf0 55%, #4338ca 100%);
            color: #fff;
            box-shadow: 0 12px 26px -10px rgba(91, 79, 233, 0.7);
        }
        .sc-btn-primary:hover { box-shadow: 0 16px 32px -10px rgba(91, 79, 233, 0.85); }
        .sc-btn-outline {
            border-color: #cbd5e1;
            color: #334155;
            background: #fff;
        }
        .sc-btn-outline:hover { border-color: var(--sc-indigo); color: var(--sc-indigo); }
        .sc-btn-light { background: #fff; color: var(--sc-deep); box-shadow: 0 16px 40px -16px rgba(2, 6, 23, 0.4); }

        /* ---------- Hero ---------- */
        .sc-hero {
            position: relative;
            overflow: hidden;
            text-align: center;
            color: #fff;
            background:
                radial-gradient(120% 140% at 80% -10%, rgba(124, 108, 240, 0.55), transparent 55%),
                radial-gradient(120% 140% at 0% 0%, rgba(6, 182, 212, 0.35), transparent 45%),
                linear-gradient(135deg, #0b1033 0%, #141b4d 50%, #1e1b4b 100%);
            padding: 4.5rem 0 5.5rem;
        }
        .sc-hero-orb {
            position: absolute;
            border-radius: 9999px;
            pointer-events: none;
            filter: blur(60px);
            opacity: 0.5;
        }
        .sc-hero-orb-1 { width: 420px; height: 420px; background: #5b4fe9; top: -120px; right: -80px; }
        .sc-hero-orb-2 { width: 340px; height: 340px; background: #06b6d4; bottom: -140px; left: -60px; }
        .sc-hero-orb-3 { width: 200px; height: 200px; background: #7c6cf0; top: 40%; left: 12%; }
        .sc-hero-content { position: relative; }
        .sc-hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #e0e7ff;
            background: rgba(129, 140, 248, 0.18);
            border: 1px solid rgba(129, 140, 248, 0.3);
            padding: 0.42rem 0.85rem;
            border-radius: 9999px;
            margin: 0 0 1.25rem;
        }
        .sc-hero h1 {
            margin: 0 0 1rem;
            font-size: clamp(2rem, 4.4vw, 3.4rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.15;
        }
        .sc-hero p {
            margin: 0 auto 1.75rem;
            max-width: 700px;
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.85);
        }
        .sc-hero-actions {
            display: flex;
            gap: 0.9rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* ---------- Sections & grid ---------- */
        .sc-section { padding: 4rem 0; }
        .sc-section-center { text-align: center; }
        .sc-grid {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(12, 1fr);
        }
        .sc-col-12 { grid-column: span 12; }
        .sc-col-lg-8 { grid-column: span 12; }
        .sc-col-lg-6 { grid-column: span 12; }
        .sc-col-lg-4 { grid-column: span 12; }
        .sc-col-md-4 { grid-column: span 12; }
        .sc-col-lg-7 { grid-column: span 12; }
        .sc-col-lg-5 { grid-column: span 12; }

        @media (min-width: 768px) {
            .sc-col-md-4 { grid-column: span 4; }
        }
        @media (min-width: 992px) {
            .sc-col-lg-8 { grid-column: span 8; }
            .sc-col-lg-6 { grid-column: span 6; }
            .sc-col-lg-4 { grid-column: span 4; }
            .sc-col-lg-7 { grid-column: span 7; }
            .sc-col-lg-5 { grid-column: span 5; }
        }

        .sc-card {
            border: 1px solid var(--sc-border);
            border-radius: var(--sc-radius);
            background: #fff;
            box-shadow: 0 2px 4px rgba(15, 23, 42, 0.05), 0 20px 44px -24px rgba(15, 23, 42, 0.25);
            height: 100%;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .sc-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px -16px rgba(15, 23, 42, 0.3); }
        .sc-card-body { padding: 1.5rem; }
        .sc-card-icon {
            width: 3rem; height: 3rem; border-radius: 0.9rem;
            display: flex; align-items: center; justify-content: center;
            background: rgba(91, 79, 233, 0.12);
            color: var(--sc-indigo);
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .sc-card h3, .sc-card h4, .sc-card h5 { margin: 0 0 0.6rem; font-weight: 800; letter-spacing: -0.01em; color: var(--sc-ink); }
        .sc-card p { margin: 0; color: var(--sc-muted); font-size: 0.9rem; }

        /* ---------- CTA band ---------- */
        .sc-cta {
            border-radius: 1.25rem;
            background: linear-gradient(135deg, #5b4fe9 0%, #06b6d4 100%);
            color: #fff;
            padding: 2.5rem;
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            align-items: center;
        }
        .sc-cta h3 { margin: 0 0 0.4rem; font-weight: 800; letter-spacing: -0.01em; }
        .sc-cta p { margin: 0; color: rgba(255, 255, 255, 0.9); }
        @media (min-width: 992px) {
            .sc-cta { grid-template-columns: 2fr 1fr; }
            .sc-cta-actions { text-align: right; }
        }

        /* ---------- Page header (about/contact) ---------- */
        .sc-page-title {
            margin: 0 0 0.75rem;
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            color: var(--sc-ink);
        }
        .sc-page-lead {
            margin: 0 0 1.25rem;
            font-size: 1.125rem;
            color: var(--sc-slate);
        }
        .sc-hr {
            border: none;
            border-top: 1px solid var(--sc-border);
            margin: 1.5rem 0;
        }
        .sc-text-muted { color: var(--sc-muted); }

        /* ---------- Alert ---------- */
        .sc-alert {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            border-radius: 1rem;
            padding: 1.1rem 1.25rem;
            margin-bottom: 0;
            border: 1px solid rgba(16, 185, 129, 0.4);
            background: #ecfdf5;
            color: #065f46;
            box-shadow: 0 10px 28px -18px rgba(16, 185, 129, 0.7);
        }
        .sc-alert strong { display: block; font-size: 0.95rem; margin-bottom: 0.15rem; }
        .sc-alert-close {
            border: none;
            background: none;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
            color: #065f46;
            padding: 0.2rem;
        }

        /* ---------- Forms ---------- */
        .sc-form { display: grid; gap: 1.1rem; }
        .sc-field { display: grid; gap: 0.4rem; }
        .sc-form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--sc-slate);
        }
        .sc-input, .sc-textarea {
            width: 100%;
            font: inherit;
            color: var(--sc-ink);
            background: #fff;
            border: 1.5px solid #cbd5e1;
            border-radius: 0.7rem;
            padding: 0.6rem 0.85rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .sc-input::placeholder, .sc-textarea::placeholder { color: #94a3b8; }
        .sc-input:focus, .sc-textarea:focus {
            outline: none;
            border-color: var(--sc-indigo);
            box-shadow: 0 0 0 3px rgba(91, 79, 233, 0.18);
        }
        .sc-textarea { resize: vertical; min-height: 6.5rem; }

        /* ---------- Footer ---------- */
        .sc-footer {
            margin-top: auto;
            background: linear-gradient(135deg, #0b1033 0%, #1e1b4b 100%);
            color: #cbd5e1;
            padding: 1.25rem 0;
            text-align: center;
        }
        .sc-footer p { margin: 0; font-size: 0.85rem; }
        .sc-footer a { color: #a5b4fc; text-decoration: none; }
        .sc-footer a:hover { text-decoration: underline; }

        /* ---------- Responsive ---------- */
        @media (max-width: 860px) {
            .sc-nav-toggle { display: inline-flex; }
            .sc-nav-inner { flex-wrap: wrap; }
            .sc-nav-links {
                display: none;
                flex-direction: column;
                align-items: stretch;
                gap: 0.25rem;
                width: 100%;
                padding: 0.5rem 0 0.25rem;
            }
            .sc-nav-links.is-open { display: flex; }
            .sc-nav-links .sc-nav-link { padding: 0.6rem 0.85rem; }
            .sc-nav-links .sc-btn { justify-content: center; }
            .sc-hero { padding: 3rem 0 3.5rem; }
            .sc-hero h1 { font-size: 2rem; }
            .sc-section { padding: 3rem 0; }
            .sc-cta { padding: 1.75rem; }
        }
    </style>
</head>
<body x-data="{ navOpen: false }">

    <nav class="sc-nav">
        <div class="sc-container sc-nav-inner">
            <a class="sc-nav-brand" href="{{ route('marketing.home') }}">
                <span class="sc-nav-logo">
                    <img src="{{ platform_logo_url() }}" alt="{{ platform_name() }}" style="width: 100%; height: 100%; object-fit: contain;">
                </span>
                {{ platform_name() }}
            </a>
            <button class="sc-nav-toggle" type="button" @click="navOpen = !navOpen" :aria-expanded="navOpen.toString()" aria-controls="marketing-nav" aria-label="{{ __('Toggle navigation') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <path x-show="!navOpen" d="M4 6h16M4 12h16M4 18h16"/>
                    <path x-show="navOpen" x-cloak d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
            <div class="sc-nav-links" id="marketing-nav" :class="{ 'is-open': navOpen }">
                <a class="sc-nav-link" href="{{ route('marketing.home') }}" @click="navOpen = false">{{ __('Home') }}</a>
                <a class="sc-nav-link" href="{{ route('marketing.about') }}" @click="navOpen = false">{{ __('About') }}</a>
                <a class="sc-nav-link" href="{{ route('marketing.contact') }}" @click="navOpen = false">{{ __('Contact') }}</a>
                @php
                    $siteLocale = app()->getLocale();
                    $siteLanguages = [
                        'en' => ['label' => 'English', 'flag' => '🇬🇧'],
                        'sn' => ['label' => 'Shona', 'flag' => '🇿🇼'],
                        'sw' => ['label' => 'Swahili', 'flag' => '🇹🇿'],
                        'fr' => ['label' => 'Français', 'flag' => '🇫🇷'],
                        'pt' => ['label' => 'Português', 'flag' => '🇵🇹'],
                        'es' => ['label' => 'Español', 'flag' => '🇪🇸'],
                    ];
                @endphp
                <div x-data="{ langOpen: false }" class="sc-lang-wrap" @click.away="langOpen = false" @keydown.escape.window="langOpen = false">
                    <button type="button" class="sc-nav-link sc-lang-btn" @click="langOpen = !langOpen" :aria-expanded="langOpen.toString()" aria-haspopup="true">
                        {{ $siteLanguages[$siteLocale]['flag'] ?? '🌐' }}
                        <span>{{ $siteLanguages[$siteLocale]['label'] ?? 'English' }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="12" height="12" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>
                    <div x-show="langOpen" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                         class="sc-lang-menu" role="menu" style="display: none;">
                        @foreach($siteLanguages as $code => $lang)
                            <a href="{{ route('locale.switch', ['locale' => $code]) }}"
                               class="sc-lang-item {{ $code === $siteLocale ? 'is-active' : '' }}"
                               role="menuitem"
                               @click="navOpen = false; langOpen = false">
                                <span class="text-base leading-none">{{ $lang['flag'] }}</span>
                                <span>{{ $lang['label'] }}</span>
                                @if($code === $siteLocale)<span style="margin-left:auto;color:#059669;">✓</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
                <a href="/platform" class="sc-btn sc-btn-outline" @click="navOpen = false" target="_blank" rel="noopener noreferrer">{{ __('Platform Login') }}</a>
                <a href="{{ route('register') }}" class="sc-btn sc-btn-primary" @click="navOpen = false" target="_blank" rel="noopener noreferrer">{{ __('Register School') }}</a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <footer class="sc-footer">
        <div class="sc-container">
            <p>
                &copy; {{ date('Y') }} <a href="{{ route('marketing.home') }}" style="color: inherit; text-decoration: none; font-weight: 600;">{{ platform_name() }}</a>. All rights reserved. &middot;
                <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer">{{ __('Terms of Service') }}</a> &middot;
                <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer">{{ __('Terms of Use') }}</a> &middot;
                <a href="{{ route('marketing.about') }}">{{ __('About') }}</a> &middot;
                <a href="{{ route('marketing.contact') }}">{{ __('Contact') }}</a> &middot;
                <a href="/platform" target="_blank" rel="noopener noreferrer">{{ __('Platform Login') }}</a>
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</body>
</html>
