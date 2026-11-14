@php
    $studentIndexUrl = \App\Filament\App\Resources\StudentResource::getUrl('index');
    $templateIndexUrl = \App\Filament\App\Resources\CardTemplateResource::getUrl('index');
@endphp

<style>
    body[data-page="card-templates"] {
        --tpl-brand: var(--sc-brand, var(--primary-600, #4f46e5));
        --tpl-brand-2: #06b6d4;
        --tpl-ok: #10b981;
        --tpl-surface: #ffffff;
        --tpl-surface-2: #f0f3fa;
        --tpl-text: #141a33;
        --tpl-muted: #6a7392;
        --tpl-line: #e4e8f3;
        --tpl-shadow: 0 1px 2px rgba(20, 26, 51, .05), 0 8px 24px rgba(20, 26, 51, .06);
    }

    .dark body[data-page="card-templates"] {
        --tpl-surface: #161a30;
        --tpl-surface-2: #1d2240;
        --tpl-text: #eef0fb;
        --tpl-muted: #99a1c4;
        --tpl-line: #272d50;
        --tpl-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 24px rgba(0, 0, 0, .35);
    }

    .tpl-head { margin-bottom: 18px; }
    .tpl-head-top { display: flex; align-items: flex-start; justify-content: flex-end; gap: 16px; flex-wrap: wrap; }
    .tpl-title { display: flex; align-items: center; gap: 14px; }
    .tpl-title-icon { width: 52px; height: 52px; border-radius: 15px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, var(--tpl-brand), var(--tpl-brand-2)); box-shadow: 0 10px 24px -8px color-mix(in srgb, var(--tpl-brand) 60%, transparent); flex: none; }
    .tpl-title-icon svg { width: 27px; height: 27px; }
    .tpl-title h1 { margin: 0; font-size: clamp(24px, 3vw, 30px); letter-spacing: -.02em; line-height: 1.15; color: var(--tpl-text); font-weight: 800; }
    .tpl-title p { margin: 4px 0 0; color: var(--tpl-muted); font-size: 13.5px; }
    .tpl-head-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; }
    .tpl-tabs { display: inline-flex; gap: 4px; padding: 4px; margin: 16px 0 18px; border: 1px solid var(--tpl-line); border-radius: 12px; background: var(--tpl-surface); }
    .tpl-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 9px; font-size: 13px; font-weight: 700; color: var(--tpl-muted); text-decoration: none; transition: background .18s ease, color .18s ease; }
    .tpl-tab:hover { background: var(--tpl-surface-2); color: var(--tpl-text); }
    .tpl-tab.is-active { background: var(--tpl-brand); color: #fff; }
    .tpl-count { display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px; border: 1px solid var(--tpl-line); border-radius: 99px; background: var(--tpl-surface); color: var(--tpl-muted); font-size: 12.5px; font-weight: 700; }
    .tpl-count b { color: var(--tpl-text); }

    body[data-page="card-templates"] .fi-ta-header-ctn { display: none !important; }
    body[data-page="card-templates"] .fi-ta-content > div.flex.items-center.gap-4 { display: none !important; }
    body[data-page="card-templates"] .fi-ta-content { border-top: 0 !important; }

    @media (max-width: 640px) {
        .tpl-head-actions { width: 100%; justify-content: flex-start; }
        .tpl-title-icon { width: 46px; height: 46px; border-radius: 13px; }
        .tpl-title h1 { font-size: 24px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .tpl-tab { transition: none !important; }
    }
</style>

<div class="tpl-head">
    <div class="tpl-head-top">
        @if (filled($actions))
            <div class="tpl-head-actions">
                <x-filament-actions::actions :actions="$actions" />
            </div>
        @endif
    </div>

</div>
