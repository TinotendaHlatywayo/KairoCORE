<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Register Your School - Kairo CORE') }}</title>
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @livewireStyles
    @stack('styles')

    <style>
        :root {
            --sc-indigo: #5b4fe9;
            --sc-violet: #7c6cf0;
            --sc-deep: #4338ca;
            --sc-cyan: #06b6d4;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        /* ─────────────────────────── SCENE WRAPPER ─────────────────────────── */
        .sc-reg-wrap {
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
            background: linear-gradient(135deg, #0b1033 0%, #141b4d 45%, #1e1b4b 100%);
        }

        .sc-reg-stage {
            position: relative;
            z-index: 10;
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            /* Removed 'will-change: transform' to eliminate subpixel rendering blur */
        }

        .sc-reg-main {
            max-width: 60rem;
            width: 100%;
        }

        @media (max-width: 768px) {
            .sc-reg-stage { padding: 1.25rem 0.75rem; }
        }

        /* ─────────────────────────── HEADER ─────────────────────────── */
        .sc-reg-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.1rem;
        }

        .sc-reg-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #0f172a; /* Changed to high-contrast dark slate for visibility on light scenes */
        }

        .sc-reg-logo {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #5b4fe9 0%, #06b6d4 100%);
            box-shadow: 0 10px 24px -8px rgba(91, 79, 233, 0.7);
            outline: 3px solid rgba(91, 79, 233, 0.25);
        }

        .sc-reg-logo svg { width: 46%; height: 46%; color: #fff; }

        .sc-reg-brand b { font-weight: 800; letter-spacing: -0.01em; color: #0f172a; }

        .sc-reg-head-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #312e81; /* High contrast Indigo 900 */
            background: #e0e7ff; /* Light Indigo 100 */
            border: 1.5px solid #4f46e5; /* Stronger border line for visual sharpness */
            padding: 0.4rem 0.8rem;
            border-radius: 9999px;
            white-space: nowrap;
        }

        .sc-reg-head-pill .dot {
            width: 7px; height: 7px; border-radius: 9999px;
            background: linear-gradient(135deg, #a78bfa, #22d3ee);
            box-shadow: 0 0 8px rgba(129, 140, 248, 0.8);
            animation: sc-reg-pulse 2.4s ease-in-out infinite;
        }

        @keyframes sc-reg-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.3); opacity: 0.7; }
        }

        /* ─────────────────────────── GLASS CARD ─────────────────────────── */
        #sc-reg-card {
            border: 1.5px solid #cbd5e1; /* Sharper border */
            border-radius: 1.6rem;
            padding: 2.2rem 2.1rem 1.8rem;
            /* Increased background opacity to 99% to ensure underlying blur filter doesn't soften the form text */
            background: rgba(255, 255, 255, 0.99);
            -webkit-backdrop-filter: blur(12px) saturate(140%);
            backdrop-filter: blur(12px) saturate(140%);
            box-shadow:
                0 2px 4px rgba(15, 23, 42, 0.06),
                0 30px 70px -24px rgba(15, 23, 42, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 1);
            color: #0f172a;
            position: relative;
            overflow: hidden;
        }

        #sc-reg-card::before {
            content: "";
            position: absolute;
            top: 0; left: 2rem; right: 2rem;
            height: 3px;
            border-radius: 0 0 6px 6px;
            background: linear-gradient(90deg, #5b4fe9, #7c6cf0 45%, #06b6d4 100%);
            opacity: 0.85;
        }

        #sc-reg-card h4 { font-weight: 800; letter-spacing: -0.02em; color: #0f172a; }

        /* Progress badges */
        #sc-reg-card .badge {
            font-weight: 700;
            font-size: 0.7rem;
            letter-spacing: 0.02em;
            padding: 0.45rem 0.75rem;
            border-radius: 9999px;
        }
        #sc-reg-card .badge.bg-primary {
            background-image: linear-gradient(135deg, #5b4fe9 0%, #7c6cf0 100%) !important;
            box-shadow: 0 6px 16px -6px rgba(91, 79, 233, 0.6);
        }
        #sc-reg-card .badge.bg-secondary {
            background-color: #e2e8f0 !important;
            color: #334155; /* Darkened for better contrast */
        }

        /* Form controls — frosted, rounded, indigo focus */
        #sc-reg-card .form-control,
        #sc-reg-card .form-select {
            border: 1.5px solid #94a3b8; /* Darkened border for higher definition boundaries */
            border-radius: 0.7rem;
            padding: 0.62rem 0.85rem;
            font-size: 0.9rem;
            color: #0f172a;
            background-color: #ffffff; /* Solid white background prevents layout transparency blur */
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }
        #sc-reg-card .form-control:focus,
        #sc-reg-card .form-select:focus {
            border-color: #5b4fe9 !important;
            box-shadow: 0 0 0 3px rgba(91, 79, 233, 0.18), 0 0 24px -8px rgba(91, 79, 233, 0.5);
        }
        #sc-reg-card .form-control.is-invalid { border-color: #dc3545; }
        
        /* Darkened labels to Slate 900 for premium readability */
        #sc-reg-card .form-label { 
            font-weight: 700; 
            font-size: 0.82rem; 
            color: #0f172a; 
            letter-spacing: 0.015em; 
        }
        
        #sc-reg-card .form-control::placeholder,
        #sc-reg-card .form-select::placeholder {
            color: #475569; /* Darkened to Slate 600 so placeholders are clearly visible */
            opacity: 1;
        }
        
        /* Help/Info text under input fields */
        #sc-reg-card .form-text { 
            font-size: 0.8rem; 
            color: #374151; /* Darkened to Gray 700 */
            font-weight: 500; /* Medium weight prevents anti-aliasing font thinning */
        }
        
        #sc-reg-card .input-group-text {
            background: #f1f5f9;
            border: 1.5px solid #94a3b8;
            border-radius: 0 0.7rem 0.7rem 0 !important;
            font-size: 0.85rem;
            color: #1e293b;
        }

        /* Switch / checkbox */
        #sc-reg-card .form-check-input:checked { background-color: #5b4fe9; border-color: #5b4fe9; }
        #sc-reg-card .form-check-input:focus { box-shadow: 0 0 0 3px rgba(91, 79, 233, 0.2); }

        /* Buttons */
        #sc-reg-card .btn-primary {
            background-image: linear-gradient(135deg, #5b4fe9 0%, #7c6cf0 55%, #4338ca 100%) !important;
            border: none !important;
            border-radius: 0.85rem !important;
            font-weight: 800 !important;
            font-size: 0.92rem !important;
            padding: 0.65rem 1.4rem;
            box-shadow: 0 14px 30px -12px rgba(91, 79, 233, 0.7);
        }
        #sc-reg-card .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 18px 36px -12px rgba(91, 79, 233, 0.8); }
        #sc-reg-card .btn-outline-secondary {
            border: 1.5px solid #cbd5e1;
            color: #334155;
            border-radius: 0.85rem;
            font-weight: 700;
        }
        #sc-reg-card .btn-outline-secondary:hover { border-color: #94a3b8; background: #f8fafc; color: #0f172a; }
        #sc-reg-card .btn-success {
            background-image: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
            border: none !important;
            border-radius: 0.85rem !important;
            font-weight: 800 !important;
            box-shadow: 0 14px 30px -12px rgba(16, 185, 129, 0.6);
        }

        /* Error text */
        #sc-reg-card .invalid-feedback { font-size: 0.76rem; font-weight: 600; color: #e11d48; }
        #sc-reg-card .text-danger.small, #sc-reg-card .text-success.small { font-size: 0.8rem; font-weight: 600; }

        /* Country dropdown */
        .sc-country-list { max-height: 260px; overflow-y: auto; z-index: 1050; }
        .sc-country-list button:hover { background: #f1f5f9 !important; }

        @media (max-width: 576px) {
            #sc-reg-card { padding: 1.6rem 1.2rem 1.4rem; border-radius: 1.35rem; }
            #sc-reg-card::before { left: 1.2rem; right: 1.2rem; }
        }
    </style>
</head>
<body>
    <div
        class="sc-reg-wrap"
        x-data="{
            mx: 0,
            my: 0,
            init() {
                const el = this.$el;
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                const onMove = (e) => {
                    const r = el.getBoundingClientRect();
                    this.mx = ((e.clientX - r.left) / Math.max(r.width, 1) - 0.5) * 2;
                    this.my = ((e.clientY - r.top) / Math.max(r.height, 1) - 0.5) * 2;
                };
                el.addEventListener('mousemove', onMove, { passive: true });
            }
        }"
    >
        {{-- Animated school-life scene --}}
        <x-auth-login-scene />

        <div class="sc-reg-stage">
            <main class="sc-reg-main">
                <header class="sc-reg-head">
                    <div class="sc-reg-brand">
                        <div class="sc-reg-logo">
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M24 7 2 17.5 24 28 46 17.5 24 7Z" fill="currentColor"/>
                                <path d="M42 20.5v9" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                                <circle cx="44" cy="31.5" r="2.6" fill="currentColor"/>
                            </svg>
                        </div>
                        <b>Kairo CORE Enterprise</b>
                    </div>
                    <span class="sc-reg-head-pill"><span class="dot"></span>{{ __('Institution Registration') }}</span>
                </header>

                {{ $slot }}

                <p class="text-center mt-3" style="font-size: 0.75rem; color: #475569;">
                    {{ __('Secure Multi-Tenant SaaS Architecture') }}
                </p>
            </main>
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
