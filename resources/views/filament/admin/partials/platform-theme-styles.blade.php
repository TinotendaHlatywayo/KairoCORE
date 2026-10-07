@php
    use Filament\Support\Colors\Color;

    /**
     * Platform (admin) console theme.
     *
     * The school workspace uses the "Developer's Choice 1 (Indigo + Cyan Blend)"
     * language. The platform keeps that same base design but colour-codes each
     * navigation area so the super-admin console is visually organised:
     *
     *   core          Indigo + Cyan   Command Center, Tenants, Intelligence, System Health
     *   communication  Sky + Violet    Platform Messages, Billing Messages, Announcements
     *   billing        Emerald + Teal  Plans, Subscriptions, Invoices, Payments, Billing Settings
     *   operations     Amber + Orange  Backup Manager, Maintenance, Templates, Audit Logs
     *
     * Filament emits its palette as `:root { --primary-*: R, G, B }` inside
     * `@filamentStyles` (early in <head>). This partial renders at HEAD_END, so
     * re-declaring those variables here overrides every Filament component,
     * form focus ring and the shared filament-custom.css accents per category.
     */
    $map = [
        'filament.admin.pages.dashboard' => 'core',
        'filament.admin.resources.schools' => 'core',
        'filament.admin.resources.tenant-healths' => 'core',
        'filament.admin.pages.platform-intelligence-dashboard' => 'core',

        'filament.admin.resources.platform-messages' => 'communication',
        'filament.admin.resources.platform-billing-messages' => 'communication',
        'filament.admin.resources.platform-announcements' => 'communication',

        'filament.admin.resources.saa-s-plans' => 'billing',
        'filament.admin.resources.school-subscriptions' => 'billing',
        'filament.admin.resources.saa-s-invoices' => 'billing',
        'filament.admin.resources.saa-s-transactions' => 'billing',
        'filament.admin.resources.pending-payments' => 'billing',
        'filament.admin.pages.platform-billing-settings-page' => 'billing',

        'filament.admin.pages.platform-backup-manager' => 'operations',
        'filament.admin.pages.platform-maintenance-page' => 'operations',
        'filament.admin.resources.platform-templates' => 'operations',
        'filament.admin.resources.platform-audit-logs' => 'operations',
    ];

    $routeName = request()->route()?->getName() ?? '';

    $category = 'core';

    foreach ($map as $prefix => $cat) {
        if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
            $category = $cat;
            break;
        }
    }

    $palette = [
        'core' => ['shades' => Color::Indigo, 'accent' => '#06b6d4'],
        'communication' => ['shades' => Color::Sky, 'accent' => '#8b5cf6'],
        'billing' => ['shades' => Color::Emerald, 'accent' => '#14b8a6'],
        'operations' => ['shades' => Color::Amber, 'accent' => '#f97316'],
    ];

    $shades = $palette[$category]['shades'];
    $accent = $palette[$category]['accent'];
    $primary = $shades[600];
    $primaryHex = sprintf(
        '#%02x%02x%02x',
        ...array_map('intval', array_map('trim', explode(',', $primary)))
    );
@endphp

<script>
    // Applied before paint so themed CSS (moving gradients, accents) is live
    // on first frame and there is no flash of an unthemed console.
    document.documentElement.setAttribute('data-sc-theme', 'dev_choice_1');
    document.documentElement.setAttribute('data-sc-category', '{{ $category }}');
</script>

<style>
    :root {
        @foreach ($shades as $shade => $value)
        --primary-{{ $shade }}: {{ $value }};
        @endforeach
        --theme-primary: {{ $primaryHex }};
        --theme-accent: {{ $accent }};
        --theme-glow: rgba({{ $primary }}, 0.12);
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

    /* Brand-coloured active nav + header accents use the category tokens. */
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
        color: color-mix(in srgb, var(--theme-primary) 45%, white) !important;
    }
</style>