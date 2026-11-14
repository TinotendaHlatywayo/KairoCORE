<style>
.kf-faq-chat {
    display: grid;
    grid-template-columns: 5fr 7fr;
    gap: 64px;
    align-items: center;
    max-width: 1180px;
    margin: 0 auto;
    padding: 80px 24px;
}
.kf-faq-chat h1 {
    font-size: clamp(34px, 5vw, 56px);
    line-height: 1.04;
    letter-spacing: -.03em;
    font-weight: 800;
    margin: 0 0 18px;
}
.kf-faq-chat .lead {
    color: var(--text-2);
    font-size: 17px;
    max-width: 36ch;
    margin: 0;
}
.kf-faq-chat .wa {
    display: inline-block;
    margin-top: 28px;
    padding: 13px 22px;
    border-radius: 999px;
    background: var(--navy);
    color: var(--bg);
    font-weight: 700;
    text-decoration: none;
    transition: transform .15s;
}
.kf-faq-chat .wa:hover { transform: translateY(-2px); }
.kf-faq-chat .chat {
    background: var(--surface, #fff);
    border: 1px solid var(--border);
    border-radius: 28px;
    box-shadow: 0 40px 70px -40px rgba(27,36,64,.28);
    overflow: hidden;
}
.kf-faq-chat .head {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
}
.kf-faq-chat .avatar {
    width: 42px;
    height: 42px;
    border-radius: 14px;
    background: #fff;
    display: grid;
    place-items: center;
    overflow: hidden;
    flex: none;
}
.kf-faq-chat .avatar img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}
.kf-faq-chat .head b { display: block; font-size: 15px; line-height: 1.2; }
.kf-faq-chat .head small {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-muted);
    font-size: 12.5px;
}
.kf-faq-chat .live {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #2fbf71;
}
.kf-faq-chat .reset {
    margin-left: auto;
    font: inherit;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-muted);
    background: none;
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 6px 12px;
    cursor: pointer;
}
.kf-faq-chat .reset:hover { color: var(--navy); border-color: var(--navy); }
.kf-faq-chat .msgs {
    height: 390px;
    overflow-y: auto;
    padding: 22px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
}
.kf-faq-chat .m {
    max-width: 86%;
    padding: 12px 16px;
    border-radius: 20px;
    font-size: 15px;
    animation: kfFaqIn .3s ease both;
}
.kf-faq-chat .bot {
    background: var(--bg-alt);
    border-bottom-left-radius: 6px;
    align-self: flex-start;
}
.kf-faq-chat .me {
    background: var(--coral-strong);
    color: #fff;
    font-weight: 600;
    border-bottom-right-radius: 6px;
    align-self: flex-end;
}
.kf-faq-chat .typing {
    display: flex;
    gap: 5px;
    padding: 16px 18px;
}
.kf-faq-chat .typing i {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: var(--text-muted);
    animation: kfFaqBob 1s infinite;
}
.kf-faq-chat .typing i:nth-child(2) { animation-delay: .15s; }
.kf-faq-chat .typing i:nth-child(3) { animation-delay: .3s; }
@keyframes kfFaqIn { from { opacity: 0; transform: translateY(8px); } }
@keyframes kfFaqBob { 0%, 60%, 100% { transform: translateY(0); opacity: .4; } 30% { transform: translateY(-4px); opacity: 1; } }
.kf-faq-chat .quick {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 16px 20px 20px;
    border-top: 1px solid var(--border);
}
.kf-faq-chat .chip {
    font: inherit;
    font-size: 14px;
    font-weight: 600;
    color: var(--navy);
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: 999px;
    padding: 9px 15px;
    cursor: pointer;
    transition: border-color .15s, color .15s, transform .15s;
}
.kf-faq-chat .chip:hover {
    border-color: var(--coral-strong);
    color: var(--coral-strong);
    transform: translateY(-1px);
}
.kf-faq-chat .done {
    margin: 0;
    font-size: 14px;
    color: var(--text-muted);
}
.kf-faq-chat .done a { color: var(--coral-strong); font-weight: 700; }
@media (max-width: 860px) {
    .kf-faq-chat { grid-template-columns: 1fr; gap: 36px; padding: 48px 18px; }
    .kf-faq-chat .msgs { height: 340px; }
    .kf-faq-chat .m { max-width: 92%; }
}
@media (prefers-reduced-motion: reduce) {
    .kf-faq-chat * { animation: none !important; transition: none !important; }
    .kf-faq-chat .msgs { scroll-behavior: auto; }
}
</style>

<section id="faq" class="kf-faq-chat">
    <div>
        <div class="ey">{{ marketing_content('home_faq_badge') ?: __('FAQ') }}</div>
        <h1>{{ marketing_content('home_faq_title') ?: __('Questions school leaders ask us') }}</h1>
        <p class="lead">{{ marketing_content('home_faq_subtitle') ?: __('Tap a question and get the answer straight away. Can’t find yours? Message us on WhatsApp.') }}</p>
        <a class="wa" href="https://wa.me/{{ preg_replace('/\D+/', '', Modules\Admin\Models\PlatformSetting::get('contact', 'whatsapp') ?: '263786366855') }}" target="_blank" rel="noopener">{{ __('Message us on WhatsApp') }}</a>
    </div>

    <section class="chat" aria-label="{{ __('Frequently asked questions') }}">
        <div class="head">
            <div class="avatar" aria-hidden="true">
                <img src="{{ platform_logo_url() }}" alt="{{ platform_name() }}">
            </div>
            <div>
                <b>{{ platform_name() }} {{ __('team') }}</b>
                <small><span class="live"></span>{{ __('Answers instantly') }}</small>
            </div>
            <button class="reset" id="kf-faq-reset" type="button">{{ __('Start over') }}</button>
        </div>
        <div class="msgs" id="kf-faq-msgs" aria-live="polite"></div>
        <div class="quick" id="kf-faq-quick"></div>
    </section>
</section>

<script>
const KF_FAQ = [
    { q: @json(__('Is Kairo CORE built for my type of school?')),
      a: @json(__('Yes. Kairo CORE is built for primary, secondary, boarding and group schools, with flexible setup for different class structures, terms and reporting needs.')) },
    { q: @json(__('What does Kairo CORE cover?')),
      a: @json(__('It brings admissions, student records, fees, attendance, academics, assessments, report cards, staff permissions, parent communication and school websites into one connected system.')) },
    { q: @json(__('Is our school data safe?')),
      a: @json(__('Each school’s data is isolated to that school, staff only see what their role allows, and permissions can be configured per user or role.')) },
    { q: @json(__('Will my team be trained?')),
      a: @json(__('Yes. Teams are supported with guided onboarding, the Help Centre, video tutorials and direct support. Additional training can be arranged for new staff or new modules.')) },
    { q: @json(__('Can we access it from any device?')),
      a: @json(__('Yes. Kairo CORE runs in the browser on phones, tablets and computers, so staff and parents can work from anywhere with an internet connection.')) },
    { q: @json(__('How long does setup take?')),
      a: @json(__('Most schools can start with core records, classes and fees quickly. A full rollout depends on how much data is imported and which modules you enable first.')) },
    { q: @json(__('Can parents see student progress and fees?')),
      a: @json(__('Yes. Parents can be given secure access to relevant student information, fees, balances and communication, depending on the permissions you configure.')) },
    { q: @json(__('Does it support ZIMSEC and Cambridge reporting?')),
      a: @json(__('Kairo CORE supports flexible grading, assessments and report cards, including ZIMSEC and Cambridge-style reporting where configured for your school.')) },
    { q: @json(__('Can we import existing student data?')),
      a: @json(__('Yes. Student records can be imported from Excel or CSV, with column matching and validation to reduce manual data entry.')) },
    { q: @json(__('Can each school have its own public website?')),
      a: @json(__('Yes. Each school can publish a branded public website with admissions, news, events, staff, contact details and SEO-friendly pages.')) },
    { q: @json(__('What support is available after go-live?')),
      a: @json(__('Support is available through the Help Centre, documentation, WhatsApp and direct contact channels. Issues are handled based on severity and school needs.')) },
    { q: @json(__('Can we try Kairo CORE before committing?')),
      a: @json(__('Yes. You can book a demo, explore the public features and Help Centre, then start with the modules that matter most to your school.')) }
];

const kfFaqMsgs = document.getElementById('kf-faq-msgs');
const kfFaqQuick = document.getElementById('kf-faq-quick');
const kfFaqReduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
let kfFaqAsked = new Set();
let kfFaqBusy = false;

function kfFaqAdd(cls, text) {
    const item = document.createElement('div');
    item.className = 'm ' + cls;
    item.textContent = text;
    kfFaqMsgs.appendChild(item);
    kfFaqMsgs.scrollTop = kfFaqMsgs.scrollHeight;
}

function kfFaqChips() {
    kfFaqQuick.innerHTML = '';
    const remaining = KF_FAQ.map((item, index) => index).filter(index => !kfFaqAsked.has(index));

    remaining.forEach(index => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'chip';
        button.textContent = KF_FAQ[index].q;
        button.onclick = () => kfFaqAsk(index);
        kfFaqQuick.appendChild(button);
    });

    if (!remaining.length) {
        kfFaqQuick.innerHTML = '<p class="done">{{ __('That’s every common question. Still curious?') }} <a href="https://wa.me/{{ preg_replace("/\D+/", "", Modules\Admin\Models\PlatformSetting::get("contact", "whatsapp") ?: "263786366855") }}" target="_blank" rel="noopener">{{ __('Message us on WhatsApp') }}</a>.</p>';
    }
}

function kfFaqAsk(index) {
    if (kfFaqBusy) return;
    kfFaqBusy = true;
    kfFaqAsked.add(index);
    kfFaqChips();
    kfFaqAdd('me', KF_FAQ[index].q);

    const typing = document.createElement('div');
    typing.className = 'm bot typing';
    typing.innerHTML = '<i></i><i></i><i></i>';
    kfFaqMsgs.appendChild(typing);
    kfFaqMsgs.scrollTop = kfFaqMsgs.scrollHeight;

    setTimeout(() => {
        typing.remove();
        kfFaqAdd('bot', KF_FAQ[index].a);
        kfFaqBusy = false;
    }, kfFaqReduce ? 0 : 700);
}

function kfFaqStart() {
    kfFaqMsgs.innerHTML = '';
    kfFaqAsked = new Set([0]);
    kfFaqBusy = false;
    kfFaqAdd('bot', @json(__('Hi, I’m here to answer the questions school leaders ask most. Tap one below.')));
    kfFaqAdd('me', KF_FAQ[0].q);
    kfFaqAdd('bot', KF_FAQ[0].a);
    kfFaqChips();
}

document.getElementById('kf-faq-reset').onclick = kfFaqStart;
kfFaqStart();
</script>
