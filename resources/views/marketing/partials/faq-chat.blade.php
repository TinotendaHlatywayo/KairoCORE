<style>
body.kairo-v2 #faq.kfaq-section { padding: 100px 0; }
body.kairo-v2 #faq.kfaq-section {
    --bg: #f8f9fd; --surface: #ffffff; --foot: #f7f8fc; --bubble: #f1f4fb; --ink: #1c2a44; --muted: #6b7691; --line: #e6eaf4;
    --accent: #ea5a41; --accent2: #f2804f; --soft: #fff1ed; --soft-line: rgba(234,90,65,.28); --ring: rgba(234,90,65,.16);
    --user: #dfe5f3; --tick: #3aa0ff; --shadow: rgba(28,42,68,.32); --ok: #2fbf71;
    color-scheme: light;
}
html.dark body.kairo-v2 #faq.kfaq-section {
    --bg: #0e1325; --surface: #161d35; --foot: #121931; --bubble: #202947; --ink: #eef1fb; --muted: #9ba5c6; --line: #27304d;
    --accent: #ff6a52; --accent2: #ff8d5c; --soft: #2e2338; --soft-line: rgba(255,106,82,.35); --ring: rgba(255,106,82,.22);
    --user: #2a3458; --shadow: rgba(0,0,0,.7);
    color-scheme: dark;
}
body.kairo-v2 #faq .kfaq-header { margin-bottom: 40px; }
body.kairo-v2 #faq .kfaq-eyebrow { display: flex; align-items: center; gap: 12px; color: var(--accent); font-weight: 700; font-size: 13px; margin-bottom: 12px; }
body.kairo-v2 #faq .kfaq-eyebrow::before { content: ""; width: 32px; height: 2px; background: var(--accent); }
body.kairo-v2 #faq .kfaq-header h2 { margin: 0 0 12px; font-size: clamp(28px, 3.6vw, 44px); line-height: 1.1; letter-spacing: -.03em; font-weight: 800; text-wrap: balance; color: var(--ink); }
body.kairo-v2 #faq .kfaq-header .lead { margin: 0; color: var(--muted); font-size: 16.5px; max-width: 60ch; }
body.kairo-v2 #faq .w { max-width: 1360px; }
body.kairo-v2 #faq .kfaq-grid { display: grid; grid-template-columns: minmax(0, 1fr) 280px; gap: 20px; align-items: start; }

body.kairo-v2 #faq .kfaq-chat { display: flex; flex-direction: column; height: min(640px, calc(100dvh - 48px)); min-height: 520px; padding: 0; background: var(--surface); border: 1px solid var(--line); border-radius: 26px; box-shadow: 0 44px 80px -48px var(--shadow); overflow: hidden; }
body.kairo-v2 #faq .kfaq-chat .kfaq-head { flex: none; display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--line); background: linear-gradient(180deg, var(--surface), var(--foot)); }
body.kairo-v2 #faq .kfaq-chat .kfaq-brand { display: flex; align-items: center; gap: 9px; padding: 6px 12px 6px 7px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); }
body.kairo-v2 #faq .kfaq-chat .kfaq-brand img { width: auto; height: 30px; object-fit: contain; }
body.kairo-v2 #faq .kfaq-chat .kfaq-brand .kfaq-logo-dark { display: none; }
html.dark body.kairo-v2 #faq .kfaq-chat .kfaq-brand .kfaq-logo-light { display: none; }
html.dark body.kairo-v2 #faq .kfaq-chat .kfaq-brand .kfaq-logo-dark { display: block; }
body.kairo-v2 #faq .kfaq-chat .kfaq-who { min-width: 0; }
body.kairo-v2 #faq .kfaq-chat .kfaq-who b { display: block; font-size: 14.5px; line-height: 1.2; }
body.kairo-v2 #faq .kfaq-chat .kfaq-who small { display: flex; align-items: center; gap: 7px; color: var(--muted); font-size: 12.5px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-live { position: relative; flex: none; width: 8px; height: 8px; border-radius: 50%; background: var(--ok); }
body.kairo-v2 #faq .kfaq-chat .kfaq-live::after { content: ""; position: absolute; inset: -4px; border-radius: 50%; border: 2px solid var(--ok); opacity: 0; animation: kfaqPulse 2s ease-out infinite; }
body.kairo-v2 #faq .kfaq-chat .kfaq-reset { margin-left: auto; display: flex; align-items: center; gap: 6px; background: var(--surface); border: 1px solid var(--line); border-radius: 999px; padding: 7px 13px; font-size: 13px; font-weight: 600; color: var(--muted); cursor: pointer; transition: color .15s, border-color .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-reset:hover { color: var(--ink); border-color: var(--ink); }
body.kairo-v2 #faq .kfaq-chat .kfaq-reset svg { width: 14px; height: 14px; }
@media (max-width: 767px) { body.kairo-v2 #faq .kfaq-chat .kfaq-reset span { display: none; } }

body.kairo-v2 #faq .kfaq-chat .kfaq-stage { position: relative; flex: 1 1 auto; min-height: 0; }
body.kairo-v2 #faq .kfaq-chat .kfaq-msgs { height: 100%; overflow-y: auto; padding: 18px 14px 20px 18px; display: flex; flex-direction: column; gap: 14px; background: var(--surface); scroll-behavior: auto; scrollbar-width: thin; scrollbar-color: var(--line) transparent; }
body.kairo-v2 #faq .kfaq-chat .kfaq-msgs::-webkit-scrollbar { width: 8px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-msgs::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-msgs::-webkit-scrollbar-track { background: transparent; }
body.kairo-v2 #faq .kfaq-chat .kfaq-day { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 11.5px; font-weight: 600; }
body.kairo-v2 #faq .kfaq-chat .kfaq-day::before, body.kairo-v2 #faq .kfaq-chat .kfaq-day::after { content: ""; flex: 1; height: 1px; background: var(--line); }
body.kairo-v2 #faq .kfaq-chat .kfaq-jump { position: absolute; right: 18px; bottom: 14px; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--line); background: var(--surface); display: none; place-items: center; cursor: pointer; box-shadow: 0 8px 20px -8px var(--shadow); }
body.kairo-v2 #faq .kfaq-chat .kfaq-jump.on { display: grid; }
body.kairo-v2 #faq .kfaq-chat .kfaq-jump svg { width: 18px; height: 18px; }

body.kairo-v2 #faq .kfaq-chat .kfaq-row { display: flex; align-items: flex-start; gap: 10px; animation: kfaqIn .3s ease both; }
body.kairo-v2 #faq .kfaq-chat .kfaq-row.me { justify-content: flex-end; }
body.kairo-v2 #faq .kfaq-chat .kfaq-pic { width: 34px; height: 34px; flex: 0 0 34px; margin-top: 2px; border-radius: 12px; overflow: hidden; display: grid; place-items: center; background: #fff; box-shadow: 0 0 0 1px var(--line); }
body.kairo-v2 #faq .kfaq-chat .kfaq-pic img { width: 100%; height: 100%; object-fit: contain; max-width: none; display: block; }
body.kairo-v2 #faq .kfaq-chat .kfaq-pic.u { background: var(--user); border-radius: 50%; box-shadow: none; }
body.kairo-v2 #faq .kfaq-chat .kfaq-pic.u svg { width: 20px; height: 20px; color: var(--muted); max-width: none; display: block; }
body.kairo-v2 #faq .kfaq-chat .kfaq-col { display: flex; flex-direction: column; min-width: 0; max-width: min(78%, 520px); }
body.kairo-v2 #faq .kfaq-chat .bot .kfaq-col { align-items: flex-start; }
body.kairo-v2 #faq .kfaq-chat .me .kfaq-col { align-items: flex-end; }
body.kairo-v2 #faq .kfaq-chat .kfaq-bub { width: fit-content; max-width: 100%; padding: 12px 16px; font-size: 15px; line-height: 1.5; border-radius: 18px; overflow-wrap: anywhere; }
body.kairo-v2 #faq .kfaq-chat .bot .kfaq-bub { background: var(--bubble); border-radius: 6px 18px 18px 18px; }
body.kairo-v2 #faq .kfaq-chat .me .kfaq-bub { background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; font-weight: 600; border-radius: 18px 6px 18px 18px; box-shadow: 0 10px 20px -12px var(--accent); }
body.kairo-v2 #faq .kfaq-chat .kfaq-meta { display: flex; align-items: center; gap: 4px; margin: 5px 4px 0; font-size: 11.5px; color: var(--muted); }
body.kairo-v2 #faq .kfaq-chat .kfaq-meta svg { width: 15px; height: 15px; color: var(--tick); }
body.kairo-v2 #faq .kfaq-chat .kfaq-caret { display: inline-block; width: 2px; height: 1em; margin-left: 2px; vertical-align: -.15em; background: var(--accent); animation: kfaqBlink .8s steps(1) infinite; }
body.kairo-v2 #faq .kfaq-chat .kfaq-dots { display: flex; gap: 5px; padding: 16px 18px; background: var(--bubble); border-radius: 6px 18px 18px 18px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-dots i { width: 7px; height: 7px; border-radius: 50%; background: var(--muted); animation: kfaqBob 1s infinite; }
body.kairo-v2 #faq .kfaq-chat .kfaq-dots i:nth-child(2) { animation-delay: .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-dots i:nth-child(3) { animation-delay: .3s; }

body.kairo-v2 #faq .kfaq-chat .kfaq-tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 0; padding: 0; list-style: none; }
body.kairo-v2 #faq .kfaq-chat .kfaq-tags li { padding: 0; border-radius: 999px; background: var(--soft); border: 1px solid var(--soft-line); font-size: 12.5px; font-weight: 600; }
body.kairo-v2 #faq .kfaq-chat .kfaq-tags li > span,
body.kairo-v2 #faq .kfaq-chat .kfaq-tags li > a { display: inline-flex; align-items: center; gap: 4px; padding: 5px 11px; border-radius: inherit; }
body.kairo-v2 #faq .kfaq-chat .kfaq-tags a { color: var(--accent); text-decoration: none; }
body.kairo-v2 #faq .kfaq-chat .kfaq-tags a::after { content: "↗"; font-size: 11px; line-height: 1; }
body.kairo-v2 #faq .kfaq-chat .kfaq-tags a:hover { text-decoration: underline; text-underline-offset: 2px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-inline-link { color: var(--accent); font-weight: 600; text-decoration: underline; text-underline-offset: 2px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-bub p { margin: 0; }
body.kairo-v2 #faq .kfaq-chat .kfaq-bub p + p { margin-top: 9px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-bub ol,
body.kairo-v2 #faq .kfaq-chat .kfaq-bub ul { display: grid; gap: 5px; margin: 9px 0 0; padding-left: 20px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-bub li { padding-left: 2px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-context { margin: 12px 0 0; padding: 9px 11px; border-left: 3px solid var(--accent); border-radius: 0 10px 10px 0; background: var(--soft); color: var(--muted); font-size: 13px; line-height: 1.55; }
body.kairo-v2 #faq .kfaq-chat .kfaq-links { display: flex; flex-wrap: wrap; gap: 7px; margin-top: 12px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-link { display: inline-flex; align-items: center; gap: 5px; padding: 8px 12px; border: 1px solid var(--soft-line); border-radius: 999px; background: var(--surface); color: var(--accent); font-size: 12.5px; font-weight: 700; text-decoration: none; transition: border-color .15s, background-color .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-link::after { content: "→"; font-size: 12px; line-height: 1; }
body.kairo-v2 #faq .kfaq-chat .kfaq-link:hover { border-color: var(--accent); background: var(--soft); }
body.kairo-v2 #faq .kfaq-chat .kfaq-src { display: flex; align-items: center; gap: 10px; margin-top: 12px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); color: inherit; text-decoration: none; font-size: 13px; transition: border-color .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-src:hover { border-color: var(--accent); }
body.kairo-v2 #faq .kfaq-chat .kfaq-src svg { width: 18px; height: 18px; flex: none; color: var(--accent); }
body.kairo-v2 #faq .kfaq-chat .kfaq-src small { display: block; color: var(--muted); font-size: 11.5px; line-height: 1.2; }
body.kairo-v2 #faq .kfaq-chat .kfaq-src b { font-weight: 600; line-height: 1.3; }
body.kairo-v2 #faq .kfaq-chat .kfaq-wa-pill { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 9px 15px; border-radius: 999px; background: #25d366; color: #06361a; font-size: 13px; font-weight: 700; text-decoration: none; }
body.kairo-v2 #faq .kfaq-chat .kfaq-opts { display: grid; gap: 8px; margin-top: 12px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-opt { text-align: left; padding: 10px 14px; border: 1px solid var(--line); border-radius: 14px; background: var(--surface); font-size: 13.5px; font-weight: 600; cursor: pointer; transition: border-color .15s, background-color .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-opt:hover { border-color: var(--accent); background: var(--soft); }
body.kairo-v2 #faq .kfaq-chat .kfaq-fb { display: flex; align-items: center; justify-content: flex-end; gap: 6px; margin-top: 10px; font-size: 12px; color: var(--muted); }
body.kairo-v2 #faq .kfaq-chat .kfaq-fb button { width: 30px; height: 30px; display: grid; place-items: center; border: 1px solid var(--line); border-radius: 50%; background: var(--surface); color: var(--muted); cursor: pointer; transition: color .15s, border-color .15s; }
body.kairo-v2 #faq .kfaq-chat .kfaq-fb button:hover { color: var(--accent); border-color: var(--accent); }
body.kairo-v2 #faq .kfaq-chat .kfaq-fb svg { width: 15px; height: 15px; }
body.kairo-v2 #faq .kfaq-chat .kfaq-follow { display: flex; flex-wrap: wrap; gap: 8px; margin: -4px 0 0 44px; animation: kfaqIn .3s ease both; }

body.kairo-v2 #faq .kfaq-quick { border-top: 1px solid var(--line); background: var(--foot); padding: 12px 18px 4px; }
body.kairo-v2 #faq .kfaq-quick .kfaq-label { margin: 0 0 8px; font-size: 12px; font-weight: 700; color: var(--muted); letter-spacing: .02em; }
body.kairo-v2 #faq .kfaq-quick .kfaq-rail { display: flex; flex-wrap: wrap; gap: 8px; overflow: visible; padding: 0 0 4px; }
body.kairo-v2 #faq .kfaq-quick .kfaq-chip { max-width: 100%; white-space: normal; text-align: left; background: var(--surface); border: 1px solid var(--line); border-radius: 999px; padding: 8px 10px; font-size: 13.5px; font-weight: 600; color: var(--ink); cursor: pointer; transition: border-color .15s, background-color .15s, transform .15s; }
body.kairo-v2 #faq .kfaq-quick .kfaq-chip:hover { border-color: var(--accent); background: var(--soft); transform: translateY(-1px); }

body.kairo-v2 #faq .kfaq-composer { padding: 10px 18px 0; }
body.kairo-v2 #faq .kfaq-field { display: flex; align-items: center; gap: 8px; padding: 6px 6px 6px 20px; background: var(--surface); border: 1px solid var(--line); border-radius: 999px; box-shadow: 0 1px 2px rgba(28,42,68,.06); transition: border-color .15s, box-shadow .15s; }
body.kairo-v2 #faq .kfaq-field:focus-within { border-color: var(--accent); box-shadow: 0 0 0 4px var(--ring); }
body.kairo-v2 #faq .kfaq-input { flex: 1 1 auto; min-width: 0; padding: 10px 0; border: 0; outline: 0; background: transparent; font-size: 15px; color: var(--ink); }
body.kairo-v2 #faq .kfaq-input::placeholder { color: var(--muted); }
body.kairo-v2 #faq .kfaq-send { width: 42px; height: 42px; flex: none; border: 0; border-radius: 50%; background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #fff; display: grid; place-items: center; cursor: pointer; transition: transform .15s, opacity .15s; }
body.kairo-v2 #faq .kfaq-send:hover:not(:disabled) { transform: scale(1.07); }
body.kairo-v2 #faq .kfaq-send:disabled { opacity: .4; cursor: default; }
body.kairo-v2 #faq .kfaq-send svg { width: 20px; height: 20px; }
body.kairo-v2 #faq .kfaq-note { display: flex; justify-content: space-between; gap: 12px; padding: 8px 24px 14px; font-size: 11.5px; color: var(--muted); }

body.kairo-v2 #faq .kfaq-aside { display: flex; flex-direction: column; gap: 12px; position: sticky; top: calc(69px + 24px); }
body.kairo-v2 #faq .kfaq-card { display: flex; align-items: center; gap: 14px; padding: 16px; min-height: 0; height: auto; border: 1px solid var(--line); border-radius: 20px; background: var(--surface); text-decoration: none; color: inherit; transition: border-color .2s, transform .2s; }
body.kairo-v2 #faq .kfaq-card:hover { border-color: var(--accent); transform: translateY(-2px); }
body.kairo-v2 #faq .kfaq-card--whatsapp:hover { border-color: #25d366; }
body.kairo-v2 #faq .kfaq-card:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
body.kairo-v2 #faq .kfaq-card .kfaq-icon { width: 44px; height: 44px; flex: none; border-radius: 14px; display: grid; place-items: center; color: #fff; }
body.kairo-v2 #faq .kfaq-card .kfaq-icon svg { width: 22px; height: 22px; }
body.kairo-v2 #faq .kfaq-card .kfaq-card-title { display: block; font-size: 15px; font-weight: 700; line-height: 1.3; }
body.kairo-v2 #faq .kfaq-card .kfaq-card-sub { display: block; font-size: 13.5px; color: var(--muted); line-height: 1.35; margin-top: 2px; }

@media (max-width: 1355px) {
    body.kairo-v2 #faq .kfaq-grid { grid-template-columns: 1fr; }
    body.kairo-v2 #faq .kfaq-aside { position: static; display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
    body.kairo-v2 #faq .kfaq-quick .kfaq-chip { font-size: 13px; }
}
@media (max-width: 767px) {
    body.kairo-v2 #faq .kfaq-grid { grid-template-columns: 1fr; }
    body.kairo-v2 #faq .kfaq-aside { display: flex; flex-direction: column; gap: 12px; }
    body.kairo-v2 #faq .kfaq-chat { height: auto; min-height: 0; }
    body.kairo-v2 #faq .kfaq-stage { flex: none; height: min(380px, 45dvh); }
    body.kairo-v2 #faq .kfaq-header h2 { font-size: clamp(28px, 6vw, 36px); }
}
@media (prefers-reduced-motion: reduce) {
    body.kairo-v2 #faq .kfaq * { animation: none !important; transition: none !important; }
    body.kairo-v2 #faq .kfaq-chat .kfaq-msgs { scroll-behavior: auto; }
}
@keyframes kfaqIn { from { opacity: 0; transform: translateY(8px); } }
@keyframes kfaqBob { 0%, 60%, 100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-4px); opacity: 1; } }
@keyframes kfaqBlink { 50% { opacity: 0; } }
@keyframes kfaqPulse { 0% { transform: scale(.6); opacity: .7; } 100% { transform: scale(1.4); opacity: 0; } }
</style>

<section id="faq" class="kfaq-section" aria-labelledby="faq-title">
    <div class="w">
        <header class="kfaq-header">
            <div class="kfaq-eyebrow">{{ marketing_content('home_faq_badge') ?: __('FAQ') }}</div>
            <h2 id="faq-title">{{ marketing_content('home_faq_title') ?: __('Questions school leaders ask us') }}</h2>
            <p class="lead">{{ marketing_content('home_faq_subtitle') ?: __('Tap a question or type your own. Can’t find your answer? Talk to a person.') }}</p>
        </header>

        <div class="kfaq-grid">
            <section class="kfaq-chat" aria-label="{{ __('Kairo assistant') }}">
                <div class="kfaq-head">
                    <div class="kfaq-brand" aria-label="{{ platform_name() }}">
                        <img class="kfaq-logo-light" src="{{ asset('img/kairo-core-logo.svg') }}" alt="{{ platform_name() }}" width="90" height="30">
                        <img class="kfaq-logo-dark" src="{{ asset('images/kairocore-logo-white.webp') }}" alt="{{ platform_name() }}" width="90" height="30">
                    </div>
                    <div class="kfaq-who">
                        <b>{{ __('Kairo assistant') }}</b>
                        <small><span class="kfaq-live" aria-hidden="true"></span>{{ __('Online · answers instantly') }}</small>
                    </div>
                    <button class="kfaq-reset" id="kfaq-reset" type="button">
                        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('Start over') }}</span>
                    </button>
                </div>

                <div class="kfaq-stage">
                    <div class="kfaq-msgs" id="kfaq-msgs" aria-live="polite"></div>
                    <button class="kfaq-jump" id="kfaq-jump" type="button" aria-label="{{ __('Jump to latest message') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7 7 7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <div class="foot">
                    <div class="kfaq-quick" id="kfaq-quick"></div>
                    <form class="kfaq-composer" id="kfaq-form" autocomplete="off">
                        <div class="kfaq-field">
                            <input id="kfaq-input" class="kfaq-input" type="text" maxlength="200" placeholder="{{ __('Type your question…') }}" aria-label="{{ __('Type your question') }}">
                            <button class="kfaq-send" id="kfaq-send" type="submit" aria-label="{{ __('Send question') }}" disabled>
                                <svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                        <div class="kfaq-note">
                            <span>{{ __('Automated assistant. Ask about any module or workflow.') }}</span>
                            <span id="kfaq-count"></span>
                        </div>
                    </form>
                </div>
            </section>

            <aside class="kfaq-aside" aria-label="{{ __('Contact options') }}">
                <a class="kfaq-card kfaq-card--whatsapp" href="https://wa.me/263786366855" target="_blank" rel="noopener">
                    <span class="kfaq-icon" style="background:#25d366;color:#06361a;">
                        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>
                        <span class="kfaq-card-title">{{ __('Chat on WhatsApp') }}</span>
                        <span class="kfaq-card-sub">+263 78 636 6855</span>
                    </span>
                </a>
                <a class="kfaq-card" href="mailto:sales@kairocore.co.zw">
                    <span class="kfaq-icon" style="background:#3b82f6;color:#fff;">
                        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 4h16v16H4zM4 4l8 8 8-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>
                        <span class="kfaq-card-title">{{ __('Email us') }}</span>
                        <span class="kfaq-card-sub">sales@kairocore.co.zw</span>
                    </span>
                </a>
                <a class="kfaq-card" href="{{ route('help.index') }}">
                    <span class="kfaq-icon" style="background:var(--accent);color:#fff;">
                        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span>
                        <span class="kfaq-card-title">{{ __('Help Centre') }}</span>
                        <span class="kfaq-card-sub">{{ __('Step-by-step guides for every module') }}</span>
                    </span>
                </a>
            </aside>
        </div>
    </div>
</section>

@php
    $kfaqSuggestions = marketing_merged_list('faq_suggestions');
    if (empty($kfaqSuggestions)) {
        $kfaqSuggestions = [
            ['question' => __('Is Kairo CORE built for my type of school?')],
            ['question' => __('What does it cover?')],
            ['question' => __('Is our data safe?')],
            ['question' => __('Will my team be trained?')],
            ['question' => __('Can we access it from any device?')],
            ['question' => __('How long does setup take?')],
            ['question' => __('Can parents see fees, attendance and results?')],
            ['question' => __('Which payments can we record?')],
            ['question' => __('How do I book a demo?')],
        ];
    }
    $kfaqSuggestionQuestions = array_values(array_filter(array_map(function ($item) {
        return $item['question'] ?? $item['q'] ?? null;
    }, $kfaqSuggestions)));
    $kfaqUi = [
        'locale' => app()->getLocale(),
        'you' => __('You'),
        'assistant' => __('Kairo assistant'),
        'suggested' => __('Suggested questions'),
        'allSuggested' => __('That’s every suggested question. Type your own below.'),
        'didYouMean' => __('Did you mean…?'),
        'greeting' => __('Hi, I’m the Kairo assistant. Tap a question below or type your own.'),
        'noAnswerYet' => __('I don’t have that one yet. Message the team on WhatsApp and a person will help.'),
        'notExact' => __('I do not have that exact answer yet, but the team can help with school-specific questions. Message us on WhatsApp and we will point you to the right module or guide.'),
        'pricingFallback' => __('I do not have exact pricing details here, but Kairo CORE plans are tailored to your school size, modules and support needs. Message the team on WhatsApp or use the contact form and we will recommend the right fit.'),
        'thanks' => __('Thanks for the feedback'),
        'helpful' => __('Helpful'),
        'notHelpful' => __('Not helpful'),
        'fromHelp' => __('From the Help Centre'),
        'messageWhatsApp' => __('Message us on WhatsApp'),
        'emailUs' => __('Email us'),
        'learnMore' => __('Learn more'),
        'today' => __('Today'),
    ];
@endphp

<script type="module">
import { matchQuestion, logUnmatched, logFeedback, normalize } from '/kfaq-engine.js';
console.log('FAQ_MODULE_START');

const msgs = document.getElementById('kfaq-msgs');
const quick = document.getElementById('kfaq-quick');
const form = document.getElementById('kfaq-form');
const input = document.getElementById('kfaq-input');
const send = document.getElementById('kfaq-send');
const reset = document.getElementById('kfaq-reset');
const jump = document.getElementById('kfaq-jump');
const count = document.getElementById('kfaq-count');
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const suggestions = {!! json_encode($kfaqSuggestionQuestions) !!};
const ui = {!! json_encode($kfaqUi) !!};
const currentLocale = ui.locale || document.documentElement.lang || 'en';
const tagLinks = {
    Security: { url: @json(route('marketing.security')), label: @js(__('Read about security and data protection')) },
    Privacy: { url: @json(route('marketing.security')), label: @js(__('Read about security and data protection')) },
    Demo: { url: @json(rtrim(route('marketing.home'), '/').'/#contact'), label: @js(__('Book a demo')) },
    Contact: { url: @json(route('marketing.contact')), label: @js(__('Contact us')) },
    Pricing: { url: @json(route('marketing.contact').'#contact-form'), label: @js(__('Ask for pricing')) },
    Support: { url: @json(route('help.index')), label: @js(__('Visit the Help Centre')) },
};
const featuresUrl = @json(route('marketing.features'));
const helpUrl = @json(route('help.index'));
const categoryLinks = {
    general: { label: @js(__('Explore all features')), url: featuresUrl },
    admissions: { label: @js(__('Explore admissions')), url: featuresUrl + '#admissions' },
    academics: { label: @js(__('Explore academics')), url: featuresUrl + '#academics' },
    students: { label: @js(__('Explore student management')), url: featuresUrl + '#students' },
    exams: { label: @js(__('Explore exams and results')), url: featuresUrl + '#exams' },
    finance: { label: @js(__('Explore finance and fees')), url: featuresUrl + '#finance' },
    hr: { label: @js(__('Explore HR and payroll')), url: featuresUrl + '#hr' },
    communication: { label: @js(__('Explore communications')), url: featuresUrl + '#communications' },
    library: { label: @js(__('Explore the library module')), url: featuresUrl + '#library' },
    health: { label: @js(__('Explore health records')), url: featuresUrl + '#health' },
    inventory: { label: @js(__('Explore inventory and procurement')), url: featuresUrl + '#inventory' },
    lms: { label: @js(__('Explore the LMS')), url: featuresUrl + '#lms' },
    boarding: { label: @js(__('Explore boarding management')), url: featuresUrl + '#boarding' },
    reports: { label: @js(__('Explore reports and analytics')), url: featuresUrl + '#reports' },
    website: { label: @js(__('Explore the website CMS')), url: featuresUrl + '#cms' },
    administration: { label: @js(__('Explore the platform')), url: featuresUrl },
    'getting-started': { label: @js(__('Visit the Help Centre')), url: helpUrl },
    troubleshooting: { label: @js(__('Visit the Help Centre')), url: helpUrl },
};
const categoryNotes = {
    general: @js(__('One connected platform means admissions, fees, attendance, academics and communication all use the same student record.')),
    admissions: @js(__('Applications, placements, student records and first invoices stay connected, so there is no re-typing between admissions and finance.')),
    academics: @js(__('Academic setup feeds timetables, assessments and report cards, so one change flows through the whole term.')),
    students: @js(__('A single student profile connects guardians, attendance, fees, health notes and results.')),
    exams: @js(__('Marks flow from assessment to report card, so teachers capture once and reports update automatically.')),
    finance: @js(__('Fees, invoices and payments share the same student record as admissions and academics.')),
    hr: @js(__('Staff records, leave and payroll sit together, giving administrators one view of the team.')),
    communication: @js(__('Messages go to the right guardians or staff from the same system that holds class and fee data.')),
    library: @js(__('Library records connect to the student profile, so issuing, returns and fines follow the learner.')),
    health: @js(__('Health notes sit on the student record and can be flagged for trips or activities.')),
    inventory: @js(__('Requisitions, purchasing and assets stay traceable from request to delivery.')),
    lms: @js(__('Lesson materials, homework and marks sit alongside the student\'s academic record.')),
    boarding: @js(__('Boarding allocations and fees stay connected to the same student record used by admissions and finance.')),
    reports: @js(__('Because data is captured once across modules, reports reflect the latest enrolment, fee and academic position.')),
    website: @js(__('Your public website draws on the same content system your admin team already uses.')),
    administration: @js(__('Role-based access keeps each team focused on the records they need, without exposing the rest of the school.')),
    'getting-started': @js(__('Setup is guided, and the Help Centre has step-by-step guides for every module.')),
    troubleshooting: @js(__('The Help Centre has fixes for common issues, and the team is one message away.')),
};

const asked = new Set();
let kb = null;
let previousTopic = null;
let busy = false;

const clock = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const scrollToBottom = (instant = false) => {
    msgs.style.scrollBehavior = (instant || reduce) ? 'auto' : 'smooth';
    requestAnimationFrame(() => requestAnimationFrame(() => { msgs.scrollTop = msgs.scrollHeight; }));
};
const down = () => scrollToBottom(false);
msgs.addEventListener('scroll', () => jump.classList.toggle('on', msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight > 90));
jump.addEventListener('click', () => scrollToBottom(false));

function clearFollowups() { msgs.querySelectorAll('.kfaq-follow').forEach(node => node.remove()); }

function row(kind) {
    clearFollowups();
    const rowEl = document.createElement('div');
    rowEl.className = 'kfaq-row ' + kind;
    const avatar = document.createElement('span');
    avatar.className = 'kfaq-pic ' + (kind === 'me' ? 'u' : '');
    if (kind === 'me') {
        avatar.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8.5" r="4" fill="currentColor"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7z" fill="currentColor"/></svg>';
    } else {
        const img = document.createElement('img');
        img.src = @json(platform_favicon_url());
        img.alt = '';
        img.addEventListener('load', () => scrollToBottom(true), { once: true });
        avatar.appendChild(img);
    }
    const col = document.createElement('div');
    col.className = 'kfaq-col';
    const bubble = document.createElement('div');
    bubble.className = 'kfaq-bub';
    const meta = document.createElement('div');
    meta.className = 'kfaq-meta';
    meta.innerHTML = (kind === 'me' ? ui.you : ui.assistant) + ' · ' + clock() + (kind === 'me' ? ' <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 13l4 4 9-10M10 15l2 2 9-10" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>' : '');
    col.append(bubble, meta);
    rowEl.append(avatar, col);
    msgs.appendChild(rowEl);
    scrollToBottom(false);
    return bubble;
}

function typing() {
    const bubble = row('bot');
    bubble.classList.add('kfaq-dots');
    bubble.innerHTML = '<i></i><i></i><i></i>';
    return bubble.closest('.kfaq-row');
}

function streamText(element, text, done) {
    if (reduce) {
        element.textContent = text;
        return done();
    }
    const words = text.split(/\s+/).filter(Boolean);
    const delay = words.length > 90 ? 10 : 30;
    const caret = document.createElement('span');
    caret.className = 'kfaq-caret';
    let i = 0;
    const timer = setInterval(() => {
        element.textContent = words.slice(0, ++i).join(' ');
        element.appendChild(caret);
        scrollToBottom(false);
        if (i >= words.length) {
            clearInterval(timer);
            caret.remove();
            done();
        }
    }, delay);
}

function renderTagList(parent, tags) {
    if (!tags || !tags.length) return;
    const list = document.createElement('ul');
    list.className = 'kfaq-tags';
    tags.forEach(tag => {
        const item = document.createElement('li');
        const target = tagLinks[tag];
        if (target) {
            const link = document.createElement('a');
            link.href = target.url;
            link.textContent = tag;
            link.setAttribute('aria-label', target.label);
            item.appendChild(link);
        } else {
            const text = document.createElement('span');
            text.textContent = tag;
            item.appendChild(text);
        }
        list.appendChild(item);
    });
    parent.appendChild(list);
}

function linkifyText(text) {
    const fragment = document.createDocumentFragment();
    const pattern = /(https?:\/\/[^\s<>"']+|[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,})/gi;
    if (!/@|https?:\/\//i.test(text)) {
        fragment.appendChild(document.createTextNode(text));
        return fragment;
    }
    let last = 0;
    text.replace(pattern, (match, value, offset) => {
        if (offset > last) fragment.appendChild(document.createTextNode(text.slice(last, offset)));
        const link = document.createElement('a');
        link.className = 'kfaq-inline-link';
        link.href = value.includes('@') ? 'mailto:' + value : value;
        link.textContent = value;
        if (!value.includes('@')) {
            link.target = '_blank';
            link.rel = 'noopener';
        }
        fragment.appendChild(link);
        last = offset + value.length;
        return match;
    });
    if (last < text.length) fragment.appendChild(document.createTextNode(text.slice(last)));
    return fragment;
}

function renderRichAnswer(bubble, text) {
    bubble.textContent = '';
    const blocks = String(text || '').split(/\n{2,}/);
    blocks.forEach(block => {
        const lines = block.split('\n').map(line => line.trim()).filter(Boolean);
        if (!lines.length) return;

        const numbered = lines.length > 1 && lines.every(line => /^\d+[.)]\s+/.test(line));
        const bullets = lines.length > 1 && lines.every(line => /^[-•]\s+/.test(line));

        if (numbered || bullets) {
            const list = document.createElement(numbered ? 'ol' : 'ul');
            lines.forEach(line => {
                const item = document.createElement('li');
                item.appendChild(linkifyText(line.replace(numbered ? /^\d+[.)]\s+/ : /^[-•]\s+/, '')));
                list.appendChild(item);
            });
            bubble.appendChild(list);
            return;
        }

        const p = document.createElement('p');
        p.appendChild(linkifyText(lines.join(' ')));
        bubble.appendChild(p);
    });
}

function renderLinks(parent, links) {
    if (!Array.isArray(links) || !links.length) return;
    const wrap = document.createElement('div');
    wrap.className = 'kfaq-links';
    links.slice(0, 4).forEach(item => {
        if (!item || typeof item.url !== 'string' || !/^(https?:\/\/|\/)/.test(item.url)) return;
        const link = document.createElement('a');
        link.className = 'kfaq-link';
        link.href = item.url;
        link.textContent = item.label || 'Learn more';
        if (/^https?:\/\//.test(item.url)) {
            link.target = '_blank';
            link.rel = 'noopener';
        }
        wrap.appendChild(link);
    });
    if (wrap.children.length) parent.appendChild(wrap);
}

function relatedFor(entry) {
    const curated = (entry.related || []).map(id => kb.find(item => item.id === id)).filter(Boolean);
    if (curated.length) return curated.slice(0, 3);

    const own = new Set([...(entry.keywords || []), ...(entry.tags || [])].map(normalize).filter(Boolean));
    return kb
        .filter(item => item.id !== entry.id && item.category === entry.category && !item.needsOwnerAnswer)
        .map(item => {
            const shared = [...new Set([...(item.keywords || []), ...(item.tags || [])])]
                .filter(word => own.has(normalize(word))).length;
            return { item, shared };
        })
        .filter(candidate => candidate.shared > 0)
        .sort((a, b) => b.shared - a.shared)
        .slice(0, 3)
        .map(candidate => candidate.item);
}

function renderAnswer(bubble, entry, secondIntent) {
    if (entry.needsOwnerAnswer) {
        const link = document.createElement('a');
        link.className = 'kfaq-wa-pill';
        link.href = 'https://wa.me/263786366855';
        link.target = '_blank';
        link.rel = 'noopener';
        link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> ' + ui.messageWhatsApp;
        bubble.appendChild(document.createElement('br'));
        bubble.appendChild(link);
        return;
    }

    renderRichAnswer(bubble, entry.answer || '');

    const context = categoryNotes[entry.category];
    if (context && entry.id !== 'what-cover') {
        const note = document.createElement('p');
        note.className = 'kfaq-context';
        note.textContent = context;
        bubble.appendChild(note);
    }

    if (Array.isArray(entry.links) && entry.links.length) {
        renderLinks(bubble, entry.links);
    } else if (categoryLinks[entry.category]) {
        renderLinks(bubble, [categoryLinks[entry.category]]);
    }

    renderTagList(bubble, entry.tags);

    if (entry.source && entry.source.url && String(entry.source.url).startsWith('/docs')) {
        const link = document.createElement('a');
        link.className = 'kfaq-src';
        link.href = entry.source.url;
        link.innerHTML = '<svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><span><small></small><b></b></span>';
        link.querySelector('small').textContent = 'From the Help Centre · ' + (entry.source.category || entry.category || '');
        link.querySelector('b').textContent = entry.source.title;
        bubble.appendChild(link);
    }

    const feedback = document.createElement('div');
    feedback.className = 'kfaq-fb';
    const up = document.createElement('button');
    up.type = 'button';
    up.setAttribute('aria-label', 'Helpful');
    up.innerHTML = '<svg viewBox="0 0 24 24"><path d="M7 11v9H4v-9zM7 11l4-8c1.7 0 2.5 1.2 2.2 2.8L12.8 9H19a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.8 20H7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>';
    const downBtn = document.createElement('button');
    downBtn.type = 'button';
    downBtn.setAttribute('aria-label', 'Not helpful');
    downBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M17 13V4h3v9zM17 13l-4 8c-1.7 0-2.5-1.2-2.2-2.8L11.2 15H5a2 2 0 0 1-2-2.3l1.2-7A2 2 0 0 1 6.2 4H17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>';
    const thanks = () => { feedback.textContent = 'Thanks for the feedback'; };
    up.addEventListener('click', () => { logFeedback(entry.question, true); thanks(); });
    downBtn.addEventListener('click', () => { logFeedback(entry.question, false); logUnmatched(input.value || entry.question); thanks(); });
    feedback.append(up, downBtn);
    bubble.appendChild(feedback);

    const related = relatedFor(entry);
    if (related.length) {
        const wrap = document.createElement('div');
        wrap.className = 'kfaq-follow';
        related.forEach(item => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'kfaq-chip';
            chip.textContent = item.question;
            chip.addEventListener('click', () => ask(item.question));
            wrap.appendChild(chip);
        });
        msgs.appendChild(wrap);
    }

    if (secondIntent && !related.some(item => item.id === secondIntent.id)) {
        const wrap = document.createElement('div');
        wrap.className = 'kfaq-follow';
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'kfaq-chip';
        chip.textContent = secondIntent.question;
        chip.addEventListener('click', () => ask(secondIntent.question));
        wrap.appendChild(chip);
        msgs.appendChild(wrap);
    }
}

function renderChips() {
    quick.innerHTML = '';
    const label = document.createElement('p');
    label.className = 'kfaq-label';
    label.textContent = suggestions.some(q => !asked.has(q)) ? 'Suggested questions' : 'That’s every suggested question. Type your own below.';
    quick.appendChild(label);
    const remaining = suggestions.filter(q => !asked.has(q));
    if (!remaining.length) return;

    const rail = document.createElement('div');
    rail.className = 'kfaq-rail';
    remaining.forEach(question => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'kfaq-chip';
        chip.textContent = question;
        chip.addEventListener('click', () => ask(question));
        rail.appendChild(chip);
    });
    quick.appendChild(rail);
}

function ask(text) {
    const value = (text || input.value || '').trim().slice(0, 200);
    if (!value || busy || !kb) return;
    busy = true;
    input.value = '';
    sync();
    asked.add(value);
    renderChips();
    row('me').textContent = value;

    const result = matchQuestion(value, kb, previousTopic, currentLocale);
    const typingRow = typing();

    setTimeout(() => {
        typingRow.remove();
        if (result.smallTalk) {
            const bubble = row('bot');
            bubble.textContent = result.smallTalk;
            busy = false;
            scrollToBottom(false);
            return;
        }
        if (result.type === 'medium') {
            const bubble = row('bot');
            bubble.textContent = 'Did you mean…?';
            const opts = document.createElement('div');
            opts.className = 'kfaq-opts';
            (result.candidates || []).slice(0, 3).forEach(candidate => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'kfaq-opt';
                button.textContent = candidate.question;
                button.addEventListener('click', () => ask(candidate.question));
                opts.appendChild(button);
            });
            bubble.appendChild(opts);
            busy = false;
            scrollToBottom(false);
            return;
        }
        if (result.type === 'fallback') {
            if (!result.entry || result.entry.needsOwnerAnswer) logUnmatched(value);
            const isPricing = /price|pricing|cost|quote/i.test(value) || (result.entry && result.entry.id === 'pricing');
            const bubble = row('bot');
            const p = document.createElement('p');
            p.textContent = isPricing
                ? 'I do not have exact pricing details here, but Kairo CORE plans are tailored to your school size, modules and support needs. Message the team on WhatsApp or use the contact form and we will recommend the right fit.'
                : 'I do not have that exact answer yet, but the team can help with school-specific questions. Message us on WhatsApp and we will point you to the right module or guide.';
            bubble.appendChild(p);
            const candidates = (result.candidates || [])
                .filter(candidate => !result.entry || candidate.id !== result.entry.id)
                .slice(0, 3);
            if (candidates.length) {
                const opts = document.createElement('div');
                opts.className = 'kfaq-opts';
                candidates.forEach(candidate => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'kfaq-opt';
                    button.textContent = candidate.question;
                    button.addEventListener('click', () => ask(candidate.question));
                    opts.appendChild(button);
                });
                bubble.appendChild(opts);
            }
            if (result.entry) renderTagList(bubble, result.entry.tags);
            const waLink = document.createElement('a');
            waLink.className = 'kfaq-wa-pill';
            waLink.href = 'https://wa.me/263786366855';
            waLink.target = '_blank';
            waLink.rel = 'noopener';
            waLink.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Message us on WhatsApp';
            bubble.appendChild(waLink);
            if (isPricing) {
                const emailLink = document.createElement('a');
                emailLink.className = 'kfaq-wa-pill';
                emailLink.href = '/contact#contact-form';
                emailLink.style.background = '#3b82f6';
                emailLink.style.color = '#fff';
                emailLink.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 4h16v16H4zM4 4l8 8 8-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Email us';
                bubble.appendChild(emailLink);
            }
            busy = false;
            scrollToBottom(false);
            return;
        }

        previousTopic = result.entry.category;
        const bubble = row('bot');
        streamText(bubble, result.entry.needsOwnerAnswer ? 'I don’t have that one yet. Message the team on WhatsApp and a person will help.' : result.entry.answer, () => {
            if (result.entry.needsOwnerAnswer) {
                const link = document.createElement('a');
                link.className = 'kfaq-wa-pill';
                link.href = 'https://wa.me/263786366855';
                link.target = '_blank';
                link.rel = 'noopener';
                link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> ' + ui.messageWhatsApp;
                bubble.appendChild(document.createElement('br'));
                bubble.appendChild(link);
            } else {
                renderAnswer(bubble, result.entry, result.secondIntent);
            }
            busy = false;
            scrollToBottom(false);
        });
    }, reduce ? 0 : 750);
}

function sync() {
    send.disabled = !input.value.trim();
}
input.addEventListener('input', sync);

function start() {
    msgs.innerHTML = '<div class="kfaq-day">Today</div>';
    asked.clear();
    previousTopic = null;
    const greeting = row('bot');
    greeting.textContent = 'Hi, I’m the Kairo assistant. Tap a question below or type your own.';
    const first = kb && kb.length ? (kb.find(item => item.id === 'fit-school-type') || kb[0]) : null;
    if (first) {
        const questionRow = row('me');
        questionRow.textContent = first.question;
        const answerRow = row('bot');
        streamText(answerRow, first.needsOwnerAnswer ? 'I don’t have that one yet. Message the team on WhatsApp and a person will help.' : first.answer, () => {
            if (!first.needsOwnerAnswer) renderAnswer(answerRow, first, null);
        });
        previousTopic = first.category;
        asked.add(first.question);
    }
    scrollToBottom(true);
    renderChips();
}

reset.addEventListener('click', () => {
    if (kb) start();
});
input.addEventListener('keydown', event => {
    if (event.key === 'Enter') {
        event.preventDefault();
        ask();
    }
});
form.addEventListener('submit', event => {
    event.preventDefault();
    ask();
});

async function loadKb() {
    try {
        let response = await fetch('/faq-knowledge.' + currentLocale + '.json', { credentials: 'same-origin' });
        if (!response.ok) response = await fetch('/faq-knowledge.json', { credentials: 'same-origin' });
        kb = await response.json();
        start();
    } catch (error) {
        const bubble = row('bot');
        bubble.textContent = ui.noAnswerYet;
        const link = document.createElement('a');
        link.className = 'kfaq-wa-pill';
        link.href = 'https://wa.me/263786366855';
        link.target = '_blank';
        link.rel = 'noopener';
        link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> ' + ui.messageWhatsApp;
        bubble.appendChild(document.createElement('br'));
        bubble.appendChild(link);
    }
}

const observer = new IntersectionObserver(entries => {
    if (entries.some(entry => entry.isIntersecting) && !kb) loadKb();
}, { rootMargin: '200px' });
observer.observe(document.getElementById('faq'));

renderChips();
</script>
