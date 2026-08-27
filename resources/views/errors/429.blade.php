<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Too Many Requests') }} — Kairo CORE</title>
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
        .glow-1{top:-200px;left:-100px;background:#f59e0b;animation:float 8s ease-in-out infinite}
        .glow-2{bottom:-200px;right:-100px;background:#ef4444;animation:float 8s ease-in-out 4s infinite}
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

        .timer-ring{
            width:80px;height:80px;margin:1.5rem auto;
            position:relative;
        }
        .timer-ring svg{width:100%;height:100%;transform:rotate(-90deg)}
        .timer-ring circle{fill:none;stroke-width:3;stroke-linecap:round}
        .timer-ring .track{stroke:rgba(255,255,255,.08)}
        .timer-ring .progress{stroke:#f59e0b;stroke-dasharray:220;stroke-dashoffset:220;animation:countdown var(--duration,30) linear forwards}
        @keyframes countdown{to{stroke-dashoffset:0}}
        .timer-text{
            position:absolute;inset:0;display:flex;align-items:center;justify-content:center;
            font-size:1.5rem;font-weight:800;color:#f59e0b;
        }

        .clock-hands{position:absolute;inset:0}
        .clock-hands::before,.clock-hands::after{
            content:'';position:absolute;background:#f59e0b;border-radius:2px;
        }
        .clock-hands::before{width:2px;height:22px;top:16px;left:calc(50% - 1px);transform-origin:bottom;animation:tick 1s steps(1) infinite}
        .clock-hands::after{width:2px;height:16px;top:22px;left:calc(50% - 1px);transform-origin:bottom;animation:tick 12s steps(1) infinite}
        @keyframes tick{0%{transform:rotate(0)}25%{transform:rotate(90deg)}50%{transform:rotate(180deg)}75%{transform:rotate(270deg)}}

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
        .actions{display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap}
        .btn{
            display:inline-flex;align-items:center;gap:.5rem;
            padding:.7rem 1.5rem;border-radius:.75rem;
            font-size:.85rem;font-weight:700;text-decoration:none;
            transition:all .2s ease;cursor:pointer;border:none;
        }
        .btn-primary{
            background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;
            box-shadow:0 6px 20px -4px rgba(245,158,11,.5);
        }
        .btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 30px -4px rgba(245,158,11,.6)}
        .btn-ghost{background:rgba(255,255,255,.06);color:#94a3b8;border:1.5px solid rgba(255,255,255,.1)}
        .btn-ghost:hover{background:rgba(255,255,255,.1);color:#f1f5f9;border-color:rgba(255,255,255,.2)}
        .brand{font-size:.7rem;color:#475569;margin-top:2rem;letter-spacing:.05em}

        .particles{position:fixed;inset:0;pointer-events:none;overflow:hidden}
        .particle{
            position:absolute;width:4px;height:4px;border-radius:50%;
            background:#f59e0b;opacity:0;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow glow-1"></div>
    <div class="glow glow-2"></div>

    <div class="particles" id="particles"></div>

    <div class="container">
        <div class="code">429</div>

        <div class="timer-ring" style="--duration:30s">
            <svg viewBox="0 0 80 80">
                <circle class="track" cx="40" cy="40" r="35"/>
                <circle class="progress" cx="40" cy="40" r="35"/>
            </svg>
            <div class="timer-text" id="countdown">30</div>
        </div>

        <h1>{{ __('Slow Down!') }}</h1>
        <p>{{ __('You\'ve sent too many requests in a short time. Please wait a moment before trying again.') }}</p>

        <div class="hint">
            <strong>{{ __('What can you do?') }}</strong>
            <ul>
                <li>{{ __('Wait for the timer to finish, then refresh the page') }}</li>
                <li>{{ __('Avoid refreshing repeatedly — this makes the wait longer') }}</li>
                <li>{{ __('If this persists, try again in a few minutes') }}</li>
            </ul>
        </div>

        <div class="actions">
            <button onclick="location.reload()" class="btn btn-primary" id="retryBtn" disabled>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
                {{ __('Retry Now') }}
            </button>
            <a href="/" class="btn btn-ghost">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="m3 12 2-2m0 0 7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11 2 2m-2-2v10a1 1 0 0 1-1 1h-3m-4 0a1 1 0 0 1-1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1"/></svg>
                {{ __('Home') }}
            </a>
        </div>

        <div class="brand">Kairo CORE</div>
    </div>

    <script>
        (function(){
            let seconds = 30;
            const el = document.getElementById('countdown');
            const btn = document.getElementById('retryBtn');
            const interval = setInterval(()=>{
                seconds--;
                if(seconds <= 0){
                    clearInterval(interval);
                    el.textContent = '0';
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                    return;
                }
                el.textContent = seconds;
            },1000);

            const container = document.getElementById('particles');
            for(let i=0;i<20;i++){
                const p = document.createElement('div');
                p.className='particle';
                p.style.left = Math.random()*100+'%';
                p.style.top = Math.random()*100+'%';
                p.style.animationDelay = Math.random()*5+'s';
                p.style.animation = `particleFloat ${4+Math.random()*4}s ease-in-out ${Math.random()*3}s infinite`;
                container.appendChild(p);
            }
        })();
    </script>
    <style>
        @keyframes particleFloat{
            0%{opacity:0;transform:translateY(0) scale(1)}
            25%{opacity:.4}75%{opacity:.4}
            100%{opacity:0;transform:translateY(-120px) scale(0)}
        }
    </style>
</body>
</html>
