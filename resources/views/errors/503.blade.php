<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Service Unavailable') }} — Kairo CORE</title>
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
        .glow-1{top:-200px;left:50%;transform:translateX(-50%);background:#06b6d4;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;left:-100px;background:#10b981;animation:float 8s ease-in-out 4s infinite}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}

        .container{position:relative;z-index:1;text-align:center;padding:2rem;max-width:600px}
        .code{
            font-size:clamp(6rem,15vw,12rem);font-weight:900;
            line-height:1;letter-spacing:-0.04em;
            background:linear-gradient(135deg,#06b6d4,#10b981);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            background-clip:text;animation:codePulse 3s ease-in-out infinite;
        }
        @keyframes codePulse{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}

        .wrench-icon{
            width:80px;height:80px;margin:1.5rem auto;position:relative;
        }
        .wrench-icon svg{width:100%;height:100%;animation:wrenchSwing 2s ease-in-out infinite;transform-origin:top center}
        @keyframes wrenchSwing{0%,100%{transform:rotate(-5deg)}50%{transform:rotate(5deg)}}

        h1{font-size:1.5rem;font-weight:700;margin:.5rem 0 .5rem;color:#f1f5f9}
        p{font-size:0.9rem;color:#94a3b8;line-height:1.6;margin-bottom:.5rem}

        .hint{
            font-size:.8rem;color:#64748b;margin:1rem auto 2rem;
            padding:1rem;border-radius:.75rem;max-width:460px;
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);
            line-height:1.6;text-align:left;
        }
        .hint strong{color:#94a3b8}
        .hint ul{margin:.5rem 0 0 1.2rem;color:#64748b}
        .hint li{margin-bottom:.25rem}

        .progress-bar{
            max-width:300px;margin:0 auto 2rem;height:4px;border-radius:2px;
            background:rgba(255,255,255,.06);overflow:hidden;
        }
        .progress-fill{
            height:100%;border-radius:2px;
            background:linear-gradient(90deg,#06b6d4,#10b981);
            animation:progress 3s ease-in-out infinite;
        }
        @keyframes progress{0%{width:0%}50%{width:100%}100%{width:0%}}

        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#06b6d4,#0891b2);color:#fff;
            box-shadow:0 6px 20px -4px rgba(6,182,212,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(6,182,212,.6)}
        .btn-ghost{background:rgba(255,255,255,.06);color:#94a3b8;border:1.5px solid rgba(255,255,255,.1)}
        .btn-ghost:hover{background:rgba(255,255,255,.1);color:#f1f5f9;border-color:rgba(255,255,255,.2)}
        .brand{font-size:.7rem;color:#475569;margin-top:2rem;letter-spacing:.05em}
    </style>
    <script>
        setTimeout(()=>location.reload(), 30000);
    </script>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="container">
        <div class="code">503</div>

        <div class="wrench-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085"/>
            </svg>
        </div>

        <h1>{{ __('Under Maintenance') }}</h1>
        <p>{{ __('We\'re performing scheduled maintenance to improve your experience. The system will be back shortly.') }}</p>

        <div class="progress-bar"><div class="progress-fill"></div></div>

        <div class="hint">
            <strong>{{ __('What\'s happening?') }}</strong>
            <ul>
                <li>{{ __('We\'re updating system components and database') }}</li>
                <li>{{ __('The page will automatically refresh when maintenance is complete') }}</li>
                <li>{{ __('Typical maintenance takes 5-15 minutes') }}</li>
            </ul>
        </div>

        <div class="actions">
            <button onclick="location.reload()" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                {{ __('Refresh Now') }}
            </button>
            <a href="/" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-4 0a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1"/></svg>
                {{ __('Home') }}
            </a>
        </div>

        <div class="brand">Kairo CORE</div>
    </div>
</body>
</html>
