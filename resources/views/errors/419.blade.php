<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page Expired') }} — Kairo CORE</title>
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
        @keyframes gridMove{0%{transform:translate(0,0)}100%{transform:translate(60px,60px)}}
        .glow{position:fixed;width:600px;height:600px;border-radius:50%;filter:blur(120px);opacity:.15;pointer-events:none}
        .glow-1{top:-200px;right:-100px;background:#8b5cf6;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;left:-100px;background:#ec4899;animation:float 8s ease-in-out 4s infinite}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}

        .container{position:relative;z-index:1;text-align:center;padding:2rem;max-width:600px}
        .code{
            font-size:clamp(6rem,15vw,12rem);font-weight:900;
            line-height:1;letter-spacing:-0.04em;
            background:linear-gradient(135deg,#8b5cf6,#ec4899);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            background-clip:text;animation:codePulse 3s ease-in-out infinite;
        }
        @keyframes codePulse{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}

        .shield-icon{
            width:80px;height:80px;margin:1.5rem auto;position:relative;
        }
        .shield-icon svg{width:100%;height:100%;animation:shieldBounce 2s ease-in-out infinite}
        @keyframes shieldBounce{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
        .shield-pulse{
            position:absolute;inset:-8px;border-radius:50%;
            border:2px solid rgba(139,92,246,.2);
            animation:shieldPulse 2s ease-in-out infinite;
        }
        @keyframes shieldPulse{0%,100%{transform:scale(1);opacity:.5}50%{transform:scale(1.15);opacity:0}}

        h1{font-size:1.5rem;font-weight:700;margin:.5rem 0 .5rem;color:#f1f5f9}
        p{font-size:0.9rem;color:#94a3b8;line-height:1.6;margin-bottom:2rem}

        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:#fff;
            box-shadow:0 6px 20px -4px rgba(139,92,246,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(139,92,246,.6)}
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
        <div class="code">419</div>

        <div class="shield-icon">
            <div class="shield-pulse"></div>
            <svg viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <path d="M12 8v4M12 16h.01"/>
            </svg>
        </div>

        <h1>{{ __('Page Expired') }}</h1>
        <p>{{ __('Your session has expired or the security token is no longer valid. This usually happens when you\'ve been idle for too long or opened the page in another tab.') }}</p>

        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                {{ __('Reload Page') }}
            </a>
            <a href="/" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-4 0a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1"/></svg>
                {{ __('Go Home') }}
            </a>
        </div>

        <div class="brand">Kairo CORE</div>
    </div>
</body>
</html>
