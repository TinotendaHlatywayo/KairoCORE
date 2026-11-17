@extends('marketing.layout')

@section('body_class', 'kairo-v2')

@section('title', marketing_content('contact_title') ?: __('Contact').' - '.platform_name())
@section('meta_description', marketing_content('contact_intro') ?: __('Contact Kairo CORE.'))

@push('styles')
@vite('resources/css/kairo-v2/contact.css')
@endpush
@push('styles')
<style>body.kairo-v2 section { padding-top: 0px !important; }</style>
@endpush

@section('content')
@php
    use Modules\SaaS\Models\PlatformSetting;
    $contactEmail = PlatformSetting::get('contact', 'email')
        ?: config('mail.platform.support')
        ?: config('mail.platform.address')
        ?: config('mail.from.address');
    $contactWhatsapp = PlatformSetting::get('contact', 'whatsapp') ?: PlatformSetting::get('notifications', 'whatsapp_number', '263786366855');
    $contactWhatsappDigits = preg_replace('/\D+/', '', $contactWhatsapp);
    $contactWhatsappDisplay = preg_replace('/^(\+?263)(\d{2})(\d{3})(\d{4})$/', '$1 $2 $3 $4', '+'.$contactWhatsappDigits);
    $socials = [
        'facebook' => 'https://www.facebook.com/',
        'instagram' => 'https://www.instagram.com/',
        'x' => 'https://x.com/',
        'tiktok' => 'https://www.tiktok.com/',
        'youtube' => 'https://www.youtube.com/',
    ];
    foreach ($socials as $key => $fallback) {
        $url = PlatformSetting::get('contact', $key);
        $socials[$key] = is_string($url) && filter_var($url, FILTER_VALIDATE_URL) ? $url : $fallback;
    }
    $contactBadge = marketing_content('contact_badge');
    $contactTitle = marketing_content('contact_title');
    $contactIntro = marketing_content('contact_intro');
    if ($contactBadge === 'Contact') $contactBadge = __('Contact us');
    if ($contactTitle === 'Reach out to us') $contactTitle = __('Let’s talk about your school');
    if ($contactIntro === 'Have questions about KairoCORE or want a personalized demo? Reach out to us through any of the channels below.') {
        $contactIntro = __('Have questions about Kairo CORE, or want a personalized demo? Reach us through any channel below.');
    }
@endphp

<header class="kh">
    <div class="w">
        <div>
            <div class="ey">{{ $contactBadge ?: __('Contact us') }}</div>
            <h1>{{ $contactTitle ?: __('Let’s talk about your school') }}</h1>
            <p>{{ $contactIntro ?: __('Have questions about Kairo CORE, or want a personalized demo? Reach us through any channel below.') }}</p>
        </div>
    </div>
</header>

<div class="w kg">
    <div class="kcol">
        <section class="kc rv">
            <div class="kt">
                <span class="ki" data-i="mail"></span>
                <div>
            <h2>{{ marketing_content('contact_form_title') ?: __('Send us a message') }}</h2>
                    <small>{{ marketing_content('contact_form_subtitle') ?: __('We respond to all school inquiries within 24 hours.') }}</small>
                </div>
            </div>
            <form id="contact-form" method="POST" action="{{ route('marketing.contact.submit') }}" data-contact-form>
                @csrf
                <input type="text" name="website_hp" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px" aria-hidden="true">
                <input type="hidden" name="started_at" value="{{ time() }}">
                <div class="kr">
                    <div class="fl2">
                        <label for="name">{{ marketing_content('contact_form_name_label') ?: __('Your name') }} * <small id="contactNameCount">{{ mb_strlen((string) old('name')) }} / 100</small></label>
                        <input id="name" name="name" value="{{ old('name') }}" placeholder="{{ marketing_content('contact_form_placeholder_name') ?: __('Tendai Marufu') }}" required maxlength="100">
                    </div>
                    <div class="fl2">
                        <label for="email">{{ marketing_content('contact_form_email_label') ?: __('Your email') }} *</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="you@yourschool.co.zw" required>
                    </div>
                </div>
                <div class="kr">
                    <div class="fl2">
                        <label for="school">{{ __('School name') }}</label>
                        <input id="school" name="school" value="{{ old('school') }}" placeholder="{{ __('Your school') }}" autocomplete="organization">
                    </div>
                    <div class="fl2">
                        <label for="topic">{{ __('I’m interested in') }}</label>
                        <select id="topic" name="topic">
                            @foreach ([__('Booking a demo'), __('A price quote'), __('Product questions'), __('Support')] as $topic)
                                <option value="{{ $topic }}" @selected(old('topic', __('Booking a demo')) === $topic)>{{ __($topic) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="fl2">
                    <label for="message">{{ marketing_content('contact_form_message_label') ?: __('Message') }} * <small id="contactMessageCount">{{ mb_strlen((string) old('message')) }} / 2000</small></label>
                    <textarea id="message" name="message" maxlength="2000" placeholder="{{ marketing_content('contact_form_placeholder_message') ?: __('Tell us a little about your school and what you need...') }}" required>{{ old('message') }}</textarea>
                </div>
                @if($errors->any())
                    <p class="kerr" role="alert">{{ $errors->first() }}</p>
                @else
                    <p class="kerr" role="alert"></p>
                @endif
                <button class="btn o" type="submit">
                    {{ marketing_content('contact_form_submit_label') ?: __('Send message') }}
                    <span data-i="send" data-s="18" aria-hidden="true"></span>
                </button>
            </form>
            @if (session('success_message'))
                <p class="ok">{{ session('success_message') }}</p>
            @endif
        </section>

        <section class="kc kst rv">
            <h2>{{ __('What happens next') }}</h2>
            <ol>
                @foreach (marketing_merged_list('contact_steps') as $step)
                    <li><div><h3>{{ $step['title'] ?? '' }}</h3><p>{{ $step['desc'] ?? '' }}</p></div></li>
                @endforeach
            </ol>
        </section>
    </div>
    <aside class="kcol">
        @if($contactWhatsapp)
            <a class="kl rv" href="https://wa.me/{{ $contactWhatsappDigits }}" target="_blank" rel="noopener" style="--c:#25D366">
                <span class="ki" data-i="wa"></span>
                <div>
                    <b>{{ __('WhatsApp us') }}</b>
                    <span class="kv">{{ $contactWhatsappDisplay }}</span>
                    <small>{{ __('Fastest replies during office hours') }}</small>
                </div>
                <span class="kch" data-i="chev" data-s="20"></span>
            </a>
        @endif
        @if($contactEmail)
            <a class="kl rv" href="mailto:{{ $contactEmail }}" style="--c:#3b82f6">
                <span class="ki" data-i="mail"></span>
                <div>
                    <b>{{ __('Email us') }}</b>
                    <span class="kv">{{ $contactEmail }}</span>
                    <small>{{ __('For quotes, demos and support') }}</small>
                </div>
                <span class="kch" data-i="chev" data-s="20"></span>
            </a>
        @endif
        <div class="kho rv">
            <span class="ki" data-i="clock"></span>
            <div>
                <b>{{ __('Office hours') }}</b>
                <p>{{ marketing_content('contact_office_hours') ?: __('Monday to Friday, 08:00 – 17:00 (CAT)') }}</p>
                <p>{{ marketing_content('contact_office_location') ?: __('Harare, Zimbabwe') }}</p>
                <span class="kop" id="kop"></span>
            </div>
        </div>
        <div class="rv">
            <div class="kfh">{{ __('Follow our official channels') }}</div>
            <div class="fol" id="fol">
                @foreach ($socials as $key => $url)
                    @if($url)
                        <a class="fo" href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($key) }}" style="--c: {{ ['facebook'=>'#1877F2','instagram'=>'#E4405F','tiktok'=>'var(--tiktok-color, #1f2f47)','youtube'=>'#FF0000','x'=>'var(--x-color, #1f2f47)','whatsapp'=>'#25D366'][$key] ?? '#1f2f47' }}">
                            @include('marketing.partials.social-icon', ['key' => $key])
                            <b>{{ ucfirst($key) }}</b>
                            <i>{{ __('Follow') }}</i>
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="kc kim rv">
            <div class="kpic">
                <img src="{{ marketing_image('contact_image') }}" alt="{{ __('A Kairo CORE school specialist helping colleagues at a laptop') }}" loading="lazy">
            </div>
            <div class="ktx">
                <h3>{{ marketing_content('contact_card_title') ?: __('Talk to a school specialist') }}</h3>
                <p>{{ marketing_content('contact_card_text') ?: __('We understand school terms, fee cycles and exam seasons, and we speak your language.') }}</p>
            </div>
        </div>
    </aside>
</div>

<section class="kmp">
    <div class="w">
        <div class="kc rv">
            <div class="kmh">
                <span class="ki" data-i="pin"></span>
                <div>
                    <b>{{ __('Our office') }}</b>
                    <small>{{ marketing_content('contact_office_location') ?: __('Harare, Zimbabwe') }}</small>
                </div>
                <a class="btn o sm" href="{{ PlatformSetting::get('contact', 'location_url') ?: 'https://www.google.com/maps/search/?api=1&query=Harare%2C%20Zimbabwe' }}" target="_blank" rel="noopener">{{ __('Open in Google Maps') }}</a>
            </div>
            <iframe loading="lazy" referrerpolicy="no-referrer-when-downgrade" src="https://www.google.com/maps?q={{ rawurlencode(marketing_content('contact_office_location') ?: __('Harare, Zimbabwe')) }}&output=embed" title="{{ __('Map of our office') }}"></iframe>
        </div>
    </div>
</section>


@endsection
