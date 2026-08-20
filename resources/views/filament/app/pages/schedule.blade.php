<x-filament-panels::page>
    <div
        class="sc-sched"
        x-data="{
            focus: @js($this->currentDate),
            dragging: null,
            isTyping() {
                const t = document.activeElement;
                return t && (t.tagName === 'INPUT' || t.tagName === 'TEXTAREA' || t.tagName === 'SELECT');
            },
            pad(n) { return String(n).padStart(2, '0'); },
            shift(days) {
                const [y, m, d] = this.focus.split('-').map(Number);
                const dt = new Date(y, m - 1, d);
                dt.setDate(dt.getDate() + days);
                this.focus = dt.getFullYear() + '-' + this.pad(dt.getMonth() + 1) + '-' + this.pad(dt.getDate());
            },
            keydown(e) {
                if (this.isTyping() || e.metaKey || e.ctrlKey || e.altKey) return;
                const modalOpen = @js($eventModalOpen) || @js($taskModalOpen) || @js($deleteEventId) || @js($deleteTaskId);
                if (e.key === 'Escape') {
                    if (modalOpen) { $wire.closeEventModal(); $wire.closeTaskModal(); }
                    $wire.closeDayPanel();
                    return;
                }
                if (e.key === 'Enter') { $wire.selectDate(this.focus); return; }
                if (e.key === 'ArrowLeft') { e.preventDefault(); this.shift(-1); return; }
                if (e.key === 'ArrowRight') { e.preventDefault(); this.shift(1); return; }
                if (e.key === 'ArrowUp') { e.preventDefault(); this.shift(-7); return; }
                if (e.key === 'ArrowDown') { e.preventDefault(); this.shift(7); return; }
                if (e.key === 'PageUp') { e.preventDefault(); if (e.shiftKey) { $wire.jumpYear(-1); } else { $wire.prevPeriod(); } return; }
                if (e.key === 'PageDown') { e.preventDefault(); if (e.shiftKey) { $wire.jumpYear(1); } else { $wire.nextPeriod(); } }
            },
            startDrag(e, type, id) {
                this.dragging = { type, id };
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', type + ':' + id);
            },
            endDrag() { this.dragging = null; },
            dropOn(e, date) {
                e.preventDefault();
                const raw = e.dataTransfer.getData('text/plain') || (this.dragging ? this.dragging.type + ':' + this.dragging.id : '');
                if (! raw) return;
                const [type, id] = raw.split(':');
                if (type === 'event') $wire.moveEvent(Number(id), date);
                if (type === 'task') $wire.moveTask(Number(id), date);
                this.dragging = null;
            },
        }"
        @keydown.window="keydown($event)"
    >
        {{-- ═══ PAGE HEADER ═══ --}}
        <div class="sc-sched-header">
            <div class="sc-sched-headline">
                <div class="sc-sched-icon-badge">
                    <x-filament::icon icon="heroicon-o-calendar-days" class="sc-header-icon" />
                </div>
                <div>
                    <h2 class="sc-sched-h2">{{ __('School Schedule') }}</h2>
                    <p class="sc-sched-subtitle">{{ __('Manage institutional events, class plans, and outstanding actions.') }}</p>
                </div>
            </div>
            <div class="sc-sched-views is-tabs">
                @foreach (['month' => 'Month', 'week' => 'Week', 'day' => 'Day', 'agenda' => 'Agenda'] as $key => $label)
                    <button
                        wire:key="view-{{ $key }}"
                        type="button"
                        class="sc-sched-view {{ $viewMode === $key ? 'is-active' : '' }}"
                        wire:click="setView('{{ $key }}')"
                    >{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- ═══ TOOLBAR ═══ --}}
        <div class="sc-sched-toolbar">
            <div class="sc-sched-nav">
                <a href="{{ $this->homeUrl() }}" class="sc-sched-pill sc-sched-home" title="Back to home" aria-label="Back to home">
                    <x-filament::icon icon="heroicon-o-home" class="w-3.5 h-3.5" />
                    <span>{{ __('Home') }}</span>
                </a>
                <button type="button" class="sc-sched-pill" wire:click="goToday">{{ __('Today') }}</button>
                <button type="button" class="sc-iconbtn" @click="$wire.jumpYear(-1)" aria-label="Previous year" title="Previous year (Shift+PageUp)">
                    <x-filament::icon icon="heroicon-o-chevron-double-left" class="w-4 h-4" />
                </button>
                <button type="button" class="sc-iconbtn" wire:click="prevPeriod" aria-label="Previous" title="Previous (PageUp)">
                    <x-filament::icon icon="heroicon-o-chevron-left" class="w-4 h-4" />
                </button>
                <button type="button" class="sc-iconbtn" wire:click="nextPeriod" aria-label="Next" title="Next (PageDown)">
                    <x-filament::icon icon="heroicon-o-chevron-right" class="w-4 h-4" />
                </button>
                <button type="button" class="sc-iconbtn" @click="$wire.jumpYear(1)" aria-label="Next year" title="Next year (Shift+PageDown)">
                    <x-filament::icon icon="heroicon-o-chevron-double-right" class="w-4 h-4" />
                </button>
                <span class="sc-sched-period">
                    {{ $this->viewMode === 'month' ? $this->monthGrid['label'] : ($this->viewMode === 'week' ? \Illuminate\Support\Carbon::parse($this->currentDate)->format('M j, Y') : ($this->viewMode === 'day' ? $this->dayAgenda['label'] : $this->agendaRangeLabel)) }}
                </span>

                <div class="sc-sched-stepwrap" title="What the previous/next buttons jump by">
                    @foreach (['auto' => 'Auto', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year'] as $key => $label)
                        <button
                            wire:key="step-{{ $key }}"
                            type="button"
                            class="sc-sched-step {{ $this->step === $key ? 'is-active' : '' }}"
                            wire:click="setStep('{{ $key }}')"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <input
                    type="date"
                    class="sc-date-input"
                    :value="focus"
                    @change="focus = $event.target.value; $wire.jumpToDate($event.target.value)"
                    title="Jump to a date"
                    aria-label="Jump to a date"
                />
            </div>

            <div class="sc-sched-adds">
                <button type="button" class="sc-btn-primary" wire:click="openEventModal">
                    <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" /> {{ __('Event') }}
                </button>
                <button type="button" class="sc-btn-primary is-task" wire:click="openTaskModal">
                    <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" /> {{ __('Task') }}
                </button>
            </div>
        </div>

        {{-- ═══ AGENDA RANGE PRESETS ═══ --}}
        @if ($viewMode === 'agenda')
            <div class="sc-sched-rangepresets">
                @foreach (['today' => 'Today', 'this_week' => 'This week', 'next7' => 'Next 7 days', 'next30' => 'Next 30 days', 'custom' => 'Custom range'] as $key => $label)
                    <button
                        wire:key="range-{{ $key }}"
                        type="button"
                        class="sc-range-chip {{ $rangePreset === $key ? 'is-active' : '' }}"
                        wire:click="setRangePreset('{{ $key }}')"
                    >{{ $label }}</button>
                @endforeach
                @if ($rangePreset === 'custom')
                    <span class="sc-range-sep">{{ __('Pick start & end on the mini calendar.') }}</span>
                    <button type="button" class="sc-range-chip" wire:click="clearRange">{{ __('Clear range') }}</button>
                @endif
            </div>
        @endif

        <div class="sc-sched-body">
            {{-- ═══ SIDEBAR ═══ --}}
            <aside class="sc-picker">
                <div class="sc-aside-section">
                    <span class="sc-aside-label">{{ __('Search') }}</span>
                    <div class="sc-sched-searchwrap">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                        <input
                            type="search"
                            class="sc-sched-search"
                            placeholder="Search events & tasks…"
                            wire:model.live.debounce.300ms="search"
                        />
                    </div>
                </div>

                <div class="sc-aside-section">
                    <span class="sc-aside-label">{{ __('Filters') }}</span>
                    <div class="sc-sched-filters">
                        <button type="button" class="sc-filter-chip {{ $showEvents ? 'is-on' : '' }}" wire:click="toggleShowEvents">{{ __('Events') }}</button>
                        <button type="button" class="sc-filter-chip {{ $showTasks ? 'is-on' : '' }}" wire:click="toggleShowTasks">{{ __('Tasks') }}</button>
                        <button type="button" class="sc-filter-chip {{ $importantOnly ? 'is-on' : '' }}" wire:click="toggleFilter('important')">{{ __('★ Important') }}</button>
                        <button type="button" class="sc-filter-chip {{ $assignedOnly ? 'is-on' : '' }}" wire:click="toggleFilter('assigned')">{{ __('Assigned to me') }}</button>
                        <button type="button" class="sc-filter-chip {{ $mineOnly ? 'is-on' : '' }}" wire:click="toggleFilter('mine')">{{ __('My tasks') }}</button>
                        <button type="button" class="sc-filter-chip {{ $showCompleted ? 'is-on' : '' }}" wire:click="toggleFilter('completed')">{{ __('Completed') }}</button>
                        <button type="button" class="sc-sched-clear" wire:click="resetFilters" title="Clear filters">{{ __('✕') }}</button>
                    </div>
                </div>

                {{-- ═══ DAY AGENDA PANEL ═══ --}}
                <div class="sc-day-agenda">
                    <div class="sc-day-agenda-head">
                        <div class="sc-day-agenda-headinfo">
                            <span class="sc-day-agenda-label">{{ __('Selected Day') }}</span>
                            <span class="sc-day-agenda-date">{{ $this->dayAgenda['label'] }}</span>
                        </div>
                        <div class="sc-day-agenda-adds">
                            <button type="button" class="sc-day-agenda-add" wire:click="openEventModal('{{ $this->dayAgenda['date'] }}')" title="Add event for this day">
                                <x-filament::icon icon="heroicon-o-plus" class="sc-day-agenda-add-icon" /> {{ __('Event') }}
                            </button>
                            <button type="button" class="sc-day-agenda-add is-task" wire:click="openTaskModal('{{ $this->dayAgenda['date'] }}')" title="Add task for this day">
                                <x-filament::icon icon="heroicon-o-plus" class="sc-day-agenda-add-icon" /> {{ __('Task') }}
                            </button>
                        </div>
                    </div>
                    <div class="sc-day-agenda-list">
                        @php
                            $dayAgendaItems = collect($this->dayAgenda['allDayEvents'])
                                ->concat($this->dayAgenda['timedEvents'])
                                ->concat($this->dayAgenda['tasksTimed'])
                                ->concat($this->dayAgenda['tasksNoTime'])
                                ->sortBy(fn ($i) => ($i['all_day'] ?? false ? '0' : '1') . ($i['start_time'] ?? $i['due_time'] ?? '99:99'))
                                ->values()
                                ->take(10);
                        @endphp
                        @forelse ($dayAgendaItems as $di)
                            <button
                                wire:key="dayag-{{ array_key_exists('done', $di) ? 't' : 'e' }}-{{ $di['id'] }}"
                                type="button"
                                class="sc-day-agenda-row {{ array_key_exists('done', $di) && $di['done'] ? 'is-done' : '' }}"
                                wire:click="{{ array_key_exists('done', $di) ? "editTask({$di['id']})" : "editEvent({$di['id']})" }}"
                                title="{{ $di['title'] }}"
                            >
                                <span class="sc-day-agenda-time">{{ ($di['all_day'] ?? false) ? 'All day' : ($di['start_time'] ?? $di['due_time'] ?? '') }}</span>
                                <span class="sc-day-agenda-title">{{ \Illuminate\Support\Str::limit($di['title'], 22) }}</span>
                                <span class="sc-day-agenda-badge {{ array_key_exists('done', $di) ? 'is-task' : 'is-event' }}">{{ array_key_exists('done', $di) ? 'Task' : 'Event' }}</span>
                            </button>
                        @empty
                            <div class="sc-day-agenda-empty">
                                <x-filament::icon icon="heroicon-o-calendar-days" class="sc-day-agenda-empty-icon" />
                                <span>{{ __('Nothing scheduled for this day.') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="sc-picker-todayline">
                    <span class="sc-picker-todaydate">{{ \Illuminate\Support\Carbon::parse($this->currentDate)->format('D, j M Y') }}</span>
                    <span class="sc-picker-todaytag">{{ \Illuminate\Support\Carbon::parse($this->currentDate)->isToday() ? 'Today' : '' }}</span>
                </div>
                @foreach ($this->pickerMonths as $pm)
                    <div wire:key="picker-{{ $pm['key'] }}" class="sc-picker-month">
                        <div class="sc-picker-head">
                            <span class="sc-picker-label">{{ $pm['label'] }}</span>
                            <div class="sc-picker-headnav">
                                @if ($loop->first)
                                    <button type="button" class="sc-picker-nav" wire:click="previousPickerYear" aria-label="Previous year" title="Previous year">
                                        <x-filament::icon icon="heroicon-o-chevron-double-left" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" class="sc-picker-nav" wire:click="previousPickerMonth" aria-label="Previous month" title="Previous month">
                                        <x-filament::icon icon="heroicon-o-chevron-left" class="w-3.5 h-3.5" />
                                    </button>
                                @else
                                    <button type="button" class="sc-picker-nav" wire:click="nextPickerMonth" aria-label="Next month" title="Next month">
                                        <x-filament::icon icon="heroicon-o-chevron-right" class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" class="sc-picker-nav" wire:click="nextPickerYear" aria-label="Next year" title="Next year">
                                        <x-filament::icon icon="heroicon-o-chevron-double-right" class="w-3.5 h-3.5" />
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="sc-picker-grid">
                            @foreach ($pm['weekdays'] as $wd)
                                <span class="sc-picker-wd">{{ $wd }}</span>
                            @endforeach
                            @foreach ($pm['cells'] as $cell)
                                <button
                                    wire:key="picker-{{ $pm['key'] }}-{{ $cell['date'] }}"
                                    type="button"
                                    class="sc-picker-day
                                        {{ ! $cell['isCurrentMonth'] ? 'is-muted' : '' }}
                                        {{ $cell['isToday'] ? 'is-today' : '' }}
                                        {{ $cell['isSelected'] ? 'is-selected' : '' }}
                                        {{ $viewMode === 'agenda' && $rangePreset === 'custom' && $rangeStart && $cell['date'] === $rangeStart ? 'is-range-start' : '' }}
                                        {{ $viewMode === 'agenda' && $rangePreset === 'custom' && $rangeEnd && $cell['date'] === $rangeEnd ? 'is-range-end' : '' }}
                                        {{ $viewMode === 'agenda' && $rangePreset === 'custom' && $rangeStart && $rangeEnd && $cell['date'] >= $rangeStart && $cell['date'] <= $rangeEnd ? 'is-in-range' : '' }}"
                                    :class="{ 'is-focus': focus === '{{ $cell['date'] }}' }"
                                    wire:click="{{ $viewMode === 'agenda' && $rangePreset === 'custom' ? "selectRangeDate('{$cell['date']}')" : "jumpToDate('{$cell['date']}')" }}"
                                >{{ $cell['day'] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </aside>

            {{-- ═══ MAIN VIEW ═══ --}}
            <section class="sc-sched-main">
                @if ($viewMode === 'month')
                    {{-- ─── MONTH GRID ─── --}}
                    <div class="sc-month">
                        <div class="sc-month-weekdays">
                            @foreach ($this->monthGrid['weekdays'] as $wd)
                                <span class="sc-month-wd">{{ $wd }}</span>
                            @endforeach
                        </div>
                        <div class="sc-month-grid">
                            @foreach ($this->monthGrid['cells'] as $cell)
                                <div
                                    wire:key="m-{{ $cell['date'] }}"
                                    class="sc-month-cell
                                        {{ ! $cell['isCurrentMonth'] ? 'is-muted' : '' }}
                                        {{ $cell['isToday'] ? 'is-today' : '' }}
                                        {{ $cell['isSelected'] ? 'is-selected' : '' }}"
                                    :class="{ 'is-focus': focus === '{{ $cell['date'] }}' }"
                                    @dragover.prevent
                                    @drop="dropOn($event, '{{ $cell['date'] }}')"
                                    wire:click="selectDate('{{ $cell['date'] }}')"
                                >
                                    <span class="sc-month-daynum">{{ $cell['day'] }}</span>
                                    <div class="sc-month-items">
                                        @foreach ($cell['events'] as $ev)
                                            <div
                                                wire:key="me-{{ $ev['id'] }}-{{ $cell['date'] }}"
                                                class="sc-month-event"
                                                :class="{ 'is-dragging': dragging && dragging.type === 'event' && dragging.id === {{ $ev['id'] }} }"
                                                style="{{ $ev['color'] ? '--ev-color:' . $ev['color'] : '' }}"
                                                draggable="true"
                                                @dragstart="startDrag($event, 'event', {{ $ev['id'] }})"
                                                @dragend="endDrag"
                                                wire:click.stop="editEvent({{ $ev['id'] }})"
                                                title="{{ $ev['title'] }}"
                                            >
                                                @if (! $ev['all_day'])
                                                    <span class="sc-month-evt-time">{{ $ev['start_time'] }}</span>
                                                @else
                                                    <span class="sc-month-evt-allday">{{ __('•') }}</span>
                                                @endif
                                                <span class="sc-month-evt-title">{{ \Illuminate\Support\Str::limit($ev['title'], 24) }}</span>
                                            </div>
                                        @endforeach
                                        @foreach ($cell['tasks'] as $tk)
                                            <div
                                                wire:key="mt-{{ $tk['id'] }}-{{ $cell['date'] }}"
                                                class="sc-month-task {{ $tk['done'] ? 'is-done' : '' }} {{ $tk['important'] ? 'is-important' : '' }}"
                                                :class="{ 'is-dragging': dragging && dragging.type === 'task' && dragging.id === {{ $tk['id'] }} }"
                                                draggable="true"
                                                @dragstart="startDrag($event, 'task', {{ $tk['id'] }})"
                                                @dragend="endDrag"
                                                wire:click.stop="editTask({{ $tk['id'] }})"
                                                title="{{ $tk['title'] }}"
                                            >
                                                <span class="sc-month-task-box">{{ __('☐') }}</span>
                                                @if ($tk['due_time'])
                                                    <span class="sc-month-task-time">{{ $tk['due_time'] }}</span>
                                                @endif
                                                <span class="sc-month-task-title">{{ \Illuminate\Support\Str::limit($tk['title'], 20) }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                @elseif ($viewMode === 'week')
                    {{-- ─── WEEK VIEW ─── --}}
                    <div class="sc-week">
                        <div class="sc-week-head">
                            @foreach ($this->weekDays as $day)
                                <div
                                    wire:key="wh-{{ $day['date'] }}"
                                    class="sc-week-headcol {{ $day['isToday'] ? 'is-today' : '' }}"
                                    :class="{ 'is-focus': focus === '{{ $day['date'] }}' }"
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                >
                                    <span class="sc-week-wd">{{ $day['label'] }}</span>
                                    <span class="sc-week-num">{{ $day['dayNum'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="sc-week-grid">
                            @foreach ($this->weekDays as $day)
                                <div
                                    wire:key="wc-{{ $day['date'] }}"
                                    class="sc-week-col {{ $day['isToday'] ? 'is-today' : '' }}"
                                    @dragover.prevent
                                    @drop="dropOn($event, '{{ $day['date'] }}')"
                                    wire:click="selectDate('{{ $day['date'] }}')"
                                >
                                    @foreach (collect($day['events'])->where('all_day', true) as $ev)
                                        <div
                                            wire:key="we-{{ $ev['id'] }}-{{ $day['date'] }}"
                                            class="sc-week-allday"
                                            draggable="true"
                                            @dragstart="startDrag($event, 'event', {{ $ev['id'] }})"
                                            @dragend="endDrag"
                                            wire:click.stop="editEvent({{ $ev['id'] }})"
                                        >
                                            <span class="sc-week-allday-label">{{ __('All day') }}</span>
                                            <span class="sc-week-allday-title">{{ \Illuminate\Support\Str::limit($ev['title'], 18) }}</span>
                                        </div>
                                    @endforeach
                                    @foreach (collect($day['events'])->where('all_day', false)->sortBy('start_time') as $ev)
                                        <div
                                            wire:key="we-{{ $ev['id'] }}-{{ $day['date'] }}-t"
                                            class="sc-week-event"
                                            style="{{ $ev['color'] ? '--ev-color:' . $ev['color'] : '' }}"
                                            draggable="true"
                                            @dragstart="startDrag($event, 'event', {{ $ev['id'] }})"
                                            @dragend="endDrag"
                                            wire:click.stop="editEvent({{ $ev['id'] }})"
                                        >
                                            <span class="sc-week-evt-time">{{ $ev['start_time'] }}</span>
                                            <span class="sc-week-evt-title">{{ \Illuminate\Support\Str::limit($ev['title'], 20) }}</span>
                                        </div>
                                    @endforeach
                                    @foreach ($day['tasks'] as $tk)
                                        <div
                                            wire:key="wt-{{ $tk['id'] }}-{{ $day['date'] }}"
                                            class="sc-week-task {{ $tk['done'] ? 'is-done' : '' }} {{ $tk['important'] ? 'is-important' : '' }}"
                                            draggable="true"
                                            @dragstart="startDrag($event, 'task', {{ $tk['id'] }})"
                                            @dragend="endDrag"
                                            wire:click.stop="editTask({{ $tk['id'] }})"
                                        >
                                            <span class="sc-week-task-box">{{ __('☐') }}</span>
                                            @if ($tk['due_time'])
                                                <span class="sc-week-task-time">{{ $tk['due_time'] }}</span>
                                            @endif
                                            <span class="sc-week-task-title">{{ \Illuminate\Support\Str::limit($tk['title'], 18) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                @elseif ($viewMode === 'day')
                    {{-- ─── DAY VIEW ─── --}}
                    <div class="sc-day">
                        <div class="sc-day-head">
                            <div>
                                <h2 class="sc-day-title">{{ $this->dayAgenda['label'] }}</h2>
                                <p class="sc-day-sub">{{ $this->dayAgenda['isToday'] ? 'Today' : \Illuminate\Support\Carbon::parse($this->dayAgenda['date'])->diffForHumans() }}</p>
                            </div>
                            <div class="sc-day-actions">
                                <button type="button" class="sc-btn-primary" wire:click="openEventModal('{{ $this->dayAgenda['date'] }}')">
                                    <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" /> {{ __('Add Event') }}
                                </button>
                                <button type="button" class="sc-btn-primary is-task" wire:click="openTaskModal('{{ $this->dayAgenda['date'] }}')">
                                    <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" /> {{ __('Add Task') }}
                                </button>
                            </div>
                        </div>

                        @php
                            $timeline = collect($this->dayAgenda['allDayEvents'])
                                ->concat($this->dayAgenda['timedEvents'])
                                ->concat($this->dayAgenda['tasksTimed'])
                                ->sortBy(fn ($e) => ($e['all_day'] ?? false) ? '00:00' : ($e['start_time'] ?? $e['due_time'] ?? '99:99'))
                                ->values();
                        @endphp

                        <div class="sc-day-timeline">
                            @forelse ($timeline as $entry)
                                @php $isTask = $entry['type'] === 'task'; @endphp
                                <div
                                    wire:key="d-{{ $isTask ? 't' : 'e' }}-{{ $entry['id'] }}"
                                    class="sc-day-entry {{ $isTask ? 'is-task' : '' }}"
                                    wire:click="{{ $isTask ? "editTask({$entry['id']})" : "editEvent({$entry['id']})" }}"
                                >
                                    <span class="sc-day-time">
                                        @if ($entry['all_day'] ?? false)
                                            All day
                                        @elseif ($isTask)
                                            {{ $entry['due_time'] ?? 'Task' }}
                                        @else
                                            {{ $entry['start_time'] }}@if ($entry['end_time']) – {{ $entry['end_time'] }}@endif
                                        @endif
                                    </span>
                                    @if ($isTask)
                                        <button class="sc-check {{ $entry['done'] ? 'checked' : '' }}" wire:click.stop="toggleTaskDone({{ $entry['id'] }})" type="button">
                                            {{ $entry['done'] ? '☑' : '☐' }}
                                        </button>
                                        <span class="sc-day-entry-title {{ $entry['done'] ? 'is-done' : '' }}">{{ $entry['title'] }}</span>
                                        @if ($entry['important'])<span class="sc-star">{{ __('★') }}</span>@endif
                                    @else
                                        <span class="sc-day-swatch" style="background: {{ $entry['color'] ?: 'var(--sc-primary-600)' }}"></span>
                                        <span class="sc-day-entry-title">{{ $entry['title'] }}</span>
                                        @if ($entry['location'])<span class="sc-day-entry-loc">{{ $entry['location'] }}</span>@endif
                                    @endif
                                </div>
                            @empty
                                <div class="sc-empty">{{ __('Nothing scheduled for this day.') }}</div>
                            @endforelse
                        </div>

                        @if ($this->dayAgenda['tasksNoTime'])
                            <div class="sc-day-section">
                                <div class="sc-day-section-label">{{ __('TASKS') }}</div>
                                @foreach ($this->dayAgenda['tasksNoTime'] as $entry)
                                    <div
                                        wire:key="d-nt-{{ $entry['id'] }}"
                                        class="sc-day-entry is-task"
                                        wire:click="editTask({{ $entry['id'] }})"
                                    >
                                        <span class="sc-day-time">{{ __('Anytime') }}</span>
                                        <button class="sc-check {{ $entry['done'] ? 'checked' : '' }}" wire:click.stop="toggleTaskDone({{ $entry['id'] }})" type="button">
                                            {{ $entry['done'] ? '☑' : '☐' }}
                                        </button>
                                        <span class="sc-day-entry-title {{ $entry['done'] ? 'is-done' : '' }}">{{ $entry['title'] }}</span>
                                        @if ($entry['important'])<span class="sc-star">{{ __('★') }}</span>@endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                @else
                    {{-- ─── AGENDA ─── --}}
                    <div class="sc-agenda">
                        <span class="sc-agenda-range">{{ $this->agendaRangeLabel }}</span>
                        @forelse ($this->agendaItems as $day)
                            <div wire:key="a-{{ $day['date'] }}" class="sc-agenda-day {{ $day['isToday'] ? 'is-today' : '' }}">
                                <button type="button" class="sc-agenda-dayhead" wire:click="jumpToDate('{{ $day['date'] }}')">
                                    <span class="sc-agenda-label">{{ $day['label'] }}</span>
                                    <span class="sc-agenda-date">{{ $day['isToday'] ? 'Today' : \Illuminate\Support\Carbon::parse($day['date'])->format('j M') }}</span>
                                </button>
                                <div class="sc-agenda-entries">
                                    @foreach ($day['entries'] as $entry)
                                        @php $isTask = $entry['type'] === 'task'; @endphp
                                        <div
                                            wire:key="ae-{{ $isTask ? 't' : 'e' }}-{{ $entry['id'] }}"
                                            class="sc-agenda-entry {{ $isTask ? 'is-task' : '' }}"
                                            wire:click="{{ $isTask ? "editTask({$entry['id']})" : "editEvent({$entry['id']})" }}"
                                        >
                                            @if ($isTask)
                                                <button class="sc-check {{ $entry['done'] ? 'checked' : '' }}" wire:click.stop="toggleTaskDone({{ $entry['id'] }})" type="button">
                                                    {{ $entry['done'] ? '☑' : '☐' }}
                                                </button>
                                                <span class="sc-agenda-time">{{ $entry['due_time'] ?? '—' }}</span>
                                                <span class="sc-agenda-title {{ $entry['done'] ? 'is-done' : '' }}">{{ $entry['title'] }}</span>
                                                @if ($entry['important'])<span class="sc-star">{{ __('★') }}</span>@endif
                                            @else
                                                <span class="sc-day-swatch" style="background: {{ $entry['color'] ?: 'var(--sc-primary-600)' }}"></span>
                                                <span class="sc-agenda-time">
                                                    {{ $entry['all_day'] ? 'All day' : $entry['start_time'] }}
                                                </span>
                                                <span class="sc-agenda-title">{{ $entry['title'] }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="sc-empty">{{ __('No events or tasks in this range.') }}</div>
                        @endforelse
                    </div>
                @endif

                {{-- ═══ SELECTED DAY PANEL ═══ --}}
                @if ($dayPanelOpen)
                    <div class="sc-daypanel">
                        <div class="sc-daypanel-head">
                            <div>
                                <div class="sc-daypanel-title">{{ $this->dayAgenda['label'] }}</div>
                                <div class="sc-daypanel-sub">{{ $this->dayAgenda['isToday'] ? 'Today' : '' }}</div>
                            </div>
                            <div class="sc-daypanel-actions">
                                <button type="button" class="sc-btn-primary" wire:click="openEventModal('{{ $this->dayAgenda['date'] }}')">{{ __('+ Event') }}</button>
                                <button type="button" class="sc-btn-primary is-task" wire:click="openTaskModal('{{ $this->dayAgenda['date'] }}')">{{ __('+ Task') }}</button>
                                <button type="button" class="sc-btn-ghost" wire:click="setView('day')">{{ __('View full day') }}</button>
                                <button type="button" class="sc-iconbtn" wire:click="closeDayPanel" aria-label="Close day panel">
                                    <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        @php
                            $panelRows = collect($this->dayAgenda['allDayEvents'])
                                ->concat($this->dayAgenda['timedEvents'])
                                ->concat($this->dayAgenda['tasksTimed'])
                                ->concat($this->dayAgenda['tasksNoTime'])
                                ->sortBy(fn ($e) => ($e['all_day'] ?? false) ? '00:00' : ($e['start_time'] ?? $e['due_time'] ?? '99:99'))
                                ->values();
                        @endphp

                        <div class="sc-daypanel-body">
                            @forelse ($panelRows as $entry)
                                @php $isTask = $entry['type'] === 'task'; @endphp
                                <div
                                    wire:key="dp-{{ $isTask ? 't' : 'e' }}-{{ $entry['id'] }}"
                                    class="sc-daypanel-row {{ $isTask ? 'is-task' : '' }}"
                                    wire:click="{{ $isTask ? "editTask({$entry['id']})" : "editEvent({$entry['id']})" }}"
                                >
                                    <span class="sc-daypanel-time">
                                        @if ($entry['all_day'] ?? false)
                                            All day
                                        @elseif ($isTask)
                                            {{ $entry['due_time'] ?? 'Task' }}
                                        @else
                                            {{ $entry['start_time'] }}
                                        @endif
                                    </span>
                                    @if ($isTask)
                                        <button class="sc-check {{ $entry['done'] ? 'checked' : '' }}" wire:click.stop="toggleTaskDone({{ $entry['id'] }})" type="button">
                                            {{ $entry['done'] ? '☑' : '☐' }}
                                        </button>
                                    @else
                                        <span class="sc-day-swatch" style="background: {{ $entry['color'] ?: 'var(--sc-primary-600)' }}"></span>
                                    @endif
                                    <span class="sc-daypanel-title {{ $isTask && $entry['done'] ? 'is-done' : '' }}">{{ $entry['title'] }}</span>
                                    @if ($isTask && $entry['important'])<span class="sc-star">{{ __('★') }}</span>@endif
                                </div>
                            @empty
                                <div class="sc-empty">{{ __('Nothing scheduled for this day.') }}</div>
                            @endforelse
                        </div>
                    </div>
                @endif
            </section>
        </div>

        {{-- ═══ EVENT MODAL ═══ --}}
        @if ($eventModalOpen)
            <div class="sc-modal" @click.self="$wire.closeEventModal()">
                <div class="sc-modal-card">
                    <div class="sc-modal-head">
                        <h3 class="sc-modal-title">{{ $editingEvent ? 'Edit Event' : 'Create Event' }}</h3>
                        <button type="button" class="sc-iconbtn" wire:click="closeEventModal" aria-label="Close">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>

                    @if ($conflicts)
                        <div class="sc-conflict">
                            <div class="sc-conflict-title">{{ __('Scheduling conflict') }}</div>
                            <p class="sc-conflict-msg">{{ __('These events overlap with the time you picked:') }}</p>
                            @foreach ($conflicts as $c)
                                <div wire:key="cf-{{ $c['id'] }}" class="sc-conflict-row">
                                    <span class="sc-conflict-name">{{ $c['title'] }}</span>
                                    <span class="sc-conflict-time">{{ $c['start'] }} – {{ $c['end'] }}</span>
                                </div>
                            @endforeach
                            <div class="sc-conflict-actions">
                                <button type="button" class="sc-btn-danger" wire:click="ignoreConflictsAndSave">{{ __('Save anyway') }}</button>
                                <button type="button" class="sc-btn-ghost" wire:click="dismissConflicts">{{ __('Keep editing') }}</button>
                            </div>
                        </div>
                    @endif

                    <form wire:submit="saveEvent" class="sc-form">
                        <label class="sc-field">
                            <span class="sc-field-label">{{ __('Title *') }}</span>
                            <input type="text" class="sc-input" wire:model="eventForm.title" placeholder="e.g. Staff meeting" maxlength="255" />
                            @error('eventForm.title')<span class="sc-error">{{ $message }}</span>@enderror
                        </label>

                        <div class="sc-grid-2">
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Category') }}</span>
                                <select class="sc-input" wire:model="eventForm.category">
                                    @foreach (\Modules\Communication\Models\EventCalendar::CATEGORIES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Color') }}</span>
                                <input type="color" class="sc-input sc-color" wire:model="eventForm.color" />
                            </label>
                        </div>

                        <label class="sc-field sc-field-check">
                            <input type="checkbox" class="sc-checkbox" wire:model="eventForm.all_day" />
                            <span>{{ __('All-day event') }}</span>
                        </label>

                        @if (! ($eventForm['all_day'] ?? false))
                            <div class="sc-grid-2">
                                <label class="sc-field">
                                    <span class="sc-field-label">{{ __('Start date') }}</span>
                                    <input type="date" class="sc-input" wire:model="eventForm.start_date" />
                                    @error('eventForm.start_date')<span class="sc-error">{{ $message }}</span>@enderror
                                </label>
                                <label class="sc-field">
                                    <span class="sc-field-label">{{ __('Start time') }}</span>
                                    <input type="time" class="sc-input" wire:model="eventForm.start_time" />
                                </label>
                            </div>
                            <div class="sc-grid-2">
                                <label class="sc-field">
                                    <span class="sc-field-label">{{ __('End date') }}</span>
                                    <input type="date" class="sc-input" wire:model="eventForm.end_date" />
                                </label>
                                <label class="sc-field">
                                    <span class="sc-field-label">{{ __('End time') }}</span>
                                    <input type="time" class="sc-input" wire:model="eventForm.end_time" />
                                </label>
                            </div>
                        @else
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Start date') }}</span>
                                <input type="date" class="sc-input" wire:model="eventForm.start_date" />
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('End date') }}</span>
                                <input type="date" class="sc-input" wire:model="eventForm.end_date" />
                            </label>
                        @endif

                        <label class="sc-field">
                            <span class="sc-field-label">{{ __('Location') }}</span>
                            <input type="text" class="sc-input" wire:model="eventForm.location" placeholder="e.g. Hall A" />
                        </label>
                        <label class="sc-field">
                            <span class="sc-field-label">{{ __('Description') }}</span>
                            <textarea class="sc-input sc-textarea" wire:model="eventForm.description" rows="2"></textarea>
                        </label>

                        <div class="sc-grid-3">
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Reminder') }}</span>
                                <select class="sc-input" wire:model="eventForm.reminder_minutes">
                                    <option value="">{{ __('None') }}</option>
                                    @foreach ([5, 10, 15, 30, 60, 120, 1440] as $min)
                                        <option value="{{ $min }}">
                                            @if ($min < 60) {{ $min }} min
                                            @elseif ($min === 60) 1 hour
                                            @elseif ($min === 120) 2 hours
                                            @else 1 day
                                            @endif before
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Repeats') }}</span>
                                <select class="sc-input" wire:model="eventForm.recurrence">
                                    @foreach (\Modules\Communication\Models\EventCalendar::RECURRENCES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        <div class="sc-form-actions">
                            <button type="button" class="sc-btn-ghost" wire:click="closeEventModal">{{ __('Cancel') }}</button>
                            @if ($editingEvent)
                                <button type="button" class="sc-btn-danger" wire:click="deleteEvent({{ $eventId }})">{{ __('Delete') }}</button>
                            @endif
                            <button type="submit" class="sc-btn-primary">
                                <x-filament::icon icon="heroicon-o-check" class="w-4 h-4" />
                                {{ $editingEvent ? 'Save changes' : 'Create Event' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ═══ TASK MODAL ═══ --}}
        @if ($taskModalOpen)
            <div class="sc-modal" @click.self="$wire.closeTaskModal()">
                <div class="sc-modal-card">
                    <div class="sc-modal-head">
                        <h3 class="sc-modal-title">{{ $editingTask ? 'Edit Task' : 'Create Task' }}</h3>
                        <button type="button" class="sc-iconbtn" wire:click="closeTaskModal" aria-label="Close">
                            <x-filament::icon icon="heroicon-o-x-mark" class="w-4 h-4" />
                        </button>
                    </div>
                    <form wire:submit="saveTask" class="sc-form">
                        <label class="sc-field">
                            <span class="sc-field-label">{{ __('Task *') }}</span>
                            <input type="text" class="sc-input" wire:model="taskForm.title" placeholder="e.g. Submit attendance" maxlength="255" />
                            @error('taskForm.title')<span class="sc-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="sc-field">
                            <span class="sc-field-label">{{ __('Notes') }}</span>
                            <textarea class="sc-input sc-textarea" wire:model="taskForm.description" rows="2"></textarea>
                        </label>

                        <div class="sc-grid-2">
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Due date') }}</span>
                                <input type="date" class="sc-input" wire:model="taskForm.due_date" />
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Due time') }}</span>
                                <input type="time" class="sc-input" wire:model="taskForm.due_time" />
                            </label>
                        </div>

                        <div class="sc-grid-2">
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Priority') }}</span>
                                <select class="sc-input" wire:model="taskForm.priority">
                                    @foreach (\App\Models\UserTask::PRIORITIES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Remind me at') }}</span>
                                <input type="datetime-local" class="sc-input" wire:model="taskForm.reminder_at" />
                            </label>
                        </div>

                        <div class="sc-grid-2">
                            <label class="sc-field sc-field-check">
                                <input type="checkbox" class="sc-checkbox" wire:model="taskForm.important" />
                                <span>{{ __('Important') }}</span>
                            </label>
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Repeats') }}</span>
                                <select class="sc-input" wire:model="taskForm.recurrence">
                                    @foreach (\App\Models\UserTask::RECURRENCES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>

                        @if ($this->canAssign)
                            <label class="sc-field">
                                <span class="sc-field-label">{{ __('Assign to') }}</span>
                                <x-task-assignee-picker
                                    name="taskForm.assignee_spec"
                                    :value="$taskForm['assignee_spec'] ?? json_encode(['mode' => 'self'])"
                                    :roles="$this->assigneeRoles"
                                    :role-members="$this->assigneeRoleMembers"
                                    :staff="$this->assigneeStaff"
                                    :students="$this->assigneeStudents"
                                    :levels="$this->assigneeLevels"
                                    :sections="$this->assigneeSections"
                                />
                            </label>
                        @endif

                        <div class="sc-form-actions">
                            <button type="button" class="sc-btn-ghost" wire:click="closeTaskModal">{{ __('Cancel') }}</button>
                            @if ($editingTask)
                                <button type="button" class="sc-btn-danger" wire:click="deleteTask({{ $taskId }})">{{ __('Delete') }}</button>
                            @endif
                            <button type="submit" class="sc-btn-primary is-task">
                                <x-filament::icon icon="heroicon-o-check" class="w-4 h-4" />
                                {{ $editingTask ? 'Save changes' : 'Create Task' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- ═══ DELETE CONFIRMATIONS ═══ --}}
        @if ($deleteEventId)
            <div class="sc-modal sc-modal-sm-wrap" @click.self="$wire.cancelDeleteEvent()">
                <div class="sc-modal-card">
                    <h3 class="sc-modal-title">{{ __('Delete this event?') }}</h3>
                    <p class="sc-modal-msg">{{ __('This will remove the event for everyone who can see it.') }}</p>
                    <div class="sc-form-actions">
                        <button type="button" class="sc-btn-ghost" wire:click="cancelDeleteEvent">{{ __('Cancel') }}</button>
                        <button type="button" class="sc-btn-danger" wire:click="confirmDeleteEvent">{{ __('Delete') }}</button>
                    </div>
                </div>
            </div>
        @endif
        @if ($deleteTaskId)
            <div class="sc-modal sc-modal-sm-wrap" @click.self="$wire.cancelDeleteTask()">
                <div class="sc-modal-card">
                    <h3 class="sc-modal-title">{{ __('Delete this task?') }}</h3>
                    <p class="sc-modal-msg">{{ __('This will permanently remove the task.') }}</p>
                    <div class="sc-form-actions">
                        <button type="button" class="sc-btn-ghost" wire:click="cancelDeleteTask">{{ __('Cancel') }}</button>
                        <button type="button" class="sc-btn-danger" wire:click="confirmDeleteTask">{{ __('Delete') }}</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
