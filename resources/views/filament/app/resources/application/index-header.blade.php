@php
    $total = max(1, (int) ($stats['total'] ?? 0));
    $pending = (int) ($stats['pending'] ?? 0);
    $review = (int) ($stats['review'] ?? 0);
    $enrolled = (int) ($stats['enrolled'] ?? 0);
    $pendingPct = (int) round(($pending / $total) * 100);
    $reviewPct = (int) round(($review / $total) * 100);
    $enrolledPct = (int) round(($enrolled / $total) * 100);
@endphp

<style>
    body[data-page="admissions"] {
        --adm-brand: #4338ca; --adm-cyan: #06b6d4; --adm-ok:#059669; --adm-warn:#b45309; --adm-info:#0369a1; --adm-bad:#be123c;
        --adm-surface:#fff; --adm-surface-2:#f6f7fd; --adm-text:#1e1b4b; --adm-muted:#64668a; --adm-line:#e6e7f5;
        --adm-shadow:0 1px 2px rgba(30,27,75,.05),0 8px 24px -12px rgba(67,56,202,.18);
    }
    .dark body[data-page="admissions"] { --adm-surface:#161a30; --adm-surface-2:#1d2240; --adm-text:#eef0fb; --adm-muted:#99a1c4; --adm-line:#272d50; }
    .adm-head { margin-bottom: 18px; }
    .adm-head-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
    .adm-title { display:flex; align-items:center; gap:14px; }
    .adm-title-icon { width:52px; height:52px; border-radius:14px; display:grid; place-items:center; color:#fff; background:linear-gradient(135deg,var(--adm-brand),#6366f1); box-shadow:0 10px 20px -8px rgba(67,56,202,.6); flex:none; }
    .adm-title-icon svg { width:26px; height:26px; }
    .adm-title h1 { margin:0; font-size:clamp(24px,3.4vw,32px); letter-spacing:-.02em; line-height:1.15; color:var(--adm-brand); font-weight:800; }
    .adm-title p { margin:4px 0 0; color:var(--adm-muted); font-size:14px; }
    .adm-actions { display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
    .adm-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-top:18px; }
    .adm-stat { position:relative; display:flex; width:100%; align-items:center; gap:14px; padding:16px 18px; border:1px solid var(--adm-line); border-radius:16px; background:var(--adm-surface); box-shadow:var(--adm-shadow); text-align:left; cursor:pointer; transition:transform .18s,border-color .18s,box-shadow .18s; overflow:hidden; }
    .adm-stat:hover { transform:translateY(-2px); border-color:color-mix(in srgb,var(--c) 45%,var(--adm-line)); }
    .adm-stat.is-active { border-color:var(--c); box-shadow:0 0 0 3px color-mix(in srgb,var(--c) 18%,transparent),var(--adm-shadow); }
    .adm-stat-icon { width:44px; height:44px; border-radius:12px; display:grid; place-items:center; color:var(--c); background:color-mix(in srgb,var(--c) 14%,transparent); flex:none; }
    .adm-stat-icon svg { width:22px; height:22px; }
    .adm-stat b { display:block; font-size:26px; line-height:1.1; color:var(--adm-text); font-weight:800; font-variant-numeric:tabular-nums; }
    .adm-stat span { color:var(--adm-muted); font-size:13px; }
    .adm-meter { height:5px; border-radius:99px; background:var(--adm-surface-2); margin-top:8px; overflow:hidden; }
    .adm-meter i { display:block; height:100%; border-radius:99px; background:var(--c); }
    body[data-page="admissions"] .fi-ta-header-ctn { display:none !important; }
    body[data-page="admissions"] .fi-ta-content > div.flex.items-center.gap-4 { display:none !important; }
    body[data-page="admissions"] .fi-header, body[data-page="admissions"] .fi-breadcrumbs { display:none !important; }
</style>

<div class="adm-head">
    <div class="adm-head-top">
        <div class="adm-title">
            <div class="adm-title-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/></svg>
            </div>
            <div>
                <h1>{{ __('Admissions') }}</h1>
                <p>{{ __('Process applications and manage the admission pipeline.') }}</p>
            </div>
        </div>
        @if (filled($actions))
            <div class="adm-actions"><x-filament-actions::actions :actions="$actions" /></div>
        @endif
    </div>

    <div class="adm-stats" aria-label="{{ __('Application statistics') }}">
        <button type="button" class="adm-stat {{ $this->statusFilter === '' ? 'is-active' : '' }}" style="--c: var(--adm-brand)" wire:click="applyStatFilter('all')" title="{{ __('Show all applications') }}">
            <span class="adm-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.5 3.3-5.5 6.5-5.5s5.9 2 6.5 5.5M16 4.5a3.5 3.5 0 0 1 0 7M18 14.8c1.8.7 3 2.5 3.5 5.2"/></svg></span>
            <div style="flex:1;min-width:0"><b>{{ number_format($stats['total'] ?? 0) }}</b><span>{{ __('Total applications') }}</span><div class="adm-meter"><i style="width:100%"></i></div></div>
        </button>
        <button type="button" class="adm-stat {{ $this->statusFilter === 'pending' ? 'is-active' : '' }}" style="--c: var(--adm-warn)" wire:click="applyStatFilter('pending')" title="{{ __('Filter pending') }}">
            <span class="adm-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <div style="flex:1;min-width:0"><b>{{ number_format($pending) }}</b><span>{{ __('Pending') }}</span><div class="adm-meter"><i style="width:{{ $pendingPct }}%"></i></div></div>
        </button>
        <button type="button" class="adm-stat {{ in_array($this->statusFilter,['verified','confirmed','review']) ? 'is-active' : '' }}" style="--c: var(--adm-info)" wire:click="applyStatFilter('review')" title="{{ __('Filter in review') }}">
            <span class="adm-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></span>
            <div style="flex:1;min-width:0"><b>{{ number_format($review) }}</b><span>{{ __('In review') }}</span><div class="adm-meter"><i style="width:{{ $reviewPct }}%"></i></div></div>
        </button>
        <button type="button" class="adm-stat {{ $this->statusFilter === 'enrolled' ? 'is-active' : '' }}" style="--c: var(--adm-ok)" wire:click="applyStatFilter('enrolled')" title="{{ __('Filter enrolled') }}">
            <span class="adm-stat-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m5 12 5 5 9-10"/></svg></span>
            <div style="flex:1;min-width:0"><b>{{ number_format($enrolled) }}</b><span>{{ __('Enrolled') }}</span><div class="adm-meter"><i style="width:{{ $enrolledPct }}%"></i></div></div>
        </button>
    </div>
</div>
