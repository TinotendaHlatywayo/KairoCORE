<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page Not Found') }} — Kairo CORE</title>
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
        .glow-1{top:-200px;right:-100px;background:#6366f1;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;left:-100px;background:#06b6d4;animation:float 8s ease-in-out 4s infinite}
        @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-30px)}}

        .container{position:relative;z-index:1;text-align:center;padding:2rem;max-width:600px}
        .code{
            font-size:clamp(6rem,15vw,12rem);font-weight:900;
            line-height:1;letter-spacing:-0.04em;
            background:linear-gradient(135deg,#6366f1,#06b6d4);
            -webkit-background-clip:text;-webkit-text-fill-color:transparent;
            background-clip:text;animation:codePulse 3s ease-in-out infinite;
        }
        @keyframes codePulse{0%,100%{filter:brightness(1)}50%{filter:brightness(1.3)}}
        .float-emoji{
            position:absolute;font-size:2rem;
            animation:floatUp 6s ease-in-out infinite;opacity:0;
        }
        @keyframes floatUp{
            0%{opacity:0;transform:translateY(40px) rotate(0deg)}
            20%{opacity:.7}80%{opacity:.7}
            100%{opacity:0;transform:translateY(-80px) rotate(20deg)}
        }

        h1{font-size:1.5rem;font-weight:700;margin:1rem 0 .5rem;color:#f1f5f9}
        p{font-size:0.9rem;color:#94a3b8;line-height:1.6;margin-bottom:2rem}
        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff;
            box-shadow:0 6px 20px -4px rgba(99,102,241,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(99,102,241,.6)}
        .btn-ghost{background:rgba(255,255,255,.06);color:#94a3b8;border:1.5px solid rgba(255,255,255,.1)}
        .btn-ghost:hover{background:rgba(255,255,255,.1);color:#f1f5f9;border-color:rgba(255,255,255,.2)}

        .search-box{
            display:flex;gap:0;margin-top:1.5rem;
            border-radius:.75rem;overflow:hidden;
            border:1.5px solid rgba(255,255,255,.1);
            background:rgba(255,255,255,.04);
            transition:border-color .2s;max-width:400px;margin-left:auto;margin-right:auto;
        }
        .search-box:focus-within{border-color:#6366f1}
        .search-box input{
            flex:1;padding:.7rem 1rem;background:transparent;border:none;
            color:#f1f5f9;font-size:.85rem;outline:none;
        }
        .search-box input::placeholder{color:#475569}
        .search-box button{
            padding:.7rem 1rem;background:rgba(99,102,241,.15);border:none;
            color:#818cf8;cursor:pointer;transition:background .2s;
        }
        .search-box button:hover{background:rgba(99,102,241,.25)}
        .brand{font-size:.7rem;color:#475569;margin-top:2rem;letter-spacing:.05em}
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <span class="float-emoji" style="left:10%;top:20%;animation-delay:0s">🔍</span>
    <span class="float-emoji" style="right:15%;top:30%;animation-delay:2s">🗺️</span>
    <span class="float-emoji" style="left:20%;bottom:25%;animation-delay:4s">🧭</span>
    <span class="float-emoji" style="right:10%;bottom:20%;animation-delay:1s">📎</span>

    <div class="container">
        <div class="code">404</div>
        <h1>{{ __('Page Not Found') }}</h1>
        <p>{{ __('The page you\'re looking for doesn\'t exist, has been moved, or is temporarily unavailable.') }}</p>

        <div class="actions">
            <a href="/" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-4 0a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1"/></svg>
                {{ __('Back to Home') }}
            </a>
            <button onclick="history.back()" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/></svg>
                {{ __('Go Back') }}
            </button>
        </div>

        <form class="search-box" action="/" method="GET">
            <input type="text" name="q" placeholder="{{ __('Search for something...') }}" autocomplete="off">
            <button type="submit">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            </button>
        </form>

        <div class="brand">Kairo CORE</div>
    </div>
</body>
</html>
