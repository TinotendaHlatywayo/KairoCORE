@php
    $title = $block['title'] ?? __('Reach Our Administration');
    $isStudio = $isStudioPreview ?? false;
    $admissionEmail = \Modules\Admin\Models\SystemSetting::get('admission', 'contact_email', $school->email_address ?? '');
    $admissionPhone = \Modules\Admin\Models\SystemSetting::get('admission', 'contact_phone', $school->phone_number ?? '');
@endphp

<div class="sc-split" style="align-items: flex-start; {{ $v['align'] !== 'center' ? 'text-align: '.$v['align'].';' : '' }}">

    <div style="display: flex; flex-direction: column; gap: 1.5rem;">
        <div>
            <span class="sc-eyebrow">{{ __('Contact') }}</span>
            <h2 {!! 'style="' . $v['titleStyle'] . '"' !!}>{{ $title }}</h2>
            <p class="sc-muted" style="margin-top: 0.7rem; max-width: 30rem;">{!! $rich($block['description']) ?: __('Our administration office is open Monday through Friday for parent inquiries.') !!}</p>
        </div>

        <div class="sc-contact-list">
            <div class="sc-contact-row">
                <span class="sc-contact-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                </span>
                <div><strong>{{ __('Address') }}</strong><span>{{ $block['address'] ?? __('Campus Address') }}</span></div>
            </div>
            <div class="sc-contact-row">
                <span class="sc-contact-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.34 1.78.65 2.62a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.46-1.27a2 2 0 0 1 2.11-.45c.84.31 1.72.53 2.62.65A2 2 0 0 1 22 16.92z"/></svg>
                </span>
                <div><strong>{{ __('Phone') }}</strong><span>{{ $block['phone'] ?? '+263 242 123456' }}</span></div>
            </div>
            <div class="sc-contact-row">
                <span class="sc-contact-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <div><strong>{{ __('Email') }}</strong><span>{{ $block['email'] ?? 'admissions@school.edu' }}</span></div>
            </div>
        </div>

        @if($admissionEmail || $admissionPhone)
            <div class="sc-card sc-fact" style="border-left: 4px solid var(--sc-primary);">
                <h4>{{ __('Admissions Enquiries') }}</h4>
                <div style="display: flex; flex-direction: column; gap: 0.4rem;">
                    @if($admissionEmail)
                        <a href="mailto:{{ $admissionEmail }}" style="font-weight: 700; color: var(--sc-text); text-decoration: underline;">{{ $admissionEmail }}</a>
                    @endif
                    @if($admissionPhone)
                        <a href="tel:{{ preg_replace('/\s+/', '', $admissionPhone) }}" style="font-weight: 700; color: var(--sc-text); text-decoration: underline;">{{ $admissionPhone }}</a>
                    @endif
                </div>
            </div>
        @endif

        <div class="sc-map">
            <iframe
                title="{{ __('School location map') }}"
                src="{{ $block['map_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3798.11894474776!2d31.050512315354924!3d-17.82485898782352!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1931a4e70dd357cf%3A0x19dfa43fb91a9953!2sHarare%2C%20Zimbabwe!5e0!3m2!1sen!2szw!4v1785386000000!5m2!1sen!2szw' }}"
                loading="lazy"
                allowfullscreen
                style="height: 16rem;"></iframe>
        </div>
    </div>

    <div class="sc-card" style="padding: clamp(1.5rem, 3vw, 2.25rem); background-color: var(--sc-bg);">
        <h3 style="font-size: 1.4rem; font-weight: 800; color: var(--sc-text);">{{ __('Send us a message') }}</h3>
        <p class="sc-muted sc-text-sm" style="margin-top: 0.4rem;">{{ __('All fields are required. We will reply using the email address you provide.') }}</p>

        @if(session('contact_success'))
            <div class="sc-alert sc-alert-success" style="margin-top: 1rem;" role="status">{{ session('contact_success') }}</div>
        @endif
        @if(isset($errors) && $errors->has('contact'))
            <div class="sc-alert sc-alert-error" style="margin-top: 1rem;" role="alert">{{ $errors->first('contact') }}</div>
        @elseif(isset($errors) && $errors->any())
            <div class="sc-alert sc-alert-error" style="margin-top: 1rem;" role="alert">{{ __('Please complete every field with valid information.') }}</div>
        @endif

        <form method="POST" action="{{ route('cms-contact-submit') }}" style="display: flex; flex-direction: column; gap: 1rem; margin-top: 1.2rem;">
            @csrf
            @php($hpName = honeypot_field_name())
            <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
                <label for="{{ $hpName }}">{{ __('Leave this field empty') }}</label>
                <input type="text" id="{{ $hpName }}" name="{{ $hpName }}" tabindex="-1" autocomplete="off" readonly onfocus="this.removeAttribute('readonly')">
            </div>
            <input type="hidden" name="page_slug" value="{{ $page->slug ?? '' }}">

            <div class="sc-grid sc-grid-2" style="gap: 1rem;">
                <label class="sc-field">
                    <span class="sc-label">{{ __('Name') }} <span class="sc-required">*</span></span>
                    <input class="sc-input" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name">
                </label>
                <label class="sc-field">
                    <span class="sc-label">{{ __('Surname') }} <span class="sc-required">*</span></span>
                    <input class="sc-input" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name">
                </label>
            </div>

            <label class="sc-field">
                <span class="sc-label">{{ __('Email address') }} <span class="sc-required">*</span></span>
                <input class="sc-input" type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
            </label>

            <label class="sc-field">
                <span class="sc-label">{{ __('Phone number') }} <span class="sc-required">*</span></span>
                <input class="sc-input" type="tel" name="phone" value="{{ old('phone') }}" required autocomplete="tel">
            </label>

            <label class="sc-field">
                <span class="sc-label">{{ __('Message') }} <span class="sc-required">*</span></span>
                <textarea class="sc-textarea" name="message" rows="5" required>{{ old('message') }}</textarea>
            </label>

            <button type="submit"
                    @if($isStudio) disabled @endif
                    class="sc-btn sc-btn-primary"
                    style="{{ $isStudio ? 'opacity: .6; cursor: not-allowed;' : '' }} width: 100%;">
                {{ $isStudio ? __('Contact form preview') : __('Send message') }}
            </button>
        </form>
    </div>
</div>
