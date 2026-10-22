{{--
    Platform bulletin banner.

    Rendered by AppPanelProvider on every tenant page. Shows the newest
    platform announcement that is addressed to the signed-in account and
    that the platform asked to be shown as a banner. Readers can hide it for
    the rest of their session.
--}}
@php
    $palette = match ($type) {
        'warning' => ['background' => '#fef3c7', 'border' => '#f59e0b', 'text' => '#78350f'],
        'danger' => ['background' => '#fee2e2', 'border' => '#dc2626', 'text' => '#7f1d1d'],
        'maintenance' => ['background' => '#e2e8f0', 'border' => '#475569', 'text' => '#1e293b'],
        default => ['background' => '#dbeafe', 'border' => '#2563eb', 'text' => '#1e3a8a'],
    };

    $icon = match ($type) {
        'warning' => 'heroicon-s-exclamation-triangle',
        'danger' => 'heroicon-s-exclamation-circle',
        'maintenance' => 'heroicon-s-wrench-screwdriver',
        default => 'heroicon-s-megaphone',
    };
@endphp

@if (! $dismissed && $announcementId !== null)
    <div
        class="fi-platform-announcement-banner"
        style="position: sticky; top: 0; z-index: 10040; display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; padding: 0.65rem 1rem; background: {{ $palette['background'] }}; border-bottom: 2px solid {{ $palette['border'] }}; color: {{ $palette['text'] }}; font-size: 0.8125rem; line-height: 1.45; box-shadow: 0 1px 4px rgb(0 0 0 / 0.12);"
        role="status"
    >
        <div style="display: flex; align-items: flex-start; gap: 0.5rem; min-width: 0;">
            <x-filament::icon
                :icon="$icon"
                style="width: 1.125rem; height: 1.125rem; flex-shrink: 0; margin-top: 0.1rem;"
            />

            <div style="min-width: 0;">
                <strong>{{ $title }}</strong>

                @if (filled($content))
                    <div style="margin-top: 0.15rem;">{{ $content }}</div>
                @endif
            </div>
        </div>

        <button
            type="button"
            wire:click="dismiss"
            wire:loading.attr="disabled"
            title="{{ __('Hide this notice') }}"
            aria-label="{{ __('Hide this notice') }}"
            style="cursor: pointer; flex-shrink: 0; border: 1px solid {{ $palette['border'] }}; border-radius: 0.375rem; background: rgb(255 255 255 / 0.55); padding: 0.2rem 0.5rem; line-height: 1; color: {{ $palette['text'] }};"
        >
            <x-filament::icon icon="heroicon-m-x-mark" style="width: 1rem; height: 1rem;" />
        </button>
    </div>
@else
    {{-- Livewire needs a root element even when there is nothing to show. --}}
    <div class="fi-platform-announcement-banner" style="display: none;" aria-hidden="true"></div>
@endif
