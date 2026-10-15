@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - Contact')
@section('meta_description', marketing_content('meta_description') ?: __('Contact us via Email, WhatsApp, Facebook, Instagram, or TikTok.'))

@push('styles')
@include('marketing.partials.animations')
<style>
    /* ==========================================================================
       Contact Form & Interactive Validation Styling
       ========================================================================== */
    .contact-field-wrap {
        position: relative;
    }
    .contact-input {
        width: 100%;
        border-radius: 0.875rem;
        border: 1.5px solid #E2E8F0;
        background-color: #FFFFFF;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #0F172A;
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        outline: none;
    }
    .contact-input:focus {
        border-color: #D94B39;
        box-shadow: 0 0 0 3px rgba(217, 75, 57, 0.15);
    }
    .contact-input.is-invalid {
        border-color: #EF4444 !important;
        background-color: #FEF2F2 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
    }
    .contact-input.is-valid {
        border-color: #10B981 !important;
        background-color: #F0FDF4 !important;
    }

    html.dark .contact-input {
        border-color: #334155;
        background-color: #1E293B;
        color: #F8FAFC;
    }
    html.dark .contact-input:focus {
        border-color: #EF5F4D;
        box-shadow: 0 0 0 3px rgba(239, 95, 77, 0.2);
    }
    html.dark .contact-input.is-invalid {
        border-color: #F87171 !important;
        background-color: rgba(239, 68, 68, 0.1) !important;
    }
    html.dark .contact-input.is-valid {
        border-color: #34D399 !important;
        background-color: rgba(16, 185, 129, 0.1) !important;
    }

    /* Custom Field Error Prompt */
    .field-hint-bubble {
        display: none;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #DC2626;
        animation: fadeInShake 0.3s cubic-bezier(0.36, 0.07, 0.19, 0.97) both;
    }
    .field-hint-bubble.show {
        display: flex;
    }
    html.dark .field-hint-bubble {
        color: #F87171;
    }

    @keyframes fadeInShake {
        0% { opacity: 0; transform: translateY(-4px) scale(0.98); }
        30% { transform: translateX(4px); }
        60% { transform: translateX(-4px); }
        100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* Live Analytics Chips */
    .metric-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 700;
        background: #F1F5F9;
        color: #475569;
        border: 1px solid #E2E8F0;
        transition: all 0.2s ease;
    }
    html.dark .metric-chip {
        background: #1E293B;
        color: #94A3B8;
        border-color: #334155;
    }
    .metric-chip.valid {
        background: #ECFDF5;
        color: #065F46;
        border-color: #A7F3D0;
    }
    html.dark .metric-chip.valid {
        background: rgba(16, 185, 129, 0.15);
        color: #34D399;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .metric-chip.warning {
        background: #FFFBEB;
        color: #92400E;
        border-color: #FDE68A;
    }
    html.dark .metric-chip.warning {
        background: rgba(245, 158, 11, 0.15);
        color: #FBBF24;
        border-color: rgba(245, 158, 11, 0.3);
    }
    .metric-chip.danger {
        background: #FEF2F2;
        color: #991B1B;
        border-color: #FECACA;
    }
    html.dark .metric-chip.danger {
        background: rgba(239, 68, 68, 0.15);
        color: #F87171;
        border-color: rgba(239, 68, 68, 0.3);
    }

    /* Security Alert Banner */
    .security-alert-box {
        display: none;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-radius: 0.875rem;
        background: #FEF2F2;
        border: 1.5px solid #EF4444;
        color: #991B1B;
        font-size: 0.775rem;
        line-height: 1.25rem;
        animation: fadeInShake 0.3s ease both;
    }
    .security-alert-box.active {
        display: flex;
    }
    html.dark .security-alert-box {
        background: rgba(239, 68, 68, 0.12);
        border-color: #F87171;
        color: #FCA5A5;
    }

    /* ==========================================================================
       Glowing Line Social Follow Cards
       ========================================================================== */
    .glow-card-wrap {
        position: relative;
        border-radius: 1.25rem;
        padding: 2px;
        background: transparent;
        transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s ease;
        text-decoration: none;
        display: flex;
        flex-direction: column;
    }
    .glow-card-wrap::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1.25rem;
        padding: 2px;
        background: var(--glow-gradient);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
        box-shadow: 0 0 14px var(--glow-color), 0 0 28px var(--glow-color-subtle);
        animation: neonLinePulse 3s ease-in-out infinite alternate;
        transition: opacity 0.3s ease;
    }
    @keyframes neonLinePulse {
        0% { opacity: 0.8; filter: drop-shadow(0 0 4px var(--glow-color)); }
        50% { opacity: 1; filter: drop-shadow(0 0 10px var(--glow-color)); }
        100% { opacity: 0.85; filter: drop-shadow(0 0 6px var(--glow-color)); }
    }
    .glow-card-wrap:hover {
        transform: translateY(-4px) scale(1.02);
    }
    .glow-card-wrap:hover::before {
        box-shadow: 0 0 22px var(--glow-color), 0 0 44px var(--glow-color-subtle);
    }
    .glow-card-wrap:active {
        transform: scale(0.97);
    }

    /* Facebook Neon Glow */
    .glow-facebook {
        --glow-color: rgba(24, 119, 242, 0.75);
        --glow-color-subtle: rgba(24, 119, 242, 0.3);
        --glow-gradient: linear-gradient(135deg, #1877F2 0%, #42A5F5 50%, #0D47A1 100%);
    }

    /* Instagram Neon Glow */
    .glow-instagram {
        --glow-color: rgba(225, 48, 108, 0.75);
        --glow-color-subtle: rgba(253, 29, 29, 0.35);
        --glow-gradient: linear-gradient(135deg, #833AB4 0%, #FD1D1D 50%, #FCAF45 100%);
    }

    /* TikTok Neon Glow */
    .glow-tiktok {
        --glow-color: rgba(37, 244, 238, 0.75);
        --glow-color-subtle: rgba(254, 44, 85, 0.4);
        --glow-gradient: linear-gradient(135deg, #25F4EE 0%, #000000 50%, #FE2C55 100%);
    }
    html.dark .glow-tiktok {
        --glow-gradient: linear-gradient(135deg, #25F4EE 0%, #1E293B 50%, #FE2C55 100%);
    }

    .glow-card-inner {
        position: relative;
        z-index: 1;
        width: 100%;
        height: 100%;
        border-radius: calc(1.25rem - 2px);
        background: #FFFFFF;
        padding: 1.125rem 0.875rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        transition: background-color 0.25s ease;
    }
    html.dark .glow-card-inner {
        background: #0F172A;
    }

    /* Channels Cards (WhatsApp & Mail) */
    .channel-card {
        display: flex;
        align-items: flex-start;
        gap: 1rem;
        border-radius: 1.5rem;
        border: 1px solid #E2E8F0;
        background: #FFFFFF;
        padding: 1.25rem;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .channel-card:hover {
        border-color: #CBD5E1;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px -8px rgba(31, 46, 67, 0.1);
    }
    .channel-card:active {
        transform: scale(0.98);
    }
    html.dark .channel-card {
        background: #0F172A;
        border-color: #1E293B;
    }
    html.dark .channel-card:hover {
        border-color: #334155;
        box-shadow: 0 16px 30px -10px rgba(0, 0, 0, 0.5);
    }

    /* Submit Button */
    .contact-submit-btn {
        width: 100%;
        border-radius: 0.875rem;
        background: #D94B39;
        padding: 0.875rem 1.5rem;
        font-size: 0.875rem;
        font-weight: 700;
        color: #FFFFFF;
        box-shadow: 0 10px 25px -5px rgba(217, 75, 57, 0.35);
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        cursor: pointer;
        border: none;
    }
    .contact-submit-btn:hover:not(:disabled) {
        background: #c14332;
        transform: translateY(-1px);
        box-shadow: 0 14px 28px -5px rgba(217, 75, 57, 0.45);
    }
    .contact-submit-btn:active:not(:disabled) {
        transform: translateY(1px) scale(0.98);
    }
    .contact-submit-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        box-shadow: none;
    }
</style>
@endpush

@section('content')
@php
    $contactEmail = \Modules\SaaS\Models\PlatformSetting::get('contact', 'email')
        ?: \Modules\SaaS\Models\PlatformSetting::get('notifications', 'super_admin_email', 'hlatwayne@gmail.com');
    $contactWhatsapp = \Modules\SaaS\Models\PlatformSetting::get('contact', 'whatsapp')
        ?: \Modules\SaaS\Models\PlatformSetting::get('notifications', 'whatsapp_number', '263786366855');
    $socialFacebook = \Modules\SaaS\Models\PlatformSetting::get('contact', 'facebook')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'facebook_url', 'https://facebook.com');
    $socialInstagram = \Modules\SaaS\Models\PlatformSetting::get('contact', 'instagram')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'instagram_url', 'https://instagram.com');
    $socialTiktok = \Modules\SaaS\Models\PlatformSetting::get('contact', 'tiktok')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'tiktok_url', 'https://tiktok.com');
@endphp

{{-- HERO --}}
<section class="relative overflow-hidden bg-white dark:bg-[#0B1120] transition-colors duration-200">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-24 left-1/3 h-80 w-80 rounded-full bg-[#D94B39]/8 dark:bg-[#D94B39]/12 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 pb-6 pt-12 sm:px-6 lg:pt-16">
        <div class="mx-auto max-w-3xl text-center">
            <p class="reveal text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39] dark:text-[#EF5F4D]">{{ marketing_content('contact_badge') }}</p>
            <h1 class="reveal reveal-d1 mt-3 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.08]">{{ marketing_content('contact_title') }}</h1>
            <p class="reveal reveal-d2 mt-4 text-base sm:text-lg leading-7 text-slate-600 dark:text-slate-300">{{ marketing_content('contact_intro') }}</p>
        </div>
    </div>
</section>

<section class="pb-20 pt-6 bg-slate-50/50 dark:bg-[#0B1120] transition-colors duration-200">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        {{-- Flash Success Alert --}}
        @if(session('success_message'))
            <div class="reveal mx-auto mb-8 max-w-3xl rounded-2xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/40 p-4 text-center text-sm font-semibold text-emerald-800 dark:text-emerald-300 shadow-sm flex items-center justify-center gap-2">
                <svg class="h-5 w-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success_message') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-5">
            {{-- Contact Form Column --}}
            <div class="reveal lg:col-span-3">
                <div class="h-full rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] p-6 sm:p-9 shadow-sm transition-colors">
                    <div class="flex items-center gap-3 pb-5 border-b border-slate-100 dark:border-slate-800">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#1F2E43] dark:bg-[#D94B39] text-white shadow-sm">
                            {{-- Real Mail Icon --}}
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ marketing_content('contact_form_title') }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ __('We respond to all school inquiries within 24 hours.') }}</p>
                        </div>
                    </div>

                    {{-- Form Security Alert Banner --}}
                    <div id="security-alert-box" class="security-alert-box mt-5">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.25-8.25-3.286zm0 13.036h.007v.008H12v-.008z"/>
                        </svg>
                        <div>
                            <strong class="font-bold block">{{ __('Security Alert') }}</strong>
                            <span id="security-alert-text">{{ __('Script tags, HTML elements, and executable code are strictly forbidden for security.') }}</span>
                        </div>
                    </div>

                    <form action="{{ route('marketing.contact.submit') }}" method="POST" id="contact-form" class="mt-6 space-y-5" novalidate>
                        @csrf
                        {{-- Anti-Bot Honeypot field (hidden from genuine users) --}}
                        <div style="opacity: 0; position: absolute; top: 0; left: 0; height: 0; width: 0; z-index: -1; overflow: hidden;" aria-hidden="true">
                            <label for="website_hp">Leave this field blank</label>
                            <input type="text" name="website_hp" id="website_hp" tabindex="-1" autocomplete="off">
                        </div>

                        {{-- Name Field --}}
                        <div class="contact-field-wrap">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300" for="cf-name">
                                    {{ marketing_content('contact_form_name_label') }} <span class="text-[#D94B39]">*</span>
                                </label>
                                <span class="text-[11px] font-semibold text-slate-400" id="name-counter">0/100 letters</span>
                            </div>
                            <input id="cf-name"
                                   type="text"
                                   name="name"
                                   value="{{ old('name') }}"
                                   required
                                   autocomplete="name"
                                   maxlength="100"
                                   class="contact-input"
                                   placeholder="{{ marketing_content('contact_form_placeholder_name') }}">
                            {{-- Custom inline validation message replacing browser default --}}
                            <div class="field-hint-bubble" id="name-hint">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span id="name-hint-text">{{ __('Please fill in this field with your name (letters only).') }}</span>
                            </div>
                            @error('name')
                                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Email Field with sales@kairocore.co.zw placeholder --}}
                        <div class="contact-field-wrap">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300" for="cf-email">
                                    {{ marketing_content('contact_form_email_label') }} <span class="text-[#D94B39]">*</span>
                                </label>
                                <span class="text-[11px] font-semibold text-slate-400" id="email-format-badge">{{ __('Official address') }}</span>
                            </div>
                            <input id="cf-email"
                                   type="email"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   maxlength="120"
                                   class="contact-input"
                                   placeholder="sales@kairocore.co.zw">
                            {{-- Custom inline validation message --}}
                            <div class="field-hint-bubble" id="email-hint">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span id="email-hint-text">{{ __('Please fill in a valid email address (e.g., sales@kairocore.co.zw).') }}</span>
                            </div>
                            @error('email')
                                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Message Field --}}
                        <div class="contact-field-wrap">
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300" for="cf-message">
                                    {{ marketing_content('contact_form_message_label') }} <span class="text-[#D94B39]">*</span>
                                </label>
                                <span class="text-[11px] font-semibold text-slate-400" id="message-char-limit">0 / 2,000 chars</span>
                            </div>
                            <textarea id="cf-message"
                                      name="message"
                                      rows="5"
                                      required
                                      maxlength="2000"
                                      class="contact-input resize-y min-h-[120px]"
                                      placeholder="{{ marketing_content('contact_form_placeholder_message') }}">{{ old('message') }}</textarea>

                            {{-- Custom inline validation message --}}
                            <div class="field-hint-bubble" id="message-hint">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span id="message-hint-text">{{ __('Please fill in your message with at least 3 words.') }}</span>
                            </div>
                            @error('message')
                                <p class="mt-1 text-xs font-semibold text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Submit Button --}}
                        <button type="submit" id="contact-submit-btn" class="contact-submit-btn group active:scale-95">
                            <span id="btn-text">{{ marketing_content('contact_form_submit_label') }}</span>
                            <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Direct Channels & Socials with Glowing Line Follow Cards --}}
            <div class="reveal reveal-d1 space-y-5 lg:col-span-2">
                {{-- Real WhatsApp Direct Channel --}}
                @if($contactWhatsapp)
                <a href="https://wa.me/{{ ltrim($contactWhatsapp, '+') }}" target="_blank" rel="noopener" class="channel-card group">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 transition-all shadow-sm hover:shadow-emerald-200">
                        {{-- Real WhatsApp Icon --}}
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ marketing_content('contact_whatsapp_title') }}</span>
                        <span class="block text-sm font-semibold text-emerald-600 dark:text-emerald-400 group-hover:underline">+{{ ltrim($contactWhatsapp, '+') }}</span>
                        <span class="mt-0.5 block text-xs text-slate-400">{{ marketing_content('contact_whatsapp_note') }}</span>
                    </div>
                    <svg class="h-4 w-4 text-slate-400 group-hover:text-emerald-600 group-hover:translate-x-1 transition-transform self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                @endif

                {{-- Real Mail Direct Channel --}}
                @if($contactEmail)
                <a href="mailto:{{ $contactEmail }}" class="channel-card group">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-[#1F2E43] dark:text-blue-400 transition-all shadow-sm hover:shadow-slate-300">
                        {{-- Real Mail Icon --}}
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                    </span>
                    <div class="flex-1 min-w-0">
                        <span class="block text-sm font-bold text-slate-900 dark:text-white">{{ marketing_content('contact_email_title') }}</span>
                        <span class="block break-all text-sm font-semibold text-slate-600 dark:text-slate-300 group-hover:text-[#D94B39] dark:group-hover:text-[#EF5F4D] transition-colors">{{ $contactEmail }}</span>
                        <span class="mt-0.5 block text-xs text-slate-400">{{ marketing_content('contact_email_note') }}</span>
                    </div>
                    <svg class="h-4 w-4 text-slate-400 group-hover:text-[#D94B39] group-hover:translate-x-1 transition-transform self-center" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                </a>
                @endif

                {{-- Office Location Card --}}
                <div class="reveal reveal-d2 rounded-3xl border border-slate-200 dark:border-slate-800 bg-[#1F2E43] dark:bg-[#0B1320] p-6 text-white shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10 text-[#EF5F4D]">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418"/></svg>
                        </span>
                        <p class="text-sm font-bold text-white">{{ marketing_content('contact_office_title') }}</p>
                    </div>
                    <p class="mt-3 text-xs leading-5 text-slate-300">{{ marketing_content('contact_office_hours') }}</p>
                    <p class="mt-1 text-xs leading-5 text-slate-300 font-medium">{{ marketing_content('contact_office_location') }}</p>
                </div>

                {{-- Glowing Line Social Follow Cards: Facebook Follow, Instagram Follow, TikTok Follow --}}
                @if($socialFacebook || $socialInstagram || $socialTiktok)
                <div class="reveal reveal-d3 pt-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3">{{ __('Follow Our Official Channels') }}</p>
                    <div class="grid grid-cols-3 gap-3">
                        {{-- 1. Facebook with Neon Blue Glowing Line --}}
                        @if($socialFacebook)
                        <a href="{{ $socialFacebook }}" target="_blank" rel="noopener" class="glow-card-wrap glow-facebook group">
                            <div class="glow-card-inner">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#1877F2] text-white transition-transform duration-300 group-hover:scale-110 shadow-md shadow-[#1877F2]/30">
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                    </svg>
                                </span>
                                <span class="mt-2 block text-xs font-bold text-slate-900 dark:text-white">Facebook</span>
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#1877F2]/10 px-2 py-0.5 text-[10px] font-bold text-[#1877F2] transition-transform duration-300 group-hover:scale-105">
                                    {{ __('Follow') }}
                                </span>
                            </div>
                        </a>
                        @endif

                        {{-- 2. Instagram with Multi-Color Sunset Neon Glowing Line --}}
                        @if($socialInstagram)
                        <a href="{{ $socialInstagram }}" target="_blank" rel="noopener" class="glow-card-wrap glow-instagram group">
                            <div class="glow-card-inner">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-[#FCAF45] via-[#E1306C] to-[#833AB4] text-white transition-transform duration-300 group-hover:scale-110 shadow-md shadow-[#E1306C]/30">
                                    {{-- Real Instagram SVG --}}
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                </span>
                                <span class="mt-2 block text-xs font-bold text-slate-900 dark:text-white">Instagram</span>
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-[#E1306C]/10 px-2 py-0.5 text-[10px] font-bold text-[#E1306C] transition-transform duration-300 group-hover:scale-105">
                                    {{ __('Follow') }}
                                </span>
                            </div>
                        </a>
                        @endif

                        {{-- 3. TikTok with Neon Cyan & Magenta Glowing Line --}}
                        @if($socialTiktok)
                        <a href="{{ $socialTiktok }}" target="_blank" rel="noopener" class="glow-card-wrap glow-tiktok group">
                            <div class="glow-card-inner">
                                <span class="relative flex h-10 w-10 items-center justify-center rounded-xl bg-black text-white transition-transform duration-300 group-hover:scale-110 shadow-md shadow-black/30">
                                    <span class="absolute -left-0.5 -top-0.5 h-full w-full rounded-xl bg-[#25F4EE]/40 -z-10"></span>
                                    <span class="absolute -right-0.5 -bottom-0.5 h-full w-full rounded-xl bg-[#FE2C55]/40 -z-10"></span>
                                    <span class="absolute inset-0 rounded-xl bg-black"></span>
                                    <svg class="relative h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V7.91a8.16 8.16 0 004.76 1.52V5.98a4.83 4.83 0 01-1-.29z"/>
                                    </svg>
                                </span>
                                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.34-6.34V7.91a8.16 8.16 0 004.76 1.52V5.98a4.83 4.83 0 01-1-.29z"/>
                                    </svg>
                                </span>
                                <span class="mt-2 block text-xs font-bold text-slate-900 dark:text-white">TikTok</span>
                                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-900 transition-transform duration-300 group-hover:scale-105">
                                    {{ __('Follow') }}
                                </span>
                            </div>
                        </a>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Trust Banner Image --}}
                <div class="reveal reveal-d4 overflow-hidden rounded-3xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0F172A] shadow-sm">
                    <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                        <img src="{{ marketing_image('contact_image') }}" alt="{{ __('Diverse team of professionals collaborating around a laptop in a modern office') }}" class="h-full w-full object-cover transition-transform duration-500 hover:scale-105" loading="lazy">
                    </div>
                    <div class="p-5">
                        <p class="text-sm font-bold text-slate-900 dark:text-white">{{ marketing_content('contact_card_title') }}</p>
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 leading-relaxed">{{ marketing_content('contact_card_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Client-Side Input Validation and Anti-Script Sanitizer --}}
<script>
(function() {
    var form = document.getElementById('contact-form');
    if (!form) return;

    var nameInput = document.getElementById('cf-name');
    var emailInput = document.getElementById('cf-email');
    var messageInput = document.getElementById('cf-message');
    var submitBtn = document.getElementById('contact-submit-btn');

    var nameCounter = document.getElementById('name-counter');
    var messageCharLimit = document.getElementById('message-char-limit');


    var securityAlert = document.getElementById('security-alert-box');
    var securityAlertText = document.getElementById('security-alert-text');

    var nameHint = document.getElementById('name-hint');
    var nameHintText = document.getElementById('name-hint-text');
    var emailHint = document.getElementById('email-hint');
    var emailHintText = document.getElementById('email-hint-text');
    var messageHint = document.getElementById('message-hint');
    var messageHintText = document.getElementById('message-hint-text');

    // Dangerous patterns that represent script injection or XSS attacks
    var scriptPatterns = [
        /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
        /<[^>]+>/g,
        /javascript\s*:/gi,
        /data\s*:\s*text\/html/gi,
        /onerror\s*=/gi,
        /onload\s*=/gi,
        /onclick\s*=/gi,
        /onmouseover\s*=/gi,
        /eval\s*\(/gi,
        /<iframe/gi,
        /<embed/gi,
        /<object/gi
    ];

    function detectScriptAttack(text) {
        if (!text) return false;
        for (var i = 0; i < scriptPatterns.length; i++) {
            if (scriptPatterns[i].test(text)) {
                // Reset regex state
                scriptPatterns[i].lastIndex = 0;
                return true;
            }
            scriptPatterns[i].lastIndex = 0;
        }
        return false;
    }

    // Strict Email Regex preventing attack injection
    var emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;

    function validateName() {
        var val = (nameInput.value || '').trim();
        nameCounter.textContent = val.length + '/100 letters';

        if (detectScriptAttack(val)) {
            nameInput.classList.add('is-invalid');
            nameInput.classList.remove('is-valid');
            nameHint.classList.add('show');
            nameHintText.textContent = 'Script tags or HTML injection detected in name!';
            return false;
        }

        if (val.length === 0) {
            nameInput.classList.remove('is-valid');
            return null; // empty
        }

        // Must be at least 2 characters and contain predominantly letters/spaces/apostrophes
        var nameLetterRegex = /^[\p{L}\s\-\'.]+$/u;
        if (val.length < 2 || !nameLetterRegex.test(val)) {
            nameInput.classList.add('is-invalid');
            nameInput.classList.remove('is-valid');
            nameHint.classList.add('show');
            nameHintText.textContent = 'Name should contain letters, spaces or hyphens only (min 2 chars).';
            return false;
        }

        nameInput.classList.remove('is-invalid');
        nameInput.classList.add('is-valid');
        nameHint.classList.remove('show');
        return true;
    }

    function validateEmail() {
        var val = (emailInput.value || '').trim();

        if (detectScriptAttack(val)) {
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            emailHint.classList.add('show');
            emailHintText.textContent = 'Malicious code detected in email address!';
            return false;
        }

        if (val.length === 0) {
            emailInput.classList.remove('is-valid');
            return null;
        }

        if (!emailRegex.test(val)) {
            emailInput.classList.add('is-invalid');
            emailInput.classList.remove('is-valid');
            emailHint.classList.add('show');
            emailHintText.textContent = 'Please enter a valid email address (e.g., sales@kairocore.co.zw).';
            return false;
        }

        emailInput.classList.remove('is-invalid');
        emailInput.classList.add('is-valid');
        emailHint.classList.remove('show');
        return true;
    }

    function validateMessage() {
        var val = (messageInput.value || '').trim();
        var charCount = val.length;
        messageCharLimit.textContent = charCount + ' / 2,000 chars';

        // Word count for validation
        var wordCount = val.length ? val.split(/\s+/).filter(Boolean).length : 0;

        // Check for script attacks
        if (detectScriptAttack(val)) {
            messageInput.classList.add('is-invalid');
            messageInput.classList.remove('is-valid');
            messageHint.classList.add('show');
            messageHintText.textContent = 'Script tags, HTML tags, or executable code detected in your message!';
            securityAlert.classList.add('active');
            securityAlertText.textContent = 'Blocked potential script injection. Remove any HTML or <script> tags before submitting.';
            return false;
        } else {
            securityAlert.classList.remove('active');
        }

        if (charCount === 0) {
            messageInput.classList.remove('is-valid');
            return null;
        }

        if (wordCount < 3) {
            messageInput.classList.add('is-invalid');
            messageInput.classList.remove('is-valid');
            messageHint.classList.add('show');
            messageHintText.textContent = 'Please provide a message with at least 3 words.';
            return false;
        }

        messageInput.classList.remove('is-invalid');
        messageInput.classList.add('is-valid');
        messageHint.classList.remove('show');
        return true;
    }

    // Live validation listeners
    nameInput.addEventListener('input', validateName);
    nameInput.addEventListener('blur', function() {
        if (!nameInput.value.trim()) {
            nameInput.classList.add('is-invalid');
            nameHint.classList.add('show');
            nameHintText.textContent = 'Please fill in this field with your name.';
        } else {
            validateName();
        }
    });

    emailInput.addEventListener('input', validateEmail);
    emailInput.addEventListener('blur', function() {
        if (!emailInput.value.trim()) {
            emailInput.classList.add('is-invalid');
            emailHint.classList.add('show');
            emailHintText.textContent = 'Please fill in this field with your email address.';
        } else {
            validateEmail();
        }
    });

    messageInput.addEventListener('input', validateMessage);
    messageInput.addEventListener('blur', function() {
        if (!messageInput.value.trim()) {
            messageInput.classList.add('is-invalid');
            messageHint.classList.add('show');
            messageHintText.textContent = 'Please fill in this field with your message.';
        } else {
            validateMessage();
        }
    });

    // Form Submission Interception
    form.addEventListener('submit', function(e) {
        var isNameValid = validateName();
        var isEmailValid = validateEmail();
        var isMessageValid = validateMessage();

        var hasError = false;
        var firstErrorField = null;

        if (isNameValid !== true) {
            nameInput.classList.add('is-invalid');
            nameHint.classList.add('show');
            if (!nameInput.value.trim()) {
                nameHintText.textContent = 'Please fill in this field with your full name.';
            }
            hasError = true;
            if (!firstErrorField) firstErrorField = nameInput;
        }

        if (isEmailValid !== true) {
            emailInput.classList.add('is-invalid');
            emailHint.classList.add('show');
            if (!emailInput.value.trim()) {
                emailHintText.textContent = 'Please fill in this field with your email (e.g. sales@kairocore.co.zw).';
            }
            hasError = true;
            if (!firstErrorField) firstErrorField = emailInput;
        }

        if (isMessageValid !== true) {
            messageInput.classList.add('is-invalid');
            messageHint.classList.add('show');
            if (!messageInput.value.trim()) {
                messageHintText.textContent = 'Please fill in this field with your message.';
            }
            hasError = true;
            if (!firstErrorField) firstErrorField = messageInput;
        }

        // Honeypot check
        var hp = document.getElementById('website_hp');
        if (hp && hp.value) {
            e.preventDefault();
            return false;
        }

        if (hasError) {
            e.preventDefault();
            if (firstErrorField) {
                firstErrorField.focus();
                firstErrorField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return false;
        }

        // Disable button to prevent double-submit
        submitBtn.disabled = true;
        var btnText = document.getElementById('btn-text');
        if (btnText) btnText.textContent = 'Sending message...';
    });
})();
</script>
@endsection
