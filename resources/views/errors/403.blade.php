<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Access Denied') }} — Kairo CORE</title>
    <style>
        *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
        body{
            min-height:100vh;display:flex;align-items:center;justify-content:center;
            font-family:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;
            background:#0f172a;color:#f1f5f9;overflow:hidden;position:relative;
        }
        .bg-grid{
            position:fixed;inset:0;
            background-image:
                linear-gradient(rgba(148,163,184,0.04) 1px,transparent 1px),
                linear-gradient(90deg,rgba(148,163,184,0.04) 1px,transparent 1px);
            background-size:60px 60px;animation:gridMove 20s linear infinite;
        }
        @keyframes gridMove{from{transform:translate(0,0)}to{transform:translate(60px,60px)}}
        .glow{position:fixed;width:600px;height:600px;border-radius:50%;filter:blur(120px);opacity:.15;pointer-events:none}
        .glow-1{top:-200px;right:-100px;background:#f59e0b;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;left:-100px;background:#ef4444;animation:float 8s ease-in-out 4s infinite}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}

        .container{position:relative;z-index:1;text-align:center;padding:2rem;max-width:600px}
        .code{
            font-size:clamp(6rem,15vw,12rem);font-weight:900;
            line-height:1;letter-spacing:-0.04em;
            background:linear-gradient(135deg,#f59e0b,#ef4444);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            background-clip:text;animation:codePulse 3s ease-in-out infinite;
        }
        @keyframes codePulse{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}

        .lock-icon{width:80px;height:80px;margin:1.5rem auto;position:relative}
        .lock-icon svg{width:100%;height:100%;animation:lockShake 3s ease-in-out infinite}
        @keyframes lockShake{
            0%,88%,100%{transform:translateY(0) rotate(0)}
            91%{transform:translateY(-3px) rotate(-6deg)}
            94%{transform:translateY(-1px) rotate(5deg)}
            97%{transform:translateY(0) rotate(-2deg)}
        }
        .lock-pulse{
            position:absolute;inset:-8px;border-radius:50%;
            border:2px solid rgba(245,158,11,.2);animation:lockPulse 2.5s ease-in-out infinite;
        }
        @keyframes lockPulse{
            0%{transform:scale(1);opacity:.5}50%{transform:scale(1.15);opacity:0}100%{transform:scale(1);opacity:0}
        }

        h1{font-size:1.5rem;font-weight:700;margin:.5rem 0 .5rem;color:#f1f5f9}
        p{font-size:0.9rem;color:#94a3b8;line-height:1.6;margin-bottom:1rem}

        .hint{
            font-size:.8rem;color:#64748b;margin:1rem auto 1.5rem;
            padding:1rem;border-radius:.75rem;max-width:470px;
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);
            line-height:1.6;text-align:left;
        }
        .hint strong{color:#94a3b8}
        .hint ul{margin:.5rem 0 0;padding-left:1.1rem}
        .hint li{margin:.3rem 0}

        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap;margin-bottom:1.5rem}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#f59e0b,#ea580c);color:#fff;
            box-shadow:0 6px 20px -4px rgba(245,158,11,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(245,158,11,.6)}
        .btn-ghost{background:rgba(255,255,255,.06);color:#94a3b8;border:1.5px solid rgba(255,255,255,.1)}
        .btn-ghost:hover{background:rgba(255,255,255,.1);color:#f1f5f9;border-color:rgba(255,255,255,.2)}
        .brand{font-size:.7rem;color:#475569;margin-top:2rem;letter-spacing:.05em}
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="container">
        <div class="code">403</div>

        <div class="lock-icon">
            <div class="lock-pulse"></div>
            <svg viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        {{-- Deliberately says nothing about which page was asked for: the
             sidebar already tells each person what they may open, and a URL
             that names a denied screen should not confirm it exists. --}}
        <h1>{{ __('Access Denied') }}</h1>
        <p>{{ __('This part of the workspace is not available to you. Your account is signed in, but the page you asked for is outside what your role covers.') }}</p>

        <div class="hint">
            <strong>{{ __('If you need this:') }}</strong>
            <ul>
                <li>{{ __('Ask a school administrator to review the permissions on your role.') }}</li>
                <li>{{ __('If your job has changed recently, the link in your browser may be out of date — use the sidebar to find where this now lives.') }}</li>
                <li>{{ __('Some areas are switched off for your school entirely, and no role can reach them.') }}</li>
            </ul>
        </div>

        <div class="actions">
            <a href="/workspace" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-4 0a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1"/></svg>
                {{ __('Back to My Workspace') }}
            </a>
            <button onclick="history.back()" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                {{ __('Go Back') }}
            </button>
        </div>

        <div class="brand">Kairo CORE</div>
    </div>
</body>
</html>
