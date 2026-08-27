{{-- Shared premium glassmorphism login card used by the School/Tenant login and
     the Super Admin login. The card is ~18% transparent with a strong backdrop
     blur so the animated school-life scene glows through subtly while every
     field stays crisp and readable. Pure CSS + the Filament form slot — no JS,
     never overlaps the form's pointer events. --}}

@props([
    'variant' => 'school',          // 'school' | 'admin'
    'title' => 'Welcome back',
    'subtitle' => null,
    'brand' => 'Kairo CORE',        // school name or platform name
    'logo' => null,                 // URL or null
    'badge' => 'School Portal',
    'footer' => null,
    'actions' => null,
    // The "Forgot password?" link is rendered by Filament itself as a hint on
    // the password field when the panel has password reset enabled — no separate
    // link is needed, keeping it a single canonical action.
])

<div @class([
    'sc-login-card',
    'sc-login-card-admin' => $variant === 'admin',
])>
    <div class="sc-login-card-top">
        <div class="sc-login-logo" @if ($logo) style="background-image:url('{{ $logo }}');" @endif>
            @if (! $logo)
                <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M24 7 2 17.5 24 28 46 17.5 24 7Z" fill="currentColor"/>
                    <path d="M42 20.5v9" stroke="currentColor" stroke-width="2.6" stroke-linecap="round"/>
                    <circle cx="44" cy="31.5" r="2.6" fill="currentColor"/>
                </svg>
            @endif
        </div>

        <span class="sc-login-badge">
            <span class="sc-login-badge-dot"></span>
            {{ $badge }}
        </span>
    </div>

    <h2 class="sc-login-title" data-sc-typing="{{ $title }}">{{ $title }}</h2>

    @if ($subtitle)
        <p class="sc-login-subtitle">{{ $subtitle }}</p>
    @endif

    <div class="sc-login-form">
        <x-filament-panels::form id="form" wire:submit="authenticate">
            <div class="sc-login-fields">
                {{ $slot }}
            </div>

            @if ($actions)
                <x-filament-panels::form.actions
                    :actions="$actions"
                    :full-width="true"
                />
            @endif
        </x-filament-panels::form>
    </div>

    <div class="sc-login-brandline">{{ $brand }}</div>

    @if ($footer)
        <p class="sc-login-footer">{{ $footer }}</p>
    @endif

<style>
    /* ─────────────────────────────── CARD SHELL ─────────────────────────────── */
    .sc-login-card {
        position: relative;
        width: 100%;
        border-radius: 1.75rem;
        padding: 2.5rem 2.25rem 1.9rem;
        background:
            radial-gradient(120% 90% at 15% 0%, rgba(255, 255, 255, 0.92), rgba(255, 255, 255, 0.65) 58%);
        -webkit-backdrop-filter: blur(26px) saturate(160%);
        backdrop-filter: blur(26px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, 0.78);
        box-shadow:
            0 2px 4px rgba(15, 23, 42, 0.06),
            0 30px 70px -24px rgba(15, 23, 42, 0.35),
            inset 0 1px 0 rgba(255, 255, 255, 0.9);
        color: #0f172a;
        overflow: hidden;
    }

    .dark .sc-login-card {
        background:
            radial-gradient(120% 90% at 15% 0%, rgba(30, 41, 72, 0.88), rgba(10, 16, 34, 0.74) 62%);
        border: 1px solid rgba(255, 255, 255, 0.14);
        box-shadow:
            0 2px 4px rgba(2, 6, 23, 0.5),
            0 36px 80px -24px rgba(2, 6, 23, 0.85),
            inset 0 1px 0 rgba(255, 255, 255, 0.08);
        color: #e2e8f0;
    }

    /* Subtle cinematic top edge that echoes the scene's sky/graduation palette. */
    .sc-login-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 2.25rem;
        right: 2.25rem;
        height: 3px;
        border-radius: 0 0 6px 6px;
        background: linear-gradient(90deg, #5b4fe9, #7c6cf0 45%, #06b6d4 100%);
        opacity: 0.85;
    }
    .dark .sc-login-card::before {
        background: linear-gradient(90deg, #818cf8, #a78bfa 45%, #22d3ee 100%);
        opacity: 1;
    }

    .sc-login-card-admin::before {
        background: linear-gradient(90deg, #0ea5e9, #22d3ee 45%, #6d5ce6 100%);
    }
    .dark .sc-login-card-admin::before {
        background: linear-gradient(90deg, #38bdf8, #67e8f9 45%, #a78bfa 100%);
    }

    /* ─────────────────────────────── BRAND / LOGO ───────────────────────────── */
    .sc-login-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .sc-login-logo {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 1.1rem;
        flex: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        background-color: #5b4fe9;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        box-shadow:
            0 10px 24px -8px rgba(91, 79, 233, 0.55),
            inset 0 1px 0 rgba(255, 255, 255, 0.45);
        outline: 3px solid rgba(255, 255, 255, 0.75);
        outline-offset: 1px;
    }
    .sc-login-card-admin .sc-login-logo {
        background-color: #0284c7;
        box-shadow:
            0 10px 24px -8px rgba(14, 165, 233, 0.55),
            inset 0 1px 0 rgba(255, 255, 255, 0.4);
    }
    .dark .sc-login-logo {
        outline-color: rgba(255, 255, 255, 0.16);
    }
    .sc-login-logo svg {
        width: 46%;
        height: 46%;
    }

    .sc-login-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #4338ca;
        background: rgba(91, 79, 233, 0.12);
        border: 1px solid rgba(91, 79, 233, 0.22);
        padding: 0.42rem 0.8rem;
        border-radius: 9999px;
        white-space: nowrap;
    }
    .dark .sc-login-badge {
        color: #c7d2fe;
        background: rgba(129, 140, 248, 0.16);
        border-color: rgba(129, 140, 248, 0.3);
    }
    .sc-login-card-admin .sc-login-badge {
        color: #0369a1;
        background: rgba(14, 165, 233, 0.13);
        border-color: rgba(14, 165, 233, 0.25);
    }
    .dark .sc-login-card-admin .sc-login-badge {
        color: #bae6fd;
        background: rgba(56, 189, 248, 0.16);
        border-color: rgba(56, 189, 248, 0.3);
    }

    .sc-login-badge-dot {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #5b4fe9, #06b6d4);
        box-shadow: 0 0 8px rgba(91, 79, 233, 0.75);
        animation: sc-login-pulse 2.4s ease-in-out infinite;
    }
    @keyframes sc-login-pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.3); opacity: 0.7; }
    }

    .sc-login-title {
        font-size: clamp(1.45rem, 1rem + 1.6vw, 1.9rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        line-height: 1.15;
        margin: 0;
        color: #0f172a;
    }
    .dark .sc-login-title { color: #f8fafc; }

    .sc-login-subtitle {
        margin: 0.55rem 0 0;
        font-size: 0.9rem;
        line-height: 1.55;
        color: #475569;
    }
    .dark .sc-login-subtitle { color: #94a3b8; }

    .sc-login-form {
        margin-top: 1.6rem;
    }

    /* ─────────────── FIELDS: tasteful frosted inputs (overrides the ────────────
       panel-wide animated conic border so the login stays elegant) ─────────── */
    .sc-login-form .fi-fo-field-wrp {
        margin-bottom: 0.35rem;
    }

    .sc-login-form .fi-input-wrp {
        animation: none !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1.5px solid #cbd5e1 !important;
        border-radius: 0.85rem !important;
        box-shadow: none !important;
        transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
    }
    .dark .sc-login-form .fi-input-wrp {
        background: rgba(2, 6, 23, 0.55) !important;
        border-color: #334155 !important;
    }

    .sc-login-form .fi-input-wrp:hover {
        border-color: #94a3b8 !important;
    }
    .dark .sc-login-form .fi-input-wrp:hover {
        border-color: #475569 !important;
    }

    .sc-login-form .fi-input-wrp:focus-within {
        border-color: #5b4fe9 !important;
        background: rgba(255, 255, 255, 0.97) !important;
        box-shadow:
            0 0 0 3px rgba(91, 79, 233, 0.18),
            0 0 24px -8px rgba(91, 79, 233, 0.55) !important;
    }
    .sc-login-card-admin .sc-login-form .fi-input-wrp:focus-within {
        border-color: #0284c7 !important;
        box-shadow:
            0 0 0 3px rgba(14, 165, 233, 0.2),
            0 0 24px -8px rgba(14, 165, 233, 0.55) !important;
    }
    .dark .sc-login-form .fi-input-wrp:focus-within {
        background: rgba(15, 23, 42, 0.85) !important;
        box-shadow:
            0 0 0 3px rgba(129, 140, 248, 0.22),
            0 0 26px -8px rgba(129, 140, 248, 0.6) !important;
    }

    .sc-login-form .fi-input {
        color: #0f172a !important;
        caret-color: #5b4fe9;
    }
    .dark .sc-login-form .fi-input {
        color: #f1f5f9 !important;
        caret-color: #a5b4fc;
    }

    /* Field labels */
    .sc-login-form .fi-fo-field-label {
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.01em;
        color: #334155;
    }
    .dark .sc-login-form .fi-fo-field-label { color: #cbd5e1; }

    /* Remember-me checkbox */
    .sc-login-form .fi-checkbox-input {
        accent-color: #5b4fe9;
        border-radius: 0.4rem;
    }
    .sc-login-card-admin .sc-login-form .fi-checkbox-input { accent-color: #0284c7; }

    /* Forgot password + validation links */
    .sc-login-form a,
    .sc-login-form .fi-input-wrp .fi-input-wrp-hint a,
    .sc-login-form .fi-fo-field-hint a {
        font-weight: 700;
        font-size: 0.78rem;
        color: #4338ca;
    }
    .dark .sc-login-form a,
    .dark .sc-login-form .fi-fo-field-hint a { color: #a5b4fc; }

    /* Validation error messages */
    .sc-login-form .fi-fo-field-error-message,
    .sc-login-form .fi-validation-message {
        font-size: 0.76rem;
        font-weight: 700;
        color: #e11d48;
    }

    /* Submit button — brand gradient, always full width from Filament actions */
    .sc-login-form .fi-btn.fi-color-primary {
        background-image: linear-gradient(135deg, #5b4fe9 0%, #7c6cf0 55%, #4338ca 100%) !important;
        background-color: #5b4fe9 !important;
        border: none !important;
        border-radius: 0.85rem !important;
        font-weight: 800 !important;
        font-size: 0.92rem !important;
        letter-spacing: 0.01em;
        box-shadow: 0 14px 30px -12px rgba(91, 79, 233, 0.7) !important;
    }
    .sc-login-card-admin .sc-login-form .fi-btn.fi-color-primary {
        background-image: linear-gradient(135deg, #0ea5e9 0%, #38bdf8 55%, #0284c7 100%) !important;
        background-color: #0284c7 !important;
        box-shadow: 0 14px 30px -12px rgba(14, 165, 233, 0.7) !important;
    }
    .sc-login-form .fi-btn.fi-color-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 36px -12px rgba(91, 79, 233, 0.8) !important;
    }

    /* Loading state lives on the Filament button automatically via wire:loading */

    /* Brand line + footer */
    .sc-login-brandline {
        margin-top: 1.5rem;
        padding-top: 1.1rem;
        text-align: center;
        font-size: 0.74rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #64748b;
        border-top: 1px solid rgba(100, 116, 139, 0.18);
    }
    .dark .sc-login-brandline {
        color: #94a3b8;
        border-top-color: rgba(148, 163, 184, 0.16);
    }

    .sc-login-footer {
        margin: 0.85rem 0 0;
        text-align: center;
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .dark .sc-login-footer { color: #64748b; }

    /* ─────────────────────────────── RESPONSIVE ─────────────────────────────── */
    @media (max-width: 520px) {
        .sc-login-card {
            padding: 1.9rem 1.4rem 1.5rem;
            border-radius: 1.35rem;
        }
        .sc-login-card::before { left: 1.4rem; right: 1.4rem; }
    }

    @media (prefers-reduced-motion: reduce) {
        .sc-login-badge-dot {
            animation: none !important;
        }
        .sc-login-form .fi-btn.fi-color-primary:hover {
            transform: none;
        }
    }

    .sc-login-title .sc-caret {
        display: inline-block;
        width: 2px;
        height: 1em;
        margin-left: 3px;
        vertical-align: -0.1em;
        background: currentColor;
        animation: sc-caret-blink 0.8s steps(2) infinite;
    }
    @keyframes sc-caret-blink {
        50% { opacity: 0; }
    }
</style>

<x-sc-typewriter />
</div>