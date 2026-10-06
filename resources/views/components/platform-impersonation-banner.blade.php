{{--
    Persistent "you are working in someone else's school" banner.

    Rendered by AppPanelProvider on every tenant page while a platform
    hand-off is active. Deliberately loud: the administrator must never be
    able to mistake edits made here for the school's own work.
--}}
@php
    /** @var array|null $platformImpersonation */
    $impersonation = $platformImpersonation ?? null;
@endphp

@if ($impersonation)
    <div
        class="fi-platform-impersonation-banner"
        style="position: sticky; top: 0; z-index: 10050; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.6rem 1rem; background: #7c2d12; color: #fff; font-size: 0.8125rem; line-height: 1.4; box-shadow: 0 2px 6px rgb(0 0 0 / 0.25);"
        role="alert"
    >
        <div style="display: flex; align-items: center; gap: 0.5rem; min-width: 0;">
            <x-filament::icon
                icon="heroicon-s-exclamation-triangle"
                style="width: 1.125rem; height: 1.125rem; flex-shrink: 0;"
            />

            <span style="min-width: 0;">
                <strong>{{ __('System Administrator mode') }}</strong>
                &mdash;
                {{ __('You are working inside') }}
                <strong>{{ $impersonation['school_name'] }}</strong>
                {{ __('as') }}
                <strong>{{ __('System Administrator') }}</strong>, {{ __('not as one of their own staff.') }}
                <span class="opacity-80">
                    {{ __('Signed in as :name', ['name' => $impersonation['platform_user_name']]) }}
                </span>
            </span>
        </div>

        <div style="display: flex; align-items: center; gap: 0.75rem; flex-shrink: 0;">
            <span
                style="opacity: 0.85; white-space: nowrap;"
                x-data="{}"
            >{{ __('Session ends :time', ['time' => \Illuminate\Support\Carbon::parse($impersonation['expires_at'])->format('H:i')]) }}</span>

            <form method="POST" action="{{ route('platform.impersonation.exit', ['tenant' => $impersonation['school_subdomain']]) }}">
                @csrf
                <button
                    type="submit"
                    style="cursor: pointer; border: 1px solid rgb(255 255 255 / 0.5); border-radius: 0.375rem; background: rgb(255 255 255 / 0.12); padding: 0.3rem 0.75rem; font-size: 0.75rem; font-weight: 600; color: #fff;"
                >
                    {{ __('Leave school') }}
                </button>
            </form>
        </div>
    </div>
@endif