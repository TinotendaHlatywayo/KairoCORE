@php
    $service = app(\App\Navigation\ModuleNavigationService::class);
    $module = $service->currentModule();
    $allTabs = $module ? array_merge($service->moduleTabs($module), $service->moduleMoreTabs($module)) : [];
    $activeLabel = $module ? $service->activeTabLabel($module) : null;
    // Group consecutive tabs that share a "group" label into dropdowns so long
    // module menus (e.g. Finance) stay compact instead of an overwhelming flat list.
    $groups = [];
    foreach ($allTabs as $tab) {
        $g = $tab['group'] ?? null;
        if ($g === null) {
            $groups[] = ['type' => 'pill', 'label' => null, 'tabs' => [$tab]];
        } else {
            $last = count($groups) - 1;
            if ($last >= 0 && $groups[$last]['type'] === 'dropdown' && $groups[$last]['label'] === $g) {
                $groups[$last]['tabs'][] = $tab;
            } else {
                $groups[] = ['type' => 'dropdown', 'label' => $g, 'tabs' => [$tab]];
            }
        }
    }
@endphp

@if(request()->has('scnav-debug'))
    <div id="scnav-debug" style="display:none;"
         data-path="{{ request()->path() }}"
         data-module="{{ $module['slug'] ?? 'NULL' }}"
         data-tabcount="{{ count($allTabs) }}"
         data-tabs="{{ json_encode(array_column($allTabs, 'label')) }}"></div>
@endif

@if ($module && count($allTabs) > 0 && ! request()->routeIs('*.courses.edit') && ! request()->routeIs('*courses.edit'))
<div class="sc-module-navigation" x-data="{}">
    <div class="sc-module-head">
        <span class="sc-module-icon">
            @svg($module['icon'], 'h-5 w-5')
        </span>
        <div class="sc-module-meta">
            <div class="sc-module-title">{{ $module['label'] }}</div>
            @if (! empty($module['description']))
                <div class="sc-module-desc">{{ $module['description'] }}</div>
            @endif
        </div>
    </div>

    <div class="sc-module-tabs-wrap">
        <div class="sc-module-tabs" x-ref="scTabs" role="tablist">
            @foreach ($groups as $group)
                @if ($group['type'] === 'pill')
                    @php $pill = $group['tabs'][0]; @endphp
                    <a
                        href="{{ url($pill['url']) }}"
                        role="tab"
                        aria-selected="{{ $pill['label'] === $activeLabel ? 'true' : 'false' }}"
                        class="sc-tab {{ $pill['label'] === $activeLabel ? 'is-active' : '' }}"
                        @if ($pill['label'] === $activeLabel) aria-current="page" @endif
                    >
                        @if (! empty($pill['icon']))
                            @svg($pill['icon'], 'sc-tab-icon')
                        @endif
                        <span>{{ $pill['label'] }}</span>
                        @if ($pill['label'] === $activeLabel)
                            <span class="sc-tab-active-dot" aria-hidden="true"></span>
                        @endif
                    </a>
                @else
                    @php
                        $groupActive = collect($group['tabs'])->contains(fn ($t) => $t['label'] === $activeLabel);
                    @endphp
                    <div class="sc-module-more" x-data="{ open: false }">
                        <button
                            type="button"
                            class="sc-more-btn {{ $groupActive ? 'is-group-active' : '' }}"
                            :class="open ? 'is-open' : ''"
                            @click="open = !open"
                            :aria-expanded="open ? 'true' : 'false'"
                        >
                            <span>{{ $group['label'] }}</span>
                            @svg('heroicon-o-chevron-down', 'sc-chevron')
                        </button>
                        <div
                            class="sc-more-menu"
                            x-show="open"
                            x-cloak
                            x-transition.opacity
                            @click.outside="open = false"
                            @keydown.escape.window="open = false"
                        >
                            @foreach ($group['tabs'] as $tab)
                                @php $tabIsActive = $tab['label'] === $activeLabel; @endphp
                                <a
                                    href="{{ url($tab['url']) }}"
                                    class="sc-more-item {{ $tabIsActive ? 'is-active' : '' }}"
                                    @click="open = false"
                                >
                                    <span>{{ $tab['label'] }}</span>
                                    @if ($tabIsActive)
                                        <span class="sc-more-item-dot" aria-hidden="true"></span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</div>

<style>
    .sc-module-navigation {
        position: sticky;
        top: 0;
        /* Must stay BELOW the Filament topbar (z-20) so the user/profile
           dropdown is never painted underneath this bar on dashboard pages. */
        z-index: 10;
        margin: 1.5rem 0 1.25rem;
        padding-inline-start: 1.25rem;
        padding-inline-end: 1.25rem;
        padding-top: 0.75rem;
        padding-bottom: 0.25rem;
        background: linear-gradient(180deg, rgba(248, 250, 252, 0.92) 0%, rgba(248, 250, 252, 0.86) 70%, rgba(248, 250, 252, 0) 100%);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        border-bottom: 1px solid transparent;
    }

    .dark .sc-module-navigation {
        background: linear-gradient(180deg, rgba(15, 23, 42, 0.92) 0%, rgba(15, 23, 42, 0.86) 70%, rgba(15, 23, 42, 0) 100%);
    }

    .sc-module-head {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1rem;
    }

    .sc-module-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: clamp(2.5rem, 4vw, 3.25rem);
        height: clamp(2.5rem, 4vw, 3.25rem);
        flex: none;
        border-radius: 0.85rem;
        color: #fff;
        background: linear-gradient(135deg, var(--theme-primary, #15803d) 0%, var(--theme-accent, #eab308) 130%);
        box-shadow: 0 8px 20px -8px var(--theme-primary, #15803d);
    }

    .sc-module-icon svg {
        width: clamp(1.25rem, 2vw, 1.6rem);
        height: clamp(1.25rem, 2vw, 1.6rem);
    }

    .sc-module-meta {
        min-width: 0;
    }

    .sc-module-title {
        font-size: clamp(1.35rem, 2.6vw, 2.1rem);
        font-weight: 800;
        line-height: 1.15;
        color: var(--gray-900, #111827);
        letter-spacing: -0.02em;
        background: linear-gradient(120deg, var(--theme-primary, #15803d) 0%, var(--theme-accent, #eab308) 90%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
    }

    .dark .sc-module-title {
        background: linear-gradient(120deg, #4ade80 0%, #facc15 90%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        color: transparent;
    }

    .sc-module-desc {
        margin-top: 0.3rem;
        font-size: clamp(0.85rem, 1.4vw, 1.05rem);
        line-height: 1.5;
        color: var(--gray-500, #6b7280);
        max-width: 60ch;
    }

    .dark .sc-module-desc {
        color: var(--gray-400, #9ca3af);
    }

    .sc-module-tabs-wrap {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .sc-module-tabs {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        flex: 1 1 auto;
        min-width: 0;
        padding: 0.25rem;
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(91, 79, 233, 0.4) transparent;
        -webkit-overflow-scrolling: touch;
        background: var(--gray-100, #f1f5f9);
        border: 1px solid var(--gray-200, #e2e8f0);
        border-radius: 9999px;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
        background-image:
            linear-gradient(90deg, rgba(241, 245, 249, 0.9), rgba(241, 245, 249, 0)) left,
            linear-gradient(90deg, rgba(241, 245, 249, 0), rgba(241, 245, 249, 0.9)) right;
        background-repeat: no-repeat;
        background-size: 20px 100%, 20px 100%;
        background-attachment: local, local;
    }

    .dark .sc-module-tabs {
        background: var(--gray-800, #1e293b);
        border-color: var(--gray-700, #334155);
        background-image:
            linear-gradient(90deg, rgba(30, 41, 59, 0.9), rgba(30, 41, 59, 0)) left,
            linear-gradient(90deg, rgba(30, 41, 59, 0), rgba(30, 41, 59, 0.9)) right;
    }

    .sc-module-tabs::-webkit-scrollbar {
        height: 6px;
    }
    .sc-module-tabs::-webkit-scrollbar-track {
        background: transparent;
    }
    .sc-module-tabs::-webkit-scrollbar-thumb {
        background: rgba(91, 79, 233, 0.35);
        border-radius: 9999px;
    }
    .sc-module-tabs::-webkit-scrollbar-thumb:hover {
        background: var(--sc-brand);
    }

    .sc-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.6rem 1.1rem;
        font-size: clamp(0.8rem, 1.05vw, 0.94rem);
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        color: var(--gray-700, #334155);
        background: var(--gray-50, #f8fafc);
        border: 1px solid var(--gray-200, #e2e8f0);
        border-radius: 9999px;
        box-shadow: 0 2px 6px -2px rgba(15, 23, 42, 0.12);
        text-decoration: none;
        cursor: pointer;
        transition: color 180ms ease, background-color 180ms ease, box-shadow 180ms ease, transform 180ms ease, border-color 180ms ease, padding-right 180ms ease;
    }

    .sc-tab::after {
        content: '\203A';
        position: absolute;
        inset-inline-end: -0.3rem;
        font-size: 0.9rem;
        color: var(--gray-400, #94a3b8);
        opacity: 0;
        transition: opacity 180ms ease, color 180ms ease, transform 180ms ease;
        pointer-events: none;
    }

    .sc-tab:hover {
        color: var(--gray-900, #0f172a);
        background: var(--gray-100, #f1f5f9);
        border-color: var(--gray-300, #cbd5e1);
        box-shadow: 0 0 0 1px var(--sc-brand-soft), 0 0 24px -8px var(--sc-brand), 0 6px 16px -6px rgba(15, 23, 42, 0.18);
        transform: translateY(-1px) scale(1.05);
        transition: color 180ms ease, background-color 180ms ease, box-shadow 180ms ease, transform 200ms cubic-bezier(0.34, 1.56, 0.64, 1), border-color 180ms ease;
    }

    .sc-tab:hover::after {
        opacity: 1;
        color: var(--sc-brand, #4f46e5);
        transform: translateX(2px);
    }

    .dark .sc-tab:hover {
        color: var(--gray-50, #f8fafc);
        background: var(--gray-700, #334155);
        border-color: var(--gray-600, #475569);
    }

    .sc-tab.is-active {
        color: #ffffff;
        background: linear-gradient(135deg, var(--theme-primary, #0d9488) 0%, var(--theme-accent, #06b6d4) 130%);
        box-shadow:
            0 6px 16px -6px var(--theme-primary, #0d9488),
            0 1px 3px rgba(15, 23, 42, 0.18);
        font-weight: 700;
        transform: translateY(-1px);
        border-color: var(--theme-primary, #0d9488);
    }

    .sc-tab.is-active::after {
        content: '';
        opacity: 0;
    }

    .sc-tab.is-active:hover {
        color: #ffffff;
        background: linear-gradient(135deg, var(--theme-primary, #0d9488) 0%, var(--theme-accent, #06b6d4) 130%);
    }

    .dark .sc-tab.is-active {
        color: #ffffff;
    }

    .sc-tab-active-dot {
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 0 6px rgba(255, 255, 255, 0.9);
        flex: none;
    }

    .sc-tab-icon {
        width: 1rem;
        height: 1rem;
        flex: none;
    }

    .sc-module-more {
        position: relative;
        flex: none;
    }

    .sc-more-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 0.85rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--gray-600, #475569);
        border-radius: 9999px;
        cursor: pointer;
        transition: color 150ms ease, background-color 150ms ease;
        background: transparent;
        border: none;
    }

    .sc-more-btn:hover {
        color: var(--gray-900, #0f172a);
        background-color: color-mix(in srgb, var(--gray-500, #6b7280) 10%, transparent);
    }

    .dark .sc-more-btn:hover {
        color: var(--gray-50, #f8fafc);
        background-color: color-mix(in srgb, var(--gray-500, #6b7280) 20%, transparent);
    }

    .sc-more-btn.is-open {
        color: var(--gray-900, #0f172a);
        background-color: color-mix(in srgb, var(--theme-primary, #0d9488) 12%, transparent);
    }

    .sc-more-btn.is-group-active {
        color: #ffffff;
        background: linear-gradient(135deg, var(--theme-primary, #0d9488) 0%, var(--theme-accent, #06b6d4) 130%);
        box-shadow: 0 6px 16px -6px var(--theme-primary, #0d9488);
    }

    .dark .sc-more-btn.is-group-active {
        color: #ffffff;
    }

    .sc-chevron {
        width: 1rem;
        height: 1rem;
        transition: transform 150ms ease;
        flex: none;
    }

    .rotate-180 {
        transform: rotate(180deg);
    }

    .sc-more-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 0.25rem);
        z-index: 40;
        min-width: 12rem;
        padding: 0.375rem;
        background: var(--gray-50, #f9fafb);
        border: 1px solid var(--gray-200, #e5e7eb);
        border-radius: 0.75rem;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .dark .sc-more-menu {
        background: var(--gray-800, #1f2937);
        border-color: var(--gray-700, #374151);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    }

    .sc-more-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.5rem 0.625rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--gray-600, #4b5563);
        border-radius: 0.5rem;
        transition: background-color 150ms ease, color 150ms ease;
    }

    .sc-more-item:hover {
        background-color: color-mix(in srgb, var(--gray-500, #6b7280) 8%, transparent);
        color: var(--gray-900, #111827);
    }

    .dark .sc-more-item {
        color: var(--gray-300, #d1d5db);
    }

    .dark .sc-more-item:hover {
        background-color: color-mix(in srgb, var(--gray-500, #6b7280) 16%, transparent);
        color: var(--gray-50, #f9fafb);
    }

    .sc-more-item.is-active {
        color: var(--primary-600, #15803d);
        background-color: color-mix(in srgb, var(--primary-600, #15803d) 8%, transparent);
    }

    .dark .sc-more-item.is-active {
        color: var(--primary-400, #4ade80);
        background-color: color-mix(in srgb, var(--primary-400, #4ade80) 14%, transparent);
    }

    .sc-more-item-dot {
        width: 5px;
        height: 5px;
        border-radius: 9999px;
        background: var(--primary-600, #15803d);
        flex: none;
    }

    @media (max-width: 640px) {
        .sc-module-tabs {
            -webkit-overflow-scrolling: touch;
        }
        .sc-tab {
            padding: 0.5rem 0.7rem;
            touch-action: pan-x;
        }
    }

    [x-cloak] {
        display: none !important;
    }
</style>
@endif
