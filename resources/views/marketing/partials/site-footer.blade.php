@php
    use Modules\SaaS\Models\PlatformSetting;

    $contactEmail = PlatformSetting::get('contact', 'email')
        ?: config('mail.platform.support')
        ?: config('mail.platform.address')
        ?: config('mail.from.address');
    $contactWhatsapp = PlatformSetting::get('contact', 'whatsapp')
        ?: PlatformSetting::get('notifications', 'whatsapp_number', '263786366855');
    $contactPhoneDigits = preg_replace('/\D+/', '', $contactWhatsapp);
    $contactPhoneDisplay = preg_replace('/^(\+?263)(\d{2})(\d{3})(\d{4})$/', '$1 $2 $3 $4', '+'.$contactPhoneDigits);
    $socials = [];
    if ($contactWhatsapp) {
        $socials[] = [
            'key' => 'whatsapp',
            'label' => __('Chat on WhatsApp'),
            'url' => 'https://wa.me/'.$contactPhoneDigits,
            'color' => '#25D366',
            'bg' => '#25D366'
        ];
    }
    foreach ([
        'facebook' => ['Facebook', '#1877F2', '#1877F2', 'https://www.facebook.com/'],
        'instagram' => ['Instagram', '#E4405F', 'linear-gradient(45deg, #F58529, #DD2A7B, #8134AF, #515BD4)', 'https://www.instagram.com/'],
        'x' => ['X', '#000000', '#000000', 'https://x.com/'],
        'tiktok' => ['TikTok', '#000000', '#000000', 'https://www.tiktok.com/'],
        'youtube' => ['YouTube', '#FF0000', '#FF0000', 'https://www.youtube.com/'],
    ] as $key => [$label, $color, $bg, $fallback]) {
        $url = PlatformSetting::get('contact', $key);
        $url = is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : $fallback;
        $socials[] = [
            'key' => $key,
            'label' => __($label),
            'url' => $url,
            'color' => $color,
            'bg' => $bg
        ];
    }
    $logo = marketing_image('footer_logo', 'images/kairocore-logo-white.webp');
@endphp

<footer class="kf-footer">
    <div class="kf-wrap kf-footer-wrap">
        <div class="kf-fgrid">
            <div class="kf-fcol kf-fcol-brand">
                <a href="{{ route('marketing.home') }}" class="kf-logo" aria-label="{{ platform_name() }} home">
                    <img src="{{ $logo }}" alt="{{ platform_name() }} logo" width="480" height="268" loading="lazy">
                </a>
                @php($kfTaglineParts = preg_split('/(?<=\.)\s+/u', __('Smarter Schools. Brighter Futures.'), 2))
                <p class="kf-tagline"><span style="white-space:nowrap;">{{ $kfTaglineParts[0] ?? '' }}</span>@if(!empty($kfTaglineParts[1])) <span style="white-space:nowrap;">{{ $kfTaglineParts[1] }}</span>@endif</p>
                <p class="kf-footer-text">{{ __('Cloud-based school management platform built for schools.') }}</p>
                <div class="kf-clock" id="kfClock" data-locale="{{ app()->getLocale() }}">
                    <div class="kf-clock-top">
                        <time class="kf-clock-time" datetime="" aria-label="{{ __('Local time in Harare') }}">
                            <span class="kf-clock-hm">--:--</span><span class="kf-clock-sec">:--</span>
                        </time>
                        <span class="kf-clock-zone">CAT · UTC+2</span>
                    </div>
                    <div class="kf-clock-date">&nbsp;</div>
                    <div class="kf-clock-place">{{ __('Harare, Zimbabwe') }}</div>
                </div>
                <script>
                (function(){
                    var el = document.getElementById('kfClock');
                    if (!el) return;
                    var loc = el.getAttribute('data-locale') || 'en';
                    if (loc === 'sn') loc = 'en';
                    var hm = el.querySelector('.kf-clock-hm');
                    var sec = el.querySelector('.kf-clock-sec');
                    var dateEl = el.querySelector('.kf-clock-date');
                    var timeEl = el.querySelector('time');
                    var timer = null;
                    var f = new Intl.DateTimeFormat(loc, { timeZone: 'Africa/Harare', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
                    function update(){
                        var p = {};
                        f.formatToParts(new Date()).forEach(function(x){ p[x.type] = x.value; });
                        hm.textContent = p.hour + ':' + p.minute;
                        sec.textContent = ':' + p.second;
                        if (timeEl) timeEl.setAttribute('datetime', new Date().toISOString());
                        dateEl.textContent = p.weekday + ', ' + p.day + ' ' + p.month + ' ' + p.year;
                    }
                    function start(){ if (timer) return; update(); timer = setInterval(function(){ if (!document.hidden) update(); }, 1000); }
                    function stop(){ if (timer){ clearInterval(timer); timer=null; } }
                    document.addEventListener('visibilitychange', function(){ if (!document.hidden) update(); });
                    start();
                    window.addEventListener('pagehide', stop);
                })();
                </script>
            </div>

            <nav class="kf-fcol" aria-label="{{ __('Product') }}">
                <h3 class="kf-fh">{{ __('Product') }}</h3>
                <div class="kf-faccent"></div>
                <ul class="kf-flist">
                    <li><a href="{{ route('marketing.features') }}">{{ __('Features') }}</a></li>
                    <li><a href="{{ route('marketing.school-fees') }}">{{ __('Fees & Finance') }}</a></li>
                    <li><a href="{{ route('marketing.admissions') }}">{{ __('Admissions') }}</a></li>
                    <li><a href="{{ route('marketing.security') }}">{{ __('Security') }}</a></li>
                    <li><a href="{{ route('marketing.features') }}#reports">{{ __('Reports & Analytics') }}</a></li>
                </ul>
            </nav>

            <nav class="kf-fcol" aria-label="{{ __('Company') }}">
                <h3 class="kf-fh">{{ __('Company') }}</h3>
                <div class="kf-faccent"></div>
                <ul class="kf-flist">
                    <li><a href="{{ route('marketing.about') }}">{{ __('About') }}</a></li>
                    <li><a href="{{ route('marketing.blog') }}">{{ __('Blog') }}</a></li>
                    <li><a href="{{ route('marketing.contact') }}">{{ __('Contact') }}</a></li>
                    <li><a href="{{ route('marketing.home') }}#why">{{ __('Why Kairo') }}</a></li>
                </ul>
            </nav>

            <nav class="kf-fcol" aria-label="{{ __('Resources') }}">
                <h3 class="kf-fh">{{ __('Resources') }}</h3>
                <div class="kf-faccent"></div>
                <ul class="kf-flist">
                    <li><a href="{{ route('help.index') }}">{{ __('Help Centre') }}</a></li>
                    <li><a href="{{ route('marketing.home') }}#start">{{ __('How it works') }}</a></li>
                    <li><a href="{{ route('help.index') }}">{{ __('Documentation') }}</a></li>
                    <li><a href="{{ route('marketing.home') }}#faq">{{ __('FAQ') }}</a></li>
                    <li><a href="{{ route('marketing.contact') }}#contact-form">{{ __('Book a demo') }}</a></li>
                </ul>
            </nav>

            <div class="kf-fcol kf-fcol-contact">
                <h3 class="kf-fh">{{ __('Reach out to us') }}</h3>
                <div class="kf-faccent"></div>
                <div class="kf-contact-list">
                    @if($contactWhatsapp)
                        <a href="tel:+{{ $contactPhoneDigits }}" class="kf-contact-row">
                            <span class="kf-ci" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg></span>
                            <span>{{ $contactPhoneDisplay }}</span>
                        </a>
                    @endif
                    @if($contactEmail)
                        <a href="mailto:{{ $contactEmail }}" class="kf-contact-row">
                            <span class="kf-ci" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                            <span>{{ $contactEmail }}</span>
                        </a>
                    @endif
                    <a href="{{ $contactWhatsapp ? 'https://wa.me/'.$contactPhoneDigits : route('marketing.contact') }}" class="kf-contact-row" @if($contactWhatsapp) target="_blank" rel="noopener noreferrer" @endif>
                        <span class="kf-ci" aria-hidden="true"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8 8 0 0 1-11.5 7.2L4 20l1-4.5A8 8 0 0 1 21 12z"/></svg></span>
                        <span>{{ __('Chat on WhatsApp') }}</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="kf-divider"></div>

        <div class="kf-fbottom">
            <div class="kf-fbottom-left">
                <span>&copy; {{ date('Y') }} {{ platform_name() }}. {{ __('All rights reserved.') }}</span>
                <span class="kf-flegal">
                    <a href="{{ route('platform.privacy') }}">{{ __('Privacy Policy') }}</a>
                    <span class="kf-dotsep" aria-hidden="true">·</span>
                    <a href="{{ route('platform.terms') }}">{{ __('Terms') }}</a>
                </span>
            </div>
            <div class="kf-fbottom-right">
                <div class="kf-socials-row">
                    @foreach($socials as $social)
                        <a href="{{ $social['url'] }}"
                           class="kf-social-icon-btn kf-social-{{ $social['key'] }}"
                           style="--sc: {{ $social['color'] }}; background: {{ $social['bg'] }};"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="{{ $social['label'] }}"
                           title="{{ $social['label'] }}">
                            @include('marketing.partials.social-icon', ['key' => $social['key']])
                        </a>
                    @endforeach
                </div>
                <a href="#" class="kf-backtotop">↑ {{ __('Back to top') }}</a>
            </div>
        </div>
    </div>
</footer>

<!-- Modern Floating Social Bar (Pill on desktop, expandable bottom-right on mobile/tablet) -->
@if(count($socials) > 0)
<div class="kf-dock" aria-label="{{ __('Quick contact links') }}" x-data="{ open: false, active: 0 }" x-init="setInterval(() => active = (active + 1) % {{ count($socials) }}, 2600)" @click.outside="open = false" @keydown.escape.window="open = false">
    <div class="kf-dock-list" :class="{ 'is-open': open }" id="kf-dock-list">
        @foreach($socials as $index => $social)
            <a href="{{ $social['url'] }}"
               target="_blank"
               rel="noopener noreferrer"
               aria-label="{{ $social['label'] }}"
               title="{{ $social['label'] }}"
               x-show="window.innerWidth >= 1024 || open || active === {{ $index }}"
               x-transition
               class="kf-dock-item kf-dock-{{ $social['key'] }}"
               style="--sc: {{ $social['color'] }}; background: {{ $social['bg'] }};">
                @include('marketing.partials.social-icon', ['key' => $social['key']])
                <span class="kf-dock-tooltip" role="tooltip">{{ $social['label'] }}</span>
            </a>
        @endforeach
    </div>
    <button type="button"
            class="kf-dock-toggle lg:hidden"
            @click="open = !open"
            :aria-expanded="open"
            aria-controls="kf-dock-list"
            aria-label="{{ __('Toggle social links') }}">
        <svg x-show="!open" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
        </svg>
        <svg x-show="open" x-cloak class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
    </button>
</div>
@endif
