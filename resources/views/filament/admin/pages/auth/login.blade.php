@php
    $logoUrl = asset('images/Transparent Logo.png');
    if (! file_exists(public_path('images/Transparent Logo.png')) && file_exists(public_path('images/TransparentLogo.png'))) {
        $logoUrl = asset('images/TransparentLogo.png');
    }
@endphp

<div>
    <x-auth-login-panel
        variant="admin"
        :logo="$logoUrl"
        brand="SchoolCore Enterprise"
        badge="Platform Control"
        title="Super Admin"
        :subtitle="__('Sign in to the SchoolCore platform control center.')"
        :actions="$this->getCachedFormActions()"
        footer="Secure Multi-Tenant SaaS Architecture"
    >
        @if ($this->notice)
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
                {{ $this->notice }}
            </div>
        @endif

        {{ $this->form }}

        <div class="mt-4">
            @include('auth.google-button')
        </div>
    </x-auth-login-panel>

    <style>
        {{-- The shared login layout widens sc-login-main to 64rem for the split-pane
             tenant login; the single-column Super Admin card stays compact. --}}
        main.sc-login-main { max-width: 26rem; }
    </style>
</div>
