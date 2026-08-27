<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Server Error') }} — Kairo CORE</title>
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
        .glow-1{top:-200px;left:-100px;background:#ef4444;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;right:-100px;background:#f97316;animation:float 8s ease-in-out 4s infinite}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}

        .container{position:relative;z-index:1;text-align:center;padding:2rem;max-width:600px}
        .code{
            font-size:clamp(6rem,15vw,12rem);font-weight:900;
            line-height:1;letter-spacing:-0.04em;
            background:linear-gradient(135deg,#ef4444,#f97316);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            background-clip:text;animation:codePulse 3s ease-in-out infinite;
        }
        @keyframes codePulse{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}

        .gear-container{
            width:80px;height:80px;margin:1.5rem auto;position:relative;
        }
        .gear{position:absolute;inset:0}
        .gear svg{width:100%;height:100%;animation:spin 4s linear infinite}
        .gear:nth-child(2) svg{animation:spinReverse 3s linear infinite;width:55%;height:55%;top:22%;left:22%;position:absolute}
        @keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}
        @keyframes spinReverse{from{transform:rotate(0)}to{transform:rotate(-360deg)}}

        h1{font-size:1.5rem;font-weight:700;margin:.5rem 0 .5rem;color:#f1f5f9}
        p{font-size:0.9rem;color:#94a3b8;line-height:1.6;margin-bottom:.5rem}

        .hint{
            font-size:.8rem;color:#64748b;margin:1rem auto 2rem;
            padding:1rem;border-radius:.75rem;max-width:460px;
            background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.06);
            line-height:1.6;text-align:left;
        }
        .hint strong{color:#94a3b8}

        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;
            box-shadow:0 6px 20px -4px rgba(239,68,68,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(239,68,68,.6)}
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
        <div class="code">500</div>

        <div class="gear-container">
            <div class="gear">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.982l-.722 1.449a1.875 1.875 0 0 0 .432 2.137l.764.765c.127.127.136.298.066.428a7.5 7.5 0 0 0 0 1.462c.07.13.06.3-.066.428l-.764.765a1.875 1.875 0 0 0-.432 2.137l.722 1.449c.406.813 1.367 1.181 2.282.982l1.018-.226c.116-.043.284-.032.45.083.311.218.642.403.986.57.182.088.277.227.297.348l.178 1.072c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.115-.26.297-.348.344-.167.675-.352.986-.57.166-.115.334-.126.45-.083l1.018.226c.915.199 1.876-.169 2.282-.982l.722-1.449a1.875 1.875 0 0 0-.432-2.137l-.764-.765c-.127-.127-.136-.298-.066-.428a7.5 7.5 0 0 0 0-1.462c-.07-.13-.06-.3.066-.428l.764-.765a1.875 1.875 0 0 0 .432-2.137l-.722-1.449a1.875 1.875 0 0 0-2.282-.982l-1.018.226c-.116.043-.284.032-.45-.083a7.493 7.493 0 0 0-.986-.57c-.182-.088-.277-.227-.297-.348l-.178-1.072A1.875 1.875 0 0 0 12.922 2.25h-1.844Z" clip-rule="evenodd"/>
                    <path fill-rule="evenodd" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="gear">
                <svg viewBox="0 0 24 24" fill="none" stroke="#f97316" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.982l-.722 1.449a1.875 1.875 0 0 0 .432 2.137l.764.765c.127.127.136.298.066.428a7.5 7.5 0 0 0 0 1.462c.07.13.06.3-.066.428l-.764.765a1.875 1.875 0 0 0-.432 2.137l.722 1.449c.406.813 1.367 1.181 2.282.982l1.018-.226c.116-.043.284-.032.45.083.311.218.642.403.986.57.182.088.277.227.297.348l.178 1.072c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.115-.26.297-.348.344-.167.675-.352.986-.57.166-.115.334-.126.45-.083l1.018.226c.915.199 1.876-.169 2.282-.982l.722-1.449a1.875 1.875 0 0 0-.432-2.137l-.764-.765c-.127-.127-.136-.298-.066-.428a7.5 7.5 0 0 0 0-1.462c-.07-.13-.06-.3.066-.428l.764-.765a1.875 1.875 0 0 0 .432-2.137l-.722-1.449a1.875 1.875 0 0 0-2.282-.982l-1.018.226c-.116.043-.284.032-.45-.083a7.493 7.493 0 0 0-.986-.57c-.182-.088-.277-.227-.297-.348l-.178-1.072A1.875 1.875 0 0 0 12.922 2.25h-1.844Z" clip-rule="evenodd"/>
                    <path fill-rule="evenodd" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd"/>
                </svg>
            </div>
        </div>

        <h1>{{ __('Something Went Wrong') }}</h1>
        <p>{{ __('Our servers encountered an unexpected error while processing your request.') }}</p>

        <div class="hint">
            <strong>{{ __('Don\'t worry — this has been logged.') }}</strong><br>
            {{ __('Our team has been notified automatically. You can try the following:') }}
            <ul style="margin:.5rem 0 0 1.2rem;color:#64748b">
                <li>{{ __('Refresh the page and try again') }}</li>
                <li>{{ __('Go back to the previous page') }}</li>
                <li>{{ __('If the problem persists, contact support with the time of the error') }}</li>
            </ul>
        </div>

        <div class="actions">
            <button onclick="location.reload()" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                {{ __('Try Again') }}
            </button>
            <button onclick="history.back()" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                {{ __('Go Back') }}
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
