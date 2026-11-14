@php
    $classOptions = $this->getClassOptions();
    $hasFilters = filled($this->tableSearch)
        || filled($this->classFilter)
        || filled($this->genderFilter)
        || filled($this->statusFilter);
    $totalStudents = method_exists($records, 'total') ? (int) $records->total() : 0;
@endphp

<div class="std-toolbar">
    <div class="std-bulkwrap" x-data="{ bulkOpen: false }" x-on:keydown.escape.window="bulkOpen = false">
        <button
            type="button"
            class="std-btn std-bulk-btn"
            x-bind:disabled="! selectedRecords.length"
            x-bind:aria-expanded="bulkOpen"
            x-on:click="bulkOpen = ! bulkOpen"
            aria-haspopup="menu"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            {{ __('Bulk actions') }}
            <span class="std-count" x-show="selectedRecords.length" x-text="selectedRecords.length" x-cloak></span>
        </button>

        <div
            class="std-menu"
            x-cloak
            x-show="bulkOpen"
            x-transition.opacity.duration.120ms
            x-on:click.outside="bulkOpen = false"
            role="menu"
        >
            <button type="button" class="std-menu-item is-info" role="menuitem" x-on:click="bulkOpen = false; mountBulkAction('sendActivationEmails')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                {{ __('Send activation email') }}
            </button>
            <button type="button" class="std-menu-item is-success" role="menuitem" x-on:click="bulkOpen = false; mountBulkAction('printSelected')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V3h12v6"/><rect x="4" y="9" width="16" height="8" rx="2"/><path d="M6 17h12v4H6z"/></svg>
                {{ __('Print selected ID cards') }}
            </button>
            <button type="button" class="std-menu-item is-primary" role="menuitem" x-on:click="bulkOpen = false; mountBulkAction('downloadPngs')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
                {{ __('Download as PNG') }}
            </button>
            <button type="button" class="std-menu-item is-danger" role="menuitem" x-on:click="bulkOpen = false; mountBulkAction('delete')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 15H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
                {{ __('Delete selected') }}
            </button>
        </div>
    </div>

    <label class="std-check" title="{{ __('Select all shown') }}">
        <input
            type="checkbox"
            class="fi-ta-page-checkbox"
            x-bind:checked="getRecordsOnPage().length && areRecordsSelected(getRecordsOnPage())"
            x-on:change="toggleSelectRecordsOnPage()"
            aria-label="{{ __('Select all shown') }}"
        >
        <span>{{ __('Select all shown') }}</span>
    </label>

    <div class="std-search">
        <svg class="std-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input
            type="search"
            x-ref="search"
            wire:model.live.debounce.300ms="tableSearch"
            placeholder="{{ __('Search by name, ID or class') }}"
            aria-label="{{ __('Search students') }}"
            autocomplete="off"
        >
        <kbd aria-hidden="true">/</kbd>
    </div>

    <select wire:model.live="studentSort" class="std-select" aria-label="{{ __('Sort students') }}">
        <option value="date">{{ __('Newest enrolled') }}</option>
        <option value="name">{{ __('Name A–Z') }}</option>
        <option value="age">{{ __('Age') }}</option>
    </select>

    <div
        class="std-combobox"
        x-data="{
            open: false,
            query: '',
            value: @js($this->classFilter),
            options: @js($classOptions),
            get filtered() {
                const needle = this.query.trim().toLowerCase();

                if (! needle) {
                    return Object.entries(this.options);
                }

                return Object.entries(this.options).filter(([, label]) => label.toLowerCase().includes(needle));
            },
            get selectedLabel() {
                return this.value && this.options[this.value] ? this.options[this.value] : @js(__('All classes'));
            },
            select(value) {
                this.value = value;
                this.open = false;
                this.query = '';
                $wire.set('classFilter', value);
            }
        }"
        x-on:keydown.escape.window="open = false"
    >
        <button
            type="button"
            class="std-combobox-trigger"
            x-on:click="open = ! open"
            x-bind:aria-expanded="open"
            aria-haspopup="listbox"
            aria-label="{{ __('Filter by class') }}"
        >
            <span x-text="selectedLabel" class="std-combobox-value">{{ __('All classes') }}</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
        </button>

        <div class="std-combobox-menu" x-cloak x-show="open" x-transition.opacity.duration.120ms x-on:click.outside="open = false" role="listbox">
            <input
                type="search"
                class="std-combobox-search"
                x-model="query"
                placeholder="{{ __('Search classes') }}"
                aria-label="{{ __('Search classes') }}"
                autocomplete="off"
            >
            <div class="std-combobox-list">
                <button type="button" class="std-combobox-option" x-show="! query || @js(__('All classes')).toLowerCase().includes(query.toLowerCase())" x-on:click="select('')" role="option">
                    {{ __('All classes') }}
                </button>
                <template x-for="[key, label] in filtered" :key="key">
                    <button type="button" class="std-combobox-option" x-bind:class="{ 'is-selected': value === key }" x-on:click="select(key)" role="option" x-text="label"></button>
                </template>
                <p class="std-combobox-empty" x-show="! filtered.length && query">{{ __('No classes match') }}</p>
            </div>
        </div>
    </div>

    <div class="std-segwrap">
        <span class="std-chip-label">{{ __('View') }}</span>
        <div
            class="std-seg"
            role="group"
            aria-label="{{ __('Student view') }}"
            x-data
            x-init="const saved = localStorage.getItem('std-view'); if (saved && saved !== @js($this->studentView)) { $wire.set('studentView', saved); }"
        >
            <button type="button" wire:click="$set('studentView', 'grid')" x-on:click="localStorage.setItem('std-view', 'grid')" x-bind:class="{ 'on': @js($this->studentView === 'grid') }" aria-label="{{ __('Grid view') }}" aria-pressed="{{ $this->studentView === 'grid' ? 'true' : 'false' }}" title="{{ __('Grid view') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span>{{ __('Grid') }}</span>
            </button>
            <button type="button" wire:click="$set('studentView', 'list')" x-on:click="localStorage.setItem('std-view', 'list')" x-bind:class="{ 'on': @js($this->studentView === 'list') }" aria-label="{{ __('List view') }}" aria-pressed="{{ $this->studentView === 'list' ? 'true' : 'false' }}" title="{{ __('List view') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                <span>{{ __('List') }}</span>
            </button>
        </div>
    </div>

    @if ($hasFilters)
        <button type="button" class="std-btn std-clear" wire:click="clearStudentFilters">
            {{ __('Clear filters') }}
        </button>
    @endif
</div>

<div class="std-selection" x-cloak x-show="selectedRecords.length" x-transition.opacity.duration.120ms>
    <span><b x-text="selectedRecords.length"></b> {{ __('selected') }}</span>
    @if ($totalStudents > 0)
        <button type="button" x-show="selectedRecords.length < {{ $totalStudents }}" x-on:click="selectAllRecords()" x-bind:disabled="isLoading">
            {{ __('Select all :total', ['total' => number_format($totalStudents)]) }}
        </button>
    @endif
    <button type="button" x-on:click="deselectAllRecords()">{{ __('Deselect all') }}</button>
</div>

<div class="std-chips" aria-label="{{ __('Filters') }}">
    <span class="std-chip-label">{{ __('Status') }}</span>
    <button type="button" class="std-chip {{ $this->statusFilter === '' ? 'on' : '' }}" wire:click="$set('statusFilter', '')">{{ __('All') }}</button>
    <button type="button" class="std-chip {{ $this->statusFilter === 'active' ? 'on' : '' }}" wire:click="$set('statusFilter', 'active')">{{ __('Active') }}</button>
    <button type="button" class="std-chip {{ $this->statusFilter === 'pending' ? 'on' : '' }}" wire:click="$set('statusFilter', 'pending')">{{ __('Pending') }}</button>

    <span class="std-chip-label std-chip-label-gap">{{ __('Gender') }}</span>
    <button type="button" class="std-chip {{ $this->genderFilter === '' ? 'on' : '' }}" wire:click="$set('genderFilter', '')">{{ __('All') }}</button>
    <button type="button" class="std-chip {{ $this->genderFilter === 'female' ? 'on' : '' }}" wire:click="$set('genderFilter', 'female')">{{ __('Female') }}</button>
    <button type="button" class="std-chip {{ $this->genderFilter === 'male' ? 'on' : '' }}" wire:click="$set('genderFilter', 'male')">{{ __('Male') }}</button>
</div>
