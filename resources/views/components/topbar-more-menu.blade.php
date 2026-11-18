{{--
    Phone-sized "More" menu for the workspace topbar.

    On phones the header cannot hold the settings shortcut, the help guide,
    the sidebar-position switch and the back button at once — the old layout
    simply overflowed and clipped the account avatar. Those secondary actions
    now live in this bottom sheet, which is only rendered as a trigger below
    the 1024px breakpoint (see section 28 of filament-custom.css). Desktop
    keeps the individual buttons untouched.
--}}
@php
    $canManageSettings = \Modules\Admin\Services\PermissionRegistry::checkPermission('administration.manage_settings');
    $tenantName = ($school ?? null)?->name ?? __('Workspace');
@endphp

<div
    class="sc-topbar-more"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
>
    <button
        type="button"
        class="sc-topbar-more-btn"
        x-on:click="open = true"
        x-bind:aria-expanded="open.toString()"
        aria-haspopup="dialog"
        aria-label="{{ __('More actions') }}"
        title="{{ __('More actions') }}"
    >
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <circle cx="12" cy="5" r="1.7"/>
            <circle cx="12" cy="12" r="1.7"/>
            <circle cx="12" cy="19" r="1.7"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            class="sc-more-overlay"
        >
            <div class="sc-more-backdrop" x-on:click="open = false"></div>

            <div
                class="sc-more-sheet"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('More actions') }}"
                x-trap.noscroll="open"
                x-transition:enter="sc-more-enter"
                x-transition:enter-start="sc-more-enter-start"
                x-transition:enter-end="sc-more-enter-end"
                x-transition:leave="sc-more-leave"
                x-transition:leave-start="sc-more-leave-start"
                x-transition:leave-end="sc-more-leave-end"
            >
                <span class="sc-more-grabber" aria-hidden="true"></span>

                <div class="sc-more-head">
                    <div class="sc-more-heading">
                        <span class="sc-more-eyebrow">{{ $tenantName }}</span>
                        <h2 class="sc-more-title">{{ __('Quick actions') }}</h2>
                    </div>
                    <button
                        type="button"
                        class="sc-more-close"
                        x-on:click="open = false"
                        aria-label="{{ __('Close') }}"
                        title="{{ __('Close') }}"
                    >
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                        </svg>
                    </button>
                </div>

                <div class="sc-more-list">
                    @if ($canManageSettings)
                        <a href="{{ \App\Filament\App\Pages\SystemSettingsPage::getUrl() }}" class="sc-more-item" wire:navigate>
                            <span class="sc-more-icon">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 0 0-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 0 0-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 0 0-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 0 0-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 0 0 1.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </span>
                            <span class="sc-more-text">
                                <span class="sc-more-label">{{ __('System Settings') }}</span>
                                <span class="sc-more-desc">{{ __('School, branding and module controls') }}</span>
                            </span>
                            <svg class="sc-more-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                        </a>
                    @endif

                    <button
                        type="button"
                        class="sc-more-item"
                        x-on:click="open = false; window.dispatchEvent(new CustomEvent('sc-open-help'))"
                    >
                        <span class="sc-more-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                        <span class="sc-more-text">
                            <span class="sc-more-label">{{ __('Help Guide') }}</span>
                            <span class="sc-more-desc">{{ __('How this page and its workflow work') }}</span>
                        </span>
                        <svg class="sc-more-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </button>

                    <button
                        type="button"
                        class="sc-more-item"
                        x-on:click="open = false; scGoBack()"
                    >
                        <span class="sc-more-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                            </svg>
                        </span>
                        <span class="sc-more-text">
                            <span class="sc-more-label">{{ __('Go Back') }}</span>
                            <span class="sc-more-desc">{{ __('Return to the previous page') }}</span>
                        </span>
                        <svg class="sc-more-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </button>

                    <a href="{{ \App\Filament\App\Pages\Dashboard::getUrl() }}" class="sc-more-item" wire:navigate>
                        <span class="sc-more-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75"/>
                            </svg>
                        </span>
                        <span class="sc-more-text">
                            <span class="sc-more-label">{{ __('Workspace Dashboard') }}</span>
                            <span class="sc-more-desc">{{ __('Back to your overview') }}</span>
                        </span>
                        <svg class="sc-more-chevron" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </a>
                </div>
            </div>
        </div>
    </template>
</div>
