<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Application Submitted Successfully - Kairo CORE') }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 1rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background:
                radial-gradient(120% 140% at 80% -10%, rgba(124, 108, 240, 0.4), transparent 55%),
                radial-gradient(120% 140% at 0% 0%, rgba(6, 182, 212, 0.25), transparent 45%),
                linear-gradient(135deg, #0b1033 0%, #141b4d 50%, #1e1b4b 100%);
            color: #0f172a;
        }

        .sc-success-wrap { position: relative; width: 100%; max-width: 560px; }

        .sc-success-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.4;
            pointer-events: none;
        }
        .sc-success-orb-1 { width: 320px; height: 320px; top: -120px; right: -100px; background: radial-gradient(circle, rgba(124, 108, 240, 0.5), transparent 70%); }
        .sc-success-orb-2 { width: 280px; height: 280px; bottom: -110px; left: -90px; background: radial-gradient(circle, rgba(6, 182, 212, 0.35), transparent 70%); }

        .sc-success-card {
            position: relative;
            width: 100%;
            border-radius: 1.75rem;
            padding: 2.75rem 2.5rem 2.25rem;
            background:
                radial-gradient(120% 90% at 15% 0%, rgba(255, 255, 255, 0.96), rgba(255, 255, 255, 0.82) 58%);
            -webkit-backdrop-filter: blur(24px) saturate(160%);
            backdrop-filter: blur(24px) saturate(160%);
            border: 1px solid rgba(255, 255, 255, 0.8);
            box-shadow:
                0 2px 4px rgba(2, 6, 23, 0.3),
                0 34px 80px -24px rgba(2, 6, 23, 0.75);
            text-align: center;
            overflow: hidden;
            animation: sc-card-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .sc-success-card::before {
            content: "";
            position: absolute;
            top: 0; left: 2.25rem; right: 2.25rem;
            height: 4px;
            border-radius: 0 0 6px 6px;
            background: linear-gradient(90deg, #10b981, #5b4fe9 55%, #06b6d4 100%);
        }
        @keyframes sc-card-in {
            from { opacity: 0; transform: translate3d(0, 15px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }

        .sc-check {
            width: 5rem;
            height: 5rem;
            margin: 0 auto;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 16px 34px -12px rgba(16, 185, 129, 0.7);
        }
        .sc-check svg { width: 2.4rem; height: 2.4rem; color: #fff; }

        .sc-success-title {
            margin: 1.1rem 0 0.6rem;
            font-size: clamp(1.4rem, 1rem + 1.4vw, 1.75rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            line-height: 1.2;
            color: #0f172a;
        }
        .sc-success-sub { margin: 0 0 0.75rem; font-size: 0.95rem; line-height: 1.6; color: #475569; }
        .sc-success-sub strong { color: #0f172a; }
        .sc-success-body { margin: 0 0 1.25rem; font-size: 0.88rem; line-height: 1.65; color: #64748b; }

        .sc-success-actions {
            border-top: 1px solid rgba(100, 116, 139, 0.2);
            padding-top: 1.4rem;
            margin-top: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
        }
        .sc-success-note { margin: 0; font-size: 0.78rem; line-height: 1.55; color: #94a3b8; }

        .sc-success-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.6rem;
            border-radius: 0.8rem;
            border: none;
            background: linear-gradient(135deg, #5b4fe9 0%, #7c6cf0 55%, #4338ca 100%);
            color: #fff;
            font-weight: 800;
            font-size: 0.9rem;
            letter-spacing: 0.01em;
            text-decoration: none;
            box-shadow: 0 14px 30px -12px rgba(91, 79, 233, 0.7);
            transition: transform 0.16s ease, box-shadow 0.16s ease;
            cursor: pointer;
        }
        .sc-success-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 36px -12px rgba(91, 79, 233, 0.8);
        }
        .sc-success-btn svg { width: 1rem; height: 1rem; }

        @media (max-width: 480px) {
            .sc-success-card { padding: 2rem 1.4rem 1.8rem; border-radius: 1.35rem; }
            .sc-success-card::before { left: 1.4rem; right: 1.4rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            .sc-success-card { animation: none; }
            .sc-success-btn { transition: none; }
        }
    </style>
</head>
<body>
    <div class="sc-success-wrap">
        <div class="sc-success-orb sc-success-orb-1" aria-hidden="true"></div>
        <div class="sc-success-orb sc-success-orb-2" aria-hidden="true"></div>

        <div class="sc-success-card">
            <div class="sc-check">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>

            <h2 class="sc-success-title">{{ __('Application Submitted!') }}</h2>
            <p class="sc-success-sub">
                {{ __('Thank you for choosing Kairo CORE for') }} <strong>{{ request('school_name') }}</strong>{{ __('.') }}
            </p>
            <p class="sc-success-body">
                {{ __('Your request has been received and is now awaiting administrator approval. We are preparing your school workspace in the background, and your administrator will receive a secure activation email as soon as it is approved. This may take a few moments. You can safely close this page.') }}
            </p>

            <div class="sc-success-actions">
                <p class="sc-success-note">
                    {{ __('Platform Administrators: You can approve this institution right now inside your global platform control panel.') }}
                </p>
                <a href="{{ url('/') }}" class="sc-success-btn">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10.3 4.3a1 1 0 0 1 1.4 1.4L7.4 10H19a1 1 0 1 1 0 2H7.4l4.3 4.3a1 1 0 1 1-1.4 1.4l-6-6a1 1 0 0 1 0-1.4l6-6Z"/></svg>
                    {{ __('Back to Home') }}
                </a>
            </div>
        </div>
    </div>
</body>
</html>
