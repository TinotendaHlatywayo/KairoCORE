@php
    /**
     * Platform (admin) console theme.
     *
     * Mirrors the school workspace "Developer's Choice 1 (Indigo + Cyan Blend)"
     * design language: fixed brand tokens, the same sidebar/topbar polish and
     * the animated gradient buttons driven by `data-sc-theme="dev_choice_1"`.
     *
     * The school panel resolves these from the tenant's SystemSettings via
     * modules.cms.dynamic-styles; the platform has no tenant, so the tokens are
     * hard-set here to keep the two consoles visually consistent in both light
     * and dark mode.
     */
    $primary = '#4f46e5'; // Indigo 600
    $accent = '#06b6d4';  // Cyan 500
    $glow = 'rgba(79, 70, 229, 0.12)';
@endphp

<script>
    // Applied before paint so themed CSS (moving gradients, accents) is live
    // on first frame and there is no flash of an unthemed console.
    document.documentElement.setAttribute('data-sc-theme', 'dev_choice_1');
</script>

<style>
    :root {
        --theme-primary: {{ $primary }};
        --theme-accent: {{ $accent }};
        --theme-glow: {{ $glow }};
        --theme-font: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        --theme-radius: 16px;
    }

    /* Enforce the shared typography across the console. */
    body, h1, h2, h3, h4, h5, h6, button, input, select, textarea, .fi-sidebar-item {
        font-family: var(--theme-font);
    }

    /* ── Sidebar item hover animations (matches the workspace) ── */
    .fi-sidebar-item {
        transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }
    .fi-sidebar-item:hover {
        transform: translateX(4px) !important;
        background: color-mix(in srgb, var(--theme-primary) 6%, transparent) !important;
    }
    .fi-sidebar-item a {
        transition: all 0.2s ease !important;
    }
    .fi-sidebar-item:hover a {
        color: var(--theme-primary) !important;
    }
    .fi-sidebar-group-label {
        transition: all 0.25s ease !important;
    }
    .fi-sidebar-group-label:hover {
        color: var(--theme-primary) !important;
    }
    .fi-sidebar-group .fi-sidebar-group-items {
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
    }

    /* ── Sidebar scrollbar ── */
    .fi-sidebar-nav::-webkit-scrollbar { width: 4px; }
    .fi-sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .fi-sidebar-nav::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 8px; }
    .fi-sidebar-nav::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    .dark .fi-sidebar-nav::-webkit-scrollbar-thumb { background: #334155; }

    /* ── Keep topbar dropdowns (avatar menu, notifications) from clipping ── */
    .fi-topbar, .fi-topbar nav, .fi-topbar header, .fi-header, .fi-topbar-item {
        overflow: visible !important;
    }
    .fi-dropdown, .fi-dropdown-panel, .fi-dropdown-list, .fi-dropdown-header, .fi-dropdown-footer {
        overflow: visible !important;
        z-index: 99999 !important;
    }
    .fi-topbar-actions, .fi-user-menu {
        overflow: visible !important;
    }

    /* Brand-coloured active nav + header accents use the theme tokens. */
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: color-mix(in srgb, var(--theme-primary) 10%, transparent) !important;
    }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label {
        color: var(--theme-primary) !important;
    }
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background: color-mix(in srgb, var(--theme-primary) 22%, transparent) !important;
    }
    .dark .fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label {
        color: #c7d2fe !important;
    }
</style>