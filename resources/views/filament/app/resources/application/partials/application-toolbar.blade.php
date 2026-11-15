@php
    $gradeOptions = $this->getGradeOptions();
    $hasFilters = filled($this->tableSearch) || filled($this->gradeFilter) || filled($this->statusFilter);
@endphp

<style>
    .adm-toolbar { display:flex; flex-wrap:wrap; align-items:center; gap:10px; margin-bottom:12px; padding:12px; border:1px solid var(--adm-line); border-radius:16px; background:var(--adm-surface); box-shadow:var(--adm-shadow); }
    .adm-field { display:flex; align-items:center; gap:8px; border:1px solid var(--adm-line); border-radius:12px; padding:0 12px; background:var(--adm-surface); flex:1; min-width:220px; }
    .adm-field:focus-within { border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
    .adm-field input { border:0; border-bottom:0; outline:0; flex:1; padding:10px 0; background:none; min-width:0; color:var(--adm-text); box-shadow:none; }
    .adm-field svg { width:18px; height:18px; color:var(--adm-muted); flex:none; }

    .adm-combobox { position:relative; min-width:190px; }
    .adm-combobox-trigger { display:flex; width:100%; align-items:center; justify-content:space-between; gap:8px; padding:10px 12px; border:1px solid var(--adm-line); border-bottom:0; border-radius:12px; background:var(--adm-surface); color:var(--adm-text); cursor:pointer; text-align:left; }
    .adm-combobox-trigger:focus-visible, .adm-combobox-search:focus { outline:0; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
    .adm-combobox-trigger svg { width:16px; height:16px; color:var(--adm-muted); flex:none; }
    .adm-combobox-menu { position:absolute; top:calc(100% + 6px); left:0; z-index:40; width:260px; padding:8px; border:1px solid var(--adm-line); border-radius:12px; background:var(--adm-surface); box-shadow:0 18px 40px -18px rgba(30,27,75,.35); }
    .adm-combobox-search { width:100%; border:1px solid var(--adm-line); border-bottom:0; border-radius:10px; padding:8px 10px; background:var(--adm-surface-2); color:var(--adm-text); }
    .adm-combobox-list { margin-top:6px; max-height:calc(5 * 36px); overflow-y:auto; }
    .adm-combobox-option { display:block; width:100%; text-align:left; padding:8px 10px; border:0; border-radius:8px; background:transparent; color:var(--adm-text); cursor:pointer; font-size:13px; }
    .adm-combobox-option:hover, .adm-combobox-option.is-selected { background:#eef0ff; color:var(--adm-brand); }
    .dark .adm-combobox-option:hover, .dark .adm-combobox-option.is-selected { background:rgba(99,102,241,.15); }
    .adm-combobox-empty { padding:8px 10px; color:var(--adm-muted); font-size:12px; }

    .adm-seg { display:flex; border:1px solid var(--adm-line); border-radius:12px; overflow:hidden; }
    .adm-seg button { padding:9px 12px; color:var(--adm-muted); display:flex; gap:6px; align-items:center; font-weight:600; background:var(--adm-surface); border:0; cursor:pointer; }
    .adm-seg button.on { background:var(--adm-brand); color:#fff; }
    .adm-chips { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; align-items:center; }
    .adm-chip { border:1px solid var(--adm-line); background:var(--adm-surface); border-radius:999px; padding:6px 14px; font-weight:600; font-size:13px; color:var(--adm-muted); cursor:pointer; }
    .adm-chip.on { background:#eef0ff; color:var(--adm-brand); border-color:#a5a8f3; }
    .dark .adm-chip.on { background:rgba(99,102,241,.15); }
    .adm-clear { border:1px solid var(--adm-line); background:var(--adm-surface); border-radius:10px; padding:8px 12px; font-weight:600; color:#be123c; cursor:pointer; }
    [x-cloak] { display:none !important; }
</style>

<div class="adm-toolbar">
    <label class="adm-field">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" wire:model.live.debounce.300ms="tableSearch" placeholder="{{ __('Search by name, application no. or phone') }}" aria-label="{{ __('Search applications') }}">
    </label>

    <div class="adm-combobox"
         x-data="{
            open: false,
            query: '',
            value: @js($this->gradeFilter),
            options: @js($gradeOptions),
            get filtered() {
                const q = this.query.trim().toLowerCase();
                const entries = Object.entries(this.options);
                if (!q) return entries;
                return entries.filter(([, label]) => String(label).toLowerCase().includes(q));
            },
            get label() {
                return this.value && this.options[this.value] ? this.options[this.value] : @js(__('All grades'));
            },
            select(v) { this.value = v; this.open = false; this.query = ''; $wire.set('gradeFilter', v); }
         }"
         x-on:keydown.escape.window="open = false">
        <button type="button" class="adm-combobox-trigger" x-on:click="open = !open" aria-haspopup="listbox">
            <span x-text="label">{{ __('All grades') }}</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <div class="adm-combobox-menu" x-cloak x-show="open" x-transition.opacity.duration.120ms x-on:click.outside="open = false">
            <input type="search" class="adm-combobox-search" x-model="query" placeholder="{{ __('Search grades') }}" autocomplete="off">
            <div class="adm-combobox-list" role="listbox">
                <button type="button" class="adm-combobox-option" x-on:click="select('')">{{ __('All grades') }}</button>
                <template x-for="[id, name] in filtered" :key="id">
                    <button type="button" class="adm-combobox-option" x-bind:class="{ 'is-selected': String(value) === String(id) }" x-on:click="select(id)" x-text="name"></button>
                </template>
                <div class="adm-combobox-empty" x-show="filtered.length === 0">{{ __('No grades found') }}</div>
            </div>
        </div>
    </div>

    <div class="adm-seg" role="group" aria-label="{{ __('View') }}">
        <button type="button" class="{{ $this->applicationView === 'grid' ? 'on' : '' }}" wire:click="$set('applicationView','grid')" aria-pressed="{{ $this->applicationView === 'grid' ? 'true' : 'false' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><rect x="14" y="14" width="6" height="6"/></svg>{{ __('Grid') }}
        </button>
        <button type="button" class="{{ $this->applicationView === 'list' ? 'on' : '' }}" wire:click="$set('applicationView','list')" aria-pressed="{{ $this->applicationView === 'list' ? 'true' : 'false' }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" width="16" height="16"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>{{ __('List') }}
        </button>
    </div>
    @if($hasFilters)
        <button type="button" class="adm-clear" wire:click="clearApplicationFilters">{{ __('Clear filters') }}</button>
    @endif
</div>

<div class="adm-chips" aria-label="{{ __('Status filters') }}">
    @foreach(['' => __('All statuses'), 'pending' => __('Pending'), 'review' => __('In review'), 'enrolled' => __('Enrolled'), 'rejected' => __('Declined')] as $key => $label)
        @php $active = $key === '' ? $this->statusFilter === '' : ($key === 'review' ? in_array($this->statusFilter,['verified','confirmed','review'], true) : $this->statusFilter === $key); @endphp
        <button type="button" class="adm-chip {{ $active ? 'on' : '' }}" wire:click="applyStatFilter('{{ $key ?: 'all' }}')">{{ $label }}</button>
    @endforeach
</div>
