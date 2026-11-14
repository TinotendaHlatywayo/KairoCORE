<style>
body.kairo-v2 #faq.kfaq-section { padding: 0; }
body.kairo-v2 #faq .kfaq {
    --bg: #f8f9fd; --surface: #ffffff; --chat: #f3f5fb; --dot: rgba(27,42,68,.07); --ink: #1c2a44; --muted: #6b7691; --line: #e6eaf4;
    --accent: #ea5a41; --accent2: #f2804f; --soft: #fff1ed; --soft-line: rgba(234,90,65,.28); --shadow: rgba(28,42,68,.32); --user: #dfe5f3;
    color-scheme: light;
    display: grid;
    grid-template-columns: 5fr 7fr;
    gap: 64px;
    align-items: center;
    padding: 48px 0;
}
body.kairo-v2 #faq .kfaq h2 {
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.08;
    letter-spacing: -.03em;
    font-weight: 800;
    margin: 0 0 18px;
}
body.kairo-v2 #faq .kfaq .lead { color: var(--text-2); font-size: 17px; max-width: 34ch; margin: 0 0 28px; }
body.kairo-v2 #faq .kfaq .talk {
    display: flex; align-items: center; gap: 14px; padding: 14px 16px; max-width: 340px;
    background: var(--surface, #fff); border: 1px solid var(--border); border-radius: 20px;
    color: inherit; text-decoration: none; transition: border-color .2s, transform .2s;
}
body.kairo-v2 #faq .kfaq .talk:hover { border-color: #25d366; transform: translateY(-2px); }
body.kairo-v2 #faq .kfaq .talk .ic { width: 44px; height: 44px; flex: none; border-radius: 14px; background: #25d366; color: #06361a; display: grid; place-items: center; }
body.kairo-v2 #faq .kfaq .talk b { display: block; font-size: 15px; line-height: 1.25; }
body.kairo-v2 #faq .kfaq .talk span { font-size: 13.5px; color: var(--text-muted); }
body.kairo-v2 #faq .kfaq :is(.talk, .chip, .reset, .send, .wa-pill, .src, .jump, .opt, .fb button):focus-visible {
    outline: 2px solid var(--coral-strong); outline-offset: 3px;
}

body.kairo-v2 #faq .kfaq .chat {
    padding: 0;
    display: flex; flex-direction: column;
    height: min(680px, calc(100dvh - 48px));
    min-height: 560px;
    background: var(--surface, #fff); border: 1px solid var(--border); border-radius: 28px;
    box-shadow: 0 44px 80px -48px rgba(28,42,68,.32); overflow: hidden;
}
body.kairo-v2 #faq .kfaq .head { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-bottom: 1px solid var(--border); }
body.kairo-v2 #faq .kfaq .brand { display: flex; align-items: center; gap: 9px; padding: 6px 12px 6px 7px; border: 1px solid var(--border); border-radius: 14px; background: var(--surface, #fff); }
body.kairo-v2 #faq .kfaq .brand img { width: auto; height: 30px; object-fit: contain; }
body.kairo-v2 #faq .kfaq .brand .logo-dark { display: none; }
html.dark body.kairo-v2 #faq .kfaq .brand .logo-light { display: none; }
html.dark body.kairo-v2 #faq .kfaq .brand .logo-dark { display: block; }
body.kairo-v2 #faq .kfaq .brand b { font-size: 17px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
body.kairo-v2 #faq .kfaq .brand b i { font-style: normal; display: block; font-size: 9.5px; letter-spacing: .2em; color: var(--coral-strong); margin-top: 2px; }
body.kairo-v2 #faq .kfaq .who { min-width: 0; }
body.kairo-v2 #faq .kfaq .who b { display: block; font-size: 14.5px; line-height: 1.2; }
body.kairo-v2 #faq .kfaq .who small { display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 12.5px; }
body.kairo-v2 #faq .kfaq .who small::before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #2fbf71; }
body.kairo-v2 #faq .kfaq .reset { margin-left: auto; display: flex; align-items: center; gap: 6px; background: none; border: 1px solid var(--border); border-radius: 999px; padding: 7px 13px; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; transition: color .15s, border-color .15s; }
body.kairo-v2 #faq .kfaq .reset:hover { color: var(--navy); border-color: var(--navy); }
body.kairo-v2 #faq .kfaq .reset svg { width: 14px; height: 14px; }
@media (max-width: 860px) { body.kairo-v2 #faq .kfaq .reset span { display: none; } }

body.kairo-v2 #faq .kfaq .stage { position: relative; flex: 1; min-height: 0; }
body.kairo-v2 #faq .kfaq .msgs { height: 100%; overflow-y: auto; padding: 18px 18px 22px; display: flex; flex-direction: column; gap: 16px; background: var(--bg-alt); background-image: radial-gradient(var(--text-muted) 1px, transparent 1px); background-size: 18px 18px; background-blend-mode: multiply; scroll-behavior: smooth; }
body.kairo-v2 #faq .kfaq .day { align-self: center; padding: 4px 12px; border-radius: 999px; background: var(--surface, #fff); border: 1px solid var(--border); font-size: 11.5px; font-weight: 600; color: var(--text-muted); }
body.kairo-v2 #faq .kfaq .jump { position: absolute; right: 16px; bottom: 14px; width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--border); background: var(--surface, #fff); color: var(--navy); display: none; place-items: center; cursor: pointer; box-shadow: 0 8px 20px -8px rgba(28,42,68,.32); }
body.kairo-v2 #faq .kfaq .jump.on { display: grid; }
body.kairo-v2 #faq .kfaq .jump svg { width: 18px; height: 18px; }

body.kairo-v2 #faq .kfaq .row { display: flex; align-items: flex-end; gap: 10px; animation: kfaqIn .3s ease both; }
body.kairo-v2 #faq .kfaq .row.me { flex-direction: row-reverse; }
body.kairo-v2 #faq .kfaq .pic { width: 34px; height: 34px; flex: 0 0 34px; overflow: hidden; border-radius: 12px; display: grid; place-items: center; background: var(--bg); border: 1px solid var(--border); max-width: 34px; }
body.kairo-v2 #faq .kfaq .pic.u { border-radius: 50%; }
body.kairo-v2 #faq .kfaq .pic img, body.kairo-v2 #faq .kfaq .pic svg { width: 100%; height: 100%; max-width: none; display: block; object-fit: contain; }
body.kairo-v2 #faq .kfaq .pic img { width: 100%; height: 100%; object-fit: contain; }
body.kairo-v2 #faq .kfaq .pic.u { background: var(--user, #dfe5f3); color: var(--text-muted); border-radius: 50%; }
body.kairo-v2 #faq .kfaq .pic.u svg { width: 20px; height: 20px; }
body.kairo-v2 #faq .kfaq .col { max-width: 78%; min-width: 0; }
body.kairo-v2 #faq .kfaq .me .col { display: flex; flex-direction: column; align-items: flex-end; }
body.kairo-v2 #faq .kfaq .bub { padding: 12px 16px; font-size: 15px; border-radius: 18px; overflow-wrap: anywhere; }
body.kairo-v2 #faq .kfaq .bub p { margin: 0; }
body.kairo-v2 #faq .kfaq .bot .bub { background: var(--surface, #fff); border: 1px solid var(--border); border-bottom-left-radius: 6px; }
body.kairo-v2 #faq .kfaq .me .bub { background: linear-gradient(135deg, var(--coral-strong), var(--coral)); color: #fff; font-weight: 600; border-bottom-right-radius: 6px; }
body.kairo-v2 #faq .kfaq .meta { margin: 5px 6px 0; font-size: 11.5px; color: var(--text-muted); }
body.kairo-v2 #faq .kfaq .caret { display: inline-block; width: 2px; height: 1em; margin-left: 2px; vertical-align: -.15em; background: var(--coral-strong); animation: kfaqBlink .8s steps(1) infinite; }
body.kairo-v2 #faq .kfaq .dots { display: flex; gap: 5px; padding: 16px 18px; }
body.kairo-v2 #faq .kfaq .dots i { width: 7px; height: 7px; border-radius: 50%; background: var(--text-muted); animation: kfaqBob 1s infinite; }
body.kairo-v2 #faq .kfaq .dots i:nth-child(2) { animation-delay: .15s; }
body.kairo-v2 #faq .kfaq .dots i:nth-child(3) { animation-delay: .3s; }

body.kairo-v2 #faq .kfaq .tags { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0 0; padding: 0; list-style: none; }
body.kairo-v2 #faq .kfaq .tags li { padding: 5px 11px; border-radius: 999px; background: var(--soft, #fff1ed); border: 1px solid var(--soft-line, rgba(234,90,65,.28)); font-size: 12.5px; font-weight: 600; }
body.kairo-v2 #faq .kfaq .src { display: flex; align-items: center; gap: 10px; margin-top: 12px; padding: 10px 12px; border: 1px solid var(--border); border-radius: 14px; background: var(--bg-alt); color: inherit; text-decoration: none; font-size: 13px; transition: border-color .15s; }
body.kairo-v2 #faq .kfaq .src:hover { border-color: var(--coral-strong); }
body.kairo-v2 #faq .kfaq .src svg { width: 18px; height: 18px; flex: none; color: var(--coral-strong); }
body.kairo-v2 #faq .kfaq .src small { display: block; color: var(--text-muted); font-size: 11.5px; line-height: 1.2; }
body.kairo-v2 #faq .kfaq .src b { font-weight: 600; line-height: 1.3; }
body.kairo-v2 #faq .kfaq .wa-pill { display: inline-flex; align-items: center; gap: 8px; margin-top: 12px; padding: 9px 15px; border-radius: 999px; background: #25d366; color: #06361a; font-size: 13px; font-weight: 700; text-decoration: none; }
body.kairo-v2 #faq .kfaq .opts { display: grid; gap: 8px; margin-top: 12px; }
body.kairo-v2 #faq .kfaq .opt { text-align: left; padding: 10px 14px; border: 1px solid var(--border); border-radius: 14px; background: var(--surface, #fff); font-size: 13.5px; font-weight: 600; cursor: pointer; transition: border-color .15s, background-color .15s; }
body.kairo-v2 #faq .kfaq .opt:hover { border-color: var(--coral-strong); background: var(--soft, #fff1ed); }
body.kairo-v2 #faq .kfaq .fb { display: flex; align-items: center; justify-content: flex-end; gap: 6px; margin-top: 10px; font-size: 12px; color: var(--text-muted); }
body.kairo-v2 #faq .kfaq .fb button { width: 30px; height: 30px; display: grid; place-items: center; border: 1px solid var(--border); border-radius: 50%; background: var(--surface, #fff); color: var(--text-muted); cursor: pointer; transition: color .15s, border-color .15s; }
body.kairo-v2 #faq .kfaq .fb button:hover { color: var(--coral-strong); border-color: var(--coral-strong); }
body.kairo-v2 #faq .kfaq .fb svg { width: 15px; height: 15px; }
body.kairo-v2 #faq .kfaq .follow { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 0 44px; animation: kfaqIn .3s ease both; }
body.kairo-v2 #faq .kfaq .follow .chip { font-size: 13px; padding: 7px 13px; }

body.kairo-v2 #faq .kfaq .quick { border-top: 1px solid var(--border); padding: 12px 0 10px; }
body.kairo-v2 #faq .kfaq .label { margin: 0 18px 8px; font-size: 12.5px; font-weight: 600; color: var(--text-muted); }
body.kairo-v2 #faq .kfaq .rail { display: grid; grid-auto-flow: column; grid-template-rows: repeat(2, auto); grid-auto-columns: max-content; justify-items: start; gap: 8px; overflow-x: auto; padding: 0 18px 4px; scroll-padding: 0 18px; scrollbar-width: none; scroll-snap-type: x proximity; -webkit-mask-image: linear-gradient(90deg, #000 calc(100% - 40px), transparent); mask-image: linear-gradient(90deg, #000 calc(100% - 40px), transparent); }
body.kairo-v2 #faq .kfaq .rail::after { content: ""; width: 18px; }
body.kairo-v2 #faq .kfaq .rail::-webkit-scrollbar { display: none; }
body.kairo-v2 #faq .kfaq button, body.kairo-v2 #faq .kfaq input { appearance: none; box-shadow: none; text-transform: none; letter-spacing: normal; font: inherit; }
body.kairo-v2 #faq .kfaq .chip { scroll-snap-align: start; white-space: nowrap; color: var(--ink); background: var(--surface, #fff); border: 1px solid var(--border); border-radius: 999px; padding: 8px 14px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: border-color .15s, background-color .15s, transform .15s; }
body.kairo-v2 #faq .kfaq .chip:hover { border-color: var(--coral-strong); background: var(--soft, #fff1ed); transform: translateY(-1px); }

body.kairo-v2 #faq .kfaq .form { width: 100%; display: flex; gap: 10px; padding: 4px 18px 8px; box-sizing: border-box; }
body.kairo-v2 #faq .kfaq .kfaq-input { flex: 1 1 auto; min-width: 0; width: auto; padding: 12px 18px; border-radius: 999px; border: 1px solid var(--line); background: var(--chat); font-size: 14.5px; color: var(--ink); box-shadow: none; }
body.kairo-v2 #faq .kfaq .kfaq-input::placeholder { color: var(--text-muted); }
body.kairo-v2 #faq .kfaq .send { width: 46px; height: 46px; flex: none; border: 0; border-radius: 50%; background: linear-gradient(135deg, var(--coral-strong), var(--coral)); color: #fff; display: grid; place-items: center; cursor: pointer; transition: transform .15s, opacity .15s; }
body.kairo-v2 #faq .kfaq .send:hover:not(:disabled) { transform: scale(1.06); }
body.kairo-v2 #faq .kfaq .send:disabled { opacity: .4; cursor: default; }
body.kairo-v2 #faq .kfaq .send svg { width: 20px; height: 20px; }
body.kairo-v2 #faq .kfaq .note { display: flex; justify-content: space-between; gap: 12px; padding: 0 22px 14px; font-size: 11.5px; color: var(--text-muted); }

@media (max-width: 860px) {
    body.kairo-v2 #faq .kfaq { grid-template-columns: 1fr; gap: 32px; padding: 32px 0; }
    body.kairo-v2 #faq .kfaq .chat { height: 640px; max-height: calc(100dvh - 24px); }
    body.kairo-v2 #faq .kfaq .col { max-width: 86%; }
    body.kairo-v2 #faq .kfaq .brand b i { display: none; }
}
html.dark body.kairo-v2 #faq .kfaq {
    --bg: #0e1325; --surface: #161d35; --chat: #111830; --dot: rgba(255,255,255,.06); --ink: #eef1fb; --muted: #9ba5c6; --line: #27304d;
    --accent: #ff6a52; --accent2: #ff8d5c; --soft: #2e2338; --soft-line: rgba(255,106,82,.35); --shadow: rgba(0,0,0,.7); --user: #2a3458;
    color-scheme: dark;
}

@media (prefers-reduced-motion: reduce) {
    body.kairo-v2 #faq .kfaq * { animation: none !important; transition: none !important; }
    body.kairo-v2 #faq .kfaq .msgs { scroll-behavior: auto; }
}
@keyframes kfaqIn { from { opacity: 0; transform: translateY(8px); } }
@keyframes kfaqBob { 0%, 60%, 100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-4px); opacity: 1; } }
@keyframes kfaqBlink { 50% { opacity: 0; } }
</style>

<section id="faq" class="kfaq-section">
    <div class="w">
        <div class="kfaq">
            <div>
                <div class="ey">{{ marketing_content('home_faq_badge') ?: __('FAQ') }}</div>
                <h2>{{ marketing_content('home_faq_title') ?: __('Questions school leaders ask us') }}</h2>
                <p class="lead">{{ marketing_content('home_faq_subtitle') ?: __('Tap a question or type your own. Can’t find your answer? Talk to a person.') }}</p>
                <a class="talk" href="https://wa.me/{{ preg_replace('/\D+/', '', Modules\SaaS\Models\PlatformSetting::get('contact', 'whatsapp') ?: '263786366855') }}" target="_blank" rel="noopener">
                    <span class="ic" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span><b>{{ __('Chat on WhatsApp') }}</b><span>+263 78 636 6855</span></span>
                </a>
            </div>

            <section class="chat" aria-label="{{ __('Kairo assistant') }}">
                <div class="head">
                    <div class="brand" aria-label="{{ platform_name() }}">
                        <img class="logo-light" src="{{ asset('img/kairo-core-logo.svg') }}" alt="{{ platform_name() }}" width="90" height="30">
                        <img class="logo-dark" src="{{ asset('images/kairocore-logo-white.webp') }}" alt="{{ platform_name() }}" width="90" height="30">
                    </div>
                    <div class="who">
                        <b>{{ __('Kairo assistant') }}</b>
                        <small>{{ __('Online · answers instantly') }}</small>
                    </div>
                    <button class="reset" id="kfaq-reset" type="button">
                        <svg width="14" height="14" viewBox="0 0 24 24"><path d="M20 12a8 8 0 1 1-2.3-5.7M20 4v5h-5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span>{{ __('Start over') }}</span>
                    </button>
                </div>

                <div class="stage">
                    <div class="msgs" id="kfaq-msgs" aria-live="polite"></div>
                    <button class="jump" id="kfaq-jump" type="button" aria-label="{{ __('Jump to latest message') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24"><path d="M12 5v14M5 12l7 7 7-7" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </div>

                <div class="quick" id="kfaq-quick"></div>

                <form class="form" id="kfaq-form" autocomplete="off">
                    <input id="kfaq-input" class="kfaq-input" type="text" maxlength="200" placeholder="{{ __('Type your question…') }}" aria-label="{{ __('Type your question') }}">
                    <button class="send" id="kfaq-send" type="submit" aria-label="{{ __('Send question') }}" disabled>
                        <svg width="20" height="20" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                </form>
                <div class="note">
                    <span>{{ __('Automated assistant. Answers come from Kairo CORE guides.') }}</span>
                    <span id="kfaq-count"></span>
                </div>
            </section>
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
@endphp

<script type="module">
import { matchQuestion, logUnmatched, logFeedback } from '/kfaq-engine.js';

const msgs = document.getElementById('kfaq-msgs');
const quick = document.getElementById('kfaq-quick');
const form = document.getElementById('kfaq-form');
const input = document.getElementById('kfaq-input');
const send = document.getElementById('kfaq-send');
const reset = document.getElementById('kfaq-reset');
const jump = document.getElementById('kfaq-jump');
const count = document.getElementById('kfaq-count');
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const resizeObserver = new ResizeObserver(() => { if (msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight <= 90) scrollToBottom(true); });
resizeObserver.observe(msgs);
const suggestions = {!! json_encode($kfaqSuggestionQuestions) !!};
const asked = new Set();
let kb = null;
let previousTopic = null;
let busy = false;

const clock = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

const scrollToBottom = (instant = false) => {
    msgs.style.scrollBehavior = instant || reduce ? 'auto' : 'smooth';
    requestAnimationFrame(() => requestAnimationFrame(() => { msgs.scrollTop = msgs.scrollHeight; }));
};
const down = () => scrollToBottom(false);
msgs.addEventListener('scroll', () => jump.classList.toggle('on', msgs.scrollHeight - msgs.scrollTop - msgs.clientHeight > 90));
jump.addEventListener('click', () => scrollToBottom(false));

function clearFollowups() { msgs.querySelectorAll('.follow').forEach(node => node.remove()); }

function row(kind) {
    clearFollowups();
    const rowEl = document.createElement('div');
    rowEl.className = 'row ' + kind;
    const avatar = document.createElement('span');
    avatar.className = 'pic ' + (kind === 'me' ? 'u' : '');
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
    col.className = 'col';
    const bubble = document.createElement('div');
    bubble.className = 'bub';
    const meta = document.createElement('div');
    meta.className = 'meta';
    meta.textContent = (kind === 'me' ? 'You' : 'Kairo assistant') + ' · ' + clock();
    col.append(bubble, meta);
    rowEl.append(avatar, col);
    msgs.appendChild(rowEl);
    scrollToBottom(false);
    return bubble;
}

function me(text) { row('me').textContent = text; }

function typing() {
    const bubble = row('bot');
    bubble.classList.add('dots');
    bubble.innerHTML = '<i></i><i></i><i></i>';
    return bubble.closest('.row');
}

function streamText(element, text, done) {
    if (reduce) {
        element.textContent = text;
        return done();
    }
    const words = text.split(/\s+/).filter(Boolean);
    const caret = document.createElement('span');
    caret.className = 'caret';
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
    }, 30);
}

function renderAnswer(bubble, entry, secondIntent) {
    if (entry.needsOwnerAnswer) {
        const link = document.createElement('a');
        link.className = 'wa-pill';
        link.href = 'https://wa.me/263786366855';
        link.target = '_blank';
        link.rel = 'noopener';
        link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Message us on WhatsApp';
        bubble.appendChild(document.createElement('br'));
        bubble.appendChild(link);
        return;
    }

    if (entry.tags && entry.tags.length) {
        const tags = document.createElement('ul');
        tags.className = 'tags';
        entry.tags.forEach(tag => {
            const item = document.createElement('li');
            item.textContent = tag;
            tags.appendChild(item);
        });
        bubble.appendChild(tags);
    }

    if (entry.source && entry.source.url && String(entry.source.url).startsWith('/docs')) {
        const link = document.createElement('a');
        link.className = 'src';
        link.href = entry.source.url;
        link.innerHTML = '<svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h7M9 17h7" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg><span><small></small><b></b></span>';
        link.querySelector('small').textContent = 'From the Help Centre · ' + (entry.source.category || entry.category || '');
        link.querySelector('b').textContent = entry.source.title;
        bubble.appendChild(link);
    }

    const feedback = document.createElement('div');
    feedback.className = 'fb';
    const up = document.createElement('button');
    up.type = 'button';
    up.setAttribute('aria-label', 'Helpful');
    up.innerHTML = '<svg viewBox="0 0 24 24"><path d="M7 11v9H4v-9zM7 11l4-8c1.7 0 2.5 1.2 2.2 2.8L12.8 9H19a2 2 0 0 1 2 2.3l-1.2 7A2 2 0 0 1 17.8 20H7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>';
    const down = document.createElement('button');
    down.type = 'button';
    down.setAttribute('aria-label', 'Not helpful');
    down.innerHTML = '<svg viewBox="0 0 24 24"><path d="M17 13V4h3v9zM17 13l-4 8c-1.7 0-2.5-1.2-2.2-2.8L11.2 15H5a2 2 0 0 1-2-2.3l1.2-7A2 2 0 0 1 6.2 4H17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round" stroke-linecap="round"/></svg>';
    const thanks = () => { feedback.textContent = 'Thanks for the feedback'; };
    up.addEventListener('click', () => { logFeedback(entry.question, true); thanks(); });
    down.addEventListener('click', () => { logFeedback(entry.question, false); logUnmatched(input.value || entry.question); thanks(); });
    feedback.append(up, down);
    bubble.appendChild(feedback);

    const related = (entry.related || []).map(id => kb.find(item => item.id === id)).filter(Boolean).slice(0, 3);
    if (related.length) {
        const wrap = document.createElement('div');
        wrap.className = 'follow';
        related.forEach(item => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip';
            chip.textContent = item.question;
            chip.addEventListener('click', () => ask(item.question));
            wrap.appendChild(chip);
        });
        msgs.appendChild(wrap);
    }

    if (secondIntent) {
        const wrap = document.createElement('div');
        wrap.className = 'follow';
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip';
        chip.textContent = secondIntent.question;
        chip.addEventListener('click', () => ask(secondIntent.question));
        wrap.appendChild(chip);
        msgs.appendChild(wrap);
    }
}

function renderChips() {
    quick.innerHTML = '';
    const label = document.createElement('p');
    label.className = 'label';
    label.textContent = suggestions.some(q => !asked.has(q)) ? 'Suggested questions' : 'That’s every suggested question. Type your own below.';
    quick.appendChild(label);
    const remaining = suggestions.filter(q => !asked.has(q));
    if (!remaining.length) return;
    const rail = document.createElement('div');
    rail.className = 'rail';
    remaining.forEach(question => {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip';
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
    row('me').querySelector('.bub').textContent = value;

    const result = matchQuestion(value, kb, previousTopic);
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
            opts.className = 'opts';
            (result.candidates || []).slice(0, 3).forEach(candidate => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'opt';
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
            const bubble = row('bot');
            const p = document.createElement('p');
            p.textContent = 'I don’t have that one yet. Message the team on WhatsApp and a person will help.';
            bubble.appendChild(p);
            const link = document.createElement('a');
            link.className = 'wa-pill';
            link.href = 'https://wa.me/263786366855';
            link.target = '_blank';
            link.rel = 'noopener';
            link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Message us on WhatsApp';
            bubble.appendChild(link);
            busy = false;
            scrollToBottom(false);
            return;
        }

        previousTopic = result.entry.category;
        const bubble = row('bot');
        streamText(bubble, result.entry.needsOwnerAnswer ? 'I don’t have that one yet. Message the team on WhatsApp and a person will help.' : result.entry.answer, () => {
            if (result.entry.needsOwnerAnswer) {
                const link = document.createElement('a');
                link.className = 'wa-pill';
                link.href = 'https://wa.me/263786366855';
                link.target = '_blank';
                link.rel = 'noopener';
                link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Message us on WhatsApp';
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
    count.textContent = input.value.length >= 150 ? input.value.length + '/200' : '';
}
input.addEventListener('input', sync);

function start() {
    msgs.innerHTML = '<div class="day">Today</div>';
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
        const response = await fetch('/faq-knowledge.json', { credentials: 'same-origin' });
        kb = await response.json();
        start();
    } catch (error) {
        const bubble = row('bot');
        bubble.textContent = 'I don’t have that one yet. Message the team on WhatsApp and a person will help.';
        const link = document.createElement('a');
        link.className = 'wa-pill';
        link.href = 'https://wa.me/263786366855';
        link.target = '_blank';
        link.rel = 'noopener';
        link.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24"><path d="M4 20l1.4-4.2A8 8 0 1 1 8.3 18.7z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg> Message us on WhatsApp';
        bubble.appendChild(document.createElement('br'));
        bubble.appendChild(link);
    }
}

const observer = new IntersectionObserver(entries => {
    if (entries.some(entry => entry.isIntersecting) && !kb) loadKb();
}, { rootMargin: '200px' });
observer.observe(document.getElementById('faq'));

scrollToBottom(true);
    renderChips();
</script>
