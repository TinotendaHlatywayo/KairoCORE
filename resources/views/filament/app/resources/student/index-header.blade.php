@php
    $studentIndexUrl = \App\Filament\App\Resources\StudentResource::getUrl('index');
    $templateIndexUrl = \App\Filament\App\Resources\CardTemplateResource::getUrl('index');
    $total = max(1, (int) ($stats['total'] ?? 0));
    $pendingPercent = (int) round((($stats['pending'] ?? 0) / $total) * 100);
    $activePercent = (int) round((($stats['active'] ?? 0) / $total) * 100);
    $femalePercent = (int) ($stats['femalePercent'] ?? 0);
    $totalActive = $this->statusFilter === '' && $this->genderFilter === '';
    $pendingActive = $this->statusFilter === 'pending';
    $activeActive = $this->statusFilter === 'active';
    $femaleActive = $this->genderFilter === 'female';
    $maleActive = $this->genderFilter === 'male';
@endphp

<style>
    body[data-page="students"] {
        --std-brand: var(--sc-brand, var(--primary-600, #4f46e5));
        --std-brand-2: #06b6d4;
        --std-ok: #10b981;
        --std-warn: #f59e0b;
        --std-bad: #ef4444;
        --std-female: #db2777;
        --std-male: #2563eb;
        --std-surface: #ffffff;
        --std-surface-2: #f0f3fa;
        --std-text: #141a33;
        --std-muted: #6a7392;
        --std-line: #e4e8f3;
        --std-shadow: 0 1px 2px rgba(20, 26, 51, .05), 0 8px 24px rgba(20, 26, 51, .06);
    }

    .dark body[data-page="students"] {
        --std-surface: #161a30;
        --std-surface-2: #1d2240;
        --std-text: #eef0fb;
        --std-muted: #99a1c4;
        --std-line: #272d50;
        --std-shadow: 0 1px 2px rgba(0, 0, 0, .4), 0 8px 24px rgba(0, 0, 0, .35);
    }

    .std-head { margin-bottom: 18px; }
    .std-head-top { display: flex; align-items: flex-start; justify-content: flex-end; gap: 16px; flex-wrap: wrap; }
    .std-title { display: flex; align-items: center; gap: 14px; }
    .std-title-icon { width: 52px; height: 52px; border-radius: 15px; display: grid; place-items: center; color: #fff; background: linear-gradient(135deg, var(--std-brand), var(--std-brand-2)); box-shadow: 0 10px 24px -8px color-mix(in srgb, var(--std-brand) 60%, transparent); flex: none; }
    .std-title-icon svg { width: 27px; height: 27px; }
    .std-title h1 { margin: 0; font-size: clamp(24px, 3vw, 30px); letter-spacing: -.02em; line-height: 1.15; color: var(--std-text); font-weight: 800; }
    .std-title p { margin: 4px 0 0; color: var(--std-muted); font-size: 13.5px; }
    .std-head-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: flex-end; gap: 8px; }
    .std-tabs { display: inline-flex; gap: 4px; padding: 4px; margin: 16px 0 18px; border: 1px solid var(--std-line); border-radius: 12px; background: var(--std-surface); }
    .std-tab { display: inline-flex; align-items: center; gap: 6px; padding: 7px 16px; border-radius: 9px; font-size: 13px; font-weight: 700; color: var(--std-muted); text-decoration: none; transition: background .18s ease, color .18s ease; }
    .std-tab:hover { background: var(--std-surface-2); color: var(--std-text); }
    .std-tab.is-active { background: var(--std-brand); color: #fff; }
    .std-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(155px, 1fr)); gap: 12px; margin-top: 18px; }
    .std-stat { position: relative; display: flex; width: 100%; align-items: center; gap: 13px; overflow: hidden; padding: 15px 16px; border: 1px solid var(--std-line); border-radius: 15px; background: var(--std-surface); box-shadow: var(--std-shadow); color: inherit; font: inherit; text-align: left; cursor: pointer; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .std-stat::after { content: ""; position: absolute; right: -30px; top: -30px; width: 92px; height: 92px; border-radius: 50%; background: color-mix(in srgb, var(--c) 12%, transparent); pointer-events: none; transition: transform .4s ease; }
    .std-stat:hover::after { transform: scale(1.7); }
    .std-stat:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--c) 45%, var(--std-line)); box-shadow: 0 12px 30px -18px color-mix(in srgb, var(--c) 55%, transparent), var(--std-shadow); }
    .std-stat:focus-visible { outline: 3px solid color-mix(in srgb, var(--c) 45%, transparent); outline-offset: 2px; }
    .std-stat.is-active { border-color: var(--c); box-shadow: 0 0 0 3px color-mix(in srgb, var(--c) 16%, transparent), var(--std-shadow); }
    .std-stat-icon { width: 46px; height: 46px; border-radius: 13px; display: grid; place-items: center; flex: none; color: var(--c); background: color-mix(in srgb, var(--c) 14%, transparent); }
    .std-stat-icon svg { width: 23px; height: 23px; }
    .std-stat-body { flex: 1; min-width: 0; }
    .std-stat b { display: block; font-size: 26px; line-height: 1; letter-spacing: -.03em; color: var(--std-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
    .std-stat b.std-stat-inline span { display: inline; }
    .std-stat-slash { color: var(--std-muted); font-weight: 400; }
    @media (max-width: 640px) {
        .std-stat b.std-stat-inline { font-size: 22px; }
    }
    .std-stat span { display: block; margin-top: 4px; color: var(--std-muted); font-size: 12.5px; }
    .std-meter { display: flex; height: 5px; overflow: hidden; gap: 2px; margin-top: 8px; border-radius: 99px; background: var(--std-surface-2); }
    .std-meter i { display: block; height: 100%; border-radius: 99px; background: var(--c); }
    .std-meter.is-split { gap: 2px; background: transparent; }
    .std-meter.is-split i:first-child { border-radius: 99px 0 0 99px; background: var(--std-female); }
    .std-meter.is-split i:last-child { border-radius: 0 99px 99px 0; background: var(--std-male); }

    body[data-page="students"] .fi-ta-header-ctn { display: none !important; }
    body[data-page="students"] .fi-ta-content > div.flex.items-center.gap-4 { display: none !important; }
    body[data-page="students"] .fi-ta-content { border-top: 0 !important; }
    body[data-page="students"] .fi-header { display: none !important; }
    body[data-page="students"] .fi-breadcrumbs { display: none !important; }

    .student-card .card-actions .btn-view,
    .student-card .card-actions .btn-edit,
    .student-card .card-actions .btn-print { text-decoration: none; }

    @media (max-width: 640px) {
        .std-head-actions { width: 100%; justify-content: flex-start; }
        .std-title-icon { width: 46px; height: 46px; border-radius: 13px; }
        .std-title h1 { font-size: 24px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .std-stat, .std-tab { transition: none !important; }
    }
</style>

<div class="std-head">
    <div class="std-head-top">
        @if (filled($actions))
            <div class="std-head-actions">
                <x-filament-actions::actions :actions="$actions" />
            </div>
        @endif
    </div>

    <div
        class="std-stats"
        aria-label="{{ __('Student statistics') }}"
        x-data="{
            target: @js([
                'total' => (int) ($stats['total'] ?? 0),
                'pending' => (int) ($stats['pending'] ?? 0),
                'active' => (int) ($stats['active'] ?? 0),
                'female' => (int) ($stats['female'] ?? 0),
                'male' => (int) ($stats['male'] ?? 0),
            ]),
            shown: { total: 0, pending: 0, active: 0, female: 0, male: 0 },
            init() {
                if (window.__stdStatsCounted) {
                    this.shown = { ...this.target };
                    return;
                }

                window.__stdStatsCounted = true;
                const started = performance.now();
                const duration = 850;

                const tick = (now) => {
                    const progress = Math.min(1, (now - started) / duration);
                    const eased = 1 - Math.pow(1 - progress, 3);

                    for (const key in this.target) {
                        this.shown[key] = Math.round(this.target[key] * eased);
                    }

                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };

                requestAnimationFrame(tick);
            },
            fmt(value) {
                return new Intl.NumberFormat().format(value);
            }
        }"
    >
        <button type="button" class="std-stat {{ $totalActive ? 'is-active' : '' }}" style="--c: var(--std-brand)" wire:click="applyStatFilter('total')" aria-pressed="{{ $totalActive ? 'true' : 'false' }}" title="{{ __('Show all students') }}">
            <span class="std-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
            </span>
            <div class="std-stat-body">
                <b x-text="fmt(shown.total)">{{ number_format($stats['total'] ?? 0) }}</b>
                <span>{{ __('Total students') }}</span>
                <div class="std-meter"><i style="width: 100%"></i></div>
            </div>
        </button>

        <button type="button" class="std-stat {{ $pendingActive ? 'is-active' : '' }}" style="--c: var(--std-warn)" wire:click="applyStatFilter('pending')" aria-pressed="{{ $pendingActive ? 'true' : 'false' }}" title="{{ __('Filter pending students') }}">
            <span class="std-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </span>
            <div class="std-stat-body">
                <b x-text="fmt(shown.pending)">{{ number_format($stats['pending'] ?? 0) }}</b>
                <span>{{ __('Pending approval') }}</span>
                <div class="std-meter"><i style="width: {{ $pendingPercent }}%"></i></div>
            </div>
        </button>

        <button type="button" class="std-stat {{ $activeActive ? 'is-active' : '' }}" style="--c: var(--std-ok)" wire:click="applyStatFilter('active')" aria-pressed="{{ $activeActive ? 'true' : 'false' }}" title="{{ __('Filter active students') }}">
            <span class="std-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>
            </span>
            <div class="std-stat-body">
                <b x-text="fmt(shown.active)">{{ number_format($stats['active'] ?? 0) }}</b>
                <span>{{ __('Active') }}</span>
                <div class="std-meter"><i style="width: {{ $activePercent }}%"></i></div>
            </div>
        </button>

        <button type="button" class="std-stat {{ ($femaleActive || $maleActive) ? 'is-active' : '' }}" style="--c: var(--std-female)" wire:click="applyStatFilter('{{ $femaleActive ? 'male' : 'female' }}')" aria-pressed="{{ ($femaleActive || $maleActive) ? 'true' : 'false' }}" title="{{ __('Filter by gender') }}">
            <span class="std-stat-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M12 14v8M9 18h6"/></svg>
            </span>
            <div class="std-stat-body">
                <b class="std-stat-inline"><span x-text="fmt(shown.female)">{{ number_format($stats['female'] ?? 0) }}</span><span class="std-stat-slash"> / </span><span x-text="fmt(shown.male)">{{ number_format($stats['male'] ?? 0) }}</span></b>
                <span>{{ __('Female / Male') }}</span>
                <div class="std-meter is-split" title="{{ __('Female') }} {{ $femalePercent }}% · {{ __('Male') }} {{ 100 - $femalePercent }}%">
                    <i style="width: {{ $femalePercent }}%"></i>
                    <i style="width: {{ 100 - $femalePercent }}%"></i>
                </div>
            </div>
        </button>
    </div>
</div>
