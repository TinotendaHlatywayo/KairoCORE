<style>
.kfaq-section { padding: 0; }
.kfaq {
    display: grid;
    grid-template-columns: 5fr 7fr;
    gap: 64px;
    align-items: center;
    padding: 48px 0;
}
.kfaq h2 {
    font-size: clamp(30px, 3vw, 42px);
    line-height: 1.08;
    letter-spacing: -.03em;
    font-weight: 800;
    margin: 0 0 18px;
}
.kfaq .lead { color: var(--text-2); font-size: 17px; max-width: 36ch; margin: 0; }
.kfaq .wa {
    display: inline-block; margin-top: 28px; padding: 13px 22px; border-radius: 999px;
    background: var(--navy); color: var(--bg); font-weight: 700; text-decoration: none; transition: transform .15s;
}
.kfaq .wa:hover { transform: translateY(-2px); }
.kfaq .wa:focus-visible, .kfaq .chip:focus-visible, .kfaq .reset:focus-visible, .kfaq .kfaq-input:focus-visible, .kfaq .send:focus-visible {
    outline: 3px solid var(--coral-strong); outline-offset: 3px;
}
.kfaq .chat {
    background: var(--surface, #fff); border: 1px solid var(--border); border-radius: 28px;
    box-shadow: 0 40px 70px -40px rgba(27,36,64,.28); overflow: hidden;
    display: flex; flex-direction: column;
}
.kfaq .head { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid var(--border); }
.kfaq .avatar { width: 42px; height: 42px; border-radius: 14px; background: #fff; display: grid; place-items: center; overflow: hidden; flex: none; }
.kfaq .avatar img { width: 100%; height: 100%; object-fit: contain; }
.kfaq .head b { display: block; font-size: 15px; line-height: 1.2; }
.kfaq .head small { display: flex; align-items: center; gap: 6px; color: var(--text-muted); font-size: 12.5px; }
.kfaq .live { width: 7px; height: 7px; border-radius: 50%; background: #2fbf71; }
.kfaq .reset { margin-left: auto; font: inherit; font-size: 13px; font-weight: 600; color: var(--text-muted); background: none; border: 1px solid var(--border); border-radius: 999px; padding: 6px 12px; cursor: pointer; }
.kfaq .reset:hover { color: var(--navy); border-color: var(--navy); }
.kfaq .msgs { height: 390px; overflow-y: auto; padding: 22px 20px; display: flex; flex-direction: column; gap: 12px; scroll-behavior: smooth; }
.kfaq .m { max-width: 86%; padding: 12px 16px; border-radius: 20px; font-size: 15px; animation: kfaqIn .3s ease both; }
.kfaq .bot { background: var(--bg-alt); border-bottom-left-radius: 6px; align-self: flex-start; }
.kfaq .me { background: var(--coral-strong); color: #fff; font-weight: 600; border-bottom-right-radius: 6px; align-self: flex-end; }
.kfaq .typing { display: flex; gap: 5px; padding: 16px 18px; }
.kfaq .typing i { width: 7px; height: 7px; border-radius: 50%; background: var(--text-muted); animation: kfaqBob 1s infinite; }
.kfaq .typing i:nth-child(2) { animation-delay: .15s; }
.kfaq .typing i:nth-child(3) { animation-delay: .3s; }
@keyframes kfaqIn { from { opacity: 0; transform: translateY(8px); } }
@keyframes kfaqBob { 0%, 60%, 100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-4px); opacity: 1; } }
.kfaq .answer-meta { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; align-items: center; }
.kfaq .tag { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; background: var(--bg-alt); color: var(--text-muted); border: 1px solid var(--border); }
.kfaq .source { font-size: 12px; color: var(--text-muted); }
.kfaq .source a { color: var(--coral-strong); font-weight: 700; text-decoration: none; }
.kfaq .feedback { display: flex; gap: 6px; margin-left: auto; }
.kfaq .feedback button { border: 1px solid var(--border); background: var(--bg); border-radius: 999px; padding: 4px 8px; cursor: pointer; font-size: 12px; }
.kfaq .followups { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.kfaq .followups .chip { font-size: 12px; }
.kfaq .quick { border-top: 1px solid var(--border); padding: 12px 20px; max-height: 112px; overflow-y: auto; }
.kfaq .chip {
    font: inherit; font-size: 14px; font-weight: 600; color: var(--navy); background: var(--bg);
    border: 1px solid var(--border); border-radius: 999px; padding: 9px 15px; cursor: pointer;
    transition: border-color .15s, color .15s, transform .15s;
}
.kfaq .chip:hover { border-color: var(--coral-strong); color: var(--coral-strong); transform: translateY(-1px); }
.kfaq .done { margin: 0; font-size: 14px; color: var(--text-muted); }
.kfaq .done a { color: var(--coral-strong); font-weight: 700; }
.kfaq .composer { display: flex; gap: 8px; padding: 14px 20px; border-top: 1px solid var(--border); }
.kfaq .kfaq-input { flex: 1; border: 1px solid var(--border); border-radius: 999px; padding: 10px 16px; font: inherit; font-size: 14px; background: var(--bg); color: var(--text); }
.kfaq .send { border: 0; border-radius: 999px; padding: 10px 16px; background: var(--coral-strong); color: #fff; font-weight: 700; cursor: pointer; }
.kfaq .fallback-link { display: inline-block; margin-top: 8px; }
@media (max-width: 860px) {
    .kfaq { grid-template-columns: 1fr; gap: 36px; padding: 32px 0; }
    .kfaq .msgs { height: 340px; }
    .kfaq .m { max-width: 92%; }
}
@media (prefers-reduced-motion: reduce) {
    .kfaq * { animation: none !important; transition: none !important; }
    .kfaq .msgs { scroll-behavior: auto; }
}
</style>

<section id="faq" class="kfaq-section">
    <div class="w">
        <div class="kfaq">
            <div>
                <div class="ey">{{ marketing_content('home_faq_badge') ?: __('FAQ') }}</div>
                <h2>{{ marketing_content('home_faq_title') ?: __('Questions school leaders ask us') }}</h2>
                <p class="lead">{{ marketing_content('home_faq_subtitle') ?: __('Ask the Kairo assistant anything about running your school.') }}</p>
                <a class="wa" href="https://wa.me/{{ preg_replace('/\D+/', '', Modules\SaaS\Models\PlatformSetting::get('contact', 'whatsapp') ?: '263786366855') }}" target="_blank" rel="noopener">{{ __('Message us on WhatsApp') }}</a>
            </div>

            <section class="chat" aria-label="{{ __('Kairo assistant') }}">
                <div class="head">
                    <div class="avatar" aria-hidden="true">
                        <img src="{{ platform_logo_url() }}" alt="{{ platform_name() }}">
                    </div>
                    <div>
                        <b>{{ __('Kairo assistant') }}</b>
                        <small><span class="live"></span>{{ __('Online · answers instantly') }}</small>
                    </div>
                    <button class="reset" id="kfaq-reset" type="button">{{ __('Start over') }}</button>
                </div>
                <div class="msgs" id="kfaq-msgs" aria-live="polite"></div>
                <div class="quick" id="kfaq-quick"></div>
                <div class="composer">
                    <label for="kfaq-input" class="sr-only">{{ __('Ask a question') }}</label>
                    <input id="kfaq-input" class="kfaq-input" type="text" maxlength="200" placeholder="{{ __('Type your question...') }}" aria-label="{{ __('Ask a question') }}">
                    <button class="send" id="kfaq-send" type="button">{{ __('Send') }}</button>
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
const input = document.getElementById('kfaq-input');
const send = document.getElementById('kfaq-send');
const reset = document.getElementById('kfaq-reset');
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const suggestions = {!! json_encode($kfaqSuggestionQuestions) !!};
const askedSuggestions = new Set();
let kb = null;
let previousTopic = null;
let busy = false;

function addMessage(role, text) {
    const div = document.createElement('div');
    div.className = 'm ' + (role === 'user' ? 'me' : 'bot');
    div.textContent = text;
    msgs.appendChild(div);
    msgs.scrollTop = msgs.scrollHeight;
    return div;
}

function addAnswer(entry, secondIntent) {
    const answer = entry.needsOwnerAnswer
        ? 'I don’t have that one yet. Message the team on WhatsApp and a person will help.'
        : entry.answer;

    const typing = document.createElement('div');
    typing.className = 'm bot typing';
    typing.innerHTML = '<i></i><i></i><i></i>';
    msgs.appendChild(typing);
    msgs.scrollTop = msgs.scrollHeight;

    const delay = reduce ? 0 : Math.min(1400, Math.max(600, (answer || '').length * 8));
    setTimeout(() => {
        typing.remove();
        const bubble = document.createElement('div');
        bubble.className = 'm bot';
        msgs.appendChild(bubble);
        const words = (answer || '').split(/\s+/).filter(Boolean);
        let i = 0;
        const stream = () => {
            if (i >= words.length) {
                renderAnswerMeta(bubble, entry, secondIntent);
                return;
            }
            bubble.textContent = words.slice(0, ++i).join(' ');
            msgs.scrollTop = msgs.scrollHeight;
            if (!reduce) setTimeout(stream, 35);
            else stream();
        };
        if (reduce) {
            bubble.textContent = words.join(' ');
            renderAnswerMeta(bubble, entry, secondIntent);
        } else {
            stream();
        }
    }, delay);
}

function renderAnswerMeta(bubble, entry, secondIntent) {
    if (entry.needsOwnerAnswer) {
        const link = document.createElement('a');
        link.className = 'wa fallback-link';
        link.href = 'https://wa.me/263786366855';
        link.target = '_blank';
        link.rel = 'noopener';
        link.textContent = 'Message us on WhatsApp';
        bubble.appendChild(document.createElement('br'));
        bubble.appendChild(link);
        return;
    }

    const meta = document.createElement('div');
    meta.className = 'answer-meta';
    (entry.tags || []).forEach(tag => {
        const pill = document.createElement('span');
        pill.className = 'tag';
        pill.textContent = tag;
        meta.appendChild(pill);
    });
    if (entry.source && entry.source.url && String(entry.source.url).startsWith('/docs')) {
        const source = document.createElement('span');
        source.className = 'source';
        const a = document.createElement('a');
        a.href = entry.source.url;
        a.textContent = 'From the Help Centre: ' + entry.source.title;
        source.appendChild(a);
        meta.appendChild(source);
    }
    const feedback = document.createElement('div');
    feedback.className = 'feedback';
    const up = document.createElement('button');
    up.type = 'button';
    up.textContent = '👍';
    up.setAttribute('aria-label', 'Helpful');
    up.onclick = () => logFeedback(entry.question, true);
    const down = document.createElement('button');
    down.type = 'button';
    down.textContent = '👎';
    down.setAttribute('aria-label', 'Not helpful');
    down.onclick = () => { logFeedback(entry.question, false); logUnmatched(input.value || entry.question); };
    feedback.append(up, down);
    meta.appendChild(feedback);
    bubble.appendChild(meta);

    const followups = (entry.related || []).map(id => kb.find(item => item.id === id)).filter(Boolean).slice(0, 3);
    if (followups.length) {
        const wrap = document.createElement('div');
        wrap.className = 'followups';
        followups.forEach(item => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip';
            chip.textContent = item.question;
            chip.onclick = () => ask(item.question);
            wrap.appendChild(chip);
        });
        bubble.appendChild(wrap);
    }

    if (secondIntent) {
        const wrap = document.createElement('div');
        wrap.className = 'followups';
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip';
        chip.textContent = secondIntent.question;
        chip.onclick = () => ask(secondIntent.question);
        wrap.appendChild(chip);
        bubble.appendChild(wrap);
    }
}

function renderChips() {
    quick.innerHTML = '';
    const container = document.createElement('div');
    container.style.maxHeight = '112px';
    container.style.overflowY = 'auto';
    suggestions.forEach((question, index) => {
        if (askedSuggestions.has(question)) return;
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'chip';
        chip.textContent = question;
        chip.onclick = () => ask(question);
        container.appendChild(chip);
    });
    if (!container.children.length) {
        const p = document.createElement('p');
        p.className = 'done';
        p.innerHTML = '{{ __('That’s every common question. Still curious?') }} <a href="https://wa.me/{{ preg_replace("/\D+/", "", Modules\SaaS\Models\PlatformSetting::get("contact", "whatsapp") ?: "263786366855") }}" target="_blank" rel="noopener">{{ __('Message us on WhatsApp') }}</a>.';
        quick.appendChild(p);
    } else {
        quick.appendChild(container);
    }
}

function ask(text) {
    const value = (text || input.value || '').trim().slice(0, 200);
    if (!value || busy || !kb) return;
    busy = true;
    input.value = '';
    askedSuggestions.add(value);
    renderChips();
    addMessage('user', value);

    const result = matchQuestion(value, kb, previousTopic);
    if (result.smallTalk) {
        setTimeout(() => {
            addMessage('bot', result.smallTalk);
            busy = false;
        }, reduce ? 0 : 400);
        return;
    }
    if (result.type === 'medium') {
        setTimeout(() => {
            addMessage('bot', 'Did you mean…?');
            const wrap = document.createElement('div');
            wrap.className = 'followups';
            (result.candidates || []).slice(0, 3).forEach(candidate => {
                const chip = document.createElement('button');
                chip.type = 'button';
                chip.className = 'chip';
                chip.textContent = candidate.question;
                chip.onclick = () => ask(candidate.question);
                wrap.appendChild(chip);
            });
            msgs.appendChild(wrap);
            msgs.scrollTop = msgs.scrollHeight;
            busy = false;
        }, reduce ? 0 : 500);
        return;
    }
    if (result.type === 'fallback') {
        if (result.entry && result.entry.needsOwnerAnswer) logUnmatched(value);
        else if (!result.entry) logUnmatched(value);
        setTimeout(() => {
            addMessage('bot', 'I don’t have that one yet. Message the team on WhatsApp and a person will help.');
            const link = document.createElement('a');
            link.className = 'wa fallback-link';
            link.href = 'https://wa.me/263786366855';
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = 'Message us on WhatsApp';
            msgs.appendChild(link);
            msgs.scrollTop = msgs.scrollHeight;
            busy = false;
        }, reduce ? 0 : 500);
        return;
    }

    previousTopic = result.entry.category;
    addAnswer(result.entry, result.secondIntent);
    setTimeout(() => { busy = false; }, reduce ? 0 : 100);
}

async function loadKb() {
    try {
        const response = await fetch('/faq-knowledge.json', { credentials: 'same-origin' });
        kb = await response.json();
        start();
    } catch (error) {
        msgs.textContent = 'I don’t have that one yet. Message the team on WhatsApp and a person will help.';
    }
}

function start() {
    msgs.innerHTML = '';
    askedSuggestions.clear();
    previousTopic = null;
    addMessage('bot', 'Hi, I’m here to answer the questions school leaders ask most. Tap one below.');
    const firstQuestion = suggestions.length ? suggestions[0] : (kb && kb.length ? kb[0].question : '');
    if (firstQuestion) {
        addMessage('me', firstQuestion);
        askedSuggestions.add(firstQuestion);
        const result = matchQuestion(firstQuestion, kb, null);
        if (result.type === 'high' && result.entry && !result.entry.needsOwnerAnswer) {
            previousTopic = result.entry.category;
            addAnswer(result.entry, result.secondIntent);
        } else {
            addMessage('bot', 'I don’t have that one yet. Message the team on WhatsApp and a person will help.');
        }
    }
    renderChips();
}

const observer = new IntersectionObserver(entries => {
    if (entries.some(entry => entry.isIntersecting) && !kb) loadKb();
}, { rootMargin: '200px' });
observer.observe(document.getElementById('faq'));

send.addEventListener('click', () => ask());
input.addEventListener('keydown', event => {
    if (event.key === 'Enter') ask();
});
reset.addEventListener('click', () => {
    if (kb) start();
});

renderChips();
</script>
