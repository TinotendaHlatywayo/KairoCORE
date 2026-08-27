<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('School Registration System') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    @livewireStyles
    <style>
        *,*::before,*::after{box-sizing:border-box}
        .sc-terms-gate{text-align:center;padding:2rem 1rem}
        .sc-terms-gate .sc-terms-icon{width:4rem;height:4rem;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,rgba(99,102,241,.1),rgba(6,182,212,.08));color:#6366f1;margin-bottom:1rem;animation:termsPulse 3s ease-in-out infinite}
        @keyframes termsPulse{0%,100%{transform:scale(1);box-shadow:0 0 0 0 rgba(99,102,241,.15)}50%{transform:scale(1.04);box-shadow:0 0 0 14px rgba(99,102,241,0)}}
        .sc-fade-up{animation:scFadeUp .4s cubic-bezier(.4,0,.2,1) both}
        @keyframes scFadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
        .sc-step-appear{animation:scFadeUp .3s cubic-bezier(.4,0,.2,1) both}
        .sc-spinner{animation:scSpin .8s linear infinite}
        @keyframes scSpin{from{transform:rotate(0)}to{transform:rotate(360deg)}}

        .sc-mod-card{display:flex;align-items:center;gap:.6rem;width:100%;padding:.65rem .75rem;border-radius:.75rem;border:1.5px solid #e2e8f0;background:#fff;cursor:pointer;transition:all .2s ease;text-decoration:none;color:inherit}
        .sc-mod-card:hover{border-color:#93c5fd;box-shadow:0 3px 10px -3px rgba(0,0,0,.06);transform:translateY(-1px)}
        .sc-mod-card.selected{border-color:#3b82f6;background:#f8fafc;box-shadow:0 3px 12px -3px rgba(37,99,235,.12)}
        .sc-mod-card.locked{opacity:.9;cursor:default;background:#fcfdfe}
        .sc-mod-card.locked:hover{transform:none;border-color:#e2e8f0}
        .sc-mod-icon{width:2rem;height:2rem;flex-shrink:0;display:flex;align-items:center;justify-content:center;border-radius:.5rem;transition:all .2s}
        .sc-mod-icon svg{width:1rem;height:1rem;stroke-width:1.9}
        .sc-mod-card:hover .sc-mod-icon{transform:scale(1.06)}
        .sc-mod-body{flex:1;min-width:0;display:flex;flex-direction:column;gap:.02rem}
        .sc-mod-name{font-size:.78rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:.35rem}
        .sc-mod-desc{font-size:.63rem;color:#64748b;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .sc-lock-badge{display:inline-flex;align-items:center;gap:.15rem;padding:.08rem .3rem;border-radius:9999px;background:rgba(34,197,94,.1);color:#16a34a;font-size:.52rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em}
        .sc-mod-check{position:absolute;opacity:0;pointer-events:none}
        .sc-country-list{max-height:220px;overflow:auto;z-index:40}
        .sc-country-list button{font-size:.9rem}
        .sc-country-list button:hover{background:#f0f9ff}
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-dark">{{ __('Kairo CORE') }}</h2>
                    <p class="text-secondary">{{ __('Register your institution and launch your portal instantly.') }}</p>
                </div>
                {{ $slot }}
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top" style="border-color:#e2e8f0 !important;">
            <p class="text-muted small mb-1">
                <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color:#6366f1;font-weight:600;">{{ __('Terms of Service') }}</a>
                <span class="mx-1">·</span>
                <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer" class="text-decoration-none" style="color:#6366f1;font-weight:600;">{{ __('Terms of Use') }}</a>
            </p>
            <p class="text-muted small mb-0" style="font-size:0.7rem;">&copy; {{ date('Y') }} {{ __('Kairo CORE') }}. {{ __('All rights reserved.') }}</p>
        </div>
    </div>
    @livewireScripts
</body>
</html>
