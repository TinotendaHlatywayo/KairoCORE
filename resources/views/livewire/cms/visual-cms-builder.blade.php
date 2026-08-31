<div class="studio-shell min-h-screen text-slate-800 flex flex-col font-sans selection:bg-[#5B4FE9]/20" 
     x-data="{ 
        tab: 'templates', 
        library: 'foundations', 
        editingMode: @js($editingMode), 
        sidebarOpen: true, 
        selectedBlock: @entangle('selectedBlockIndex')
     }" 
     x-on:cms-block-selected.window="tab = 'inspector'; sidebarOpen = true">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="{{ \Modules\CMS\Services\CmsTemplateService::googleFontsUrl(\Modules\CMS\Services\CmsTemplateService::availableFonts(), '400;500;600;700') }}" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════════════════════
           DESIGN TOKENS — "Control Deck" system
           Dark toolbar/rail (the studio's controls) framing a bright,
           paper-white canvas (the work). High-contrast, purposeful color:
           violet = act on the draft, emerald = ship it, rose = destroy.
           ═══════════════════════════════════════════════════════════════ */
        .studio-shell {
            --sc-ink: var(--sc-primary-600);
            --sc-ink-soft: var(--sc-primary-500); /* color of the gradient on the dark deck */
            --sc-ink-line: rgba(255, 255, 255, 0.08);
            --sc-canvas: #eef0f7;
            --sc-panel: #ffffff;
            --sc-border: #dfe3ef;
            --sc-primary: var(--sc-primary-500);
            --sc-primary-dark: var(--sc-primary-700);
            --sc-primary-light: var(--sc-primary-50);
            --sc-accent: var(--theme-accent, #22d3ee);
            --sc-success: #0ea768;
            --sc-success-dark: #087a4c;
            --sc-danger: #f0355c;
            --sc-danger-dark: #c81e46;
            --sc-warning: #e9a13a;
            --sc-text: #0d1220;
            --sc-text-muted: #5c6478;
            font-family: 'Inter', ui-sans-serif, system-ui, sans-serif;
            background: var(--sc-canvas);
        }

        .studio-shell h1, .studio-shell h2, .studio-shell h3, .studio-shell h4,
        .studio-shell .font-black, .studio-shell .font-bold,
        .studio-shell button, .studio-shell select, .studio-shell label {
            font-family: 'Sora', 'Inter', ui-sans-serif, sans-serif;
        }

        .sc-mono { font-family: 'JetBrains Mono', ui-monospace, monospace; }

        .studio-shell :is(button, a, select, input, textarea) { min-height: 44px; }
        .studio-shell button:focus-visible,
        .studio-shell a:focus-visible,
        .studio-shell select:focus-visible,
        .studio-shell input:focus-visible,
        .studio-shell textarea:focus-visible {
            outline: 2px solid var(--sc-accent);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            .studio-shell * { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; }
        }

        /* ── Control-deck surfaces (header + tool rail): near-black, glowing edge ── */
        .sc-deck {
            background: linear-gradient(180deg, var(--sc-ink) 0%, var(--sc-ink-soft) 100%);
            border-color: var(--sc-ink-line) !important;
            position: relative;
            z-index: 50;
        }
        .sc-deck::after {
            content: '';
            position: absolute; inset: auto 0 -1px 0; height: 1px;
            background: linear-gradient(90deg, transparent, rgba(91,79,233,0.55), rgba(34,211,238,0.55), transparent);
        }

        .studio-canvas {
            background: var(--sc-canvas);
            background-image: radial-gradient(circle at 1px 1px, rgba(91,79,233,0.10) 1px, transparent 0);
            background-size: 22px 22px;
        }

        .studio-panel {
            background: var(--sc-panel);
            border-right: 1px solid var(--sc-border);
        }

        /* ── Site theme tokens consumed by the public section renderer ── */
        .studio-shell .cms-card {
            border-radius: var(--theme-radius, 20px);
            box-shadow: var(--theme-shadow, 0 10px 30px -5px rgba(15,23,42,0.08));
        }
        .studio-shell .cms-btn {
            border-radius: var(--theme-btn-radius, var(--theme-radius, 20px));
        }

        /* ── Brand mark ── */
        .sc-logo {
            background: linear-gradient(135deg, var(--sc-primary) 0%, var(--sc-primary-400) 55%, var(--sc-accent) 100%);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.12) inset, 0 6px 18px rgba(91,79,233,0.45);
        }

        /* ── Glowing Clickable Sidebar Items ── */
        .glow-clickable {
            position: relative;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            cursor: pointer;
        }
        .glow-clickable::before {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            padding: 2px;
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-400), var(--sc-accent));
            background-size: 300% 300%;
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask-composite: xor;
            mask-composite: exclude;
            opacity: 0;
            transition: opacity 0.35s ease;
            animation: glow-pulse 3s ease-in-out infinite;
        }
        .glow-clickable:hover::before,
        .glow-clickable.active::before { opacity: 1; }
        .glow-clickable:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(91,79,233,0.14); }
        .glow-clickable.active { box-shadow: 0 10px 28px rgba(91,79,233,0.18); }

        @keyframes glow-pulse {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }

        /* ── Tool-rail tab buttons (now living on the dark deck) ── */
        .tab-btn {
            position: relative;
            transition: all 0.25s ease;
            cursor: pointer;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            color: #a6acc4;
        }
        .tab-btn:hover { background: rgba(255,255,255,0.08); color: #ffffff; }
        .tab-btn.active {
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-400)) !important;
            color: #ffffff !important;
            border-color: transparent;
            box-shadow: 0 8px 22px rgba(91,79,233,0.5), 0 0 0 1px rgba(255,255,255,0.08) inset;
        }

        /* Segment control (preview size / editing-mode toggles) */
        .sc-seg-btn {
            border-radius: 10px; padding: 0.5rem 0.875rem;
            font-size: 0.8125rem; font-weight: 700; color: #a6acc4;
            transition: all 0.15s ease; cursor: pointer; min-height: 36px;
        }
        .sc-seg-btn:hover { background: rgba(255,255,255,0.08); color: #ffffff; }
        .sc-seg-btn.active {
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-400));
            color: #ffffff;
            box-shadow: 0 6px 18px rgba(91,79,233,0.45);
        }

        /* Preview device widths for the canvas */
        .sc-preview-full { width: 100%; }
        .sc-preview-tablet { width: 768px; max-width: 100%; }
        .sc-preview-mobile { width: 375px; max-width: 100%; }
        @media (max-width: 767px) {
            .sc-preview-tablet, .sc-preview-mobile { width: 100%; }
        }
        .sc-canvas-minh { min-height: 720px; }

        /* Active page tab in the canvas nav preview */
        .sc-tab-active {
            color: var(--sc-primary) !important;
            border-bottom: 2px solid var(--sc-primary);
            padding-bottom: 0.25rem;
        }

        /* Active theme-preset card */
        .sc-preset-active {
            border-color: var(--sc-primary) !important;
            box-shadow: 0 0 0 2px rgba(91, 79, 233, 0.25), 0 10px 28px rgba(91, 79, 233, 0.15);
        }
        .sc-accent-text { color: var(--sc-primary) !important; }

        /* Library segmented tabs (light background) */
        .sc-lib-tab {
            border-radius: 8px; padding: 0.5rem; font-weight: 800;
            color: var(--sc-text-muted); transition: all 0.15s ease; cursor: pointer;
        }
        .sc-lib-tab:hover { background: #f1f5f9; color: var(--sc-text); }
        .sc-lib-tab.active {
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-400));
            color: #fff;
            box-shadow: 0 4px 12px rgba(91, 79, 233, 0.4);
        }

        /* Hover actions toolbox on the light canvas */
        .sc-toolbox {
            position: absolute; top: 0.75rem; right: 1rem; z-index: 20;
            display: flex; align-items: center; gap: 0.375rem;
            background: rgba(10, 14, 26, 0.95); backdrop-filter: blur(6px);
            color: #fff; padding: 0.375rem; border-radius: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 12px 32px rgba(2, 6, 23, 0.4);
            user-select: none; opacity: 0; transition: opacity 0.15s ease;
        }
        .group:hover .sc-toolbox, .sc-toolbox.active { opacity: 1; }
        .sc-toolbox-label {
            padding: 0 0.5rem; font-size: 0.625rem; font-weight: 800;
            text-transform: uppercase; letter-spacing: 0.03em; color: var(--sc-primary-400);
        }
        .sc-toolbox-btn {
            padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 700;
            border-radius: 0.5rem; color: #fff; background: rgba(255, 255, 255, 0.1);
            transition: background 0.15s ease, color 0.15s ease; cursor: pointer;
        }
        .sc-toolbox-btn:hover { background: rgba(255, 255, 255, 0.2); }
        .sc-toolbox-btn:disabled { opacity: 0.3; cursor: not-allowed; }
        .sc-toolbox-btn-primary { background: linear-gradient(135deg, var(--sc-primary-500), var(--sc-primary-400)); }
        .sc-toolbox-btn-primary:hover { filter: brightness(1.1); }
        .sc-toolbox-btn-cyan { background: rgba(34, 211, 238, 0.15); color: #7dd3fc; }
        .sc-toolbox-btn-cyan:hover { background: var(--sc-accent); color: #fff; }
        .sc-toolbox-btn-danger { background: rgba(240, 53, 92, 0.2); color: #ff9db1; }
        .sc-toolbox-btn-danger:hover { background: #f0355c; color: #fff; }

        /* ── Inputs ── */
        .studio-input-field, .studio-select-field {
            height: 46px;
            border-radius: 12px;
            background: #ffffff !important;
            border: 1.5px solid var(--sc-border);
            color: var(--sc-text) !important;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            width: 100%;
            padding-left: 14px;
            padding-right: 14px;
        }
        .studio-input-field:focus, .studio-select-field:focus {
            border-color: var(--sc-primary);
            box-shadow: 0 0 0 4px rgba(91,79,233,0.14);
            outline: none;
        }
        .studio-input-field::placeholder { color: #9aa1b5; }
        textarea.studio-input-field { padding-top: 12px; height: auto; }

        input[type="color"] {
            padding: 3px;
            border: 1.5px solid var(--sc-border);
        }

        /* ── File upload: real input, styled shell + native file-selector button ── */
        .sc-dropzone {
            position: relative;
            border: 1.5px dashed #c7cce3;
            border-radius: 14px;
            background: linear-gradient(180deg, #f7f8fd 0%, #f1f2fb 100%);
            padding: 14px;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .sc-dropzone:hover { border-color: var(--sc-primary); background: #f2f0ff; }
        .sc-dropzone input[type="file"] {
            font-size: 12.5px;
            color: var(--sc-text-muted);
            font-weight: 500;
            width: 100%;
        }
        .sc-dropzone input[type="file"]::file-selector-button {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 11.5px;
            letter-spacing: 0.02em;
            color: #fff;
            background: linear-gradient(135deg, var(--sc-primary), var(--sc-primary-dark));
            border: none;
            border-radius: 8px;
            padding: 8px 12px;
            margin-right: 10px;
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .sc-dropzone input[type="file"]::file-selector-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(91,79,233,0.35);
        }

        /* ── Button system ── */
        .btn-primary, .btn-success, .btn-secondary, .btn-danger, .btn-ghost, .btn-upload {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 13.5px;
            border-radius: 12px;
            padding: 0 18px;
            height: 44px;
            transition: transform 0.15s cubic-bezier(0.16,1,0.3,1), box-shadow 0.2s ease, background 0.2s ease, filter 0.2s ease;
            white-space: nowrap;
            border: 1px solid transparent;
        }
        .studio-shell .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, var(--sc-primary) 0%, var(--sc-primary-400) 100%);
            box-shadow: 0 8px 20px rgba(91,79,233,0.35), 0 0 0 1px rgba(255,255,255,0.08) inset;
        }
        .studio-shell .btn-primary:hover { transform: translateY(-1.5px); box-shadow: 0 12px 26px rgba(91,79,233,0.45); }

        .studio-shell .btn-success {
            color: #fff;
            background: linear-gradient(135deg, var(--sc-success) 0%, #16c98a 100%);
            box-shadow: 0 8px 20px rgba(14,167,104,0.35), 0 0 0 1px rgba(255,255,255,0.08) inset;
        }
        .studio-shell .btn-success:hover { transform: translateY(-1.5px); box-shadow: 0 12px 26px rgba(14,167,104,0.45); }

        .studio-shell .btn-secondary {
            color: var(--sc-text);
            background: #ffffff;
            border-color: var(--sc-border);
        }
        .studio-shell .btn-secondary:hover { border-color: var(--sc-primary); color: var(--sc-primary-dark); background: var(--sc-primary-light); }

        .studio-shell .btn-danger {
            color: var(--sc-danger-dark);
            background: #ffe4e9;
            border-color: #ffc9d4;
        }
        .studio-shell .btn-danger:hover { background: var(--sc-danger); color: #fff; border-color: var(--sc-danger); }

        .studio-shell .btn-ghost {
            color: #cbd0e6;
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.08);
        }
        .studio-shell .btn-ghost:hover { background: rgba(255,255,255,0.12); color: #fff; }
        .studio-shell .btn-ghost:disabled, .studio-shell button:disabled { opacity: 0.35; cursor: not-allowed; transform: none !important; }

        /* ── Status pill ── */
        .sc-pulse-dot { position: relative; width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
        .sc-pulse-dot::after {
            content: ''; position: absolute; inset: -4px; border-radius: 50%;
            border: 2px solid currentColor; opacity: 0.55;
            animation: pulse-ring 1.8s ease-out infinite;
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.6); opacity: 0.6; }
            100% { transform: scale(1.8); opacity: 0; }
        }

        /* ── Canvas Active Pulsing Border ── */
        .active-canvas-block {
            border: 2.5px solid var(--sc-primary) !important;
            box-shadow: 0 0 0 4px rgba(91,79,233,0.12), 0 0 24px rgba(91,79,233,0.2) !important;
            animation: border-pulse 2.2s infinite alternate;
        }
        @keyframes border-pulse {
            0% { border-color: rgba(91,79,233,0.65); }
            100% { border-color: rgba(34,211,238,0.9); }
        }

        /* ── Custom Scrollbars ── */
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #c7cce3; border-radius: 8px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #9aa1c9; }

        [x-cloak] { display: none !important; }

        @media (max-width: 1023px) {
            .studio-shell aside {
                position: fixed;
                inset: 69px 0 0;
                z-index: 40;
                width: min(100%, 420px);
                box-shadow: 24px 0 60px rgba(0,0,0,0.35);
            }
        }
    </style>

    {{-- Design system used by the tenant page sections (scoped under .sc-site) --}}
    @include('modules.cms.site.design-system')

    {{-- Preview hardening: after every Livewire update, reveal any x-cloak
         sections again (the attribute is re-sent by the template but Alpine
         only removes it during initial boot) and force scroll-reveal sections
         fully visible inside the canvas. Also re-sync interactive widgets
         (e.g. the coverflow carousel) so prop edits reflect instantly. --}}
    <script>
        (function () {
            'use strict';

            function fixPreview() {
                var canvas = document.getElementById('sc-canvas');
                if (!canvas) return;

                canvas.querySelectorAll('[x-cloak]').forEach(function (el) {
                    el.removeAttribute('x-cloak');
                });

                canvas.querySelectorAll('[data-sc-reveal]').forEach(function (el) {
                    el.classList.add('is-in');
                });

                canvas.querySelectorAll('.sc-kinetic-word').forEach(function (el) {
                    el.classList.add('is-in');
                });

                canvas.querySelectorAll('.sc-sh-word').forEach(function (el) {
                    el.classList.add('is-lit');
                });

                canvas.querySelectorAll('.sc-coverflow-stage').forEach(function (el) {
                    if (el._x_dataStack && typeof el._x_dataStack[0].resize === 'function') {
                        el._x_dataStack[0].resize();
                    } else if (el.__x && typeof el.__x.$data.resize === 'function') {
                        el.__x.$data.resize();
                    }
                });
            }

            function onReady() {
                fixPreview();
                window.addEventListener('livewire:update', fixPreview);
                window.addEventListener('livewire:morph', fixPreview);
                window.addEventListener('livewire:morphed', fixPreview);

                if ('MutationObserver' in window) {
                    var canvas = document.getElementById('sc-canvas');
                    if (canvas) {
                        new MutationObserver(fixPreview).observe(canvas, {
                            childList: true,
                            subtree: true,
                        });
                    }
                }
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', onReady);
            } else {
                onReady();
            }
        })();
    </script>

    <div wire:loading wire:target="applyCustomizations,publishPage,setActiveTemplate,applyPageTemplate,attachUploadedImage,uploadWebsiteLogo"
         class="fixed inset-x-0 top-[68px] z-[60] h-[3px] overflow-hidden bg-white/10"
         role="status" aria-label="Saving changes">
        <div class="h-full w-1/3 animate-pulse bg-gradient-to-r from-[var(--sc-primary-500)] via-[var(--sc-primary-400)] to-[var(--sc-accent)]"></div>
    </div>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- TOP BAR: STUDIO CONTROL DECK                                     -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <header class="sc-deck min-h-[68px] border-b px-4 lg:px-6 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-[100] overflow-visible">

        <!-- Left: Brand & Page Selector -->
        <div class="flex min-w-0 items-center gap-3">
            <button x-on:click="sidebarOpen = !sidebarOpen"
                    class="btn-ghost inline-flex items-center justify-center !px-3 !h-10"
                    aria-label="Toggle Studio tools" :aria-expanded="sidebarOpen">
                <span x-text="sidebarOpen ? '✕' : '☰'" aria-hidden="true" class="text-lg font-light"></span>
            </button>

            <div class="sc-logo flex h-10 w-10 shrink-0 items-center justify-center rounded-xl">
                <span class="font-black text-sm text-white tracking-widest leading-none">{{ __('SC') }}</span>
            </div>

            <div class="hidden xl:flex items-center gap-2 whitespace-nowrap text-sm font-semibold">
                <span class="text-slate-400">{{ __('Kairo CORE') }}</span>
                <span class="text-slate-600">{{ __('/') }}</span>
                <span class="text-slate-400">{{ __('Website Studio') }}</span>
                <span class="text-slate-600">{{ __('/') }}</span>
                <span class="text-white font-bold">{{ $page->title }}</span>
            </div>

            <div class="hidden lg:block h-6 w-px bg-white/10"></div>

            <!-- Page Switcher -->
            <div class="flex items-center gap-2 min-w-0">
                <span class="hidden xl:inline text-[11px] font-bold uppercase text-slate-500 tracking-wider">{{ __('Page') }}</span>
                <div class="relative z-[999]" x-data="{ open: false }">
                    <button type="button" @click="open = !open" @keydown.escape="open = false" aria-haspopup="listbox" aria-expanded="open"
                            class="flex items-center gap-2 min-w-[9.5rem] appearance-none bg-white/90 border border-slate-200 text-slate-800 text-sm font-semibold rounded-xl pl-4 pr-3 py-2.5 focus:ring-2 focus:ring-[color:var(--sc-accent)] focus:outline-none">
                        <span class="truncate">{{ $page->title }} {{ $page->is_homepage ? '🏠' : '' }}</span>
                        <span class="shrink-0 text-slate-400 text-sm">{{ __('⌄') }}</span>
                    </button>
                    <div x-show="open" @click.outside="open = false" x-cloak role="listbox"
                         class="absolute left-0 sm:right-0 mt-2 w-64 max-h-80 overflow-y-auto rounded-xl bg-white border border-slate-200 shadow-2xl py-1 z-[1000] text-left">
                        @foreach($sitePages as $p)
                            <a href="{{ \App\Filament\App\Pages\VisualCmsBuilder::getUrl(['pageId' => $p['id']]) }}" role="option" aria-selected="{{ $p['id'] === $page['id'] ? 'true' : 'false' }}"
                               class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm {{ $p['id'] === $page['id'] ? 'bg-indigo-50 text-indigo-800 font-bold' : 'text-slate-800 hover:bg-slate-100' }}">
                                <span class="truncate">{{ $p['title'] }} {{ $p['is_homepage'] ? '🏠' : '' }}</span>
                                @if($p['id'] === $page['id'])
                                    <span class="shrink-0 rounded-full bg-indigo-600 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide text-white">{{ __('Editing') }}</span>
                                @elseif(!$p['is_published'])
                                    <span class="shrink-0 text-[10px] font-bold text-slate-400">{{ __('📄') }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Per-Page Theme Switcher -->
                <div class="flex items-center gap-2">
                    <select wire:change="switchPageTemplate($event.target.value)"
                            class="hidden sm:block text-xs rounded-lg border border-slate-200 bg-white/90 text-slate-700 focus:ring-2 focus:ring-[color:var(--sc-accent)] focus:outline-none px-2.5 py-1.5"
                            title="{{ $pageHasThemeOverride ? 'Page theme override active' : 'Using site-wide theme' }}">
                        @foreach(\Modules\CMS\Services\CmsTemplateService::getTemplates() as $key => $tpl)
                            <option value="{{ $key }}" {{ ($pageTemplate === $key) ? 'selected' : '' }}>{{ $tpl['name'] }}</option>
                        @endforeach
                    </select>
                    @php
                        $pageLayouts = \Modules\CMS\Services\CmsTemplateService::pageLayoutsFor($page->slug, $page->is_homepage);
                    @endphp
                    @if(count($pageLayouts) > 0)
                        <select wire:change="if($event.target.value === '') { $wire.resetPageLayout(); } else { $wire.applyPageTemplate($event.target.value); }"
                                class="hidden sm:block text-xs rounded-lg border border-slate-200 bg-white/90 text-slate-700 focus:ring-2 focus:ring-[color:var(--sc-accent)] focus:outline-none px-2.5 py-1.5"
                                title="Page layout (block structure)">
                            <option value="" {{ empty($pageLayout) ? 'selected' : '' }}>{{ __('Layout: Auto') }}</option>
                            @foreach($pageLayouts as $layoutKey => $layout)
                                <option value="{{ $layoutKey }}" {{ ($pageLayout === $layoutKey) ? 'selected' : '' }}>{{ $layout['name'] }}</option>
                            @endforeach
                        </select>
                    @endif
                    @if($pageHasThemeOverride)
                        <button wire:click="resetPageTheme"
                                class="hidden sm:flex items-center gap-1 text-[10px] font-bold text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-2.5 py-1.5 hover:bg-amber-100 transition"
                                title="Remove per-page theme override">
                            {{ __('↩ Site-wide') }}
                        </button>
                    @endif
                </div>
            </div>

            <button wire:click="$set('showSeoModal', true)"
                    class="btn-ghost hidden sm:flex !px-4">
                <span aria-hidden="true">{{ __('⚙') }}</span>
                <span>{{ __('SEO') }}</span>
            </button>
        </div>

        <!-- Viewport Controls -->
        <div class="flex items-center space-x-3">
            <div class="flex items-center divide-x divide-white/10 bg-white/[0.06] border border-white/10 p-1 rounded-xl">
                <button wire:click="undo" class="btn-ghost !h-9 !px-3 text-sm rounded-lg" @if($historyIndex <= 0) disabled @endif>
                    {{ __('↩ Undo') }}
                </button>
                <button wire:click="redo" class="btn-ghost !h-9 !px-3 text-sm rounded-lg" @if($historyIndex >= count($historyStack) - 1) disabled @endif>
                    {{ __('↪ Redo') }}
                </button>
            </div>

            <div class="flex items-center bg-white/[0.06] border border-white/10 p-1 rounded-xl">
                <button wire:click="$set('previewSize', 'full')"
                        class="sc-seg-btn {{ $previewSize === 'full' ? 'active' : '' }}">
                    {{ __('🖥️') }}
                </button>
                <button wire:click="$set('previewSize', 'tablet')"
                        class="sc-seg-btn {{ $previewSize === 'tablet' ? 'active' : '' }}">
                    {{ __('▣') }}
                </button>
                <button wire:click="$set('previewSize', 'mobile')"
                        class="sc-seg-btn {{ $previewSize === 'mobile' ? 'active' : '' }}">
                    {{ __('▯') }}
                </button>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center space-x-3">
            <div class="hidden xl:flex items-center rounded-xl border border-white/10 bg-white/[0.06] p-1 text-sm font-bold">
                <button x-on:click="editingMode = 'simple'; $wire.setEditingMode('simple')"
                        :class="editingMode === 'simple' ? 'active' : ''"
                        class="sc-seg-btn">{{ __('Simple') }}</button>
                <button x-on:click="editingMode = 'advanced'; $wire.setEditingMode('advanced')"
                        :class="editingMode === 'advanced' ? 'active' : ''"
                        class="sc-seg-btn">{{ __('Advanced') }}</button>
            </div>

            @if($hasUnpublishedChanges)
                <span class="flex items-center gap-2 text-[10px] font-black uppercase px-3 py-1.5 rounded-full bg-[#e9a13a]/15 text-[#f4b95a] border border-[#e9a13a]/30">
                    <span class="sc-pulse-dot bg-[#e9a13a]"></span> {{ __('Draft') }}
                </span>
                <button wire:click="discardDraft" class="btn-secondary !bg-white/[0.06] !text-slate-300 !border-white/10 hover:!bg-white/10 hover:!text-white !px-4 text-sm">
                    {{ __('Discard') }}
                </button>
            @else
                <span class="flex items-center gap-2 text-[10px] font-black uppercase px-3 py-1.5 rounded-full bg-[#0ea768]/15 text-[#3fe0a4] border border-[#0ea768]/30">
                    <span class="sc-pulse-dot bg-[#0ea768]"></span> {{ __('Synced') }}
                </span>
            @endif

            @if($siteTemplateId)
                <button wire:click="makeTemplateLive" class="btn-success">
                    <span>{{ __('🚀') }}</span> {{ __('Make Live') }}
                </button>
            @else
                <button wire:click="publishPage" class="btn-success">
                    <span>{{ __('🚀') }}</span> {{ __('Publish Changes') }}
                </button>
            @endif

            <!-- Live Website Globe Button -->
            <a href="{{ route('tenant.home') }}" target="_blank"
               class="px-4 py-2.5 bg-gradient-to-tr from-[color:var(--sc-accent)] to-[var(--sc-primary-500)] hover:brightness-110 text-white font-extrabold text-sm rounded-xl shadow-lg shadow-black/20 transition duration-200 flex items-center gap-2 whitespace-nowrap"
               title="View Public Live Website">
                <span>{{ __('🌐') }}</span>
                <span>{{ __('View Website') }}</span>
            </a>
        </div>
    </header>

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- TEMPLATE EDIT MODE BANNER                                         -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    @if($siteTemplateId)
        @php
            $templateName = collect($premadeTemplates)->pluck('name', 'template_id')->get($siteTemplateId)
                ?? collect($savedSiteTemplates)->pluck('name', 'id')->get($siteTemplateId)
                ?? __('this template');
        @endphp
        <div class="flex items-center justify-between gap-3 px-4 lg:px-6 py-2.5 border-b bg-[#0ea768]/10 border-[#0ea768]/30 text-slate-700">
            <div class="flex items-center gap-2 min-w-0 text-xs font-semibold">
                <span class="shrink-0 rounded-full bg-[#0ea768] text-white px-2 py-0.5 text-[9px] font-black uppercase tracking-wide">Draft</span>
                <span class="truncate">{{ __('Editing template:') }} <strong>{{ $templateName }}</strong> — {{ __('changes stay inside this draft until you make it live.') }}</span>
            </div>
            <button wire:click="makeTemplateLive"
                    class="shrink-0 btn-success !h-9 !px-4 !text-xs">
                {{ __('🚀 Make This Template Live') }}
            </button>
        </div>
    @endif

    <!-- ════════════════════════════════════════════════════════════════ -->
    <!-- MAIN WORKSPACE                                                   -->
    <!-- ════════════════════════════════════════════════════════════════ -->
    <div class="flex-1 grid grid-cols-12 overflow-hidden">

        <!-- A. LEFT STUDIO CONTROL PANEL -->
        <aside x-cloak x-show="sidebarOpen"
               x-transition:enter="transition ease-out duration-200"
               x-transition:enter-start="opacity-0 -translate-x-3"
               x-transition:enter-end="opacity-100 translate-x-0"
               class="studio-panel col-span-12 lg:col-span-4 xl:col-span-3 h-[calc(100vh-68px)]">
            <div class="flex h-full min-w-0">

            <!-- Tab Switcher: lives on the dark control deck for continuity with the header -->
            <nav aria-label="Studio tools" class="sc-deck w-[88px] shrink-0 border-r px-2 py-4 space-y-2 text-[10px] font-black uppercase tracking-wider select-none flex flex-col items-center">
                <button x-on:click="tab = 'siteTemplates'"
                        :class="tab === 'siteTemplates' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('▦') }}</span>
                    <span class="text-[9px]">{{ __('Website') }}<br>{{ __('Templates') }}</span>
                </button>

                <button x-on:click="tab = 'templates'"
                        :class="tab === 'templates' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('◈') }}</span>
                    <span class="text-[9px]">{{ __('Global') }}<br>{{ __('Styles') }}</span>
                </button>

                <button x-show="editingMode === 'advanced'"
                        x-on:click="tab = 'blocks'"
                        :class="tab === 'blocks' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('⊞') }}</span>
                    <span class="text-[9px]">{{ __('Insert') }}<br>{{ __('Blocks') }}</span>
                </button>

                <button x-on:click="tab = 'inspector'"
                        :class="tab === 'inspector' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('☷') }}</span>
                    <span class="text-[9px]">{{ __('Layout') }}<br>{{ __('Settings') }}</span>
                </button>

                <button x-on:click="tab = 'theme'"
                        :class="tab === 'theme' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('✦') }}</span>
                    <span class="text-[9px]">{{ __('Brand') }}<br>{{ __('Assets') }}</span>
                </button>

                <button x-on:click="tab = 'pages'"
                        :class="tab === 'pages' ? 'tab-btn active' : 'tab-btn'"
                        class="relative w-full rounded-xl px-1 py-3 text-center transition duration-300">
                    <span class="mb-1 block text-lg leading-none">{{ __('▤') }}</span>
                    <span class="text-[9px]">{{ __('Site') }}<br>{{ __('Navigator') }}</span>
                </button>
            </nav>

            <!-- Scrollable Tab Content -->
            <div class="flex-1 min-w-0 overflow-y-auto p-6 space-y-6 custom-scrollbar bg-white">

                <!-- 1. WEBSITE TEMPLATES TAB (premade hub) -->
                <div x-show="tab === 'siteTemplates'" class="space-y-5" x-transition>
                    <div>
                        <h3 class="text-sm font-black text-[color:var(--sc-text)] tracking-tight">{{ __('Website Templates') }}</h3>
                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-1 leading-relaxed">{{ __('Pick a complete, professionally-designed website, replace the text and photos, then make it live. Your changes only affect the template you are editing.') }}</p>
                    </div>

                    {{-- Premade template cards --}}
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Premade Templates') }} · {{ count($premadeTemplates) }}</h4>
                        @foreach($premadeTemplates as $tpl)
                            <div class="glow-clickable p-4 rounded-2xl bg-white border border-[color:var(--sc-border)] shadow-sm hover:shadow-md transition {{ $tpl['is_active'] ? 'sc-preset-active' : '' }}">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h5 class="text-sm font-bold text-[color:var(--sc-text)] truncate">{{ $tpl['name'] }}</h5>
                                            @if($tpl['is_active'])
                                                <span class="shrink-0 rounded-full bg-[#0ea768]/15 text-[#0ea768] border border-[#0ea768]/30 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide">● Live</span>
                                            @endif
                                        </div>
                                        <p class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ $tpl['subtitle'] }}</p>
                                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-1 line-clamp-2 leading-relaxed">{{ $tpl['description'] }}</p>
                                        <div class="flex items-center gap-2 mt-2">
                                            <div class="flex gap-1">
                                                @foreach(['primary', 'secondary', 'accent', 'background', 'text'] as $sw)
                                                    <span class="h-3 w-3 rounded-full border border-black/10 shadow-sm" style="background-color: {{ $tpl['palette'][$sw] ?? '#ccc' }}"></span>
                                                @endforeach
                                            </div>
                                            <span class="text-[10px] font-semibold text-slate-400">{{ $tpl['fonts']['primary'] ?? '' }} · {{ $tpl['fonts']['secondary'] ?? '' }}</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1.5 shrink-0">
                                        @if($tpl['template_id'])
                                            <button wire:click="editTemplate({{ $tpl['template_id'] }})"
                                                    class="btn-primary !h-9 !px-3 !text-xs whitespace-nowrap">{{ __('✏️ Edit') }}</button>
                                            @if(!$tpl['is_active'])
                                                <button wire:click="applyTemplate({{ $tpl['template_id'] }})"
                                                        class="btn-success !h-9 !px-3 !text-xs whitespace-nowrap">{{ __('🚀 Make Live') }}</button>
                                            @else
                                                <span class="text-center text-[10px] font-black text-[#0ea768] uppercase tracking-wide">{{ __('Active Site') }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Saved custom templates --}}
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-2">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('My Templates') }}</h4>
                        @forelse($savedSiteTemplates as $saved)
                            <div class="flex items-center justify-between gap-2 p-3 bg-white border border-[color:var(--sc-border)] rounded-xl shadow-sm">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-bold text-[color:var(--sc-text)] truncate">{{ $saved['name'] }}</span>
                                        @if($saved['is_active'])
                                            <span class="shrink-0 rounded-full bg-[#0ea768]/15 text-[#0ea768] border border-[#0ea768]/30 px-2 py-0.5 text-[9px] font-black uppercase tracking-wide">● Live</span>
                                        @endif
                                    </div>
                                    @if($saved['description'])
                                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-0.5 truncate">{{ $saved['description'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button wire:click="editTemplate({{ $saved['id'] }})" class="btn-secondary !h-9 !px-2.5 !text-xs" title="Edit">{{ __('✏️') }}</button>
                                    @if(!$saved['is_active'])
                                        <button wire:click="applyTemplate({{ $saved['id'] }})" class="btn-success !h-9 !px-2.5 !text-xs" title="Make Live">{{ __('🚀') }}</button>
                                    @endif
                                    <button wire:click="duplicateSiteTemplate({{ $saved['id'] }})" class="btn-secondary !h-9 !px-2.5 !text-xs" title="Duplicate">{{ __('📋') }}</button>
                                    <button wire:click="deleteSiteTemplate({{ $saved['id'] }})" wire:confirm="Delete template '{{ $saved['name'] }}'? This cannot be undone." class="btn-danger !h-9 !px-2.5 !text-xs cursor-pointer" title="Delete">{{ __('🗑') }}</button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-[color:var(--sc-text-muted)]">{{ __('No custom templates yet. Save your current website as a template below.') }}</p>
                        @endforelse
                    </div>

                    {{-- Create new template --}}
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Create New Template') }}</h4>
                        <div class="space-y-2">
                            <select wire:model="newSiteTemplatePreset" class="studio-select-field w-full text-xs">
                                @foreach(\Modules\CMS\Services\CmsTemplateService::getTemplates() as $key => $preset)
                                    <option value="{{ $key }}">{{ $preset['name'] }} — start from this design</option>
                                @endforeach
                            </select>
                            <input type="text" wire:model="newSiteTemplateName" placeholder="Template name (optional)"
                                   class="studio-input-field w-full text-xs">
                            <button wire:click="createSiteTemplateFromPreset"
                                    class="btn-primary w-full !h-9 !text-xs">
                                {{ __('＋ Create & Open in Editor') }}
                            </button>
                            <button wire:click="$set('showNewSiteTemplate', ! $showNewSiteTemplate)"
                                    class="w-full text-[11px] font-bold text-[color:var(--sc-primary)] hover:underline">
                                {{ __('… or save the current website as a template') }}
                            </button>
                            @if($showNewSiteTemplate)
                                <div class="flex gap-2">
                                    <input type="text" wire:model="newSiteTemplateName" placeholder="Name this template"
                                           class="studio-input-field flex-1 text-xs">
                                    <button wire:click="createSiteTemplateFromLive" class="btn-secondary !h-9 !px-3 !text-xs">{{ __('Save') }}</button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- 2. GLOBAL STYLES TAB -->
                <div x-show="tab === 'templates'" class="space-y-4" x-transition>
                    <div>
                        <h3 class="text-sm font-black text-[color:var(--sc-text)] tracking-tight">{{ __('Global Styles') }}</h3>
                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-1 leading-relaxed">{{ __('Set your site-wide theme: preset, palette, fonts, and design tokens. Everything updates live.') }}</p>
                    </div>

                    <!-- Theme Presets -->
                    <div class="space-y-2">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Theme Presets') }}</h4>
                        <div class="space-y-3">
                            @foreach(\Modules\CMS\Services\CmsTemplateService::getTemplates() as $key => $tpl)
                                <div wire:click="setActiveTemplate('{{ $key }}')"
                                     class="glow-clickable p-5 rounded-2xl bg-white border transition shadow-sm hover:shadow-md {{ $activeTemplate === $key ? 'sc-preset-active' : 'border-[color:var(--sc-border)]' }}">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-bold text-[color:var(--sc-text)] truncate">{{ $tpl['name'] }}</h4>
                                            <p class="text-xs text-[color:var(--sc-text-muted)] mt-1 line-clamp-2">{{ $tpl['subtitle'] }}</p>
                                            <div class="flex gap-1 mt-2">
                                                @foreach(['primary', 'secondary', 'accent', 'background', 'text'] as $sw)
                                                    <span class="h-3 w-3 rounded-full border border-black/10 shadow-sm" style="background-color: {{ $tpl['palette'][$sw] }}"></span>
                                                @endforeach
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold sc-accent-text shrink-0">{{ $activeTemplate === $key ? '● Active' : '→' }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Site Palette -->
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Site Palette') }}</h4>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach([
                                ['color_primary', 'Primary'],
                                ['color_secondary', 'Secondary'],
                                ['color_accent', 'Accent'],
                                ['color_background', 'Background'],
                                ['color_text', 'Text'],
                                ['color_card_bg', 'Card BG'],
                            ] as [$field, $label])
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 block">{{ $label }}</label>
                                    <input type="color" wire:model.live="{{ $field }}" class="w-full h-8 rounded-lg cursor-pointer bg-slate-50">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Fonts -->
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Fonts') }}</h4>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Body Font') }}</label>
                            <select wire:model.live="font_primary" class="studio-select-field w-full text-xs">
                                @foreach(\Modules\CMS\Services\CmsTemplateService::availableFontsByCategory() as $category => $fonts)
                                    <optgroup label="{{ $category }}">
                                        @foreach($fonts as $f)
                                            <option value="{{ $f }}" style="font-family: '{{ $f }}', sans-serif;">{{ $f }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Display / Brand Font') }}</label>
                            <select wire:model.live="font_secondary" class="studio-select-field w-full text-xs">
                                @foreach(\Modules\CMS\Services\CmsTemplateService::availableFontsByCategory() as $category => $fonts)
                                    <optgroup label="{{ $category }}">
                                        @foreach($fonts as $f)
                                            <option value="{{ $f }}" style="font-family: '{{ $f }}', sans-serif;">{{ $f }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Title Heading Font (all section titles)') }}</label>
                            <select wire:model.live="font_heading" class="studio-select-field w-full text-xs">
                                <option value="">{{ __('Same as Display Font') }}</option>
                                @foreach(\Modules\CMS\Services\CmsTemplateService::availableFontsByCategory() as $category => $fonts)
                                    <optgroup label="{{ $category }}">
                                        @foreach($fonts as $f)
                                            <option value="{{ $f }}" style="font-family: '{{ $f }}', sans-serif;">{{ $f }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-snug">{{ __('Applies to every H1–H6 heading across the live website instantly.') }}</p>
                        </div>
                    </div>

                    <!-- Design Tokens -->
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Design Tokens') }}</h4>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Corner Radius') }}</label>
                                <select wire:model.live="design_radius" class="studio-select-field w-full text-xs">
                                    @foreach(\Modules\CMS\Services\CmsTemplateService::RADIUS_SCALE as $k => $v)
                                        <option value="{{ $k }}">{{ ucfirst($k) }} ({{ $v }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Shadow') }}</label>
                                <select wire:model.live="design_shadow" class="studio-select-field w-full text-xs">
                                    @foreach(\Modules\CMS\Services\CmsTemplateService::SHADOW_SCALE as $k => $v)
                                        <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Container') }}</label>
                                <select wire:model.live="design_container" class="studio-select-field w-full text-xs">
                                    @foreach(\Modules\CMS\Services\CmsTemplateService::CONTAINER_SCALE as $k => $v)
                                        <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Button Style') }}</label>
                                <select wire:model.live="design_button_style" class="studio-select-field w-full text-xs">
                                    @foreach(\Modules\CMS\Services\CmsTemplateService::BUTTON_STYLES as $k => $v)
                                        <option value="{{ $k }}">{{ ucfirst($k) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Mix & Match: Import a Single Aspect From Another Template -->
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Mix &amp; Match') }}</h4>
                        <p class="text-xs text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Pull one design aspect from another template into this website without switching everything.') }}</p>

                        <div class="space-y-1">
                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Source Template') }}</label>
                            <select wire:model="aspectSourceTemplate" class="studio-select-field w-full text-xs">
                                @foreach(\Modules\CMS\Services\CmsTemplateService::getTemplates() as $key => $tpl)
                                    @continue($key === $activeTemplate)
                                    <option value="{{ $key }}">{{ $tpl['name'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button wire:click="importTemplateAspect('{{ $aspectSourceTemplate }}', 'palette')" class="btn-secondary !h-10 !px-3 !text-xs justify-center">
                                {{ __('🎨 Colors') }}
                            </button>
                            <button wire:click="importTemplateAspect('{{ $aspectSourceTemplate }}', 'fonts')" class="btn-secondary !h-10 !px-3 !text-xs justify-center">
                                {{ __('🔤 Fonts') }}
                            </button>
                            <button wire:click="importTemplateAspect('{{ $aspectSourceTemplate }}', 'design')" class="btn-secondary !h-10 !px-3 !text-xs justify-center">
                                {{ __('📐 Design Tokens') }}
                            </button>
                            <button wire:click="importTemplateAspect('{{ $aspectSourceTemplate }}', 'all')" class="btn-primary !h-10 !px-3 !text-xs justify-center">
                                {{ __('✦ Everything') }}
                            </button>
                        </div>
                    </div>

                    <!-- Save as Template -->
                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Save as Template') }}</h4>
                        <p class="text-xs text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Save the current colors, fonts, layout, and structure as a reusable template. Apply it later and only edit content — styling carries over automatically.') }}</p>
                        <div class="flex gap-2">
                            <input type="text" wire:model="schoolTemplateName" placeholder="Template name..."
                                   class="studio-input-field flex-1 text-xs">
                            <button wire:click="saveAsSchoolTemplate" class="btn-primary !px-4">
                                {{ __('Save') }}
                            </button>
                        </div>

                        @if(count($schoolTemplates))
                            <div class="space-y-2">
                                @foreach($schoolTemplates as $tpl)
                                    <div class="rounded-xl border border-[color:var(--sc-border)] bg-slate-50 p-3 space-y-2">
                                        <span class="block text-sm font-bold text-[color:var(--sc-text)] truncate">{{ $tpl['name'] }}</span>
                                        <div class="flex gap-1.5">
                                            <button wire:click="applySchoolTemplate({{ $tpl['id'] }})"
                                                    wire:confirm="Apply '{{ $tpl['name'] }}' to this page draft (styles will carry over)?"
                                                    class="btn-primary !h-8 !px-3 !text-[11px] flex-1 justify-center">
                                                {{ __('Apply') }}
                                            </button>
                                            <button wire:click="duplicateSchoolTemplate({{ $tpl['id'] }})"
                                                    class="btn-secondary !h-8 !px-3 !text-[11px]">
                                                {{ __('Copy') }}
                                            </button>
                                            <button wire:click="deleteSchoolTemplate({{ $tpl['id'] }})"
                                                    wire:confirm="Delete '{{ $tpl['name'] }}'?"
                                                    class="btn-danger !h-8 !px-3 !text-[11px]">
                                                {{ __('✕') }}
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-2">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Page Structures') }}</h4>
                        @foreach(\Modules\CMS\Services\CmsTemplateService::pageLayoutsFor($page->slug, $page->is_homepage) as $layoutKey => $layout)
                            <button wire:click="applyPageTemplate('{{ $layoutKey }}')"
                                    wire:confirm="Replace this page with {{ $layout['name'] }}?"
                                    class="w-full rounded-xl border border-[color:var(--sc-border)] bg-white p-4 text-left text-sm font-bold text-[color:var(--sc-text)] transition hover:border-[color:var(--sc-primary)] hover:shadow-md glow-clickable">
                                {{ $layout['name'] }}
                                <span class="ml-2 text-[10px] font-medium text-[color:var(--sc-text-muted)]">{{ implode(' · ', array_map(fn($b) => str_replace('_', ' ', $b), $layout['blocks'])) }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- 2. INSERT BLOCKS TAB -->
                <div x-show="tab === 'blocks'" class="space-y-6" x-transition>
                    <div class="rounded-2xl border border-[color:var(--sc-border)] bg-gradient-to-br from-[#efedff] via-white to-[#e6fbff] p-5 text-center shadow-sm">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-white text-2xl text-[color:var(--sc-primary)] shadow-sm">{{ __('✦') }}</span>
                        <h3 class="mt-3 text-base font-black text-[color:var(--sc-text)] tracking-tight">{{ __('Build this page') }}</h3>
                        <p class="mt-1.5 text-xs leading-relaxed text-[color:var(--sc-text-muted)]">{{ __('Click a section to add it, or drag directly onto the canvas.') }}</p>
                    </div>

                    <div class="grid grid-cols-3 rounded-xl border border-[color:var(--sc-border)] bg-white p-1 text-[10px] font-black">
                        <button x-on:click="library = 'foundations'" :class="library === 'foundations' ? 'active' : ''" class="sc-lib-tab">{{ __('Foundations') }}</button>
                        <button x-on:click="library = 'sync'" :class="library === 'sync' ? 'active' : ''" class="sc-lib-tab">{{ __('Live Sync') }}</button>
                        <button x-on:click="library = 'showcase'" :class="library === 'showcase' ? 'active' : ''" class="sc-lib-tab">{{ __('Showcase') }}</button>
                    </div>

                    @php
                        $sectionLibrary = [
                            'foundations' => [
                                ['hero', '🚀', 'Hero Banner', 'Make a first impression.', 'bg-indigo-50 text-indigo-600'],
                                ['principal_welcome', '👨‍🏫', 'Principal Message', 'Custom welcome note.', 'bg-sky-50 text-sky-600'],
                                ['about_section', '🏛️', 'About & Mission', 'Our vision & story.', 'bg-emerald-50 text-emerald-600'],
                                ['academics_grid', '🎓', 'Academics Grid', 'Showcase faculties.', 'bg-violet-50 text-violet-600'],
                            ],
                            'sync' => [
                                ['dynamic_news', '📰', 'News Feed', 'Synced notices.', 'bg-amber-50 text-amber-600'],
                                ['events_calendar', '📅', 'Event Calendar', 'Term schedule feed.', 'bg-fuchsia-50 text-fuchsia-600'],
                                ['admissions_block', '📝', 'Online Application', 'Student enrollment form.', 'bg-rose-50 text-rose-600'],
                            ],
                            'showcase' => [
                                ['features_grid', '⭐', 'Advantages Cards', 'Our highlights grid.', 'bg-orange-50 text-orange-600'],
                                ['gallery', '🖼️', 'Photo Gallery', 'Direct PC upload grid.', 'bg-pink-50 text-pink-600'],
                                ['testimonials', '💬', 'Parent Reviews', 'Quotes with ratings.', 'bg-cyan-50 text-cyan-600'],
                                ['team_directory', '👥', 'Staff Directory', 'Faculty profiles.', 'bg-lime-50 text-lime-600'],
                                ['faq_accordion', '❓', 'FAQ Accordion', 'Collapsible questions.', 'bg-blue-50 text-blue-600'],
                                ['contact_map', '📍', 'Location & Map', 'Maps & contact details.', 'bg-slate-100 text-slate-600'],
                                ['cta_banner', '📢', 'CTA Banner', 'Call-to-action layout.', 'bg-red-50 text-red-600'],
                                ['video_embed', '🎬', 'Video Player', 'Virtual tours video.', 'bg-purple-50 text-purple-600'],
                                ['logo_cloud', '🤝', 'Affiliations', 'Affiliation badges.', 'bg-teal-50 text-teal-600'],
                                ['divider', '➖', 'Divider / Spacer', 'Page gaps.', 'bg-zinc-100 text-zinc-600'],
                            ],
                        ];
                    @endphp

                    @foreach($sectionLibrary as $group => $sections)
                        <div x-show="library === '{{ $group }}'" x-transition class="grid gap-3">
                            @foreach($sections as [$type, $icon, $name, $description, $iconClass])
                                <button draggable="true" ondragstart="event.dataTransfer.setData('application/x-schoolcore-block', '{{ $type }}')"
                                        wire:click="addBlock('{{ $type }}')"
                                        class="group flex w-full items-start gap-4 rounded-2xl border border-[color:var(--sc-border)] bg-white p-4 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--sc-primary)] hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-[color:var(--sc-accent)]">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-xl {{ $iconClass }}">{{ $icon }}</span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-[color:var(--sc-text)]">{{ $name }}</span>
                                        <span class="mt-1 block text-xs leading-relaxed text-[color:var(--sc-text-muted)]">{{ $description }}</span>
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <!-- 3. LAYOUT SETTINGS (INSPECTOR) -->
                <div x-show="tab === 'inspector'" class="space-y-5" x-transition>
                    @if(!is_null($selectedBlockIndex) && isset($blocks[$selectedBlockIndex]))
                        <div class="bg-white border border-[color:var(--sc-border)] rounded-2xl p-5 space-y-4 shadow-lg shadow-slate-200/60">
                            <div class="flex items-center justify-between border-b border-[color:var(--sc-border)] pb-3">
                                <span class="text-sm font-black text-[color:var(--sc-text)] flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-[color:var(--sc-primary)]"></span>
                                    Editing: {{ str_replace('_', ' ', $blocks[$selectedBlockIndex]['type']) }}
                                </span>
                                <button wire:click="$set('selectedBlockIndex', null)" class="text-xs text-[color:var(--sc-danger-dark)] hover:text-[color:var(--sc-danger)] font-bold">
                                    {{ __('✕ Close') }}
                                </button>
                            </div>

                            <!-- Content, Typography & Style merged into a single flow -->
                            <div class="space-y-4">
                                @if(isset($selectedBlockData['title']))
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Title Heading') }}</label>
                                            <input type="text" wire:model.blur="selectedBlockData.title"
                                                   class="studio-input-field w-full text-xs">
                                        </div>
                                    @endif

                                    @if(isset($selectedBlockData['description']))
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Description / Body Text') }}</label>
                                            @include('modules.cms.richtext', [
                                                'rtKey' => 'b' . $selectedBlockIndex . '-description',
                                                'rtPath' => 'description',
                                                'rtValue' => $selectedBlockData['description'] ?? '',
                                                'rtPlaceholder' => 'Write your description… supports bold, italic, lists & emoji',
                                            ])
                                        </div>
                                    @endif

                                    {{-- Hero-only: editable enrollment badge. Empty = dynamic "Enrollment Open for YYYY/YY". --}}
                                    @if(($selectedBlockData['type'] ?? '') === 'hero')
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Badge Text (top ribbon)') }}</label>
                                            <input type="text" wire:model.blur="selectedBlockData.badge_text"
                                                   placeholder="Leave empty for automatic 'Enrollment Open for {{ now()->format('Y') }}/{{ str_pad((string) ((int) now()->format('Y') + 1) % 100, 2, '0', STR_PAD_LEFT) }}'"
                                                   class="studio-input-field w-full text-xs">
                                        </div>
                                    @endif

                                    {{-- Staff Directory: members live in HR, not the page. --}}
                                    @if(($selectedBlockData['type'] ?? '') === 'team_directory')
                                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-[11px] leading-relaxed text-amber-800">
                                            <strong>{{ __('Staff are managed in the HR module.') }}</strong><br>
                                            {{ __('Go to HR & Payroll → Employees to add or remove staff, upload photos and edit names/designations. Active employees appear here automatically.') }}
                                            <a href="{{ \App\Filament\App\Resources\EmployeeResource::getUrl('index') }}" target="_blank" rel="noopener noreferrer"
                                               class="mt-1 inline-block font-bold underline">{{ __('Open Employee Manager →') }}</a>
                                        </div>
                                    @endif

                                    <!-- HERO DUAL ACTION BUTTON EDITORS -->
                                    @if(isset($selectedBlockData['cta_text']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Primary Action Button') }}</span>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" wire:model.blur="selectedBlockData.cta_text" placeholder="Label text" class="studio-input-field text-xs">
                                                <input type="text" wire:model.blur="selectedBlockData.cta_url" placeholder="Redirect URL" class="studio-input-field text-xs">
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($selectedBlockData['secondary_cta_text']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Secondary Action Button') }}</span>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" wire:model.blur="selectedBlockData.secondary_cta_text" placeholder="Label text" class="studio-input-field text-xs">
                                                <input type="text" wire:model.blur="selectedBlockData.secondary_cta_url" placeholder="Redirect URL" class="studio-input-field text-xs">
                                            </div>
                                        </div>
                                    @endif

                                    <!-- WELCOME MESSAGE & POSITION CREDENTIALS -->
                                    @if(isset($selectedBlockData['principal_name']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Principal Credentials') }}</span>
                                            <div class="grid grid-cols-2 gap-2">
                                                <input type="text" wire:model.blur="selectedBlockData.principal_name" placeholder="Principal name" class="studio-input-field text-xs">
                                                <input type="text" wire:model.blur="selectedBlockData.principal_title" placeholder="Title under name" class="studio-input-field text-xs">
                                            </div>
                                        </div>
                                    @endif

                                    @if(isset($selectedBlockData['mission']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Mission & Vision Statements') }}</span>
                                            @include('modules.cms.richtext', [
                                                'rtKey' => 'b' . $selectedBlockIndex . '-mission',
                                                'rtPath' => 'mission',
                                                'rtValue' => $selectedBlockData['mission'] ?? '',
                                                'rtPlaceholder' => 'Write the mission statement…',
                                            ])
                                            @include('modules.cms.richtext', [
                                                'rtKey' => 'b' . $selectedBlockIndex . '-vision',
                                                'rtPath' => 'vision',
                                                'rtValue' => $selectedBlockData['vision'] ?? '',
                                                'rtPlaceholder' => 'Write the vision statement…',
                                            ])
                                        </div>
                                    @endif

                                    <!-- CONTACT AND EMAIL INFORMATION -->
                                    @if(isset($selectedBlockData['address']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Contact Coordinates') }}</span>
                                            <input type="text" wire:model.blur="selectedBlockData.address" placeholder="Physical Address" class="studio-input-field text-xs">
                                            <input type="text" wire:model.blur="selectedBlockData.phone" placeholder="Telephone Number" class="studio-input-field text-xs">
                                            <input type="email" wire:model.blur="selectedBlockData.email" placeholder="Contact Email" class="studio-input-field text-xs">
                                        </div>
                                    @endif

                                    <!-- PC IMAGE FILE UPLOADER FOR MAIN SECTION IMAGE -->
                                    @if(isset($selectedBlockData['image_url']))
                                        <div class="border-t border-slate-100 pt-3 space-y-2">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Section Photo Asset') }}</span>
                                            <input type="text" wire:model.blur="selectedBlockData.image_url" placeholder="Direct url path..." class="studio-input-field text-xs">

                                            <div class="sc-dropzone">
                                                <span class="text-[10px] font-bold text-[color:var(--sc-text-muted)] block mb-2">{{ __('📷 Upload photo from your computer') }}</span>
                                                <div class="flex items-center gap-2">
                                                    <input type="file" wire:model="tempImage" accept="image/*" class="flex-1">
                                                    <button type="button" wire:click="attachUploadedImage('image_url')" class="btn-primary !h-10 !px-4 !text-xs">
                                                        {{ __('Attach') }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- NESTED ARRAY PC FILE UPLOADER FOR GALLERIES / ORBIT -->
                                    @if(isset($selectedBlockData['images']) && is_array($selectedBlockData['images']))
                                        @php
                                            $isOrbit = ($selectedBlockData['type'] ?? '') === 'orbit_gallery';
                                        @endphp
                                        <div class="border-t border-slate-100 pt-3 space-y-3">
                                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ $isOrbit ? __('Orbit Items') : __('Photo Gallery Elements') }}</span>
                                            @foreach($selectedBlockData['images'] as $imageIdx => $image)
                                                <div class="p-3 bg-slate-50 border border-[color:var(--sc-border)] rounded-2xl space-y-2">
                                                    <span class="text-[9px] font-extrabold text-slate-400 block uppercase">{{ $isOrbit ? 'Orbit Slot #'.($imageIdx + 1) : 'Gallery Image Slot #'.($imageIdx + 1) }}</span>
                                                    @if($isOrbit)
                                                        <input type="text" wire:model.blur="selectedBlockData.images.{{ $imageIdx }}.image" class="studio-input-field text-xs" placeholder="Image URL">
                                                        <input type="text" wire:model.blur="selectedBlockData.images.{{ $imageIdx }}.label" class="studio-input-field text-xs" placeholder="Label text">
                                                        <div class="sc-dropzone !p-2 mt-1.5">
                                                            <div class="flex items-center gap-2">
                                                                <input type="file" wire:model="tempImage" accept="image/*" class="flex-1 text-[11px]">
                                                                <button type="button" wire:click="attachUploadedImage('images.{{ $imageIdx }}.image')" class="btn-primary !h-9 !px-3 !text-[11px]">{{ __('Attach') }}</button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <input type="text" wire:model.blur="selectedBlockData.images.{{ $imageIdx }}.url" class="studio-input-field text-xs" placeholder="Image URL">
                                                        <input type="text" wire:model.blur="selectedBlockData.images.{{ $imageIdx }}.caption" class="studio-input-field text-xs" placeholder="Caption label">
                                                        <div class="sc-dropzone !p-2 mt-1.5">
                                                            <div class="flex items-center gap-2">
                                                                <input type="file" wire:model="tempImage" accept="image/*" class="flex-1 text-[11px]">
                                                                <button type="button" wire:click="attachUploadedImage('images.{{ $imageIdx }}.url')" class="btn-primary !h-9 !px-3 !text-[11px]">{{ __('Attach') }}</button>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <!-- EDITABLE CARD COLLECTIONS (items, features, testimonials, faqs) -->
                                    @php
                                        $blockColType = $selectedBlockData['type'] ?? '';
                                        $collectionFields = [
                                            'items' => in_array($blockColType, ['marquee_ticker'], true)
                                                ? ['label' => 'Text']
                                                : ['title' => 'Title', 'desc' => 'Description'],
                                            'features' => ['title' => 'Title', 'desc' => 'Description', 'image' => 'Image URL'],
                                            'faqs' => ['q' => 'Question', 'a' => 'Answer'],
                                            'testimonials' => ['quote' => 'Quote', 'name' => 'Name', 'role' => 'Role'],
                                        ];
                                    @endphp
                                    @foreach($collectionFields as $collection => $fields)
                                        @if(isset($selectedBlockData[$collection]) && is_array($selectedBlockData[$collection]))
                                            <div class="space-y-2 border-t border-slate-100 pt-3">
                                                <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ ucfirst($collection) }} Details</span>
                                                @foreach($selectedBlockData[$collection] as $itemIdx => $item)
                                                    <div class="rounded-xl border border-[color:var(--sc-border)] bg-slate-50 p-2.5 space-y-1">
                                                        @foreach($fields as $field => $label)
                                                            <input type="text" wire:model.blur="selectedBlockData.{{ $collection }}.{{ $itemIdx }}.{{ $field }}" placeholder="{{ $label }}" class="studio-input-field text-xs h-10">
                                                            @if($field === 'image')
                                                                <div class="flex items-center gap-2">
                                                                    <input type="file" wire:model="tempImage" accept="image/*" class="flex-1 text-[11px]">
                                                                    <button type="button" wire:click="attachUploadedImage('{{ $collection }}.{{ $itemIdx }}.image')" class="btn-primary !h-9 !px-3 !text-[11px]">
                                                                        {{ __('Upload') }}
                                                                    </button>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                <!-- ══ TYPOGRAPHY ══ -->
                                <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                    <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Typography') }}</span>

                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Heading Font') }}</label>
                                        <select wire:model.live="selectedBlockData.styles.title_font" class="studio-select-field w-full text-xs">
                                            <option value="">{{ __('Use site default') }}</option>
                                            @foreach(\Modules\CMS\Services\CmsTemplateService::availableFontsByCategory() as $category => $fonts)
                                                <optgroup label="{{ $category }}">
                                                    @foreach($fonts as $f)
                                                        <option value="{{ $f }}" style="font-family: '{{ $f }}', sans-serif;">{{ $f }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="space-y-1">
                                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Body Font') }}</label>
                                        <select wire:model.live="selectedBlockData.styles.font_family" class="studio-select-field w-full text-xs">
                                            <option value="">{{ __('Use site default') }}</option>
                                            @foreach(\Modules\CMS\Services\CmsTemplateService::availableFontsByCategory() as $category => $fonts)
                                                <optgroup label="{{ $category }}">
                                                    @foreach($fonts as $f)
                                                        <option value="{{ $f }}" style="font-family: '{{ $f }}', sans-serif;">{{ $f }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Heading Size') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                      x-data="{ v: $wire.$entangle('selectedBlockData.styles.title_size') }"
                                                      x-text="(Number(v) || 36) + 'px'"></span>
                                            </div>
                                            <input type="range" min="16" max="96" step="1"
                                                   wire:model.live="selectedBlockData.styles.title_size"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Body Size') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                      x-data="{ v: $wire.$entangle('selectedBlockData.styles.font_size') }"
                                                      x-text="(Number(v) || 16) + 'px'"></span>
                                            </div>
                                            <input type="range" min="10" max="32" step="1"
                                                   wire:model.live="selectedBlockData.styles.font_size"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Text Alignment') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.text_align" class="studio-select-field w-full text-xs">
                                                <option value="text-left">{{ __('Left') }}</option>
                                                <option value="text-center">{{ __('Center') }}</option>
                                                <option value="text-right">{{ __('Right') }}</option>
                                                <option value="text-justify">{{ __('Justify') }}</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Line Height') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.line_height" class="studio-select-field w-full text-xs">
                                                <option value="">{{ __('Auto') }}</option>
                                                @foreach([1.2, 1.4, 1.6, 1.8, 2.0] as $lh)
                                                    <option value="{{ $lh }}">{{ $lh }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Heading Color') }}</label>
                                            <input type="color" wire:model.live="selectedBlockData.styles.title_color"
                                                   class="w-full h-10 rounded-lg cursor-pointer bg-slate-50">
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Text Color') }}</label>
                                            <input type="color" wire:model.live="selectedBlockData.styles.text_color"
                                                   class="w-full h-10 rounded-lg cursor-pointer bg-slate-50">
                                        </div>
                                    </div>

                                    <button wire:click="resetBlockStyle('typography')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                        {{ __('↺ Reset Typography to Site Defaults') }}
                                    </button>
                                </div>

                                <!-- ══ STYLE & LAYOUT ══ -->
                                <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                    <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Style &amp; Layout') }}</span>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Background') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.bg_style"
                                                    class="studio-select-field w-full text-xs">
                                                <option value="solid">{{ __('Solid Color') }}</option>
                                                <option value="gradient">{{ __('Gradient') }}</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Animation') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.animate"
                                                    class="studio-select-field w-full text-xs">
                                                @foreach(\Modules\CMS\Services\CmsTemplateService::ANIMATIONS as $anim)
                                                    <option value="{{ $anim }}">{{ ucfirst(str_replace('-', ' ', $anim)) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Background Color') }}</label>
                                            <input type="color" wire:model.live="selectedBlockData.styles.bg_color"
                                                   class="w-full h-10 rounded-lg cursor-pointer bg-slate-50">
                                        </div>
                                        @if(($selectedBlockData['styles']['bg_style'] ?? '') === 'gradient')
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Gradient End') }}</label>
                                                <input type="color" wire:model.live="selectedBlockData.styles.bg_gradient_end"
                                                       class="w-full h-10 rounded-lg cursor-pointer bg-slate-50">
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Custom Section Background Image + opacity -->
                                    <div class="space-y-1 border-t border-slate-100 pt-3">
                                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Custom Section Background Image') }}</label>
                                        <input type="text" wire:model.blur="selectedBlockData.styles.bg_image_url" placeholder="Direct Image URL path..." class="studio-input-field text-xs">
                                        <div class="sc-dropzone mt-1.5">
                                            <span class="text-[10px] font-bold text-[color:var(--sc-text-muted)] block mb-2">{{ __('📷 Upload background from your computer') }}</span>
                                            <div class="flex items-center gap-2">
                                                <input type="file" wire:model="tempImage" accept="image/*" class="flex-1">
                                                <button type="button" wire:click="attachUploadedImage('styles.bg_image_url')" class="btn-primary !h-10 !px-4 !text-xs">
                                                    {{ __('Attach') }}
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space-y-1 pt-2">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Background Image Opacity') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]" x-data="{ v: {{ $selectedBlockData['styles']['bg_image_opacity'] ?? 1 }} }" x-text="Math.round(v * 100) + '%'"></span>
                                            </div>
                                            <input type="range" min="0" max="1" step="0.05" wire:model.live="selectedBlockData.styles.bg_image_opacity"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Vertical Spacing') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.padding_top"
                                                    class="studio-select-field w-full text-xs">
                                                <option value="py-8">Compact (32px)</option>
                                                <option value="py-16">Standard (64px)</option>
                                                <option value="py-24">Spacious (96px)</option>
                                            </select>
                                        </div>
                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Container') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.container"
                                                    class="studio-select-field w-full text-xs">
                                                <option value="default">{{ __('Site Default') }}</option>
                                                <option value="boxed">{{ __('Boxed') }}</option>
                                                <option value="wide">{{ __('Wide') }}</option>
                                                <option value="full">{{ __('Full Bleed') }}</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Position Nudging -->
                                    <div class="border-t border-slate-100 pt-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Nudge Position') }}</span>
                                            <div class="flex items-center gap-1.5">
                                                <span class="text-[10px] font-bold text-slate-400">{{ __('Step') }}</span>
                                                <select wire:model="nudgeStep" class="studio-select-field !h-8 !w-20 !px-2 !text-[11px]">
                                                    @foreach([1, 5, 10, 25, 50] as $s)
                                                        <option value="{{ $s }}">{{ $s }}px</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <button wire:click="nudgeBlock('up')" class="btn-secondary !h-9 !px-0 justify-center">{{ __('↑') }}</button>
                                            <button wire:click="nudgeBlock('down')" class="btn-secondary !h-9 !px-0 justify-center">{{ __('↓') }}</button>
                                            <button wire:click="nudgeBlock('left')" class="btn-secondary !h-9 !px-0 justify-center">{{ __('←') }}</button>
                                            <button wire:click="nudgeBlock('right')" class="btn-secondary !h-9 !px-0 justify-center">{{ __('→') }}</button>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-[10px] font-bold text-slate-400 sc-mono">X: {{ $selectedBlockData['styles']['offset_x'] ?? 0 }} · Y: {{ $selectedBlockData['styles']['offset_y'] ?? 0 }}</span>
                                            <button wire:click="resetBlockOffset" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center">{{ __('Reset') }}</button>
                                        </div>
                                    </div>

                                    <button wire:click="resetBlockStyle('background')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                        {{ __('↺ Reset Background to Site Default') }}
                                    </button>
                                </div>

                                <!-- ══ SECTION PHOTO ══ -->
                                @if(isset($selectedBlockData['image_url']))
                                    <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                        <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Section Photo') }}</span>
                                        <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Replace the photo in the Content tab; these controls size, crop and position it.') }}</p>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Object Fit') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.image_fit"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="cover">{{ __('Cover (crop to fill)') }}</option>
                                                    <option value="contain">{{ __('Contain (fit whole photo)') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Focus Position') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.image_position"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="center">{{ __('Center') }}</option>
                                                    <option value="top">{{ __('Top') }}</option>
                                                    <option value="bottom">{{ __('Bottom') }}</option>
                                                    <option value="left">{{ __('Left') }}</option>
                                                    <option value="right">{{ __('Right') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Aspect Ratio') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.image_ratio"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="auto">{{ __('Original') }}</option>
                                                    <option value="16 / 9">{{ __('Wide 16:9') }}</option>
                                                    <option value="4 / 3">{{ __('Classic 4:3') }}</option>
                                                    <option value="1 / 1">{{ __('Square 1:1') }}</option>
                                                    <option value="3 / 4">{{ __('Portrait 3:4') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Photo Width') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.image_width"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Template default') }}</option>
                                                    <option value="none">{{ __('Full column') }}</option>
                                                    <option value="90%">{{ __('Wide (90%)') }}</option>
                                                    <option value="80%">{{ __('Standard (80%)') }}</option>
                                                    <option value="70%">{{ __('Compact (70%)') }}</option>
                                                    <option value="720px">{{ __('Extra Large') }}</option>
                                                    <option value="640px">{{ __('Large') }}</option>
                                                    <option value="560px">{{ __('Medium') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Corner Radius') }}</label>
                                            <select wire:model.live="selectedBlockData.styles.image_radius"
                                                    class="studio-select-field w-full text-xs">
                                                <option value="">{{ __('Template default') }}</option>
                                                <option value="0px">{{ __('Sharp') }}</option>
                                                <option value="12px">{{ __('Soft') }}</option>
                                                <option value="24px">{{ __('Rounded') }}</option>
                                                <option value="999px">{{ __('Pill') }}</option>
                                            </select>
                                        </div>

                                        <button wire:click="resetBlockStyle('photo')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                            {{ __('↺ Reset Photo to Template Default') }}
                                        </button>
                                    </div>
                                @endif

                                <!-- ══ GALLERY IMAGES ══ -->
                                @if(isset($selectedBlockData['images']) && is_array($selectedBlockData['images']))
                                    <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                        <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Gallery Images') }}</span>
                                        <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Applies to every photo in this gallery. Edit individual photos in the Content tab.') }}</p>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Object Fit') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.gallery_fit"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="cover">{{ __('Cover (crop to fill)') }}</option>
                                                    <option value="contain">{{ __('Contain (fit whole photo)') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Focus Position') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.gallery_position"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="center">{{ __('Center') }}</option>
                                                    <option value="top">{{ __('Top') }}</option>
                                                    <option value="bottom">{{ __('Bottom') }}</option>
                                                    <option value="left">{{ __('Left') }}</option>
                                                    <option value="right">{{ __('Right') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Tile Ratio') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.gallery_ratio"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="auto">{{ __('Original') }}</option>
                                                    <option value="16 / 9">{{ __('Wide 16:9') }}</option>
                                                    <option value="4 / 3">{{ __('Classic 4:3') }}</option>
                                                    <option value="1 / 1">{{ __('Square 1:1') }}</option>
                                                    <option value="3 / 4">{{ __('Portrait 3:4') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Corner Radius') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.gallery_radius"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Template default') }}</option>
                                                    <option value="0px">{{ __('Sharp') }}</option>
                                                    <option value="12px">{{ __('Soft') }}</option>
                                                    <option value="24px">{{ __('Rounded') }}</option>
                                                    <option value="999px">{{ __('Pill') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button wire:click="resetBlockStyle('photo')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                            {{ __('↺ Reset Gallery to Template Default') }}
                                        </button>
                                    </div>
                                @endif

                                <!-- ══ COVERFLOW / CAROUSEL PHOTO SIZE ══ -->
                                @if(($selectedBlockData['type'] ?? '') === 'coverflow_carousel')
                                    <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                        <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Carousel Photo Size') }}</span>
                                        <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Adjust the size of every photo in this carousel — the preview updates instantly.') }}</p>

                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Photo Width') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                      x-data="{ v: $wire.$entangle('selectedBlockData.card_width') }"
                                                      x-text="(Number(v) || 380) + 'px'"></span>
                                            </div>
                                            <input type="range" min="220" max="640" step="1"
                                                   wire:model.live="selectedBlockData.card_width"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>

                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Photo Height') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                      x-data="{ v: $wire.$entangle('selectedBlockData.card_height') }"
                                                      x-text="(Number(v) || 300) + 'px'"></span>
                                            </div>
                                            <input type="range" min="160" max="640" step="1"
                                                   wire:model.live="selectedBlockData.card_height"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Corner Radius') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.radius') }"
                                                          x-text="(Number(v) || 12) + 'px'"></span>
                                                </div>
                                                <input type="range" min="0" max="60" step="1"
                                                       wire:model.live="selectedBlockData.radius"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Card Spacing') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.gap') }"
                                                          x-text="(Number(v) || 8)"></span>
                                                </div>
                                                <input type="range" min="2" max="24" step="1"
                                                       wire:model.live="selectedBlockData.gap"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('3D Tilt') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.tilt') }"
                                                          x-text="(Number(v) || 12)"></span>
                                                </div>
                                                <input type="range" min="0" max="40" step="1"
                                                       wire:model.live="selectedBlockData.tilt"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Dim Opacity') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.opacity') }"
                                                          x-text="(Number(v) || 60) + '%'"></span>
                                                </div>
                                                <input type="range" min="0" max="100" step="1"
                                                       wire:model.live="selectedBlockData.opacity"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Auto Play') }}</label>
                                                <select wire:model.live="selectedBlockData.autoplay"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="0">{{ __('Off') }}</option>
                                                    <option value="1">{{ __('On') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Title Position') }}</label>
                                                <select wire:model.live="selectedBlockData.title_position"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="bottomLeft">{{ __('Bottom Left') }}</option>
                                                    <option value="bottomRight">{{ __('Bottom Right') }}</option>
                                                    <option value="topLeft">{{ __('Top Left') }}</option>
                                                    <option value="topRight">{{ __('Top Right') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button wire:click="resetBlockStyle('carousel')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                            {{ __('↺ Reset Carousel to Template Default') }}
                                        </button>
                                    </div>
                                @endif

                                <!-- ══ ORBIT GALLERY ══ -->
                                @if(($selectedBlockData['type'] ?? '') === 'orbit_gallery')
                                    <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                        <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Orbit Diagram') }}</span>
                                        <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Adjust the hub label and the position & size of the orbiting items.') }}</p>

                                        <div class="space-y-1">
                                            <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Center Hub Label') }}</label>
                                            <input type="text" wire:model.blur="selectedBlockData.center_label"
                                                   placeholder="{{ __('e.g. Seabreeze') }}" class="studio-input-field text-xs h-10">
                                        </div>

                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Item Size') }}</label>
                                                <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                      x-data="{ v: $wire.$entangle('selectedBlockData.item_size') }"
                                                      x-text="(Number(v) || 84) + 'px'"></span>
                                            </div>
                                            <input type="range" min="48" max="160" step="2"
                                                   wire:model.live="selectedBlockData.item_size"
                                                   class="w-full accent-[color:var(--sc-primary)]">
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Orbit Width') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.orbit_radius_x') }"
                                                          x-text="(Number(v) || 180) + 'px'"></span>
                                                </div>
                                                <input type="range" min="80" max="420" step="10"
                                                       wire:model.live="selectedBlockData.orbit_radius_x"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Orbit Height') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.orbit_radius_y') }"
                                                          x-text="(Number(v) || 70) + 'px'"></span>
                                                </div>
                                                <input type="range" min="30" max="200" step="5"
                                                       wire:model.live="selectedBlockData.orbit_radius_y"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Rotation Speed') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.rotation_speed') }"
                                                          x-text="(Number(v) || 6)"></span>
                                                </div>
                                                <input type="range" min="0" max="30" step="1"
                                                       wire:model.live="selectedBlockData.rotation_speed"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                            <div class="space-y-1">
                                                <div class="flex items-center justify-between">
                                                    <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Perspective Tilt') }}</label>
                                                    <span class="text-xs font-bold text-[color:var(--sc-primary)]"
                                                          x-data="{ v: $wire.$entangle('selectedBlockData.tilt') }"
                                                          x-text="(Number(v) || 18) + 'deg'"></span>
                                                </div>
                                                <input type="range" min="0" max="40" step="1"
                                                       wire:model.live="selectedBlockData.tilt"
                                                       class="w-full accent-[color:var(--sc-primary)]">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Direction') }}</label>
                                                <select wire:model.live="selectedBlockData.direction"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="clockwise">{{ __('Clockwise') }}</option>
                                                    <option value="counter_clockwise">{{ __('Counter-clockwise') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Orbit Shape') }}</label>
                                                <select wire:model.live="selectedBlockData.variant"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="ellipse">{{ __('Elliptical') }}</option>
                                                    <option value="circle">{{ __('Circle') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button wire:click="resetBlockStyle('orbit')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                            {{ __('↺ Reset Orbit to Template Default') }}
                                        </button>
                                    </div>
                                @endif

                                <!-- ══ CARD IMAGES ══ -->
                                @if(($selectedBlockData['type'] ?? '') === 'features_grid' && isset($selectedBlockData['features']))
                                    <div class="border-t border-[color:var(--sc-border)] pt-3 space-y-3">
                                        <span class="text-[10px] font-black text-[color:var(--sc-primary)] block uppercase">{{ __('Card Images') }}</span>
                                        <p class="text-[10px] text-[color:var(--sc-text-muted)] leading-relaxed">{{ __('Card photos show automatically when a card has an image set in the Content tab.') }}</p>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Object Fit') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.card_image_fit"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="cover">{{ __('Cover (crop to fill)') }}</option>
                                                    <option value="contain">{{ __('Contain (fit whole photo)') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Focus Position') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.card_image_position"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="center">{{ __('Center') }}</option>
                                                    <option value="top">{{ __('Top') }}</option>
                                                    <option value="bottom">{{ __('Bottom') }}</option>
                                                    <option value="left">{{ __('Left') }}</option>
                                                    <option value="right">{{ __('Right') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Photo Ratio') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.card_image_ratio"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Default') }}</option>
                                                    <option value="16 / 9">{{ __('Wide 16:9') }}</option>
                                                    <option value="4 / 3">{{ __('Classic 4:3') }}</option>
                                                    <option value="1 / 1">{{ __('Square 1:1') }}</option>
                                                    <option value="3 / 4">{{ __('Portrait 3:4') }}</option>
                                                </select>
                                            </div>
                                            <div class="space-y-1">
                                                <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Corner Radius') }}</label>
                                                <select wire:model.live="selectedBlockData.styles.card_image_radius"
                                                        class="studio-select-field w-full text-xs">
                                                    <option value="">{{ __('Template default') }}</option>
                                                    <option value="0px">{{ __('Sharp') }}</option>
                                                    <option value="12px">{{ __('Soft') }}</option>
                                                    <option value="24px">{{ __('Rounded') }}</option>
                                                    <option value="999px">{{ __('Pill') }}</option>
                                                </select>
                                            </div>
                                        </div>

                                        <button wire:click="resetBlockStyle('photo')" class="btn-secondary !h-8 !px-3 !text-[11px] justify-center w-full">
                                            {{ __('↺ Reset Cards to Template Default') }}
                                        </button>
                                    </div>
                                @endif

                            <div class="grid grid-cols-2 gap-3 pt-2 border-t border-[color:var(--sc-border)]">
                                <button wire:click="deleteSelectedBlock"
                                        wire:confirm="Remove this section layout from the current page?"
                                        class="btn-danger justify-center">
                                    {{ __('Remove') }}
                                </button>
                                <button wire:click="applyCustomizations"
                                        class="btn-primary justify-center">
                                    {{ __('Apply & Save') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12 px-4 border border-dashed border-[color:var(--sc-border)] rounded-2xl bg-slate-50">
                            <span class="text-3xl block mb-3">{{ __('👆') }}</span>
                            <span class="text-sm font-bold text-[color:var(--sc-text)] block">{{ __('No Section Selected') }}</span>
                            <span class="text-xs text-[color:var(--sc-text-muted)] block mt-1 leading-relaxed">{{ __('Click any block inside the live visual editor preview canvas to load its settings and text.') }}</span>
                        </div>
                    @endif
                </div>

                <!-- 4. BRAND ASSETS TAB -->
                <div x-show="tab === 'theme'" class="space-y-5" x-transition>
                    <div>
                        <h3 class="text-sm font-black text-[color:var(--sc-text)] tracking-tight">{{ __('Brand Assets') }}</h3>
                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-1">Manage school's visual identity colors & assets.</p>
                    </div>

                    <!-- Brand Assets Colors & Tokens -->
                    <div class="space-y-4">
                        <div class="glow-clickable p-5 rounded-2xl bg-white border border-[color:var(--sc-border)] shadow-sm hover:shadow-md transition">
                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] uppercase block tracking-wider mb-2">{{ __('Palette Color Tokens') }}</span>
                            <div class="grid grid-cols-3 gap-2">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 block">{{ __('Primary') }}</label>
                                    <input type="color" wire:model="color_primary" class="w-full h-8 rounded-lg cursor-pointer bg-slate-50">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 block">{{ __('Secondary') }}</label>
                                    <input type="color" wire:model="color_secondary" class="w-full h-8 rounded-lg cursor-pointer bg-slate-50">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[9px] font-bold text-slate-400 block">{{ __('Accent') }}</label>
                                    <input type="color" wire:model="color_accent" class="w-full h-8 rounded-lg cursor-pointer bg-slate-50">
                                </div>
                            </div>
                        </div>

                        <div class="p-5 rounded-2xl bg-white border border-[color:var(--sc-border)] shadow-sm hover:shadow-md transition space-y-3">
                            <span class="text-[10px] font-black text-[color:var(--sc-primary)] uppercase block tracking-wider">Direct Logo Upload (PC)</span>
                            <input type="text" wire:model="mediaAltText" placeholder="Logo accessibility description"
                                   class="studio-input-field text-xs">
                            <div class="sc-dropzone">
                                <div class="flex items-center gap-3">
                                    <input type="file" wire:model="tempImage" accept="image/*" class="flex-1">
                                    <button wire:click="uploadWebsiteLogo"
                                            class="btn-primary !h-10 !px-4 !text-xs">
                                        {{ __('Upload') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. SITE NAVIGATOR TAB -->
                <div x-show="tab === 'pages'" class="space-y-5" x-transition>
                    <div>
                        <h3 class="text-sm font-black text-[color:var(--sc-text)] tracking-tight">{{ __('Site Navigator') }}</h3>
                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-1">{{ __('Manage pages, navigation, and site structure.') }}</p>
                    </div>

                    <!-- Site Navigator List -->
                    <div class="space-y-2">
                        @foreach($sitePages as $p)
                            <div class="glow-clickable p-4 bg-white border border-[color:var(--sc-border)] rounded-xl shadow-sm hover:shadow-md transition">
                                @if(($editingPageId ?? null) === $p['id'])
                                    {{-- Inline rename form --}}
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Rename Page') }}</span>
                                            <button type="button" wire:click="$set('editingPageId', null)" class="text-slate-400 hover:text-slate-600 text-sm">{{ __('✕') }}</button>
                                        </div>
                                        <input type="text" wire:model="editingPageTitle" placeholder="Page title..."
                                               class="studio-input-field text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-slate-400 sc-mono">/</span>
                                            <input type="text" wire:model="editingPageSlug" placeholder="page-url"
                                                   class="studio-input-field flex-1 text-xs sc-mono">
                                        </div>
                                        @error('editingPageSlug')
                                            <span class="text-[10px] font-bold text-rose-500">{{ $message }}</span>
                                        @enderror
                                        <label class="flex items-center gap-2 text-xs font-semibold text-[color:var(--sc-text)] cursor-pointer select-none">
                                            <input type="checkbox" wire:model="editingPageHidden" class="rounded border-slate-300 text-[var(--sc-primary-500)] focus:ring-[var(--sc-primary-500)]">
                                            {{ __('Hide from navigation') }}
                                        </label>
                                        <div class="flex gap-2">
                                            <button wire:click="savePageSettings" class="btn-primary flex-1 !h-9 !px-3 !text-xs">{{ __('Save') }}</button>
                                            <button wire:click="$set('editingPageId', null)" class="btn-secondary flex-1 !h-9 !px-3 !text-xs">{{ __('Cancel') }}</button>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="min-w-0 pr-2">
                                            <span class="text-sm font-bold text-[color:var(--sc-text)] block truncate">{{ $p['title'] }}</span>
                                            <span class="text-xs text-slate-400 truncate sc-mono">/{{ $p['slug'] }} {{ $p['is_homepage'] ? '🏠' : '' }}</span>
                                            @if(!empty($p['page_theme']))
                                                @php $themeName = (\Modules\CMS\Services\CmsTemplateService::getTemplates()[$p['page_theme']] ?? [])['name'] ?? $p['page_theme']; @endphp
                                                <span class="inline-flex items-center gap-1 mt-1 text-[9px] font-bold text-purple-700 bg-purple-50 border border-purple-200 rounded-full px-2 py-0.5">
                                                    🎨 {{ $themeName }}
                                                </span>
                                            @endif
                                            @if(!empty($p['page_layout']))
                                                <span class="inline-flex items-center gap-1 mt-1 ml-1 text-[9px] font-bold text-blue-700 bg-blue-50 border border-blue-200 rounded-full px-2 py-0.5">
                                                    ▤ {{ $p['page_layout'] }}
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 shrink-0">
                                            <button wire:click="movePage({{ $p['id'] }}, 'up')"
                                                    class="btn-secondary !h-9 !px-2.5 !text-xs" title="Move up in navigation">
                                                ↑
                                            </button>
                                            <button wire:click="movePage({{ $p['id'] }}, 'down')"
                                                    class="btn-secondary !h-9 !px-2.5 !text-xs" title="Move down in navigation">
                                                ↓
                                            </button>
                                            @if(!$p['is_homepage'])
                                                <button wire:click="setHomepage({{ $p['id'] }})"
                                                        class="btn-secondary !h-9 !px-2.5 !text-xs" title="Set as Homepage">
                                                    {{ __('🏠') }}
                                                </button>
                                            @endif
                                            <button wire:click="togglePageVisibility({{ $p['id'] }})"
                                                    class="btn-secondary !h-9 !px-2.5 !text-xs" title="Show or hide in the site navigation">
                                                {{ $p['hide_from_nav'] ? '◉ Hidden' : '◌ Visible' }}
                                            </button>
                                            <button wire:click="beginEditingPage({{ $p['id'] }})"
                                                    class="btn-secondary !h-9 !px-2.5 !text-xs" title="Rename Page">
                                                {{ __('✏️') }}
                                            </button>
                                            <button wire:click="duplicatePage({{ $p['id'] }})"
                                                    class="btn-secondary !h-9 !px-2.5 !text-xs" title="Duplicate Page">
                                                {{ __('📋') }}
                                            </button>
                                            @if(!$p['is_homepage'])
                                                <button type="button"
                                                        wire:click="deletePage({{ $p['id'] }})"
                                                        wire:confirm="Delete page '{{ $p['title'] }}'? This cannot be undone."
                                                        class="btn-danger !h-9 !px-2.5 !text-xs cursor-pointer" title="Delete Page">
                                                    {{ __('🗑') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-[color:var(--sc-border)] pt-4 space-y-3">
                        <h4 class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ __('Create New Webpage') }}</h4>
                        <div class="flex gap-3">
                            <input type="text" wire:model="newPageTitle" placeholder="Page title..."
                                   class="studio-input-field flex-1 text-xs">
                            <button wire:click="addPage"
                                    class="btn-primary !px-5">
                                {{ __('+ Add') }}
                            </button>
                        </div>
                    </div>
                </div>

            </div>
            </div>
        </aside>

        <!-- B. RIGHT CANVAS / PREVIEW -->
        <main x-bind:class="sidebarOpen ? 'lg:col-span-8 xl:col-span-9' : 'lg:col-span-12'"
              class="studio-canvas col-span-12 p-4 lg:p-6 overflow-y-auto h-[calc(100vh-68px)] flex justify-center custom-scrollbar">

            <div class="transition-all duration-300 sc-preview-{{ $previewSize }} sc-canvas-minh bg-white rounded-3xl shadow-2xl shadow-slate-400/20 overflow-hidden border border-[color:var(--sc-border)] flex flex-col my-auto"
                 {!! 'sty' . 'le="--theme-primary: ' . $color_primary . '; --theme-secondary: ' . $color_secondary . '; --theme-accent: ' . $color_accent . '; --theme-bg: ' . $color_background . '; --theme-text: ' . $color_text . '; --theme-card-bg: ' . $color_card_bg . '; --theme-radius: ' . (\Modules\CMS\Services\CmsTemplateService::RADIUS_SCALE[$design_radius] ?? '20px') . '; --theme-shadow: ' . (\Modules\CMS\Services\CmsTemplateService::SHADOW_SCALE[$design_shadow] ?? '0 10px 30px -5px rgba(15,23,42,0.08)') . '; --theme-btn-radius: ' . (\Modules\CMS\Services\CmsTemplateService::BUTTON_STYLES[$design_button_style] ?? 'rounded-full') . '; --font-primary: \'' . $font_primary . '\', sans-serif; --font-secondary: \'' . $font_secondary . '\', serif;"' !!}>

                <!-- Browser Simulated Header Frame -->
                <div class="sc-deck px-4 py-3 flex items-center justify-between text-sm font-bold text-slate-400 select-none border-b-0">
                    <div class="flex items-center space-x-1.5">
                        <span class="w-3 h-3 rounded-full bg-[#f0355c] block"></span>
                        <span class="w-3 h-3 rounded-full bg-[#e9a13a] block"></span>
                        <span class="w-3 h-3 rounded-full bg-[#0ea768] block"></span>
                    </div>
                    <div class="bg-white/[0.07] border border-white/10 px-4 py-1 rounded-full text-xs sc-mono text-slate-300 flex items-center space-x-2">
                        <span class="text-[#3fe0a4]">{{ __('🔒') }}</span>
                        <span>https://{{ $website->school->slug ?? 'school' }}.kairocore.edu/{{ $page->is_homepage ? '' : $page->slug }}</span>
                    </div>
                    <span class="text-[10px] uppercase font-black text-slate-500">{{ str_replace(['full', 'tablet', 'mobile'], ['Desktop Canvas', 'Tablet Mode', 'Mobile Mode'], $previewSize) }}</span>
                </div>

                <!-- Public Menu Navigation Bar Preview -->
                <header class="border-b px-6 md:px-8 py-4 flex items-center justify-between gap-6 select-none"
                        {!! 'sty' . 'le="background-color: var(--theme-card-bg); border-color: rgba(0,0,0,0.06);"' !!}>
                    <div class="flex items-center space-x-3 min-w-0 max-w-[38%]">
                        @if($website->logo_light_path ?? $website->school->logo_path ?? null)
                            <img src="{{ asset('storage/' . ($website->logo_light_path ?? $website->school->logo_path)) }}"
                                 class="h-9 w-9 object-contain rounded-full border shrink-0" alt="School logo">
                        @endif
                        <span class="text-xl font-black tracking-tight truncate" style="color: var(--theme-primary);">
                            {{ $website->school->name ?? 'Kairo Demo Academy' }}
                        </span>
                    </div>
                    <nav class="hidden lg:flex items-center justify-center gap-5 text-sm font-bold flex-1 min-w-0" style="color: var(--theme-text);">
                        @foreach($sitePages as $p)
                            @continue($p['hide_from_nav'])
                            @continue(!$p['is_published'])
                            <span class="truncate {{ $p['id'] === $page['id'] ? 'sc-tab-active' : 'opacity-70' }}">
                                {{ $p['title'] }}
                            </span>
                        @endforeach
                    </nav>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="hidden sm:block h-6 w-px" style="background-color: rgba(0,0,0,0.08);"></span>
                        <span class="hidden sm:inline-flex px-4 py-2 font-bold text-sm rounded-full border-2"
                              style="color: var(--theme-primary); border-color: color-mix(in srgb, var(--theme-primary) 50%, transparent);">
                            {{ __('Log In') }}
                        </span>
                        <span class="inline-flex px-5 py-2 font-bold text-sm text-white rounded-full shadow-md transition"
                              style="background-color: var(--theme-primary);">
                            {{ __('Apply Online') }}
                            <span class="ml-1 transition-transform duration-200 inline-block">→</span>
                        </span>
                    </div>
                </header>

                <!-- Drag-and-Drop Canvas Frame with click shielding overlay mask -->
                <div id="sc-canvas" class="relative min-h-[600px] flex-1" x-data="blockSorter(@js(array_values($blocks)))"
                     x-on:dragover.prevent x-on:drop="dropOnCanvas($event)">
                    @forelse($blocks as $index => $block)
                        @continue($block['styles']['hidden'] ?? false)

                        <!-- Click Shield Mask wrapper ensures exact selection click bindings and avoids event bubbling -->
                        <div wire:key="canvas-block-{{ $block['id'] }}-{{ $index }}"
                             class="group relative border-2 border-transparent transition-all rounded-2xl"
                             x-bind:class="selectedBlock === {{ $index }} ? 'active-canvas-block' : 'hover:border-[var(--sc-primary-500)]/40 hover:bg-[var(--sc-primary-500)]/[0.03]'"
                             draggable="true" data-id="{{ $block['id'] }}"
                             x-on:dragstart="dragStart($event, '{{ $block['id'] }}')"
                             x-on:dragover.prevent
                             x-on:drop.stop="drop($event, '{{ $block['id'] }}')"
                             x-on:click="selectedBlock = {{ $index }}; $wire.selectBlock({{ $index }})">

                            <!-- Hover Actions Tool Box -->
                            <div class="sc-toolbox select-none">
                                <span class="sc-toolbox-label">
                                    {{ str_replace('_', ' ', $block['type']) }}
                                </span>
                                <button wire:click.stop="selectBlock({{ $index }})"
                                        class="sc-toolbox-btn sc-toolbox-btn-primary">
                                    {{ __('Edit') }}
                                </button>
                                <button wire:click.stop="duplicateBlock({{ $index }})"
                                        class="sc-toolbox-btn">
                                    {{ __('Clone') }}
                                </button>
                                <button wire:click.stop="openBlockImporter({{ $index }})"
                                        class="sc-toolbox-btn sc-toolbox-btn-cyan">
                                    {{ __('Swap') }}
                                </button>
                                <button wire:click.stop="moveBlockUp({{ $index }})"
                                        class="sc-toolbox-btn" @if($index === 0) disabled @endif>
                                    {{ __('↑') }}
                                </button>
                                <button wire:click.stop="moveBlockDown({{ $index }})"
                                        class="sc-toolbox-btn" @if($index === count($blocks) - 1) disabled @endif>
                                    {{ __('↓') }}
                                </button>
                                <button wire:click.stop="deleteBlock({{ $index }})"
                                        class="sc-toolbox-btn sc-toolbox-btn-danger">
                                    {{ __('Delete') }}
                                </button>
                            </div>

                            <!-- Click Shield overlay allows simple selecting click over dynamic widgets/forms -->
                            <div class="absolute inset-0 z-10 cursor-pointer pointer-events-auto"></div>

                            <div class="sc-site pointer-events-none select-none"
                                 data-sc-template="{{ $pageTemplate }}"
                                 data-sc-motion="off"
                                 style="min-height: 0;
                                        --sc-primary: {{ $color_primary }};
                                        --sc-secondary: {{ $color_secondary }};
                                        --sc-accent: {{ $color_accent }};
                                        --sc-bg: {{ $color_background }};
                                        --sc-surface: {{ $color_card_bg }};
                                        --sc-text: {{ $color_text }};
                                        --sc-ink: {{ \Modules\CMS\Services\CmsTemplateService::getAdaptiveTextColor($color_primary, '#ffffff', '#0f172a') }};
                                        --sc-border: rgba(15,23,42,0.1);
                                        --sc-radius: {{ \Modules\CMS\Services\CmsTemplateService::RADIUS_SCALE[$design_radius] ?? '20px' }};
                                        --sc-radius-btn: {{ \Modules\CMS\Services\CmsTemplateService::BUTTON_STYLES[$design_button_style] ?? '9999px' }};
                                        --sc-shadow: {{ \Modules\CMS\Services\CmsTemplateService::SHADOW_SCALE[$design_shadow] ?? '0 10px 30px -5px rgba(15,23,42,0.08)' }};
                                        --sc-shadow-lg: {{ \Modules\CMS\Services\CmsTemplateService::SHADOW_SCALE[$design_shadow === 'xl' ? 'xl' : 'lg'] ?? '0 20px 40px -15px rgba(15,23,42,0.12)' }};
                                        --sc-container: {{ ($design_container === 'full') ? 'none' : '80rem' }};
                                        --sc-font-sans: '{{ $font_primary }}', ui-sans-serif, system-ui, sans-serif;
                                        --sc-font-display: '{{ $font_secondary }}', ui-sans-serif, system-ui, sans-serif;
                                        --sc-font-heading: '{{ $font_heading ?: $font_secondary }}', ui-sans-serif, system-ui, sans-serif;">
                                @include('modules.cms.sections.preview-block', [
                                    'block' => $block,
                                    'stats' => $stats,
                                    'news' => $news,
                                    'events' => $events,
                                    'staff' => $staff,
                                    'page' => $page,
                                    'school' => $website->school,
                                    'isStudioPreview' => true,
                                     'theme' => [
                                         'template' => $pageTemplate,
                                         'primary' => $color_primary,
                                        'secondary' => $color_secondary,
                                        'accent' => $color_accent,
                                        'background' => $color_background,
                                        'text' => $color_text,
                                        'cardBg' => $color_card_bg,
                                        'fontPrimary' => $font_primary,
                                        'fontSecondary' => $font_secondary,
                                        'container' => \Modules\CMS\Services\CmsTemplateService::CONTAINER_SCALE[$design_container] ?? 'max-w-7xl',
                                    ],
                                ])
                            </div>
                        </div>
                    @empty
                        <div class="m-8 rounded-3xl border-2 border-dashed border-[var(--sc-primary-500)]/30 bg-[var(--sc-primary-500)]/[0.04] px-6 py-24 text-center shadow-inner">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-2xl text-[color:var(--sc-primary)] shadow-sm">{{ __('✦') }}</span>
                            <h3 class="mt-4 text-lg font-bold text-[color:var(--sc-text)]">{{ __('Start Page Composition') }}</h3>
                            <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[color:var(--sc-text-muted)]">{{ __('Choose a structural element block on the left panel or drag one directly to build the page.') }}</p>
                        </div>
                    @endforelse

                    <div class="m-6 rounded-2xl border-2 border-dashed border-[var(--sc-primary-500)]/30 bg-[var(--sc-primary-500)]/[0.03] px-6 py-6 text-center text-sm font-bold text-[color:var(--sc-text-muted)]"
                         x-on:dragover.prevent x-on:drop.stop="dropOnCanvas($event)">
                        {{ __('Drop any layout element block structure here, or rearrange active layouts') }}
                    </div>
                </div>

                <!-- Footer -->
                <footer class="mt-auto py-6 bg-slate-50 text-slate-500 text-center text-xs border-t select-none">
                    &copy; {{ date('Y') }} {{ $website->school->name ?? 'Kairo Demo Academy' }}. All Rights Reserved.
                </footer>
            </div>
        </main>
    </div>

    <!-- SEO Modal -->
    @if($showSeoModal)
        <div class="fixed inset-0 bg-[#0a0e1a]/70 backdrop-blur-sm z-[100] flex items-center justify-center p-6">
            <div class="bg-white border border-[color:var(--sc-border)] w-full max-w-lg rounded-2xl p-6 space-y-4 shadow-2xl">
                <div class="flex items-center justify-between border-b border-[color:var(--sc-border)] pb-3">
                    <h3 class="text-sm font-black text-[color:var(--sc-text)] uppercase tracking-wider">{{ __('SEO & Meta Settings') }}</h3>
                    <button wire:click="$set('showSeoModal', false)" class="text-slate-400 hover:text-slate-600 text-xl">{{ __('✕') }}</button>
                </div>

                <div class="space-y-3">
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('SEO Page Title') }}</label>
                        <input type="text" wire:model="seoTitle"
                               class="studio-input-field w-full text-xs">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Meta Description') }}</label>
                        <textarea wire:model="seoDescription" rows="3"
                                  class="studio-input-field w-full text-xs h-20"></textarea>
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-bold text-[color:var(--sc-text-muted)] block">{{ __('Meta Keywords') }}</label>
                        <input type="text" wire:model="seoKeywords" placeholder="school, admissions, education"
                               class="studio-input-field w-full text-xs">
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-2 border-t border-[color:var(--sc-border)]">
                    <button wire:click="$set('showSeoModal', false)"
                            class="btn-secondary">
                        {{ __('Cancel') }}
                    </button>
                    <button wire:click="saveSeoSettings"
                            class="btn-primary">
                        {{ __('Save Meta Data') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Block Importer Modal (deep per-block mixing) -->
    @if($showBlockImporter)
        <div class="fixed inset-0 bg-[#0a0e1a]/70 backdrop-blur-sm z-[110] flex items-center justify-center p-6">
            <div class="bg-white border border-[color:var(--sc-border)] w-full max-w-3xl rounded-2xl shadow-2xl max-h-[85vh] flex flex-col">
                <div class="flex items-center justify-between border-b border-[color:var(--sc-border)] px-6 py-4">
                    <div>
                        <h3 class="text-sm font-black text-[color:var(--sc-text)] uppercase tracking-wider">{{ __('Swap / Import a Section') }}</h3>
                        <p class="text-xs text-[color:var(--sc-text-muted)] mt-0.5">
                            {{ __('Pull a section from the 5 predesigned layouts, any of the 5 system templates, or your other saved templates, then replace it or copy only its styling.') }}
                        </p>
                    </div>
                    <button wire:click="closeBlockImporter" class="text-slate-400 hover:text-slate-600 text-xl">{{ __('✕') }}</button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-6">
                    @forelse($blockImportSources as $group)
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-[10px] font-black uppercase tracking-wider text-[color:var(--sc-primary)]">{{ $group['title'] }}</span>
                                <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full bg-[var(--sc-primary-light)] text-[var(--sc-primary)]">{{ $group['badge'] }}</span>
                            </div>
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                                @foreach($group['blocks'] as $sourceBlock)
                                    <div class="rounded-xl border border-[color:var(--sc-border)] p-3 space-y-2 bg-slate-50/60">
                                        <span class="block text-[9px] font-black uppercase tracking-wider text-slate-400">{{ $sourceBlock['preview'] }}</span>
                                        <p class="text-xs font-bold text-[color:var(--sc-text)] leading-snug line-clamp-2 min-h-[2rem]">{{ $sourceBlock['title'] }}</p>
                                        <div class="flex gap-1.5">
                                            <button wire:click="importBlock('replace', '{{ $group['key'] }}', '{{ $sourceBlock['id'] }}')"
                                                    class="flex-1 px-2 py-1.5 text-[10px] font-bold rounded-lg bg-[var(--sc-primary-500)] text-white hover:bg-[var(--sc-primary-700)] transition">
                                                {{ __('Replace') }}
                                            </button>
                                            <button wire:click="importBlock('styles', '{{ $group['key'] }}', '{{ $sourceBlock['id'] }}')"
                                                    title="Copy only the styles (same section type)"
                                                    class="flex-1 px-2 py-1.5 text-[10px] font-bold rounded-lg bg-white border border-[color:var(--sc-border)] text-[color:var(--sc-text)] hover:border-[var(--sc-primary-500)] hover:text-[var(--sc-primary-500)] transition">
                                                {{ __('Styles') }}
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[color:var(--sc-text-muted)]">{{ __('No import sources available.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function blockSorter(blocks) {
        return {
            dragging: null,
            dragStart(e, id) {
                this.dragging = id;
                e.dataTransfer.effectAllowed = 'move';
            },
            drop(e, targetId) {
                const blockType = e.dataTransfer.getData('application/x-schoolcore-block');
                if (blockType) {
                    this.$wire.call('addBlock', blockType);
                    return;
                }
                if (!this.dragging || this.dragging === targetId) return;
                const ids = blocks.map(b => b.id);
                const from = ids.indexOf(this.dragging);
                const to = ids.indexOf(targetId);
                ids.splice(to, 0, ids.splice(from, 1)[0]);
                this.dragging = null;
                this.$wire.call('reorderBlocks', ids);
            },
            dropOnCanvas(e) {
                const blockType = e.dataTransfer.getData('application/x-schoolcore-block');
                if (blockType) this.$wire.call('addBlock', blockType);
            },
        };
    }
</script>
