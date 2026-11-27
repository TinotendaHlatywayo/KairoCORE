@php
    $formatGuideText = static function ($text): string {
        return (string) preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', e((string) $text));
    };

    $formatStep = static function ($step): array {
        $plain = trim(strip_tags((string) $step));
        $text = (string) preg_replace('/^\s*\d+\.\s*(?:Step\s*\d+\s*-\s*)?/i', '', $plain);
        $parts = explode(':', $text, 2);

        if (count($parts) === 2 && mb_strlen($parts[0]) < 90) {
            return ['title' => trim($parts[0]), 'body' => trim($parts[1])];
        }

        return ['title' => 'Step', 'body' => $text];
    };

    $stepQuestion = static function ($step): string {
        $text = (string) preg_replace('/^\s*\d+\.\s*(?:Step\s*\d+\s*-\s*)?/i', '', strip_tags((string) $step));
        $text = str_replace('**', '', $text);

        return trim($text);
    };
@endphp

<div
    id="kh"
    @if(!$visible) hidden @endif
    wire:ignore.self
    :class="open ? 'open' : ''"
    x-data="{ open: false, tab: 'guide' }"
    x-init="
        open = sessionStorage.getItem(@js('kairo_assistant_open')) === '1';
        $watch('open', value => sessionStorage.setItem(@js('kairo_assistant_open'), value ? '1' : '0'));
        window.addEventListener('kh-open', () => { open = true; tab = 'guide'; });
        window.addEventListener('livewire:navigated', () => {
            try { $wire.refreshGuide(); } catch (error) {}
        });
    "
>
@if($visible)
    <style>
        #kh *{box-sizing:border-box}
        #kh{
            font-family:inherit;
            --kh-surface:#fff; --kh-text:#16213a; --kh-muted:#6b7694; --kh-line:#e4e8f2;
            --kh-bot:#eef1f9; --kh-wall:#f7f8fc; --kh-bg:#f4f6fb;
        }
        @media (prefers-color-scheme:dark){
            #kh{--kh-surface:#171d33;--kh-text:#e8ecf8;--kh-muted:#8e99b8;--kh-line:#262e4a;--kh-bot:#222a45;--kh-wall:#121830;--kh-bg:#0f1424}
        }
        .dark #kh{--kh-surface:#171d33;--kh-text:#e8ecf8;--kh-muted:#8e99b8;--kh-line:#262e4a;--kh-bot:#222a45;--kh-wall:#121830;--kh-bg:#0f1424}
        #kh button{cursor:pointer}
        #kh-fab{
            position:fixed; right:20px; bottom:calc(20px + env(safe-area-inset-bottom,0px)); z-index:9998;
            display:flex; align-items:center; gap:8px; height:52px; padding:0 20px 0 14px;
            border:0; border-radius:26px; background:#2563eb; color:#fff;
            font:700 15px/1 inherit; box-shadow:0 8px 24px rgba(37,99,235,.38);
            transition:transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        #kh-fab:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(37,99,235,.45)}
        #kh-fab:focus-visible,#kh button:focus-visible,#kh input:focus-visible,#kh a:focus-visible{outline:3px solid #f58a4c;outline-offset:2px}
        #kh-fab svg{width:24px;height:24px;flex:none}
        #kh-fab .dot{position:absolute;top:-2px;right:-2px;width:14px;height:14px;border-radius:50%;background:#ef5b3c;border:2px solid var(--kh-surface)}
        #kh.open #kh-fab .dot{display:none}

        #kh-panel{
            position:fixed; right:20px; bottom:calc(84px + env(safe-area-inset-bottom,0px)); z-index:9999;
            width:420px; max-width:calc(100vw - 24px); height:640px; max-height:calc(100dvh - 110px);
            display:flex; flex-direction:column; overflow:hidden;
            background:var(--kh-surface); color:var(--kh-text);
            border:1px solid var(--kh-line); border-radius:20px;
            box-shadow:0 24px 60px rgba(15,25,60,.28);
            transform-origin:bottom right; transform:scale(.92) translateY(12px); opacity:0; visibility:hidden;
            transition:transform .2s ease, opacity .2s ease, visibility .2s;
        }
        #kh.open #kh-panel{transform:none;opacity:1;visibility:visible}
        .kh-head{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--kh-line);background:var(--kh-surface)}
        .kh-logo{width:42px;height:42px;border-radius:12px;background:#2563eb;display:grid;place-items:center;flex:none;overflow:hidden}
        .kh-logo img{width:100%;height:100%;object-fit:contain;background:#fff}
        .kh-title{flex:1;min-width:0}
        .kh-title b{display:block;font-size:15px;line-height:1.2}
        .kh-title span{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--kh-muted)}
        .kh-title span i{width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block}
        .kh-btn{border:1px solid var(--kh-line);background:var(--kh-surface);color:var(--kh-text);border-radius:999px;font:600 12px/1 inherit;padding:8px 12px}
        .kh-btn:hover{background:var(--kh-bot)}
        .kh-x{width:34px;height:34px;padding:0;display:grid;place-items:center;font-size:18px}
        .kh-tabs{display:flex;gap:6px;padding:8px 12px;border-bottom:1px solid var(--kh-line)}
        .kh-tab{flex:1;border:0;background:transparent;color:var(--kh-muted);font:700 13px/1 inherit;padding:10px;border-radius:10px}
        .kh-tab[aria-selected="true"]{background:var(--kh-bot);color:#2563eb;box-shadow:inset 0 0 0 1px rgba(37,99,235,.18)}
        .kh-view{flex:1;min-height:0;display:none;flex-direction:column}
        .kh-view.on{display:flex}

        #kh-guide{overflow-y:auto;padding:16px}
        .kh-kicker{display:inline-block;margin-bottom:6px;padding:4px 9px;border-radius:999px;background:rgba(37,99,235,.1);color:#2563eb;font:800 10px/1 inherit;letter-spacing:.08em}
        #kh-guide h3{margin:0 0 6px;font-size:17px;line-height:1.3}
        #kh-guide .lead{margin:0 0 16px;color:var(--kh-muted);font-size:13px;line-height:1.5}
        .kh-section-title{margin:14px 0 6px;font-size:13px;font-weight:800;color:var(--kh-text)}
        #kh-guide ol{list-style:none;margin:0;padding:0;counter-reset:kh-step}
        #kh-guide li{counter-increment:kh-step;position:relative;display:flex;gap:10px;padding:11px 0 11px 34px;border-top:1px solid var(--kh-line);font-size:13px;line-height:1.5}
        #kh-guide li::before{content:counter(kh-step);position:absolute;left:0;top:12px;width:24px;height:24px;border-radius:50%;border:1.5px solid #2563eb;color:#2563eb;display:grid;place-items:center;font-size:12px;font-weight:800}
        #kh-guide li b{display:block;margin-bottom:2px;font-size:13px}
        #kh-guide li span{display:block;color:var(--kh-muted)}
        .kh-step-ask{margin-top:6px;padding:0;border:0;background:none;color:#2563eb;font:700 11.5px/1 inherit;text-decoration:underline}
        #kh-guide .kh-detail{padding:9px 0;border-top:1px solid var(--kh-line);font-size:12.5px;line-height:1.5}
        #kh-guide .kh-detail strong{color:var(--kh-text)}
        #kh-guide .kh-detail span{color:var(--kh-muted)}
        #kh-guide .link{margin-top:14px;padding:10px 12px;border-radius:12px;background:var(--kh-bot);font-size:12px;line-height:1.5}
        #kh-guide .kh-doc{display:inline-flex;margin-top:12px;padding:10px 14px;border-radius:999px;background:#2563eb;color:#fff;font-size:12.5px;font-weight:700;text-decoration:none}
        #kh-guide .kh-doc:hover{background:#1d4ed8}
        #kh-guide .ask{margin-top:10px;margin-left:8px;border-color:#ef5b3c;color:#ef5b3c;font-weight:800}

        #kh-log{
            flex:1;min-height:0;overflow-y:auto;padding:14px 14px 6px;display:flex;flex-direction:column;gap:6px;
            background:var(--kh-wall);
            background-image:radial-gradient(circle at 1px 1px,rgba(120,135,180,.14) 1px,transparent 0);
            background-size:18px 18px;
        }
        .kh-day{align-self:center;font-size:11px;color:var(--kh-muted);background:var(--kh-surface);border:1px solid var(--kh-line);padding:3px 10px;border-radius:999px;margin:2px 0 6px}
        .kh-row{display:flex;gap:8px;align-items:flex-end;max-width:100%}
        .kh-row.me{justify-content:flex-end}
        .kh-av{width:28px;height:28px;border-radius:50%;background:#2563eb;color:#fff;font-weight:800;font-size:13px;display:grid;place-items:center;flex:none;overflow:hidden}
        .kh-av img{width:100%;height:100%;object-fit:contain;background:#fff}
        .kh-msg{max-width:88%;padding:9px 12px 6px;border-radius:16px;font-size:13.5px;line-height:1.5;overflow-wrap:anywhere}
        .kh-row.bot .kh-msg{background:var(--kh-bot);color:var(--kh-text);border-bottom-left-radius:4px}
        .kh-row.me .kh-msg{background:linear-gradient(135deg,#ef5b3c,#f58a4c);color:#fff;border-bottom-right-radius:4px}
        .kh-msg p{margin:0 0 6px}
        .kh-msg ul,.kh-msg ol{margin:0 0 6px;padding-left:18px}
        .kh-msg li{margin:3px 0}
        .kh-msg a{color:#2563eb;font-weight:700;text-decoration:underline}
        .kh-row.me .kh-msg a{color:#fff}
        .kh-msg button{display:block;margin:4px 0;padding:0;border:0;background:none;color:#2563eb;font:700 inherit;text-align:left;text-decoration:underline}
        .kh-answer-title{display:block;margin-bottom:4px;font-size:13px;font-weight:800}
        .kh-answer-intro{margin-bottom:6px}
        .kh-answer-note{padding:6px 0;border-top:1px solid rgba(100,116,139,.22);font-size:12.5px}
        .kh-page-links{margin-top:8px;padding-top:8px;border-top:1px solid rgba(100,116,139,.22);font-size:12px}
        .kh-page-links a{display:inline-block;margin:3px 6px 0 0;padding:4px 8px;border-radius:999px;background:rgba(37,99,235,.1);color:#2563eb;text-decoration:none}
        .kh-meta{display:flex;justify-content:flex-end;align-items:center;gap:4px;font-size:10.5px;opacity:.7;margin-top:2px}
        .kh-tick{width:16px;height:10px}
        .kh-tick path{fill:none;stroke:#cfe6ff;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
        .kh-typing .kh-msg{padding:12px 14px}
        .kh-typing span{display:inline-block;width:7px;height:7px;margin:0 2px;border-radius:50%;background:var(--kh-muted);animation:kh-bounce 1.1s infinite ease-in-out}
        .kh-typing span:nth-child(2){animation-delay:.15s}.kh-typing span:nth-child(3){animation-delay:.3s}
        .kh-typing-inline::after{content:'▌';color:#2563eb;animation:kh-caret .7s steps(2,start) infinite}
        @keyframes kh-bounce{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-4px);opacity:1}}
        @keyframes kh-caret{0%,100%{opacity:.2}50%{opacity:1}}
        .kh-sugg{padding:8px 12px 4px;border-top:1px solid var(--kh-line);background:var(--kh-surface)}
        .kh-sugg small{display:block;font-size:12px;font-weight:700;color:var(--kh-muted);margin-bottom:6px}
        .kh-chips{display:flex;gap:7px;overflow-x:auto;padding-bottom:6px;scrollbar-width:thin}
        .kh-chip{flex:none;border:1.5px solid #2563eb;background:#eff6ff;color:#1d4ed8;border-radius:999px;font:700 12.5px/1 inherit;padding:9px 13px;white-space:nowrap;box-shadow:0 2px 8px rgba(37,99,235,.14);transition:transform .15s ease,box-shadow .15s ease,background .15s ease}
        .kh-chip:hover{transform:translateY(-2px);background:#2563eb;color:#fff;box-shadow:0 6px 16px rgba(37,99,235,.32)}
        .kh-chip:first-child{background:linear-gradient(135deg,#ef5b3c,#f58a4c);border-color:transparent;color:#fff;box-shadow:0 3px 12px rgba(239,91,60,.32)}
        .kh-chip:first-child:hover{box-shadow:0 8px 18px rgba(239,91,60,.42)}
        .kh-form{display:flex;align-items:center;gap:8px;padding:8px 12px 10px;background:var(--kh-surface)}
        .kh-form input{flex:1;min-width:0;border:1.5px solid var(--kh-line);background:var(--kh-bg);color:var(--kh-text);border-radius:999px;padding:12px 16px;font:14px inherit}
        .kh-form input:focus{border-color:#ef5b3c}
        .kh-send{width:42px;height:42px;border:0;border-radius:50%;background:linear-gradient(135deg,#ef5b3c,#f58a4c);color:#fff;display:grid;place-items:center;flex:none}
        .kh-send:disabled{opacity:.45;cursor:default}
        .kh-send svg{width:18px;height:18px}
        .kh-foot{padding:0 14px 10px;font-size:11px;color:var(--kh-muted);background:var(--kh-surface)}
        .kh-foot a{color:#2563eb;font-weight:700;text-decoration:underline}

        @media (max-width:520px){
            #kh-panel{right:0;bottom:0;width:100vw;max-width:100vw;height:100dvh;max-height:100dvh;border-radius:0;border:0}
            #kh.open #kh-fab{display:none}
        }
        @media (prefers-reduced-motion:reduce){
            #kh *{animation:none!important;transition:none!important}
            .kh-typing-inline::after{content:''}
        }
    </style>

    <button id="kh-fab" type="button" @click="open = !open" :aria-expanded="open ? 'true' : 'false'" aria-haspopup="dialog" aria-controls="kh-panel">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.4A8 8 0 1 1 21 12Z"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .8-1 1.5"/><circle cx="12" cy="16" r=".6" fill="currentColor"/></svg>
        <span>{{ __('Help') }}</span>
        <span class="dot" aria-hidden="true"></span>
    </button>

    <section id="kh-panel" role="dialog" aria-label="{{ __('Help and assistant') }}" :aria-hidden="open ? 'false' : 'true'">
        <header class="kh-head">
            <div class="kh-logo" aria-hidden="true"><img src="{{ $faviconUrl }}" alt=""></div>
            <div class="kh-title">
                <b>{{ __('Kairo assistant') }}</b>
                <span><i></i>{{ $isDemo ? __('Demo workspace · answers instantly') : __('Online · answers instantly') }}</span>
            </div>
            <button class="kh-btn" type="button" @click="window.kairoAssistantReset && window.kairoAssistantReset()">{{ __('Start over') }}</button>
            <button class="kh-btn kh-x" type="button" @click="open = false" aria-label="{{ __('Close help') }}">✕</button>
        </header>

        <div class="kh-tabs" role="tablist" aria-label="{{ __('Help sections') }}">
            <button class="kh-tab" type="button" role="tab" :aria-selected="tab === 'guide' ? 'true' : 'false'" @click="tab = 'guide'">{{ __('Help') }}</button>
            <button class="kh-tab" type="button" role="tab" :aria-selected="tab === 'chat' ? 'true' : 'false'" @click="tab = 'chat'; window.kairoAssistantFocus && window.kairoAssistantFocus()">{{ __('Assistant') }}</button>
        </div>

        <div class="kh-view on" id="v-guide" :class="tab === 'guide' ? 'on' : ''" role="tabpanel">
            <div id="kh-guide">
                <span class="kh-kicker">{{ __('Current page') }}</span>
                <h3>{{ $help['title'] ?? __('System Guidance') }}</h3>
                <p class="lead">{{ $help['summary'] ?? '' }}</p>

                <h4 class="kh-section-title">{{ __('Step-by-step workflow') }}</h4>
                @if(!empty($help['workflow']))
                    <ol>
                        @foreach($help['workflow'] as $step)
                            @php $formattedStep = $formatStep($step); @endphp
                            <li>
                                <div>
                                    <b>{{ $formattedStep['title'] }}</b>
                                    <span>{!! $formatGuideText($formattedStep['body']) !!}</span>
                                    <button class="kh-step-ask" type="button" data-question="{{ $stepQuestion($step) }}">{{ __('Ask about this step') }}</button>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if(!empty($help['details']))
                    <h4 class="kh-section-title">{{ __('Good to know') }}</h4>
                    @foreach($help['details'] as $label => $text)
                        <div class="kh-detail"><strong>{{ $label }}:</strong> <span>{{ $text }}</span></div>
                    @endforeach
                @endif

                <div class="link"><b>{{ __('Page-aware help:') }}</b> {{ __('This guide and the assistant suggestions change as you move around the workspace.') }}</div>

                <a class="kh-doc" href="{{ $docsUrl }}" target="_blank" rel="noopener">{{ __('Full Documentation') }}</a>
                <button class="kh-btn ask" type="button" @click="tab = 'chat'; window.kairoAssistantFocus && window.kairoAssistantFocus()">{{ __('Still stuck? Ask the assistant') }}</button>
            </div>
        </div>

        <div class="kh-view" id="v-chat" :class="tab === 'chat' ? 'on' : ''" role="tabpanel">
            <div id="kh-log" aria-live="polite"></div>
            <div class="kh-sugg">
                <small>{{ __('Suggested questions for this page') }}</small>
                <div class="kh-chips" id="kh-chips"></div>
            </div>
            <form class="kh-form" id="kh-form" autocomplete="off">
                <input id="kh-input" type="text" placeholder="{{ __('Ask about any page or workflow…') }}" aria-label="{{ __('Type your question') }}">
                <button class="kh-send" id="kh-send" type="submit" aria-label="{{ __('Send') }}" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </button>
            </form>
            <div class="kh-foot">{{ __('Answers use the Kairo CORE workflow engine and Help Centre.') }} <a href="{{ $helpCentreUrl }}" target="_blank" rel="noopener">{{ __('Help Centre') }}</a></div>
        </div>
    </section>
@endif
</div>

@if($visible)
@script
<script type="module">
import { matchQuestion, logUnmatched } from '/kfaq-engine.js';
import { assistantAnswer } from '/kairo-assistant-engine.js';

const state = window.__kairoAssistant || (window.__kairoAssistant = {
    booted: false,
    busy: false,
    kbLoaded: false,
    kb: [],
    messages: [],
    previousTopic: null,
    guide: {},
    workflows: [],
    pages: [],
    suggestions: [],
    currentTitle: '',
    userId: 'guest',
    docsUrl: '',
    helpCentreUrl: '',
    faviconUrl: ''
});

const el = (id) => document.getElementById(id);
const clock = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
const tickSvg = '<svg class="kh-tick" viewBox="0 0 16 10" aria-label="Read"><path d="M1 5.5 4 8.5 10 1.5M6 8.5 12 1.5"/></svg>';
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function linkify(text) {
    return escapeHtml(text)
        .replace(/\n/g, '<br>')
        .replace(/\bhttps?:\/\/[^\s<]+/gi, (url) => `<a href="${url}" target="_blank" rel="noopener">${url}</a>`)
        .replace(/\b([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})\b/gi, '<a href="mailto:$1">$1</a>');
}

function safeHref(url) {
    const value = String(url || '');
    if (value.startsWith('/')) return value;

    try {
        const parsed = new URL(value, window.location.href);
        if (parsed.origin === window.location.origin) return parsed.pathname + parsed.search + parsed.hash;
    } catch (error) {}

    return state.docsUrl || state.helpCentreUrl || '#';
}

function renderAnswer(result) {
    const data = result || {};
    let html = `<span class="kh-answer-title">${escapeHtml(data.title || 'Kairo assistant')}</span>`;
    html += `<p class="kh-answer-intro">${linkify(data.intro || '')}</p>`;

    if ((data.steps || []).length) {
        html += '<ol>' + data.steps.map((step) => `<li>${escapeHtml(step)}</li>`).join('') + '</ol>';
    }

    (data.notes || []).forEach((note) => {
        const label = note?.label ? `<b>${escapeHtml(note.label)}:</b> ` : '';
        html += `<div class="kh-answer-note">${label}${escapeHtml(note?.text || '')}</div>`;
    });

    const links = data.links || [];
    if (links.length) {
        html += `<div class="kh-page-links"><b>{{ __('Open next:') }}</b> `;
        html += links.map((link) => {
            if (link.question) return `<button type="button" data-question="${escapeHtml(link.question)}">${escapeHtml(link.label)}</button>`;
            return `<a href="${escapeHtml(safeHref(link.url))}" target="_blank" rel="noopener">${escapeHtml(link.label || link.url)}</a>`;
        }).join('');
        html += '</div>';
    }

    html += `<div class="kh-page-links"><a href="${escapeHtml(state.docsUrl)}" target="_blank" rel="noopener">{{ __('Full documentation') }}</a><a href="${escapeHtml(state.helpCentreUrl)}" target="_blank" rel="noopener">{{ __('Help Centre') }}</a></div>`;

    return html;
}

function htmlToText(html) {
    const holder = document.createElement('div');
    holder.innerHTML = String(html || '');
    holder.querySelectorAll('li').forEach((item) => item.prepend(document.createTextNode('• ')));
    holder.querySelectorAll('button, a').forEach((item) => item.prepend(document.createTextNode(' ')));
    return (holder.textContent || '').replace(/\s+/g, ' ').trim();
}

function persist() {
    try {
        localStorage.setItem(state.storageKey, JSON.stringify(state.messages));
    } catch (error) {}
}

function avatarHtml() {
    return `<div class="kh-av" aria-hidden="true"><img src="${escapeHtml(state.faviconUrl || '/images/kairocore-icon.png')}" alt=""></div>`;
}

function appendRow(who, html, at = null, persistMessage = true) {
    const row = document.createElement('div');
    row.className = `kh-row ${who}`;
    const time = at || clock();
    const avatar = who === 'bot' ? avatarHtml() : '';
    row.innerHTML = avatar + `<div class="kh-msg">${html}<div class="kh-meta">${escapeHtml(time)}${who === 'me' ? tickSvg : ''}</div></div>`;
    el('kh-log')?.appendChild(row);
    scrollLog();
    if (persistMessage) {
        state.messages.push({ who, html, at: time });
        persist();
    }
    return row;
}

function scrollLog() {
    const log = el('kh-log');
    if (log) log.scrollTop = log.scrollHeight;
}

function appendAnswer(html) {
    const at = clock();
    state.messages.push({ who: 'bot', html, at });
    persist();

    const row = document.createElement('div');
    row.className = 'kh-row bot';
    const meta = `<div class="kh-meta">${escapeHtml(at)}</div>`;
    row.innerHTML = avatarHtml() + `<div class="kh-msg">${html}${meta}</div>`;
    el('kh-log')?.appendChild(row);

    if (reducedMotion()) {
        scrollLog();
        return;
    }

    const bubble = row.querySelector('.kh-msg');
    const cursor = document.createElement('span');
    cursor.className = 'kh-typing-inline';
    cursor.setAttribute('aria-hidden', 'true');
    bubble.innerHTML = cursor.outerHTML + meta;

    const target = bubble.querySelector('.kh-typing-inline');
    const text = htmlToText(html);
    let index = 0;
    const chunk = Math.max(2, Math.ceil(text.length / 150));

    const tick = () => {
        index = Math.min(text.length, index + chunk);
        target.textContent = text.slice(0, index);
        scrollLog();

        if (index < text.length) {
            requestAnimationFrame(tick);
        } else {
            bubble.innerHTML = html + meta;
            scrollLog();
        }
    };

    requestAnimationFrame(tick);
}

function renderMessages() {
    const log = el('kh-log');
    if (!log) return;
    log.innerHTML = '<div class="kh-day">{{ __('Today') }}</div>';
    state.messages.forEach((message) => appendRow(message.who, message.html, message.at, false));
}

function setChips(questions) {
    const holder = el('kh-chips');
    if (!holder) return;
    holder.innerHTML = '';
    (questions || []).forEach((question) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'kh-chip';
        button.dataset.question = question;
        button.textContent = question;
        holder.appendChild(button);
    });
}

async function ensureKb() {
    if (state.kbLoaded) return;
    const locale = @js(app()->getLocale());
    const urls = locale && locale !== 'en'
        ? [`/faq-knowledge.${locale}.json`, '/faq-knowledge.json']
        : ['/faq-knowledge.json'];

    for (const url of urls) {
        try {
            const response = await fetch(url);
            if (response.ok) {
                state.kb = await response.json();
                break;
            }
        } catch (error) {}
    }
    state.kbLoaded = true;
}

function typingRow() {
    const row = document.createElement('div');
    row.className = 'kh-row bot kh-typing';
    row.innerHTML = avatarHtml() + '<div class="kh-msg" aria-label="{{ __('Assistant is typing') }}"><span></span><span></span><span></span></div>';
    el('kh-log')?.appendChild(row);
    scrollLog();
    return row;
}

async function ask(question) {
    const text = String(question || '').trim();
    if (!text || state.busy) return;

    state.busy = true;
    appendRow('me', escapeHtml(text));
    const input = el('kh-input');
    const send = el('kh-send');
    if (input) input.value = '';
    if (send) send.disabled = true;

    const typing = typingRow();
    await ensureKb();
    if (!reducedMotion()) await new Promise((resolve) => setTimeout(resolve, 180));

    try {
        const small = matchQuestion(text, state.kb || [], state.previousTopic, @js(app()->getLocale()));

        if (small.type === 'smalltalk') {
            typing.remove();
            appendAnswer(`<p>${escapeHtml(small.smallTalk)}</p><div class="kh-page-links"><a href="${escapeHtml(state.docsUrl)}" target="_blank" rel="noopener">{{ __('Full documentation') }}</a></div>`);
            setChips(state.suggestions);
        } else {
            const result = assistantAnswer(text, {
                kb: state.kb || [],
                workflows: state.workflows || [],
                pages: state.pages || [],
                suggestions: state.suggestions || [],
                currentTitle: state.currentTitle,
                locale: @js(app()->getLocale()),
            });

            typing.remove();
            appendAnswer(renderAnswer(result));
            setChips((result.followUps || []).length ? result.followUps : state.suggestions);

            if (result.kind === 'fallback') logUnmatched(text);
        }
    } catch (error) {
        typing.remove();
        appendAnswer(`<p>{{ __('That answer was interrupted. Please try again.') }}</p><div class="kh-page-links"><a href="${escapeHtml(state.docsUrl)}" target="_blank" rel="noopener">{{ __('Full documentation') }}</a></div>`);
        logUnmatched(text);
    } finally {
        state.busy = false;
    }
}

function resetConversation() {
    state.messages = [];
    state.previousTopic = null;
    const log = el('kh-log');
    if (log) log.innerHTML = '<div class="kh-day">{{ __('Today') }}</div>';
    const title = state.currentTitle || '{{ __('Kairo CORE') }}';
    appendRow('bot', `<p>{{ __('Hi, I am the Kairo assistant. I can walk you through') }} <b>${escapeHtml(title)}</b> {{ __('and answer questions about any Kairo CORE workflow.') }}</p>`);
    setChips(state.suggestions);
}

window.kairoAssistantFocus = () => el('kh-input')?.focus();
window.kairoAssistantReset = resetConversation;
window.kairoAssistantSetGuide = (guide, workflows, pages, suggestions, currentTitle, faviconUrl, docsUrl, helpCentreUrl) => {
    state.guide = guide || {};
    state.workflows = workflows || [];
    state.pages = pages || [];
    state.suggestions = suggestions || [];
    state.currentTitle = currentTitle || state.currentTitle;
    state.faviconUrl = faviconUrl || state.faviconUrl;
    state.docsUrl = docsUrl || state.docsUrl;
    state.helpCentreUrl = helpCentreUrl || state.helpCentreUrl;

    if (state.booted) {
        renderMessages();
        setChips(state.suggestions);
    }
};

window.kairoAssistantSetGuide(
    @js($help),
    @js($workflows),
    @js($pages),
    @js($suggestions),
    @js($currentTitle),
    @js($faviconUrl),
    @js($docsUrl),
    @js($helpCentreUrl)
);

if (!state.booted) {
    state.booted = true;
    state.userId = @js(auth()->id() ?? 'guest');
    state.storageKey = `kairo_assistant_chat_${state.userId}`;

    try {
        state.messages = JSON.parse(localStorage.getItem(state.storageKey) || '[]');
        if (!Array.isArray(state.messages)) state.messages = [];
    } catch (error) {
        state.messages = [];
    }

    renderMessages();
    if (state.messages.length === 0) resetConversation();
    else setChips(state.suggestions);
    ensureKb();

    document.addEventListener('submit', (event) => {
        if (event.target?.id !== 'kh-form') return;
        event.preventDefault();
        ask(el('kh-input')?.value || '');
    });

    document.addEventListener('input', (event) => {
        if (event.target?.id !== 'kh-input') return;
        const input = event.target;
        const send = el('kh-send');
        if (send) send.disabled = !input.value.trim();
    });

    document.addEventListener('click', (event) => {
        const guideStep = event.target.closest('#kh-guide button[data-question]');
        if (guideStep) {
            document.querySelector('#kh .kh-tabs .kh-tab:nth-child(2)')?.click();
            ask(guideStep.dataset.question);
            return;
        }

        const chip = event.target.closest('#kh-chips .kh-chip');
        if (chip) {
            ask(chip.dataset.question);
            return;
        }

        const followUp = event.target.closest('#kh-log button[data-question]');
        if (followUp) ask(followUp.dataset.question);
    });
}
</script>
@endscript
@endif
