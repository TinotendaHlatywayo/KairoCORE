@php
    $user = $this->getUser();
    $school = $user?->school;
    $avatarUrl = \Filament\Facades\Filament::getUserAvatarUrl($user);
    $roleLabel = $this->getRoleLabel();
    $employeePhoto = $this->getEmployeePhoto();
    $photoRejection = $this->getEmployeePhotoRejection();
@endphp

<x-dynamic-component
    :component="static::isSimple() ? 'filament-panels::page.simple' : 'filament-panels::page'"
>
<div class="sc-profile-wrap">
            @if ($user->employee)
                <div class="sc-profile-photo-card">
                    <h3 class="sc-profile-photo-title">{{ __('Passport Profile Photo') }}</h3>

                    @if ($photoRejection)
                        <div class="mb-3 rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
                            <p class="text-xs font-bold text-amber-800 dark:text-amber-200">{{ __('Your photo was removed') }}</p>
                            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">
                                {{ __('Reason') }}: {{ $photoRejection['reason'] ?: __('No reason provided') }}
                                <span class="opacity-70">· {{ $photoRejection['rejected_at']?->format('d M Y') }}</span>
                            </p>
                            <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Please upload a new passport-style photo below.') }}</p>
                        </div>
                    @endif

                    <x-passport-photo-uploader
                        wire-method="savePhoto"
                        :current-photo="$employeePhoto"
                        :has-photo="filled($employeePhoto)"
                        label="{{ __('Staff Profile Photo') }}"
                    />
                </div>
            @endif

            <div class="sc-profile-header">
            <div class="sc-profile-avatar">
                @if ($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" />
                @else
                    <svg viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="24" cy="18" r="9" fill="currentColor" opacity="0.9"/>
                        <path d="M9 41c0-8.3 6.7-15 15-15s15 6.7 15 15" fill="currentColor" opacity="0.75"/>
                    </svg>
                @endif
            </div>
            <div class="sc-profile-meta">
                <h2 class="sc-profile-name">{{ $user->name }}</h2>
                <div class="sc-profile-chips">
                    <span class="sc-profile-chip">{{ $roleLabel }}</span>
                    @if ($school)
                        <span class="sc-profile-chip sc-profile-chip-soft">{{ $school->name }}</span>
                    @endif
                </div>
                <p class="sc-profile-email">{{ $user->email }}</p>
            </div>
        </div>

        <x-filament-panels::form id="form" wire:submit="save">
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </div>

<style>
    .sc-profile-wrap { max-width: 44rem; }

    .sc-profile-photo-card {
        margin-bottom: 1.25rem;
        padding: 1.25rem 1.5rem;
        border-radius: 1.1rem;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 18px 40px -22px rgba(15, 23, 42, 0.35);
    }
    .dark .sc-profile-photo-card {
        background: rgba(15, 23, 42, 0.75);
        border-color: rgba(255, 255, 255, 0.1);
    }
    .sc-profile-photo-title {
        margin: 0 0 0.75rem;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
    }
    .dark .sc-profile-photo-title { color: #f8fafc; }

    .sc-profile-header {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        margin-bottom: 1.5rem;
        padding: 1.4rem 1.5rem;
        border-radius: 1.1rem;
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 18px 40px -22px rgba(15, 23, 42, 0.35);
    }
    .dark .sc-profile-header {
        background: rgba(15, 23, 42, 0.75);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .sc-profile-avatar {
        width: 4.5rem;
        height: 4.5rem;
        flex: none;
        border-radius: 1.25rem;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #15803d, #0f766e);
        color: #fff;
        box-shadow: 0 12px 26px -12px rgba(15, 118, 110, 0.7);
    }
    .sc-profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .sc-profile-avatar svg { width: 55%; height: 55%; }

    .sc-profile-meta { min-width: 0; }
    .sc-profile-name {
        margin: 0;
        font-size: 1.3rem;
        font-weight: 800;
        letter-spacing: -0.015em;
        color: #0f172a;
    }
    .dark .sc-profile-name { color: #f8fafc; }

    .sc-profile-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-top: 0.45rem;
    }
    .sc-profile-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.28rem 0.7rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        color: #065f46;
        background: rgba(5, 150, 105, 0.12);
        border: 1px solid rgba(5, 150, 105, 0.25);
    }
    .sc-profile-chip-soft {
        color: #475569;
        background: rgba(100, 116, 139, 0.1);
        border-color: rgba(100, 116, 139, 0.22);
    }
    .dark .sc-profile-chip { color: #6ee7b7; background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.3); }
    .dark .sc-profile-chip-soft { color: #cbd5e1; background: rgba(148, 163, 184, 0.12); border-color: rgba(148, 163, 184, 0.22); }

    .sc-profile-email {
        margin: 0.5rem 0 0;
        font-size: 0.85rem;
        color: #64748b;
    }
    .dark .sc-profile-email { color: #94a3b8; }

    @media (max-width: 520px) {
        .sc-profile-header { flex-direction: column; align-items: flex-start; gap: 0.9rem; }
    }
</style>
</x-dynamic-component>