<div
    id="kh"
    @if(!$visible) hidden @endif
    wire:ignore.self
    :class="open ? 'open' : ''"
    x-data="{ open: false, tab: 'assistant' }"
    x-init="
        open = sessionStorage.getItem(@js('kairo_assistant_open')) === '1';
        $watch('open', value => sessionStorage.setItem(@js('kairo_assistant_open'), value ? '1' : '0'));
        window.addEventListener('kh-open', () => { open = true; tab = 'assistant'; });
        window.addEventListener('livewire:navigated', () => {
            try { $wire.refreshGuide(window.location.pathname); } catch (error) {}
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
            --kh-accent:#ea5a41; --kh-accent-dark:#d8462b; --kh-accent-soft:rgba(234,90,65,.12);
        }
        @media (prefers-color-scheme:dark){
            #kh{--kh-surface:#171d33;--kh-text:#e8ecf8;--kh-muted:#8e99b8;--kh-line:#262e4a;--kh-bot:#222a45;--kh-wall:#121830;--kh-bg:#0f1424}
        }
        .dark #kh{--kh-surface:#171d33;--kh-text:#e8ecf8;--kh-muted:#8e99b8;--kh-line:#262e4a;--kh-bot:#222a45;--kh-wall:#121830;--kh-bg:#0f1424}
        #kh button{cursor:pointer}
        #kh-fab{
            position:fixed; right:20px; bottom:calc(20px + env(safe-area-inset-bottom,0px)); z-index:9998;
            display:flex; align-items:center; gap:8px; height:52px; padding:0 20px 0 14px;
            border:0; border-radius:26px; background:var(--kh-accent); color:#fff;
            font-family:inherit;font-size:15px;font-weight:700;line-height:1; box-shadow:0 8px 24px rgba(234,90,65,.38);
            transition:transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }
        #kh-fab:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(234,90,65,.45)}
        #kh-fab:focus-visible,#kh button:focus-visible,#kh input:focus-visible,#kh a:focus-visible{outline:3px solid #f58a4c;outline-offset:2px}
        #kh-fab svg{width:24px;height:24px;flex:none}
        #kh-fab .dot{position:absolute;top:-2px;right:-2px;width:14px;height:14px;border-radius:50%;background:#ef5b3c;border:2px solid var(--kh-surface)}
        #kh.open #kh-fab .dot{display:none}

        #kh-panel{
            position:fixed; right:20px; bottom:calc(84px + env(safe-area-inset-bottom,0px)); z-index:9999;
            width:400px; max-width:calc(100vw - 24px); height:600px; max-height:calc(100dvh - 110px);
            display:flex; flex-direction:column; overflow:hidden;
            background:var(--kh-surface); color:var(--kh-text);
            border:1px solid var(--kh-line); border-radius:18px;
            box-shadow:0 22px 54px rgba(15,25,60,.28);
            transform-origin:bottom right; transform:scale(.94) translateY(10px); opacity:0; visibility:hidden;
            transition:transform .18s ease, opacity .18s ease, visibility .18s;
        }
        #kh.open #kh-panel{transform:none;opacity:1;visibility:visible}
        .kh-head{display:flex;align-items:center;gap:8px;padding:10px 12px;border-bottom:1px solid var(--kh-line);background:var(--kh-surface)}
        .kh-logo{width:36px;height:36px;border-radius:10px;background:var(--kh-accent);display:grid;place-items:center;flex:none;overflow:hidden}
        .kh-logo img{width:100%;height:100%;object-fit:contain;background:#fff}
        .kh-title{flex:1;min-width:0}
        .kh-title b{display:block;font-size:14px;line-height:1.2}
        .kh-title span{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--kh-muted)}
        .kh-title span i{width:7px;height:7px;border-radius:50%;background:#22c55e;display:inline-block}
        .kh-btn{border:1px solid var(--kh-line);background:var(--kh-surface);color:var(--kh-text);border-radius:999px;font-family:inherit;font-size:11.5px;font-weight:600;line-height:1;padding:7px 10px;white-space:nowrap}
        .kh-btn:hover{background:var(--kh-bot)}
        .kh-x{width:30px;height:30px;padding:0;display:grid;place-items:center;font-size:16px}
        .kh-tabs{display:grid;grid-template-columns:1fr 1fr;gap:4px;padding:8px 10px 0;border-bottom:1px solid var(--kh-line);background:var(--kh-surface)}
        .kh-tab{border:1px solid transparent;border-bottom:0;border-radius:9px 9px 0 0;background:transparent;color:var(--kh-muted);font-family:inherit;font-size:11.5px;font-weight:700;line-height:1;padding:8px 6px 9px}
        .kh-tab:hover{background:var(--kh-bot);color:var(--kh-text)}
        .kh-tab.active{background:var(--kh-accent-soft);border-color:var(--kh-line);color:var(--kh-accent-dark)}
        .kh-pane{flex:1;min-height:0;display:flex;flex-direction:column}
        .kh-chat{flex:1;min-height:0;display:flex;flex-direction:column}
        .kh-guide{flex:1;min-height:0;overflow-y:auto;padding:12px;background:var(--kh-wall)}
        .kh-guide-card{padding:12px;border:1px solid var(--kh-line);border-radius:14px;background:var(--kh-surface);box-shadow:0 6px 16px rgba(15,25,60,.05)}
        .kh-guide-card h3{margin:0;font-size:14px;line-height:1.3}
        .kh-guide-summary{margin:6px 0 0;font-size:11.5px;line-height:1.5;color:var(--kh-muted)}
        .kh-guide-section{margin-top:12px}
        .kh-guide-section h4{margin:0 0 6px;font-size:10px;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--kh-accent-dark)}
        .kh-guide-section p{margin:0;font-size:11.5px;line-height:1.55}
        .kh-step-list{list-style:none;counter-reset:kh-step;margin:0;padding:0;display:grid;gap:8px}
        .kh-step-list li{counter-increment:kh-step;display:grid;grid-template-columns:20px 1fr;gap:7px;font-size:11.5px;line-height:1.5}
        .kh-step-list li::before{content:counter(kh-step);display:grid;place-items:center;width:20px;height:20px;border-radius:50%;background:var(--kh-accent);color:#fff;font-size:10px;font-weight:800;line-height:1}
        .kh-detail-list{margin:0;display:grid;gap:8px}
        .kh-detail-list div{padding:8px 10px;border-radius:10px;background:var(--kh-bot)}
        .kh-detail-list dt{font-size:10px;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--kh-accent-dark)}
        .kh-detail-list dd{margin:3px 0 0;font-size:11.5px;line-height:1.5}
        .kh-guide-links{margin-top:12px;display:grid;gap:5px}
        .kh-guide-links button{display:flex;align-items:center;justify-content:space-between;gap:8px;width:100%;padding:8px 10px;border:1px solid var(--kh-line);border-radius:10px;background:var(--kh-surface);color:var(--kh-text);font-family:inherit;font-size:11.5px;font-weight:700;line-height:1.2;text-align:left}
        .kh-guide-links button:hover{background:var(--kh-accent-soft);color:var(--kh-accent-dark)}
        .kh-guide-links button.active{border-color:var(--kh-accent);background:var(--kh-accent-soft);color:var(--kh-accent-dark)}
        .kh-guide-links button span:last-child{font-size:13px;color:var(--kh-accent)}

        #kh-log{
            flex:1;min-height:0;overflow-y:auto;padding:14px 14px 6px;display:flex;flex-direction:column;gap:6px;
            background:var(--kh-wall);
            background-image:radial-gradient(circle at 1px 1px,rgba(120,135,180,.14) 1px,transparent 0);
            background-size:18px 18px;
        }
        .kh-day{align-self:center;font-size:11px;color:var(--kh-muted);background:var(--kh-surface);border:1px solid var(--kh-line);padding:3px 10px;border-radius:999px;margin:2px 0 6px}
        .kh-row{display:flex;gap:8px;align-items:flex-end;max-width:100%}
        .kh-row.bot{align-items:flex-start}
        .kh-row.bot .kh-av{margin-top:2px}
        .kh-row.me{justify-content:flex-end}
        .kh-av{width:28px;height:28px;border-radius:50%;background:var(--kh-accent);color:#fff;font-weight:800;font-size:13px;display:grid;place-items:center;flex:none;overflow:hidden}
        .kh-av img{width:100%;height:100%;object-fit:contain;background:#fff}
        .kh-msg{max-width:90%;padding:8px 11px 5px;border-radius:15px;font-size:12.5px;line-height:1.5;overflow-wrap:anywhere}
        .kh-row.bot .kh-msg{background:var(--kh-bot);color:var(--kh-text);border-bottom-left-radius:4px}
        .kh-row.me .kh-msg{background:linear-gradient(135deg,#ef5b3c,#f58a4c);color:#fff;border-bottom-right-radius:4px}
        .kh-msg p{margin:0 0 5px}
        .kh-msg ul,.kh-msg ol{margin:0 0 5px;padding-left:16px}
        .kh-msg li{margin:2px 0}
        .kh-msg a{color:var(--kh-accent);font-weight:700;text-decoration:underline}
        .kh-row.me .kh-msg a{color:#fff}
        .kh-msg button{display:block;margin:3px 0;padding:0;border:0;background:none;color:var(--kh-accent);font-family:inherit;font-size:11px;font-weight:700;line-height:1;text-align:left;text-decoration:underline}
        .kh-answer-note{padding:5px 0;border-top:1px solid rgba(100,116,139,.22);font-size:11.5px}
        .kh-page-links{margin-top:6px;padding-top:6px;border-top:1px solid rgba(100,116,139,.22);font-size:11px}
        .kh-page-links a{display:inline-block;margin:2px 5px 0 0;padding:3px 7px;border-radius:999px;background:rgba(234,90,65,.1);color:var(--kh-accent);text-decoration:none}
        .kh-opts{display:flex;flex-wrap:wrap;gap:5px;margin-top:5px;align-items:center}
        .kh-opts button{display:inline-block;margin:0;border:1px solid var(--kh-line);background:var(--kh-surface);color:var(--kh-accent);border-radius:999px;padding:4px 8px;font-family:inherit;font-size:10.5px;font-weight:700;line-height:1}
        .kh-wa{display:inline-flex;align-items:center;margin:5px 5px 0 0;padding:6px 10px;border-radius:999px;background:#22c55e;color:#fff;font-size:11px;font-weight:700;text-decoration:none}
        .kh-email{background:#3b82f6}
        .kh-tag-row{display:flex;flex-wrap:wrap;gap:4px;margin-top:5px}
        .kh-tag{padding:3px 7px;border-radius:999px;background:var(--kh-bot);color:var(--kh-muted);font-size:10px;text-decoration:none}
        .kh-tag:hover{background:var(--kh-accent-soft);color:var(--kh-accent)}
        .kh-feedback{display:flex;gap:7px;margin-top:6px;font-size:10.5px;color:var(--kh-muted)}
        .kh-feedback button{border:0;background:none;color:var(--kh-accent);font-family:inherit;font-size:10.5px;font-weight:700;line-height:1;text-decoration:underline}
        .kh-meta{display:flex;justify-content:flex-end;align-items:center;gap:4px;font-size:10px;opacity:.7;margin-top:2px}
        .kh-tick{width:15px;height:9px}
        .kh-tick path{fill:none;stroke:#cfe6ff;stroke-width:1.6;stroke-linecap:round;stroke-linejoin:round}
        .kh-typing .kh-msg{padding:10px 12px}
        .kh-typing span{display:inline-block;width:6px;height:6px;margin:0 2px;border-radius:50%;background:var(--kh-muted);animation:kh-bounce 1.1s infinite ease-in-out}
        .kh-typing span:nth-child(2){animation-delay:.15s}.kh-typing span:nth-child(3){animation-delay:.3s}
        .kh-typing-inline::after{content:'▌';color:var(--kh-accent);animation:kh-caret .7s steps(2,start) infinite}
        @keyframes kh-bounce{0%,60%,100%{transform:translateY(0);opacity:.5}30%{transform:translateY(-4px);opacity:1}}
        @keyframes kh-caret{0%,100%{opacity:.2}50%{opacity:1}}
        .kh-sugg{padding:6px 10px 3px;border-top:1px solid var(--kh-line);background:var(--kh-surface)}
        .kh-sugg small{display:block;font-size:10.5px;font-weight:700;color:var(--kh-muted);margin-bottom:5px}
        .kh-chips{display:flex;flex-wrap:wrap;gap:5px;max-height:56px;overflow-y:auto;padding-bottom:3px;scrollbar-width:thin}
        .kh-chip{flex:none;max-width:100%;overflow:hidden;text-overflow:ellipsis;border:1px solid var(--kh-accent);background:var(--kh-surface);color:var(--kh-accent-dark);border-radius:999px;font-family:inherit;font-size:10.5px;font-weight:700;line-height:1;padding:6px 9px;white-space:nowrap;transition:background .15s ease,color .15s ease,border-color .15s ease}
        .kh-chip:hover{background:var(--kh-accent);color:#fff;border-color:var(--kh-accent)}
        .kh-chip:first-child{background:var(--kh-accent-soft);border-color:var(--kh-accent);color:var(--kh-accent-dark)}
        .kh-form{display:flex;align-items:center;gap:7px;padding:7px 10px 8px;background:var(--kh-surface)}
        .kh-form input{flex:1;min-width:0;border:1px solid var(--kh-line);background:var(--kh-bg);color:var(--kh-text);border-radius:999px;padding:9px 13px;font-family:inherit;font-size:13px}
        .kh-form input:focus{border-color:var(--kh-accent)}
        .kh-send{width:36px;height:36px;border:0;border-radius:50%;background:linear-gradient(135deg,#ef5b3c,#f58a4c);color:#fff;display:grid;place-items:center;flex:none}
        .kh-send:disabled{opacity:.45;cursor:default}
        .kh-send svg{width:16px;height:16px}
        .kh-foot{padding:0 12px 8px;font-size:10.5px;color:var(--kh-muted);background:var(--kh-surface)}
        .kh-foot a{color:var(--kh-accent);font-weight:700;text-decoration:underline}

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

    <section id="kh-panel" role="dialog" aria-label="{{ __('Kairo assistant') }}" :aria-hidden="open ? 'false' : 'true'">
        <header class="kh-head">
            <div class="kh-logo" aria-hidden="true"><img src="{{ $faviconUrl }}" alt=""></div>
            <div class="kh-title">
                <b>{{ __('Kairo assistant') }}</b>
                <span><i></i>{{ $isDemo ? __('Demo workspace · answers instantly') : __('Online · answers instantly') }}</span>
            </div>
            <button class="kh-btn" type="button" @click="window.kairoAssistantReset && window.kairoAssistantReset()">{{ __('Start over') }}</button>
            <button class="kh-btn kh-x" type="button" @click="open = false" aria-label="{{ __('Close help') }}">✕</button>
        </header>

        @php
            $guide = $this->guide();
            $guideSteps = $this->guideSteps();
        @endphp

        <div class="kh-tabs" role="tablist" aria-label="{{ __('Help sections') }}">
            <button class="kh-tab" type="button" role="tab" aria-controls="v-chat" :class="tab === 'assistant' ? 'active' : ''" :aria-selected="tab === 'assistant' ? 'true' : 'false'" @click="tab = 'assistant'">{{ __('Assistant') }}</button>
            <button class="kh-tab" type="button" role="tab" aria-controls="v-guide" :class="tab === 'guide' ? 'active' : ''" :aria-selected="tab === 'guide' ? 'true' : 'false'" @click="tab = 'guide'">{{ __('Help guide') }}</button>
        </div>

        <div class="kh-pane kh-chat" id="v-chat" role="tabpanel" x-show="tab === 'assistant'">
            <div id="kh-log" aria-live="polite" wire:ignore></div>
            <div class="kh-sugg">
                <small>{{ __('Suggested questions for this page') }}</small>
                <div class="kh-chips" id="kh-chips" wire:ignore></div>
            </div>
            <form class="kh-form" id="kh-form" autocomplete="off">
                <input id="kh-input" type="text" placeholder="{{ __('Ask about any page or workflow…') }}" aria-label="{{ __('Type your question') }}">
                <button class="kh-send" id="kh-send" type="submit" aria-label="{{ __('Send') }}" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </button>
            </form>
            <div class="kh-foot">{{ __('For more detailed instruction, see') }} <a href="{{ $helpCentreUrl }}" target="_blank" rel="noopener">{{ __('Help Centre') }}</a>.</div>
        </div>

        <div class="kh-pane" id="v-guide" role="tabpanel" x-show="tab === 'guide'">
            <div class="kh-guide">
                <div class="kh-guide-card">
                    <h3>{{ $this->guideTitle() }}</h3>
                    @if(! empty($guide['summary']))
                        <p class="kh-guide-summary">{{ $guide['summary'] }}</p>
                    @endif

                    @if($guideSteps !== [])
                        <section class="kh-guide-section">
                            <h4>{{ __('Steps') }}</h4>
                            <ol class="kh-step-list">
                                @foreach($guideSteps as $step)
                                    <li><span>{{ $step }}</span></li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    @if(! empty($guide['details']))
                        <section class="kh-guide-section">
                            <h4>{{ __('Good to know') }}</h4>
                            <dl class="kh-detail-list">
                                @foreach($guide['details'] as $label => $detail)
                                    <div>
                                        <dt>{{ $label }}</dt>
                                        <dd>{{ $detail }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </section>
                    @endif

                    @if($guideLinks !== [])
                        <section class="kh-guide-section">
                            <h4>{{ __('Guides and related pages') }}</h4>
                            <div class="kh-guide-links">
                                @foreach($guideLinks as $link)
                                    <button type="button" @class(['active' => $link['path'] === $currentPath]) aria-pressed="{{ $link['path'] === $currentPath ? 'true' : 'false' }}" wire:click="showGuideFor(@js($link['path']), @js($link['class']))">
                                        <span>{{ $link['label'] }}</span><span aria-hidden="true">›</span>
                                    </button>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endif
</div>

@if($visible)
@script
<script type="module">
let faqEngine = null;

async function loadFaqEngine() {
    if (!faqEngine) faqEngine = await import('/kfaq-engine.js');
    return faqEngine;
}

const state = window.__kairoAssistant || (window.__kairoAssistant = {
    booted: false,
    busy: false,
    kbLoaded: false,
    kbError: false,
    kb: [],
    messages: [],
    previousTopic: null,
    suggestions: [],
    pageCategory: '',
    guide: null,
    systemKb: [],
    userId: 'guest',
    docsUrl: '',
    helpCentreUrl: '',
    faviconUrl: ''
});

const locale = @js(app()->getLocale());
const whatsAppUrl = 'https://wa.me/263786366855';
const emailUrl = 'mailto:support@kairocore.co.zw';
const ui = {
    didYouMean: @js(__('Did you mean…?')),
    notExact: @js(__('I do not have that exact answer yet, but the team can help with school-specific questions. Message us on WhatsApp and we will point you to the right module or guide.')),
    pricingFallback: @js(__('I do not have exact pricing details here, but Kairo CORE plans are tailored to your school size, modules and support needs. Message the team on WhatsApp or use the contact form and we will recommend the right fit.')),
    messageWhatsApp: @js(__('Message us on WhatsApp')),
    emailUs: @js(__('Email us')),
    stepsOnThisPage: @js(__('Steps on this page:')),
    startWith: @js(__('Start with:')),
    connectedTo: @js(__('How it connects:')),
    fromHelp: @js(__('From the Help Centre')),
    helpful: @js(__('Helpful')),
    notHelpful: @js(__('Not helpful')),
    thanks: @js(__('Thanks for the feedback')),
    noAnswerYet: @js(__('I don’t have that one yet. Message the team on WhatsApp and a person will help.'))
};

state.kbError ??= false;

const categoryLinks = {
    students: { label: @js(__('Open students')), url: '/workspace/students' },
    finance: { label: @js(__('Open invoices')), url: '/workspace/invoices' },
    exams: { label: @js(__('Open exams')), url: '/workspace/exams' },
    reports: { label: @js(__('Open reports')), url: '/workspace/reports' }
};
const categoryNotes = {
    students: @js(__('A single student profile connects guardians, attendance, fees, health notes and results.')),
    finance: @js(__('Fees, invoices and payments share the same student record as admissions and academics.')),
    exams: @js(__('Marks flow from assessment to report card, so teachers capture once and reports update automatically.')),
    reports: @js(__('Because data is captured once across modules, reports reflect the latest enrolment, fee and academic position.'))
};
const tagLinks = {
    docs: () => ({ label: @js(__('Full documentation')), url: state.docsUrl }),
    documentation: () => ({ label: @js(__('Full documentation')), url: state.docsUrl }),
    help: () => ({ label: @js(__('Help Centre')), url: state.helpCentreUrl }),
    'help-centre': () => ({ label: @js(__('Help Centre')), url: state.helpCentreUrl }),
    whatsapp: () => ({ label: @js(__('WhatsApp')), url: whatsAppUrl }),
    contact: () => ({ label: @js(__('Contact the team')), url: '/contact#contact-form' })
};

const el = (id) => document.getElementById(id);
const clock = () => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
const normalizeText = (value) => String(value ?? '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
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

function baseLinksHtml() {
    return `<div class="kh-page-links"><a href="${escapeHtml(state.helpCentreUrl)}" target="_blank" rel="noopener">{{ __('Help Centre') }}</a></div>`;
}

function renderLinksHtml(links, intro = @js(__('Open next:'))) {
    const list = (links || []).filter((link) => link?.url).slice(0, 4);
    if (!list.length) return '';

    return `<div class="kh-page-links"><b>${escapeHtml(intro)}</b> ` + list.map((link) => `<a href="${escapeHtml(safeHref(link.url))}" target="_blank" rel="noopener">${escapeHtml(link.label || link.url)}</a>`).join('') + '</div>';
}

function renderTagsHtml(tags) {
    if (!tags?.length) return '';

    return '<div class="kh-tag-row">' + tags.map((tag) => {
        const resolver = tagLinks[String(tag).toLowerCase()];
        const link = typeof resolver === 'function' ? resolver() : null;
        return link?.url
            ? `<a class="kh-tag" href="${escapeHtml(safeHref(link.url))}" target="_blank" rel="noopener">${escapeHtml(tag)}</a>`
            : `<span class="kh-tag">${escapeHtml(tag)}</span>`;
    }).join('') + '</div>';
}

function waLinkHtml(extraClass = '') {
    return `<a class="kh-wa ${extraClass}" href="${whatsAppUrl}" target="_blank" rel="noopener">${escapeHtml(ui.messageWhatsApp)}</a>`;
}

function emailLinkHtml(extraClass = '') {
    return `<a class="kh-wa kh-email ${extraClass}" href="${emailUrl}">${escapeHtml(ui.emailUs)}</a>`;
}

function renderRichHtml(text) {
    return String(text || '')
        .split(/\n{2,}/)
        .map((block) => {
            const lines = block.split('\n').map((line) => line.trim()).filter(Boolean);
            if (!lines.length) return '';

            if (lines.length > 1 && lines.every((line) => /^\d+[.)]\s+/.test(line))) {
                return '<ol>' + lines.map((line) => `<li>${linkify(line.replace(/^\d+[.)]\s+/, ''))}</li>`).join('') + '</ol>';
            }

            if (lines.length > 1 && lines.every((line) => /^[-•]\s+/.test(line))) {
                return '<ul>' + lines.map((line) => `<li>${linkify(line.replace(/^[-•]\s+/, ''))}</li>`).join('') + '</ul>';
            }

            return `<p>${linkify(lines.join(' '))}</p>`;
        })
        .join('');
}

function questionLabel(item) {
    if (typeof item === 'string') return item;

    const labels = {
        'what-cover': @js(__('What modules and features does Kairo CORE include?')),
    };

    return labels[item?.id] || item?.question || '';
}

function renderFaqEntry(entry) {
    if (entry?.needsOwnerAnswer) return `<p>${escapeHtml(ui.noAnswerYet)}</p>${waLinkHtml()}${emailLinkHtml()}`;

    let html = renderRichHtml(entry?.answer || '');
    const note = categoryNotes[entry?.category];
    if (note) html += `<div class="kh-answer-note">${escapeHtml(note)}</div>`;

    html += renderLinksHtml([...(entry?.links || []), categoryLinks[entry?.category]]);
    html += renderTagsHtml(entry?.tags);

    if (entry?.source?.url?.startsWith('/docs')) {
        html += `<div class="kh-page-links"><a href="${escapeHtml(safeHref(entry.source.url))}" target="_blank" rel="noopener">${escapeHtml(ui.fromHelp)} · ${escapeHtml(entry.source.title || '')}</a></div>`;
    }

    html += `<div class="kh-feedback"><span>{{ __('Was this helpful?') }}</span><button type="button" data-faq-feedback="yes" data-question="${escapeHtml(entry?.question || '')}">${escapeHtml(ui.helpful)}</button><button type="button" data-faq-feedback="no" data-question="${escapeHtml(entry?.question || '')}">${escapeHtml(ui.notHelpful)}</button></div>`;

    return html + baseLinksHtml();
}

function fallbackHtml(result, text) {
    const isPricing = /\b(price|pricing|cost|quote|fees?)\b/i.test(text);
    const module = result?.module;
    let html = module?.summary ? `<p><b>${escapeHtml(module.name)}</b> — ${escapeHtml(module.summary)}</p>` : '';
    html += `<p>${escapeHtml(isPricing ? ui.pricingFallback : ui.notExact)}</p>`;

    const candidates = (result?.candidates || []).filter((item) => !result?.entry || item.id !== result.entry.id).slice(0, 3);
    if (candidates.length) {
        html += `<div class="kh-opts"><b>${escapeHtml(ui.didYouMean)}</b>`;
        candidates.forEach((item) => {
            const label = questionLabel(item);
            html += `<button type="button" data-question="${escapeHtml(label)}">${escapeHtml(label)}</button>`;
        });
        html += '</div>';
    }

    html += waLinkHtml() + emailLinkHtml();
    if (isPricing) {
        html += `<a class="kh-wa" href="${escapeHtml(safeHref('/contact#contact-form'))}" target="_blank" rel="noopener">{{ __('Contact form') }}</a>`;
    }

    return html + baseLinksHtml();
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
    const words = htmlToText(html).split(/\s+/).filter(Boolean);
    if (!words.length) {
        bubble.innerHTML = html + meta;
        scrollLog();
        return;
    }

    let index = 0;
    const delay = words.length > 90 ? 10 : 30;
    const timer = setInterval(() => {
        index += 1;
        target.textContent = words.slice(0, index).join(' ');
        scrollLog();

        if (index >= words.length) {
            clearInterval(timer);
            bubble.innerHTML = html + meta;
            scrollLog();
        }
    }, delay);
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
    (questions || []).slice(0, 4).forEach((question) => {
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

    try {
        const response = await fetch('/kairo-system-knowledge.json');
        if (response.ok) state.systemKb = await response.json();
    } catch (error) {}

    state.kbError = !Array.isArray(state.kb) || state.kb.length === 0;
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

function guideIntent(text) {
    const question = normalizeText(text);
    const guide = state.guide;

    if (!guide?.title || !question) return null;
    if (/\b(how long|cost|price|pricing|quote|book a demo|free trial|offline|accounting|quickbooks|xero)\b/.test(question)) return null;
    if (/\b(report|reports|invoice|invoices|payment|payments|marks|grades|homework|application|applications|troubleshooting|issue|issues|error|errors)\b/.test(question)) return null;

    const titleWords = normalizeText(guide.title).split(' ').filter((word) => word.length > 3);
    const mentionsGuide = titleWords.some((word) => question.includes(word)) || /\b(this page|current page|here)\b/.test(question);
    const genericGuide = /\b(what does it cover|what does it do|what is this page|how do i use it|how does it connect|what should i do first|show me the steps|walk me through)\b/.test(question);

    if (!mentionsGuide && !genericGuide) return null;
    if (/\b(connect|connected|relationship|cross module|other modules|related modules)\b/.test(question)) return 'connect';
    if (/\b(first|start|begin|initial)\b/.test(question)) return 'start';
    if (/\b(use|workflow|step|steps|process|walk me through|show me)\b/.test(question)) return 'workflow';

    return 'overview';
}

function guideAnswerHtml(text) {
    const intent = guideIntent(text);
    if (!intent) return null;

    const guide = state.guide || {};
    const steps = (guide.steps || []).filter(Boolean);
    const details = guide.details || [];
    let html = `<p><b>${escapeHtml(guide.title || '')}</b>${guide.summary ? ` — ${escapeHtml(guide.summary)}` : ''}</p>`;

    if (intent === 'connect') {
        const connections = details.filter((item) => /relationship|cross|connect|link|integration/i.test(item.label || ''));
        const shown = (connections.length ? connections : details).slice(0, 3);

        if (shown.length) {
            html += `<p>${escapeHtml(ui.connectedTo)}</p><ul>` + shown.map((item) => `<li><b>${escapeHtml(item.label)}:</b> ${escapeHtml(item.detail)}</li>`).join('') + '</ul>';
        }
    } else {
        const shown = intent === 'start' ? steps.slice(0, 2) : intent === 'workflow' ? steps : steps.slice(0, 3);

        if (shown.length) {
            html += `<p>${escapeHtml(intent === 'start' ? ui.startWith : ui.stepsOnThisPage)}</p><ol>` + shown.map((step) => `<li>${escapeHtml(step)}</li>`).join('') + '</ol>';
        }
    }

    return html + baseLinksHtml();
}

async function ask(question) {
    const text = String(question || el('kh-input')?.value || '').trim().slice(0, 200);
    if (!text || state.busy) return;

    state.busy = true;
    appendRow('me', escapeHtml(text));
    setChips(state.suggestions);

    const input = el('kh-input');
    const send = el('kh-send');
    if (input) input.value = '';
    if (send) send.disabled = true;

    const typing = typingRow();

    try {
        await ensureKb();
        const engine = await loadFaqEngine();

        if (!reducedMotion()) await new Promise((resolve) => setTimeout(resolve, 750));
        typing.remove();

        const guideHtml = guideAnswerHtml(text);
        if (guideHtml) {
            appendAnswer(guideHtml);
            state.previousTopic = state.pageCategory || state.previousTopic;
            return;
        }

        const result = engine.matchQuestion(text, state.kb || [], state.previousTopic, locale, {
            category: state.pageCategory,
            title: state.guide?.title || '',
            modules: state.systemKb || []
        });

        if (result.type === 'smalltalk') {
            appendAnswer(`<p>${escapeHtml(result.smallTalk)}</p>${baseLinksHtml()}`);
            setChips(state.suggestions);
            return;
        }

        if (result.type === 'medium') {
            const candidates = (result.candidates || []).map(questionLabel).filter(Boolean);
            let html = `<p>${escapeHtml(ui.didYouMean)}</p><div class="kh-opts">`;
            candidates.forEach((item) => {
                html += `<button type="button" data-question="${escapeHtml(item)}">${escapeHtml(item)}</button>`;
            });
            html += '</div>' + waLinkHtml() + emailLinkHtml() + baseLinksHtml();
            appendAnswer(html);
            return;
        }

        if (result.type === 'fallback' || !result.entry) {
            engine.logUnmatched(text);
            appendAnswer(fallbackHtml(result, text));
            setChips(state.suggestions);
            return;
        }

        state.previousTopic = result.entry.category;
        appendAnswer(renderFaqEntry(result.entry));
    } catch (error) {
        typing.remove();
        appendAnswer(`<p>{{ __('That answer was interrupted. Please try again.') }}</p>${waLinkHtml()}${emailLinkHtml()}${baseLinksHtml()}`);
        if (faqEngine) faqEngine.logUnmatched(text);
    } finally {
        state.busy = false;
        if (send) send.disabled = !input?.value.trim();
    }
}

function resetConversation() {
    state.messages = [];
    state.previousTopic = null;
    const log = el('kh-log');
    if (log) log.innerHTML = '<div class="kh-day">{{ __('Today') }}</div>';
    appendRow('bot', `<p>{{ __('Hi, I am the Kairo assistant. Ask me anything about Kairo CORE.') }}</p>`);
    if (state.kbError) appendAnswer(`<p>${escapeHtml(ui.noAnswerYet)}</p>${waLinkHtml()}${emailLinkHtml()}`);
    setChips(state.suggestions);
}

window.kairoAssistantFocus = () => el('kh-input')?.focus();
window.kairoAssistantReset = resetConversation;
window.kairoAssistantSetContext = (suggestions, pageCategory, faviconUrl, docsUrl, helpCentreUrl, guide) => {
    state.suggestions = suggestions || [];
    state.pageCategory = pageCategory || state.pageCategory;
    state.faviconUrl = faviconUrl || state.faviconUrl;
    state.docsUrl = docsUrl || state.docsUrl;
    state.helpCentreUrl = helpCentreUrl || state.helpCentreUrl;
    state.guide = guide || state.guide;

    if (state.booted) {
        renderMessages();
        setChips(state.suggestions);
    }
};

window.kairoAssistantSetContext(
    @js($suggestions),
    @js($pageCategory),
    @js($faviconUrl),
    @js($docsUrl),
    @js($helpCentreUrl),
    @js($this->guideContext())
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

    document.addEventListener('kh-guide-updated', (event) => {
        const payload = event.detail || {};
        const detail = Array.isArray(payload) ? (payload[0] || {}) : payload;
        window.kairoAssistantSetContext(
            detail.suggestions,
            detail.pageCategory,
            detail.faviconUrl,
            detail.docsUrl,
            detail.helpCentreUrl,
            detail.guide
        );
    });

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
        const chip = event.target.closest('#kh-chips .kh-chip');
        if (chip) {
            ask(chip.dataset.question);
            return;
        }

        const feedback = event.target.closest('#kh-log button[data-faq-feedback]');
        if (feedback) {
            const container = feedback.closest('.kh-feedback');
            loadFaqEngine().then((engine) => engine.logFeedback(feedback.dataset.question, feedback.dataset.faqFeedback === 'yes')).catch(() => {});
            if (container) container.textContent = ui.thanks;
            return;
        }

        const followUp = event.target.closest('#kh-log button[data-question]');
        if (followUp) ask(followUp.dataset.question);
    });
}

ensureKb().then(() => {
    renderMessages();
    if (state.messages.length === 0) resetConversation();
    else setChips(state.suggestions);
});
</script>
@endscript
@endif
