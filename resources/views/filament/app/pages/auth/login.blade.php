{{-- File: resources/views/filament/app/pages/auth/login.blade.php --}}

@php
    use Modules\Admin\Models\SystemSetting;

    $school = $school ?? null;
    $profileSchoolName = $school ? SystemSetting::get('profile', 'school_name') : null;
    $schoolName = $profileSchoolName ?: ($school?->name ?? 'Kairo CORE');
    $motto = $school?->motto ?? null;
    $schoolSubdomain = $school?->subdomain ?? null;

    $profileAddress = $school ? SystemSetting::get('profile', 'address') : null;
    $profilePhone = $school ? SystemSetting::get('profile', 'phone') : null;
    $profileReg = $school ? SystemSetting::get('profile', 'reg_number') : null;
    $establishedYear = $school ? SystemSetting::get('profile', 'established_year') : null;
    $principalName = $school ? SystemSetting::get('profile', 'principal_name') : null;

    $logoUrl = asset('images/logo-transparent.png');

    $customLogo = $school ? SystemSetting::get('branding', 'logo_path') : null;
    if (! empty($customLogo)) {
        $logoUrl = asset('storage/' . $customLogo);
    }

    // Tenant accent colors parsed safely
    $accentKey = 'emerald_heritage';
    if ($school) {
        $themeRaw = SystemSetting::get('branding', 'theme');
        if (is_string($themeRaw) && $themeRaw !== '') {
            $accentKey = $themeRaw;
        }
    }

    $accents = [
        'digital_cobalt' => '#2563eb',
        'obsidian_gold' => '#d4af37',
        'crimson_academy' => '#dc2626',
        'ocean_breeze' => '#0d9488',
        'forest_pine' => '#059669',
        'sunset_amber' => '#ea580c',
        'royal_purple' => '#7c3aed',
        'steel_slate' => '#475569',
        'rosewood' => '#e11d48',
        'emerald_heritage' => '#0f766e',
        'dev_choice_1' => '#4f46e5',
        'dev_choice_2' => '#c026d3',
        'dev_choice_3' => '#0891b2',
        'dev_choice_4' => '#f05438',
    ];
    $accent = $accents[$accentKey] ?? '#0f766e';
    $accentStrong = $accent === '#0f766e' ? '#0d5f56' : $accent;
    $accentRgb = implode(',', [hexdec(substr($accent, 1, 2)), hexdec(substr($accent, 3, 2)), hexdec(substr($accent, 5, 2))]);

    $onAccent = '#ffffff';
    $roleOptions = \App\Models\User::REGISTRATION_ROLES;
@endphp

<div
    class="sc-login2"
    style="--sc-accent: {{ $accent }}; --sc-accent-strong: {{ $accentStrong }}; --sc-accent-rgb: {{ $accentRgb }}; --sc-on-accent: {{ $onAccent }};"
>
    <div class="sc-l2-frame">
        {{-- Premium Ambient Dynamic Depth Orbs --}}
        <div class="sc-l2-orbs" aria-hidden="true">
            <div class="sc-l2-orb sc-l2-orb-1"></div>
            <div class="sc-l2-orb sc-l2-orb-2"></div>
            <div class="sc-l2-orb sc-l2-orb-3"></div>
        </div>

        <div class="sc-l2-card">
            {{-- Symmetrical Visual Base Gradients --}}
            <div class="sc-l2-gradient" aria-hidden="true"></div>

            {{-- Section 1: Dynamic Tenant Identity --}}
            <header class="sc-l2-head">
                <div class="sc-l2-brand">
                    <div class="sc-l2-logo" @if ($logoUrl) style="background-image:url('{{ $logoUrl }}');" @endif>
                        @if (! $logoUrl)
                            <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <rect x="4" y="4" width="40" height="40" rx="12" fill="currentColor" opacity="0.15"/>
                                <path d="M24 10 8 22l16 12 16-12-16-12Z" fill="currentColor" opacity="0.9"/>
                                <path d="M24 22v12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                                <circle cx="24" cy="22" r="3" fill="currentColor"/>
                            </svg>
                        @endif
                    </div>
                    <div class="sc-l2-head-text">
                        <h1 class="sc-l2-school">{{ $schoolName }}</h1>
                        <div class="sc-l2-badges">
                            @if ($establishedYear)
                                <span class="sc-l2-pill sc-l2-pill-outline">Est. {{ $establishedYear }}</span>
                            @endif
                        </div>
                        @if ($motto)
                            <p class="sc-l2-motto">"{{ $motto }}"</p>
                        @endif
                        @if ($principalName || $profileAddress)
                            <p class="sc-l2-meta">
                                @if ($principalName)
                                    <span class="sc-l2-meta-item">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M10.5 2.5a4.5 4.5 0 1 0 0 9 4.5 4.5 0 0 0 0-9Zm-7 11a5.5 5.5 0 0 1 5.5-5.5h3a5.5 5.5 0 0 1 5.5 5.5 1.5 1.5 0 0 1-1.5 1.5H5a1.5 1.5 0 0 1-1.5-1.5ZM18.5 10.5a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm1 1.5h-1.9a3.48 3.48 0 0 1 1.9 2.7c0 .83-.5 1.3-1 1.3h2.5a1.5 1.5 0 0 0 1.5-1.5 3.5 3.5 0 0 0-3-3.5Z"/></svg>
                                        {{ $principalName }}
                                    </span>
                                @endif
                                @if ($profileAddress)
                                    <span class="sc-l2-meta-item">
                                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a7.5 7.5 0 0 0-7.5 7.5c0 5.55 6.05 10.38 6.85 10.98a1.04 1.04 0 0 0 1.3 0c.8-.6 6.85-5.43 6.85-10.98A7.5 7.5 0 0 0 12 2Zm0 10.5a3 3 0 1 1 0-6 3 3 0 0 1 0 6Z"/></svg>
                                        {{ $profileAddress }}
                                    </span>
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </header>

            {{-- Section 2: Dynamic Form Stage --}}
            <div
                class="sc-l2-body"
                x-data="{ view: 'signin' }"
                :class="view === 'register' ? 'sc-swapped' : ''"
            >

                {{-- Symmetrical Aside Message Overlay --}}
                <aside class="sc-l2-aside">
                    
                    {{-- Positioned absolutely inside the dark panel header bounds --}}
                    <span class="sc-l2-pill sc-l2-aside-badge" aria-hidden="true">
                        <span class="sc-l2-pill-dot"></span>
                        {{ $schoolName }}
                    </span>

                    <div class="sc-l2-overlay" aria-hidden="true">

                        {{-- SIGN IN state --}}
                        <div
                            class="sc-ov-pane sc-ov-signin"
                            :class="view === 'signin' ? 'sc-ov-in' : 'sc-ov-out rx'"
                        >
                            <div class="sc-ov-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2a4.5 4.5 0 0 0-4.5 4.5V9H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-1.5V6.5A4.5 4.5 0 0 0 12 2Zm0 2a2.5 2.5 0 0 1 2.5 2.5V9h-5V6.5A2.5 2.5 0 0 1 12 4Z" fill="currentColor"/><circle cx="12" cy="16.5" r="1.6" fill="currentColor"/></svg>
                            </div>
                            <span class="sc-ov-eyebrow">{{ __('Secure Portal') }}</span>
                            <h3 data-sc-tw-once="Welcome Back">{{ __('Welcome Back') }}</h3>
                            <p class="sc-typing-target" data-sc-tw='["Reconnect with your educational community.","Track performance, manage billing and collaborate with ease."]'>{{ __('Reconnect with your educational community.') }}</p>
                            <button
                                type="button"
                                class="sc-ov-cta"
                                @click="view = 'register'"
                            >
                                {{ __('Create Account') }}
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a.75.75 0 0 1 .75.75v5.5h5.5a.75.75 0 0 1 0 1.5h-5.5v5.5a.75.75 0 0 1-1.5 0v-5.5h-5.5a.75.75 0 0 1 0-1.5h5.5v-5.5A.75.75 0 0 1 10 3Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>

                        {{-- CREATE ACCOUNT state --}}
                        <div
                            class="sc-ov-pane sc-ov-register"
                            :class="view === 'register' ? 'sc-ov-in' : 'sc-ov-out lx'"
                        >
                            <div class="sc-ov-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 2.5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 12 2.5Zm4.14 7.4-4.9 4.9a.75.75 0 0 1-1.06 0l-2.3-2.3a.75.75 0 0 1 1.06-1.06l1.77 1.77 4.37-4.37a.75.75 0 1 1 1.06 1.06Z" fill="currentColor"/></svg>
                            </div>
                            <span class="sc-ov-eyebrow">{{ __('Join the Community') }}</span>
                            <h3 data-sc-tw-once="Begin Your Journey">{{ __('Begin Your Journey') }}</h3>
                            <p class="sc-typing-target" data-sc-tw='["Join your designated school platform.","Every account requires approval by school administrators."]'>{{ __('Join your designated school platform.') }}</p>
                            <button
                                type="button"
                                class="sc-ov-cta"
                                @click="view = 'signin'"
                            >
                                {{ __('Sign In') }}
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 4.01a.75.75 0 1 1-1.04 1.08l-5.5-5.82a.75.75 0 0 1 0-1.06l5.5-5.82a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                    </div>

                    {{-- Journey Steps --}}
                    <div class="sc-l2-journey" aria-hidden="true">
                        <span class="sc-l2-jstep"><i></i>{{ __('Learn') }}</span>
                        <span class="sc-l2-jstep"><i></i>{{ __('Grow') }}</span>
                        <span class="sc-l2-jstep"><i></i>{{ __('Succeed') }}</span>
                    </div>
                </aside>

                {{-- Symmetrical Forms Workspace --}}
                <section class="sc-l2-forms">

                    {{-- FORM A: SIGN IN --}}
                    <div
                        class="sc-pane sc-pane-signin"
                        x-data="{ shake: false }"
                        x-init="
                            const obs = new MutationObserver((muts) => {
                                for (const m of muts) for (const n of m.addedNodes) {
                                    if (n.nodeType === 1 && (n.matches?.('.sc-field-err, .fi-fo-field-error-message, .fi-validation-message') || n.querySelector?.('.sc-field-err, .fi-fo-field-error-message, .fi-validation-message'))) {
                                        shake = true;
                                        setTimeout(() => shake = false, 500);
                                        return;
                                    }
                                }
                            });
                            obs.observe($el, { childList: true, subtree: true });
                        "
                        :class="{ 'sc-shake': shake }"
                        x-show="view === 'signin' && ! $wire.regSubmitted"
                        x-transition:enter="sc-pane-in" x-transition:enter-start="sc-pane-in-start" x-transition:enter-end="sc-pane-in-end"
                        x-transition:leave="sc-pane-out" x-transition:leave-start="sc-pane-out-start" x-transition:leave-end="sc-pane-out-end"
                        x-cloak
                    >
                        <h2 class="sc-l2-title">{{ __('Welcome Back') }}</h2>
                        <p class="sc-l2-subtitle">{{ __('Sign in to access your workspace') }}</p>

                        <div class="sc-login-form-fields">
                            <x-filament-panels::form id="form" wire:submit="authenticate">
                                <div class="sc-login-fields">
                                    {{ $this->form }}
                                </div>
                                <x-filament-panels::form.actions
                                    :actions="$this->getCachedFormActions()"
                                    :full-width="true"
                                />
                            </x-filament-panels::form>
                        </div>

                        @include('auth.google-button')
                    </div>

                    {{-- FORM B: REGISTER ACCOUNT --}}
                    <form
                        class="sc-pane sc-reg-form"
                        x-data="scRegValid('{{ $regRole }}')"
                        x-init="
                            const obs = new MutationObserver((muts) => {
                                for (const m of muts) for (const n of m.addedNodes) {
                                    if (n.nodeType === 1 && (n.matches?.('.sc-field-err, .fi-fo-field-error-message, .fi-validation-message') || n.querySelector?.('.sc-field-err, .fi-fo-field-error-message, .fi-validation-message'))) {
                                        shake = true;
                                        setTimeout(() => shake = false, 500);
                                        return;
                                    }
                                }
                            });
                            obs.observe($el, { childList: true, subtree: true });
                        "
                        :class="{ 'sc-shake': shake }"
                        x-show="view === 'register' && ! $wire.regSubmitted"
                        x-transition:enter="sc-pane-in" x-transition:enter-start="sc-pane-in-start" x-transition:enter-end="sc-pane-in-end"
                        x-transition:leave="sc-pane-out" x-transition:leave-start="sc-pane-out-start" x-transition:leave-end="sc-pane-out-end"
                        x-cloak
                        wire:submit="registerAccount"
                        novalidate
                    >
                        <h2 class="sc-l2-title">{{ __('Create Account') }}</h2>
                        <p class="sc-l2-subtitle">{{ __('Fill in your details to get started') }}</p>

                        <div class="sc-reg-hp" aria-hidden="true">
                            <label for="sc-reg-website">{{ __('Leave this field empty') }}</label>
                            <input id="sc-reg-website" type="text" name="website" autocomplete="off" tabindex="-1" />
                        </div>

                        <div class="sc-reg-grid">
                            <div class="sc-field sc-field-full">
                                <label for="sc-reg-name">{{ __('Full Name') }}</label>
                                 <input id="sc-reg-name" type="text" autocomplete="name" placeholder="e.g. Tendai Moyo" wire:model.live="regName"
                                       x-on:blur="validateField('name', $el.value)"
                                       x-on:input="clearError('name')"
                                       maxlength="100" />
                                @error('regName') <span class="sc-field-err">{{ $message }}</span> @enderror
                                <span class="sc-field-err" x-cloak x-show="errors.name" x-text="errors.name"></span>
                            </div>

                            <div class="sc-field">
                                <label for="sc-reg-role">{{ __('Role') }}</label>
                                <select id="sc-reg-role" wire:model.defer="regRole" x-model="regRole">
                                    @foreach ($roleOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('regRole') <span class="sc-field-err">{{ $message }}</span> @enderror
                            </div>

                            <div
                                class="sc-field"
                                x-show="regRole !== 'administrator'"
                                x-transition.opacity
                            >
                                <label for="sc-reg-identifier">
                                    <span x-text="regRole === 'student' ? '{{ __('Student Registration No') }}' : (regRole === 'teaching_staff' || regRole === 'non_teaching_staff' ? '{{ __('Staff ID') }}' : '{{ __('Student Reg No / Staff ID') }}')"></span>
                                    <span class="sc-opt" x-show="regRole === 'administrator'">({{ __('Optional') }})</span>
                                </label>
                                <input
                                    id="sc-reg-identifier"
                                    type="text"
                                    :placeholder="regRole === 'student' ? 'e.g. REG-2026-001' : (regRole === 'teaching_staff' || regRole === 'non_teaching_staff' ? 'e.g. STF-012' : 'e.g. REG-2026-001 or STF-012')"
                                    wire:model.defer="regIdentifier"
                                    maxlength="100"
                                />
                                @error('regIdentifier') <span class="sc-field-err">{{ $message }}</span> @enderror
                            </div>

                            <div class="sc-field">
                                <label for="sc-reg-email">{{ __('Email Address') }}</label>
                                <input id="sc-reg-email" type="email" autocomplete="email" placeholder="you@school.com" wire:model.live="regEmail"
                                       maxlength="255" />
                                @error('regEmail') <span class="sc-field-err">{{ $message }}</span> @enderror
                            </div>

                            <div class="sc-field">
                                <label for="sc-reg-phone">{{ __('Phone Number') }} <span class="sc-opt">(Optional)</span></label>
                                <input id="sc-reg-phone" type="tel" autocomplete="tel" placeholder="+263 77 123 4567" wire:model.live="regPhone"
                                       maxlength="30" />
                                @error('regPhone') <span class="sc-field-err">{{ $message }}</span> @enderror
                            </div>

                            <div class="sc-field" style="margin-top: 0.5rem; margin-bottom: 1.25rem;">
                                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                                    <input type="checkbox" id="sc-reg-terms" wire:model.live="regAgreeTerms" style="margin-top: 0.25rem; width: 1.1rem; height: 1.1rem; accent-color: var(--sc-accent, #10b981); cursor: pointer;" />
                                    <label for="sc-reg-terms" style="font-size: 0.85rem; color: #475569; line-height: 1.5; cursor: pointer;">
                                        {{ __('I have read and agree to the') }}
                                        <a href="{{ route('platform.terms') }}" target="_blank" style="color: var(--sc-accent, #10b981); text-decoration: underline; font-weight: 600;">{{ __('Platform Terms of Service') }}</a>
                                        {{ __('and the') }}
                                        @if (request()->route('tenant') ?? current_tenant()?->subdomain)
                                            <a href="{{ route('school.terms', ['tenant' => request()->route('tenant') ?? current_tenant()?->subdomain]) }}" target="_blank" style="color: var(--sc-accent, #10b981); text-decoration: underline; font-weight: 600;">{{ __('School Terms & Conditions') }}</a>.
                                        @endif
                                    </label>
                                </div>
                                @error('regAgreeTerms') <span class="sc-field-err" style="display: block; margin-top: 0.35rem;">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <button type="submit" class="sc-l2-submit" wire:loading.attr="disabled" wire:target="registerAccount" @if(! $regAgreeTerms || empty($regName) || empty($regEmail)) disabled style="opacity: 0.6; cursor: not-allowed; background-color: #94a3b8; border-color: #94a3b8;" @endif>
                            <span wire:loading.remove wire:target="registerAccount">{{ __('Submit Request') }}</span>
                            <span wire:loading wire:target="registerAccount">{{ __('Processing...') }}</span>
                        </button>

                        <p class="sc-l2-activate-note">
                            {{ __('You will set your password via the activation email we send after a school administrator approves your request.') }}
                        </p>

                        <p class="sc-l2-switch">
                            {{ __('Already have an account?') }}
                            <a href="#" @click.prevent="view = 'signin'">{{ __('Sign In') }}</a>
                        </p>
                    </form>

                    {{-- SUCCESS STATE --}}
                    <div
                        class="sc-pane sc-l2-success"
                        x-show="$wire.regSubmitted"
                        x-transition:enter="sc-pane-in" x-transition:enter-start="sc-pane-in-start" x-transition:enter-end="sc-pane-in-end"
                        x-cloak
                    >
                        <div class="sc-l2-success-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        </div>
                        <h2 class="sc-l2-title">{{ __('Registration Successful!') }}</h2>
                        <p class="sc-l2-subtitle">
                            {{ __('Thank you,') }} <strong>{{ $regSubmittedName }}</strong>{{ __('. An activation email has been sent to your email address. Please check your inbox to activate your account.') }}
                        </p>
                        <p class="sc-l2-success-hint">
                            {{ __('Didn’t receive the email?') }}
                            <a href="{{ route('account.activate.request') }}">{{ __('Request a new activation link') }}</a>
                        </p>
                        <button type="button" class="sc-l2-submit sc-l2-submit-ghost" @click="$wire.regSubmitted = false; view = 'signin'">
                            {{ __('Return to Sign In') }}
                        </button>
                    </div>
                </section>
            </div>
        </div>
    </div>

    {{-- Footer --}}
    <footer class="sc-l2-legal">
        <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:none;font-weight:600;">{{ __('Terms of Service') }}</a>
        <span style="margin:0 .4rem;opacity:.4;">·</span>
        <a href="{{ route('platform.terms') }}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:none;font-weight:600;">{{ __('Terms of Use') }}</a>
        <span style="margin:0 .4rem;opacity:.4;">·</span>
        <span>{{ __('Powered by Tinway Technologies') }}</span>
    </footer>

<style>
    /* ────────────────────────── BASE CONTAINER ────────────────────────── */
    .sc-login2 {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 1.5rem 1rem;
        font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
        background: radial-gradient(ellipse at 20% 50%, rgba(var(--sc-accent-rgb), 0.06), transparent 60%),
                    radial-gradient(ellipse at 80% 50%, rgba(var(--sc-accent-rgb), 0.04), transparent 50%);
        min-height: 100vh;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: geometricPrecision; /* Maximum vector typography alignment */
        font-smooth: always;
    }

    /* ────────────────────────── AMBIENT ORBS ────────────────────────── */
    .sc-l2-orbs {
        position: absolute;
        inset: -120px;
        pointer-events: none;
        z-index: 0;
        overflow: hidden;
    }
    .sc-l2-orb {
        position: absolute;
        border-radius: 50%;
        filter: blur(100px);
        opacity: 0.3;
        animation: sc-orb-float 15s ease-in-out infinite alternate;
    }
    .sc-l2-orb-1 {
        width: 400px;
        height: 400px;
        top: -150px;
        right: -80px;
        background: radial-gradient(circle, rgba(var(--sc-accent-rgb), 0.35), transparent 70%);
        animation-delay: 0s;
    }
    .sc-l2-orb-2 {
        width: 350px;
        height: 350px;
        bottom: -120px;
        left: -80px;
        background: radial-gradient(circle, rgba(var(--sc-accent-rgb), 0.2), transparent 70%);
        animation-delay: -5s;
    }
    .sc-l2-orb-3 {
        width: 200px;
        height: 200px;
        top: 40%;
        right: 30%;
        background: radial-gradient(circle, rgba(var(--sc-accent-rgb), 0.15), transparent 70%);
        animation-delay: -10s;
        animation-duration: 20s;
    }
    @keyframes sc-orb-float {
        0% { transform: translate(0, 0) scale(1); }
        100% { transform: translate(40px, -30px) scale(1.15); }
    }

    .sc-l2-frame {
        position: relative;
        width: 100%;
        max-width: 1120px;
        z-index: 0;
    }

    /* ────────────────────────── CARD (SOLID FLAT FOR MAXIMUM VECTOR LEGIBILITY) ────────────────────────── */
    .sc-l2-card {
        position: relative;
        z-index: 1;
        width: 100%;
        border-radius: 2rem;
        overflow: hidden;
        background: rgba(255, 255, 255, 0.75); /* 25% transparent */
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1.5px solid rgba(226, 232, 240, 0.8);
        box-shadow:
            0 30px 80px -20px rgba(15, 23, 42, 0.14),
            0 0 0 1px rgba(255, 255, 255, 0.9) inset,
            0 0 60px -20px rgba(var(--sc-accent-rgb), 0.1);
        color: #0f172a;
        transition: background 0.4s ease, border-color 0.4s ease;
        animation: sc-card-in 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    .dark .sc-l2-card {
        background: rgba(10, 15, 30, 0.75); /* 25% transparent */
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-color: rgba(255, 255, 255, 0.08);
        box-shadow:
            0 35px 85px -20px rgba(0, 0, 0, 0.6),
            0 0 0 1px rgba(255, 255, 255, 0.02) inset,
            0 0 60px -20px rgba(var(--sc-accent-rgb), 0.15);
        color: #f1f5f9;
    }
    @keyframes sc-card-in {
        from { opacity: 0; transform: translate3d(0, 15px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }

    .sc-l2-gradient {
        position: absolute;
        inset: 0;
        z-index: 0;
        background: linear-gradient(145deg, rgba(var(--sc-accent-rgb), 0.03), transparent 50%, rgba(var(--sc-accent-rgb), 0.01));
        pointer-events: none;
    }

    .sc-l2-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 4px;
        background: linear-gradient(90deg, var(--sc-accent), var(--sc-accent-strong) 50%, rgba(var(--sc-accent-rgb), 0.3));
        z-index: 1;
        opacity: 0.9;
    }

    .sc-l2-card > *:not(.sc-l2-gradient) { position: relative; z-index: 2; }

    /* ────────────────────────── HEADER ────────────────────────── */
    .sc-l2-head {
        padding: 1.75rem 2.5rem 1.25rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }
    .dark .sc-l2-head { border-bottom-color: rgba(255, 255, 255, 0.05); }

    .sc-l2-brand {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .sc-l2-logo {
        width: 4rem;
        height: 4rem;
        flex: none;
        border-radius: 1.125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--sc-accent);
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: var(--sc-on-accent);
        box-shadow: 0 8px 28px -10px rgba(var(--sc-accent-rgb), 0.5), 0 0 40px -16px rgba(var(--sc-accent-rgb), 0.25);
        transition: transform 0.3s ease;
    }
    .sc-l2-logo:hover { transform: scale(1.04); }
    .sc-l2-logo svg { width: 48%; height: 48%; }

    .sc-l2-head-text { min-width: 0; flex: 1; }

    .sc-l2-school {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        color: #0f172a;
    }
    .dark .sc-l2-school { color: #f1f5f9; }

    .sc-l2-badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.25rem;
    }

    .sc-l2-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--sc-accent-strong);
        background: rgba(var(--sc-accent-rgb), 0.1);
        border: 1px solid rgba(var(--sc-accent-rgb), 0.15);
        padding: 0.3rem 0.8rem;
        border-radius: 9999px;
    }
    .dark .sc-l2-pill {
        color: color-mix(in srgb, var(--sc-accent) 75%, #fff);
        background: rgba(var(--sc-accent-rgb), 0.15);
    }
    .sc-l2-pill-outline {
        background: transparent;
        border-color: rgba(15, 23, 42, 0.1);
        color: #64748b;
    }
    .dark .sc-l2-pill-outline {
        border-color: rgba(255, 255, 255, 0.08);
        color: #94a3b8;
    }

    .sc-l2-pill-dot {
        width: 5px; height: 5px; border-radius: 50%;
        background: var(--sc-accent);
        box-shadow: 0 0 12px var(--sc-accent);
        animation: sc-pulse-dot 2s infinite ease-in-out;
    }
    @keyframes sc-pulse-dot {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.6); opacity: 0.4; }
    }

    .sc-l2-motto {
        margin: 0.4rem 0 0;
        font-size: 0.85rem;
        font-weight: 500;
        font-style: italic;
        color: #475569;
        letter-spacing: 0.01em;
    }
    .dark .sc-l2-motto { color: #cbd5e1; }

    .sc-l2-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem 1.25rem;
        margin: 0.3rem 0 0;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .dark .sc-l2-meta { color: #64748b; }
    .sc-l2-meta-item { display: inline-flex; align-items: center; gap: 0.35rem; }
    .sc-l2-meta-item svg { width: 0.85rem; height: 0.85rem; flex: none; opacity: 0.8; }

    /* ────────────────────────── BODY (SYMMETRICAL GRID) ────────────────────────── */
    .sc-l2-body {
        position: relative;
        display: grid;
        grid-template-columns: 1fr 1fr; /* Fixed asymmetrical transform bugs */
        gap: 2rem;
        padding: 2rem 2.5rem 2.5rem;
        height: 38rem; /* Sized generously to prevent vertical clipping */
        max-height: 38rem;
    }

    /* ────────────────────────── ASIDE ────────────────────────── */
    .sc-l2-aside {
        position: relative;
        display: flex;
        flex-direction: column;
        border-radius: 1.25rem;
        overflow: hidden;
        height: 100%;
        max-height: 100%;
        background:
            radial-gradient(130% 80% at 0% 0%, rgba(var(--sc-accent-rgb), 0.3), transparent 60%),
            linear-gradient(155deg, #1a2538, #0f172a);
        color: #f1f5f9;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 0 50px -20px rgba(var(--sc-accent-rgb), 0.35);
        will-change: transform;
        transform: translate3d(0, 0, 0); /* Subpixel render optimization */
        transform-style: preserve-3d;
        backface-visibility: hidden;
        transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
    }
    .dark .sc-l2-aside {
        background:
            radial-gradient(130% 80% at 0% 0%, rgba(var(--sc-accent-rgb), 0.35), transparent 50%),
            linear-gradient(155deg, #0b0f1a, #111827);
    }

    /* Top-Right Scoped Portal Brand Badge inside the Dark Aside Panel */
    .sc-l2-aside-badge {
        position: absolute !important;
        top: 1.25rem !important;
        right: 1.25rem !important;
        z-index: 10 !important;
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1.5px solid rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25) !important;
        backdrop-filter: blur(8px);
    }
    .sc-l2-aside-badge .sc-l2-pill-dot {
        background: #34d399 !important; /* Premium Emerald Active Status Indicator */
        box-shadow: 0 0 12px #34d399 !important;
    }

    .sc-l2-overlay {
        position: relative;
        flex: 1;
        min-height: 16rem;
    }

    .sc-ov-pane {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.35rem;
        padding: 2.25rem 2rem;
        transition: transform 600ms cubic-bezier(0.22, 1, 0.36, 1), opacity 450ms ease;
        will-change: transform, opacity;
        transform: translate3d(0,0,0);
        transform-style: preserve-3d;
        backface-visibility: hidden;
    }
    .sc-ov-pane.sc-ov-out {
        pointer-events: none;
        opacity: 0;
    }
    .sc-ov-pane:not(.sc-ov-out) { opacity: 1; }

    .sc-ov-icon {
        width: 2.6rem;
        height: 2.6rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--sc-accent);
    }
    .sc-ov-icon svg { width: 1.9rem; height: 1.9rem; }
    .sc-ov-eyebrow {
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.5);
    }
    .sc-ov-pane h3 {
        margin: 0.1rem 0 0.1rem;
        font-size: 1.65rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        text-shadow: 0 0 40px rgba(var(--sc-accent-rgb), 0.3);
        color: #ffffff;
    }
    
    .sc-typing-target {
        display: block;
        min-height: 3.5rem; /* Prevents text wrap container shifts during animation loops */
    }

    .sc-ov-pane p {
        margin: 0;
        font-size: 0.85rem;
        line-height: 1.5;
        color: rgba(255, 255, 255, 0.7);
        max-width: 90%;
    }

    .sc-ov-cta {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        align-self: flex-start;
        margin-top: 0.75rem;
        padding: 0.6rem 1.4rem;
        border-radius: 0.75rem;
        border: 1px solid rgba(255,255,255,0.15);
        background: rgba(255,255,255,0.08);
        color: #fff;
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.25s ease;
    }
    .sc-ov-cta:hover {
        background: rgba(255,255,255,0.18);
        border-color: rgba(255,255,255,0.3);
        transform: translate3d(0, -2px, 0);
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.4), 0 0 40px -12px rgba(var(--sc-accent-rgb), 0.4);
    }
    .sc-ov-cta:active { transform: translate3d(0, 0, 0) scale(0.97); }
    .sc-ov-cta svg { width: 1rem; height: 1rem; }

    .sc-l2-journey {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 0 2rem 1.5rem;
        font-size: 0.6rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.3);
    }
    .sc-l2-jstep { display: inline-flex; align-items: center; gap: 0.4rem; }
    .sc-l2-jstep i {
        width: 5px; height: 5px; border-radius: 50%;
        background: rgba(255,255,255,0.15);
        transition: background 0.3s;
    }
    .sc-l2-jstep:nth-child(2) i { background: var(--sc-accent); box-shadow: 0 0 14px var(--sc-accent); }
    .sc-l2-jstep:last-child i { background: #10b981; box-shadow: 0 0 14px #10b981; }

    /* ────────────────────────── SWAP (PRECISE 3D TRANSLATIONS) ────────────────────────── */
    .sc-l2-forms {
        min-width: 0;
        height: 100%;
        max-height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
        z-index: 2;
        will-change: transform;
        transform: translate3d(0,0,0);
        transform-style: preserve-3d;
        backface-visibility: hidden;
        transition: transform 700ms cubic-bezier(0.22, 1, 0.36, 1);
    }
    .sc-swapped .sc-l2-aside { transform: translate3d(calc(100% + 2rem), 0, 0); }
    .sc-swapped .sc-l2-forms { transform: translate3d(calc(-100% - 2rem), 0, 0); }

    /* ────────────────────────── PANELS (CRISP TYPOGRAPHY FIX) ────────────────────────── */
    .sc-pane {
        min-width: 0;
        height: 100%;
        max-height: 100%;
        overflow-y: auto;
        padding-right: 0.5rem;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
        justify-content: center;
        will-change: opacity;
    }
    .sc-pane::-webkit-scrollbar { width: 4px; }
    .sc-pane::-webkit-scrollbar-track { background: transparent; }
    .sc-pane::-webkit-scrollbar-thumb { background: rgba(var(--sc-accent-rgb), 0.15); border-radius: 10px; }

    .sc-l2-title {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.2;
        color: #0f172a;
    }
    .dark .sc-l2-title { color: #f1f5f9; }

    .sc-l2-subtitle {
        margin: 0.25rem 0 1.25rem;
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.4;
    }
    .dark .sc-l2-subtitle { color: #94a3b8; }

    .sc-pane-in-start { opacity: 0; transform: translate3d(0, 10px, 0); }
    .sc-pane-in-end   { opacity: 1; transform: translate3d(0, 0, 0); }
    .sc-pane-in       { transition: opacity 350ms ease, transform 400ms cubic-bezier(0.22, 1, 0.36, 1); }
    .sc-pane-out      { transition: opacity 200ms ease, transform 250ms ease; }
    .sc-pane-out-start{ opacity: 1; transform: translate3d(0, 0, 0); }
    .sc-pane-out-end  { opacity: 0; transform: translate3d(0, -8px, 0); }

    /* ────────────────────────── SIGN-IN FORM ────────────────────────── */
    .sc-login-form-fields .fi-fo-field-wrp { margin-bottom: 0.4rem; }

    .sc-login-form-fields .fi-input-wrp {
        background: rgba(255,255,255,0.95) !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 0.75rem !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03) !important;
        transition: border-color 0.2s, box-shadow 0.2s !important;
    }
    .dark .sc-login-form-fields .fi-input-wrp {
        background: rgba(15, 23, 42, 0.5) !important;
        border-color: #334155 !important;
    }
    .sc-login-form-fields .fi-input-wrp:hover { border-color: #94a3b8 !important; }
    .dark .sc-login-form-fields .fi-input-wrp:hover { border-color: #475569 !important; }

    .sc-login-form-fields .fi-input-wrp:focus-within {
        border-color: var(--sc-accent) !important;
        box-shadow: 0 0 0 3px rgba(var(--sc-accent-rgb), 0.1), 0 0 30px -10px rgba(var(--sc-accent-rgb), 0.35) !important;
    }
    .sc-login-form-fields .fi-input { color: #0f172a !important; font-weight: 500; }
    .dark .sc-login-form-fields .fi-input { color: #f1f5f9 !important; }

    .sc-login-form-fields .fi-fo-field-label {
        font-weight: 600;
        font-size: 0.78rem;
        color: #334155;
    }
    .dark .sc-login-form-fields .fi-fo-field-label { color: #cbd5e1; }

    .sc-login-form-fields .fi-checkbox-input { accent-color: var(--sc-accent); border-radius: 0.3rem; }

    .sc-login-form-fields a,
    .sc-login-form-fields .fi-fo-field-hint a {
        font-weight: 600;
        font-size: 0.75rem;
        color: var(--sc-accent-strong);
        text-decoration: none;
    }
    .sc-login-form-fields a:hover { text-decoration: underline; }
    .dark .sc-login-form-fields a,
    .dark .sc-login-form-fields .fi-fo-field-hint a {
        color: color-mix(in srgb, var(--sc-accent) 75%, #fff);
    }

    .sc-login-form-fields .fi-fo-field-error-message,
    .sc-login-form-fields .fi-validation-message {
        font-size: 0.72rem;
        font-weight: 600;
        color: #e11d48;
    }

    .sc-login-form-fields .fi-btn.fi-color-primary {
        background: linear-gradient(135deg, var(--sc-accent) 0%, var(--sc-accent-strong) 80%) !important;
        border: none !important;
        border-radius: 0.75rem !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        padding: 0.75rem 1.5rem !important;
        box-shadow: 0 10px 32px -10px rgba(var(--sc-accent-rgb), 0.5) !important;
        transition: all 0.25s ease !important;
    }
    .sc-login-form-fields .fi-btn.fi-color-primary:hover {
        transform: translate3d(0, -2px, 0);
        box-shadow: 0 14px 40px -10px rgba(var(--sc-accent-rgb), 0.6) !important;
    }
    .sc-login-form-fields .fi-btn.fi-color-primary:active { transform: translate3d(0,0,0) scale(0.98); }

    /* ────────────────────────── REGISTRATION FORM ────────────────────────── */
    .sc-reg-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.7rem;
    }
    .sc-field-full { grid-column: 1 / -1; }

    /* Honeypot trap — invisible to humans, ignored by well-behaved bots. */
    .sc-reg-hp {
        position: absolute;
        left: -9999px;
        width: 1px;
        height: 1px;
        overflow: hidden;
    }

    .sc-field {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }
    .sc-field label {
        font-size: 0.76rem;
        font-weight: 600;
        color: #334155;
        letter-spacing: 0.01em;
    }
    .dark .sc-field label { color: #cbd5e1; }
    .sc-field label .sc-opt {
        font-weight: 400;
        font-size: 0.65rem;
        color: #94a3b8;
    }

    .sc-field input,
    .sc-field select {
        width: 100%;
        padding: 0.6rem 0.9rem;
        border-radius: 0.75rem;
        border: 1.5px solid #cbd5e1;
        background: rgba(255,255,255,0.95);
        color: #0f172a;
        font-size: 0.85rem;
        font-weight: 500;
        transition: border-color 0.2s, box-shadow 0.2s;
        appearance: none;
    }
    .dark .sc-field input,
    .dark .sc-field select {
        background: rgba(15, 23, 42, 0.5);
        border-color: #334155;
        color: #f1f5f9;
    }
    .sc-field select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2364748b'%3E%3Cpath fill-rule='evenodd' d='M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' clip-rule='evenodd'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 1rem;
        padding-right: 2.25rem;
    }
    .sc-field input:hover, .sc-field select:hover { border-color: #cbd5e1; }
    .dark .sc-field input:hover, .dark .sc-field select:hover { border-color: #475569; }

    .sc-field input:focus, .sc-field select:focus {
        outline: none;
        border-color: var(--sc-accent);
        box-shadow: 0 0 0 3px rgba(var(--sc-accent-rgb), 0.1), 0 0 30px -10px rgba(var(--sc-accent-rgb), 0.25);
    }
    .sc-field input::placeholder {
        color: #94a3b8;
        font-weight: 400;
    }
    .dark .sc-field input::placeholder {
        color: #64748b;
    }

    .sc-field-err {
        font-size: 0.7rem;
        font-weight: 600;
        color: #e11d48;
        margin-top: 0.1rem;
    }

    /* Password toggle */
    .sc-pw { position: relative; }
    .sc-pw input { padding-right: 2.5rem; }
    .sc-pw-toggle {
        position: absolute;
        right: 0.4rem;
        top: 50%;
        transform: translateY(-50%);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        border: none;
        background: none;
        color: #94a3b8;
        cursor: pointer;
        border-radius: 0.5rem;
        transition: all 0.2s;
    }
    .sc-pw-toggle:hover {
        color: var(--sc-accent-strong);
        background: rgba(var(--sc-accent-rgb), 0.06);
    }
    .sc-pw-toggle:active { transform: translateY(-50%) scale(0.9); }
    .dark .sc-pw-toggle { color: #64748b; }
    .dark .sc-pw-toggle:hover {
        color: color-mix(in srgb, var(--sc-accent) 75%, #fff);
        background: rgba(var(--sc-accent-rgb), 0.1);
    }
    .sc-pw-eye { width: 1.1rem; height: 1.1rem; }

    /* ────────────────────────── SUBMIT BUTTONS ────────────────────────── */
    .sc-l2-submit {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        margin-top: 1rem;
        padding: 0.75rem 1.2rem;
        border: none;
        border-radius: 0.75rem;
        background: linear-gradient(135deg, var(--sc-accent) 0%, var(--sc-accent-strong) 80%);
        color: var(--sc-on-accent);
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        box-shadow: 0 10px 32px -10px rgba(var(--sc-accent-rgb), 0.5);
        transition: all 0.25s ease;
    }
    .sc-l2-submit:hover {
        transform: translate3d(0, -2px, 0);
        box-shadow: 0 14px 40px -10px rgba(var(--sc-accent-rgb), 0.6);
    }
    .sc-l2-submit:active { transform: translate3d(0,0,0) scale(0.98); }
    .sc-l2-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

    .sc-l2-submit-ghost {
        background: transparent;
        background-image: none;
        border: 2px solid rgba(var(--sc-accent-rgb), 0.3);
        color: var(--sc-accent-strong);
        box-shadow: none;
    }
    .sc-l2-submit-ghost:hover {
        background: rgba(var(--sc-accent-rgb), 0.05);
        box-shadow: none;
    }
    .dark .sc-l2-submit-ghost {
        color: color-mix(in srgb, var(--sc-accent) 75%, #fff);
        border-color: rgba(var(--sc-accent-rgb), 0.25);
    }

    .sc-l2-switch {
        margin: 0.75rem 0 0;
        text-align: center;
        font-size: 0.8rem;
        color: #475569;
    }
    .dark .sc-l2-switch { color: #64748b; }
    .sc-l2-switch a {
        font-weight: 700;
        color: var(--sc-accent-strong);
        text-decoration: none;
        transition: color 0.2s;
    }
    .sc-l2-switch a:hover { text-decoration: underline; }
    .dark .sc-l2-switch a { color: color-mix(in srgb, var(--sc-accent) 75%, #fff); }

    .sc-l2-activate-note {
        margin: 0.6rem auto 0;
        max-width: 30rem;
        text-align: center;
        font-size: 0.72rem;
        line-height: 1.5;
        color: #94a3b8;
    }
    .dark .sc-l2-activate-note { color: #64748b; }

    /* ────────────────────────── SUCCESS PANEL ────────────────────────── */
    .sc-l2-success {
        text-align: center;
        padding: 0.5rem 0.5rem;
    }
    .sc-l2-success-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 3.75rem;
        height: 3.75rem;
        margin-bottom: 0.75rem;
        border-radius: 50%;
        color: var(--sc-accent-strong);
        background: rgba(var(--sc-accent-rgb), 0.1);
        border: 2px solid rgba(var(--sc-accent-rgb), 0.15);
        box-shadow: 0 0 40px -12px rgba(var(--sc-accent-rgb), 0.4);
    }
    .sc-l2-success-icon svg { width: 2rem; height: 2rem; }

    .sc-l2-success-hint {
        margin: 0.85rem 0 0;
        font-size: 0.78rem;
        color: #64748b;
    }
    .dark .sc-l2-success-hint { color: #94a3b8; }
    .sc-l2-success-hint a {
        font-weight: 700;
        color: var(--sc-accent-strong);
        text-decoration: none;
        transition: color 0.2s;
    }
    .sc-l2-success-hint a:hover { text-decoration: underline; }
    .dark .sc-l2-success-hint a { color: color-mix(in srgb, var(--sc-accent) 75%, #fff); }

    /* ────────────────────────── SHAKE ────────────────────────── */
    .sc-shake { animation: sc-shake 0.45s cubic-bezier(0.36, 0.07, 0.19, 0.97) both; }
    @keyframes sc-shake {
        10%, 90% { transform: translate3d(-2px, 0, 0); }
        20%, 80% { transform: translate3d(3px, 0, 0); }
        30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
        40%, 60% { transform: translate3d(4px, 0, 0); }
    }

    /* ────────────────────────── STAGGERED ────────────────────────── */
    .sc-pane .sc-l2-title,
    .sc-pane .sc-l2-subtitle,
    .sc-pane > .sc-login-form-fields,
    .sc-pane > .sc-l2-submit,
    .sc-pane > .sc-l2-switch,
    .sc-reg-grid > *,
    .sc-login-form-fields .fi-btn {
        animation: sc-field-in 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .sc-pane .sc-l2-title { animation-delay: 0.05s; }
    .sc-pane .sc-l2-subtitle { animation-delay: 0.1s; }
    .sc-pane > .sc-login-form-fields { animation-delay: 0.15s; }
    .sc-login-form-fields .fi-btn { animation-delay: 0.35s; }
    .sc-reg-grid > *:nth-child(1) { animation-delay: 0.08s; }
    .sc-reg-grid > *:nth-child(2) { animation-delay: 0.12s; }
    .sc-reg-grid > *:nth-child(3) { animation-delay: 0.16s; }
    .sc-reg-grid > *:nth-child(4) { animation-delay: 0.20s; }
    .sc-reg-grid > *:nth-child(5) { animation-delay: 0.24s; }
    .sc-reg-grid > *:nth-child(6) { animation-delay: 0.28s; }
    .sc-reg-form > .sc-l2-submit { animation-delay: 0.34s; }
    .sc-reg-form > .sc-l2-switch { animation-delay: 0.40s; }

    @keyframes sc-field-in {
        from { opacity: 0; transform: translate3d(0, 10px, 0); }
        to { opacity: 1; transform: translate3d(0, 0, 0); }
    }

    /* ────────────────────────── CARET ────────────────────────── */
    .sc-caret {
        display: inline-block;
        width: 2px;
        height: 1em;
        margin-left: 2px;
        vertical-align: -0.1em;
        background: currentColor;
        box-shadow: 0 0 10px currentColor;
        animation: sc-caret-blink 0.8s steps(2) infinite;
    }
    @keyframes sc-caret-blink {
        50% { opacity: 0; }
    }

    /* ────────────────────────── FOOTER ────────────────────────── */
    .sc-l2-legal {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        margin-top: 1.5rem;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .dark .sc-l2-legal { color: #64748b; }
    .sc-l2-legal a {
        font-weight: 600;
        color: var(--sc-accent-strong);
        text-decoration: none;
        transition: color 0.2s;
    }
    .dark .sc-l2-legal a { color: color-mix(in srgb, var(--sc-accent) 75%, #fff); }
    .sc-l2-legal a:hover { text-decoration: underline; }
    .sc-l2-legal-dot { opacity: 0.4; }

    /* ────────────────────────── RESPONSIVE ────────────────────────── */
    @media (max-width: 900px) {
        .sc-l2-body {
            grid-template-columns: 1fr;
            gap: 1.25rem;
            padding: 1.25rem 1.75rem 1.75rem;
            height: auto;
            max-height: none;
            min-height: 0;
        }
        .sc-l2-aside { min-height: 11.5rem; max-height: 13rem; }
        .sc-l2-overlay { min-height: 8.5rem; }
        .sc-ov-pane { padding: 1.25rem 1.5rem; }
        .sc-ov-pane p { max-width: 100%; }
        .sc-l2-journey { padding: 0 1.5rem 1rem; }
        .sc-reg-grid { grid-template-columns: 1fr; }
        .sc-field-full { grid-column: 1; }
        .sc-swapped .sc-l2-aside,
        .sc-swapped .sc-l2-forms { transform: none; }
        .sc-l2-forms { height: auto; max-height: none; }
        .sc-pane { padding: 0.5rem 0; height: auto; max-height: none; }
        .sc-l2-head { padding: 1.25rem 1.75rem 1rem; }
    }

    @media (max-width: 480px) {
        .sc-login2 { padding: 0.5rem; }
        .sc-l2-head { padding: 1rem 1rem 0.75rem; }
        .sc-l2-body { padding: 1rem 1rem 1.25rem; gap: 1rem; }
        .sc-l2-brand { gap: 0.75rem; }
        .sc-l2-logo { width: 3.25rem; height: 3.25rem; }
        .sc-l2-school { font-size: 1.1rem; }
        .sc-l2-motto { font-size: 0.72rem; }
        .sc-l2-aside { min-height: 9.5rem; max-height: 10.5rem; }
        .sc-l2-overlay { min-height: 6.5rem; }
        .sc-ov-pane { padding: 1rem; }
        .sc-ov-pane h3 { font-size: 1.15rem; }
        .sc-ov-icon { width: 2.1rem; height: 2.1rem; margin-bottom: 0.35rem; }
        .sc-ov-icon svg { width: 1.5rem; height: 1.5rem; }
        .sc-ov-pane p.sc-typing-target { display: none; }
        .sc-l2-journey { display: none; }
        .sc-ov-cta { width: 100%; justify-content: center; }
        .sc-l2-title { font-size: 1.2rem; }
        .sc-l2-subtitle { font-size: 0.78rem; }
        .sc-field input, .sc-field select { font-size: 0.8rem; padding: 0.5rem 0.75rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sc-ov-pane, .sc-pane, .sc-l2-pill-dot, .sc-l2-submit, .sc-ov-cta,
        .sc-l2-aside, .sc-l2-forms, .sc-l2-orb {
            transition: none !important;
            animation: none !important;
        }
        .sc-l2-card,
        .sc-pane .sc-l2-title,
        .sc-pane .sc-l2-subtitle,
        .sc-pane > .sc-login-form-fields,
        .sc-pane > .sc-l2-submit,
        .sc-pane > .sc-l2-switch,
        .sc-reg-grid > *,
        .sc-login-form-fields .fi-btn,
        .sc-shake {
            animation: none !important;
        }
        .sc-l2-pill-dot { animation: none !important; }
        .sc-caret { animation: none !important; opacity: 1; }
    }
</style>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('scRegValid', (initialRole) => ({
            regRole: initialRole || 'student',
            errors: {},
            msgs: {
                nameReq: 'Please enter your full name.',
                nameLen: 'Number of characters should not exceed 100.',
                email: 'Email address is not valid. It should be in the form name@gmail.com.',
                phone: 'Enter a valid phone number, e.g. +263 77 123 4567.',
            },
            validateField(field, value) {
                const v = (value ?? '').trim();
                switch (field) {
                    case 'name':
                        if (!v) this.errors.name = this.msgs.nameReq;
                        else if (v.length < 2 || v.length > 100) this.errors.name = this.msgs.nameLen;
                        else delete this.errors.name;
                        break;
                    case 'email':
                        if (!v || !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) this.errors.email = this.msgs.email;
                        else delete this.errors.email;
                        break;
                    case 'phone':
                        if (v && !/^\+?[\d\s().-]{7,18}$/.test(v)) this.errors.phone = this.msgs.phone;
                        else delete this.errors.phone;
                        break;
                }
            },
            clearError(field) {
                delete this.errors[field];
            },
        }));
    });
</script>

<x-sc-typewriter />
</div>
