@php
    $acadIcons = [
        'calendar' => 'calendar',
        'chart' => 'chart-bar',
        'doc' => 'document-text',
        'grid' => 'squares-2x2',
        'cap' => 'academic-cap',
        'home' => 'home-modern',
        'check' => 'check-circle',
    ];

    $pageIds = $this->pagedRows->pluck('id')->map(fn ($id) => (int) $id)->all();
    $selectedIds = array_map('intval', $selected);
    $allSelected = count($pageIds) > 0 && count(array_intersect($pageIds, $selectedIds)) === count($pageIds);
    $firstRow = $this->total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
    $lastRow = min($currentPage * $perPage, $this->total);
    $pageStart = max(1, min($this->totalPages - 4, $currentPage - 2));
    $pageEnd = min($this->totalPages, $pageStart + 4);
    $passedChecks = $readinessData['details']['passed_checks'] ?? 0;
    $totalChecks = $readinessData['details']['total_checks'] ?? 0;
    $blockedSteps = $progressData['blocked_steps'] ?? 0;
@endphp

<x-filament::page>
    <div class="acad"
         x-data="{
            cmd: false,
            cq: '',
            confetti() {
                if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
                const colours = ['#4f46e5', '#0f9d68', '#f59e0b', '#ef4444', '#06b6d4'];
                for (let i = 0; i < 60; i++) {
                    const piece = document.createElement('i');
                    const angle = Math.random() * Math.PI * 2;
                    const velocity = 150 + Math.random() * 260;
                    piece.style.cssText = 'position:fixed;left:50%;top:40%;width:8px;height:12px;z-index:90;pointer-events:none;border-radius:2px;background:' + colours[i % 5];
                    document.body.appendChild(piece);
                    piece.animate([
                        { transform: 'translate(0, 0) rotate(0)', opacity: 1 },
                        { transform: 'translate(' + Math.cos(angle) * velocity + 'px,' + (Math.sin(angle) * velocity + 320) + 'px) rotate(' + Math.random() * 720 + 'deg)', opacity: 0 }
                    ], { duration: 1400 + Math.random() * 600, easing: 'cubic-bezier(.2,.7,.3,1)' }).onfinish = () => piece.remove();
                }
            },
            toasts: [],
            toast(payload) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, message: payload.message });
                setTimeout(() => { this.toasts = this.toasts.filter((toast) => toast.id !== id); }, 4500);
            }
         }"
         @keydown.window.escape="cmd = false"
         @keydown.window.cmd.k.prevent="cmd = true; $nextTick(() => $refs.cq.focus())"
         @keydown.window.ctrl.k.prevent="cmd = true; $nextTick(() => $refs.cq.focus())"
         @acad-confetti.window="confetti()"
         @acad-toast.window="toast($event.detail)">

        {{-- Header --}}
        <div class="acad-header">
            <div class="acad-header-actions" style="width:100%; justify-content:flex-end;">
                {{-- Academic year switcher --}}
                <div class="acad-year" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="btn" @click="open = !open">
                        <span class="pill ok"><span class="dot"></span></span>
                        <span>{{ $activeYearName ? __('Year :name', ['name' => $activeYearName]) : __('No active year') }}</span>
                        <x-heroicon-o-chevron-down class="h-3.5 w-3.5" />
                    </button>

                    <div class="year-menu" x-show="open" x-cloak x-transition>
                        @forelse ($this->yearOptions as $year)
                            <button type="button"
                                    class="year-option {{ $year->is_active ? 'is-active' : '' }}"
                                    wire:click="activateYear({{ $year->id }})"
                                    @click="open = false">
                                <span>{{ $year->name }}</span>
                                @if ($year->is_active)
                                    <span class="pill ok">{{ __('Current') }}</span>
                                @endif
                            </button>
                        @empty
                            <p class="year-empty">{{ __('No academic years yet.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div class="acad-searchbox">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                    <input type="search" class="fld" placeholder="{{ __('Search commands…') }}"
                           @focus="cmd = true; $nextTick(() => $refs.cq.focus())"
                           @click="cmd = true; $nextTick(() => $refs.cq.focus())"
                           readonly>
                </div>

                <button type="button" class="btn" wire:click="mountAction('pageHelp')">
                    <x-heroicon-o-question-mark-circle class="h-4 w-4" />
                    {{ __('Help') }}
                </button>

                @if ($tab !== 'overview')
                    <button type="button" class="btn primary" wire:click="openCreate">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        {{ __('New :noun', ['noun' => $this->noun]) }}
                    </button>
                @endif
            </div>
        </div>

        {{-- Tabs --}}
        <nav class="acad-tabs" aria-label="{{ __('Academics sections') }}">
            @foreach ($this->tabs as $key => $tabItem)
                <button type="button"
                        class="acad-tab {{ $tab === $key ? 'is-active' : '' }}"
                        wire:click="setTab('{{ $key }}')">
                    <span>{{ $tabItem['label'] }}</span>
                    @if ($tabItem['count'] !== null)
                        <span class="pill">{{ $tabItem['count'] }}</span>
                    @endif
                </button>
            @endforeach
        </nav>

        @if ($tab === 'overview')
            <div class="acad-stack">
                {{-- Readiness hero --}}
                <section class="card acad-readiness">
                    <div class="acad-ring"
                         x-data="{ score: 0, target: {{ $readinessScore }} }"
                         x-init="setTimeout(() => {
                            const started = performance.now();
                            const tick = (now) => {
                                const progress = Math.min(1, (now - started) / 900);
                                score = Math.round(target * (1 - Math.pow(1 - progress, 3)));
                                if (progress < 1) requestAnimationFrame(tick);
                            };
                            requestAnimationFrame(tick);
                         }, 250)">
                        <svg viewBox="0 0 130 130" aria-hidden="true">
                            <circle class="track" cx="65" cy="65" r="56" fill="none" stroke-width="10" />
                            <circle class="value" cx="65" cy="65" r="56" fill="none" stroke-width="10"
                                    stroke-dasharray="351.9"
                                    :stroke-dashoffset="351.9 * (1 - score / 100)" />
                        </svg>
                        <div class="acad-ring-caption">
                            <b><span x-text="score">0</span>%</b>
                            <small>{{ __('ready') }}</small>
                        </div>
                    </div>

                    <div class="acad-readiness-copy">
                        @if ($unassignedCount > 0)
                            <h2>{{ __('One thing left before you’re fully set up') }}</h2>
                            <p>{{ trans_choice(':count level has no teacher assigned, so its timetable can’t be built.|:count levels have no teacher assigned, so their timetables can’t be built.', $unassignedCount, ['count' => $unassignedCount]) }}</p>
                            <div class="acad-checks">
                                <button type="button" class="btn primary" wire:click="openAssign">
                                    <x-heroicon-o-user class="h-4 w-4" />
                                    {{ __('Assign teachers') }}
                                </button>
                                <button type="button" class="btn" wire:click="setTab('levels')">
                                    {{ __('Review levels') }}
                                </button>
                                <span class="pill warn">{{ $passedChecks }} / {{ $totalChecks }} {{ __('checks passed') }}</span>
                                <span class="pill ok">{{ $blockedSteps }} {{ __('blocked steps') }}</span>
                            </div>
                        @else
                            <h2>{{ __('Your academic setup is complete') }}</h2>
                            <p>{{ __('Every level has a teacher and every check has passed.') }}</p>
                            <div class="acad-checks">
                                <span class="pill ok">{{ $passedChecks }} / {{ $totalChecks }} {{ __('checks passed') }}</span>
                                <span class="pill ok">{{ $blockedSteps }} {{ __('blocked steps') }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Stats --}}
                <section class="acad-stats">
                    @foreach ($stats as $index => $stat)
                        <button type="button"
                                class="stat rise {{ $stat['featured'] ? 'featured' : '' }}"
                                style="animation-delay: {{ $index * 70 }}ms; --h: {{ $stat['color'] }};"
                                @if ($stat['tab']) wire:click="setTab('{{ $stat['tab'] }}')" @endif>
                            <div class="stat-top">
                                <span class="chip">
                                    <x-dynamic-component :component="'heroicon-o-'.($acadIcons[$stat['icon']] ?? 'chart-bar')" class="h-5 w-5" />
                                </span>
                                @if ($stat['tab'])
                                    <x-heroicon-o-arrow-right class="go h-4 w-4" />
                                @endif
                            </div>
                            <b>{{ $stat['value'] }}</b>
                            <span class="lbl">{{ $stat['label'] }}</span>
                            <span class="detail">{{ $stat['detail'] }}</span>
                        </button>
                    @endforeach
                </section>

                <div class="acad-columns">
                    {{-- Setup journey --}}
                    <section class="card acad-panel">
                        <div class="acad-panel-head">
                            <h3>{{ __('Setup journey') }}</h3>
                            <span>{{ $progressData['completed_steps'] ?? 0 }} {{ __('of') }} {{ $progressData['total_steps'] ?? 0 }} {{ __('steps done') }}</span>
                        </div>

                        <div class="acad-journey-bar">
                            <i style="width: {{ ($progressData['total_steps'] ?? 0) > 0 ? round(($progressData['completed_steps'] ?? 0) / $progressData['total_steps'] * 100) : 0 }}%"></i>
                        </div>

                        <div class="acad-phases" x-data="{ open: -1 }">
                            @foreach ($phases as $phaseIndex => $phase)
                                <div class="phase">
                                    <button type="button" class="phase-head"
                                            @click="open = open === {{ $phaseIndex }} ? -1 : {{ $phaseIndex }}">
                                        <span class="phase-icon {{ $phase['done'] === $phase['total'] ? 'ok' : 'warn' }}">
                                            @if ($phase['done'] === $phase['total'])
                                                <x-heroicon-o-check class="h-4 w-4" />
                                            @else
                                                <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                                            @endif
                                        </span>
                                        <span class="phase-copy">
                                            <b>{{ $phase['label'] }}</b>
                                            <small>{{ $phase['done'] }} {{ __('of') }} {{ $phase['total'] }} {{ __('complete') }}</small>
                                            <i class="phase-progress" style="width: {{ $phase['percent'] }}%"></i>
                                        </span>
                                        <span class="phase-chevron" :class="open === {{ $phaseIndex }} && 'is-open'">
                                            <x-heroicon-o-chevron-down class="h-4 w-4" />
                                        </span>
                                    </button>

                                    <div class="phase-steps" x-show="open === {{ $phaseIndex }}" x-cloak x-transition.opacity>
                                        @foreach ($phase['steps'] as $step)
                                            <div class="phase-step">
                                                <span class="mark {{ $step['complete'] ? 'ok' : 'warn' }}">
                                                    @if ($step['complete'])
                                                        <x-heroicon-o-check class="h-3.5 w-3.5" />
                                                    @else
                                                        <x-heroicon-o-exclamation-triangle class="h-3.5 w-3.5" />
                                                    @endif
                                                </span>
                                                <div>
                                                    <b>{{ $step['title'] }}</b>
                                                    <small>{{ $step['description'] }}</small>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <div class="acad-side">
                        {{-- Academic year timeline --}}
                        <section class="card acad-panel">
                            <div class="acad-panel-head">
                                <h3>{{ $activeYearName ? __('Academic year :name', ['name' => $activeYearName]) : __('Academic year') }}</h3>
                            </div>

                            @if ($activeYearStart && $activeYearEnd)
                                <p class="acad-sub">{{ $activeYearRange }} · {{ $activeTerms }} {{ __('terms') }}</p>

                                <div x-data="{
                                        hover: null,
                                        start: '{{ $activeYearStart }}',
                                        end: '{{ $activeYearEnd }}',
                                        tooltipDate() {
                                            if (this.hover === null) return '';
                                            const start = new Date(this.start).getTime();
                                            const end = new Date(this.end).getTime();
                                            return new Date(start + this.hover * (end - start)).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                                        }
                                     }">
                                    <div class="acad-year-track"
                                         @mousemove="hover = Math.max(0, Math.min(1, ($event.clientX - $el.getBoundingClientRect().left) / $el.offsetWidth))"
                                         @mouseleave="hover = null">
                                        <i class="acad-year-fill" style="width: {{ $activeYearProgress }}%"></i>
                                        <span class="acad-year-today" style="left: {{ $activeYearProgress }}%">{{ __('Today') }}</span>
                                        <template x-if="hover !== null">
                                            <span class="acad-year-tooltip" :style="'left:' + (hover * 100) + '%'" x-text="tooltipDate()"></span>
                                        </template>
                                    </div>
                                </div>

                                @if ($nextTermName)
                                    <div class="acad-year-footer">
                                        <div>
                                            <b>{{ $nextTermName }} {{ __('ends') }}</b>
                                            <small>{{ $nextTermEnd }}</small>
                                        </div>
                                        <span class="pill {{ ($nextTermDays ?? 0) <= 14 ? 'warn' : 'ok' }}">
                                            {{ max(0, $nextTermDays ?? 0) }} {{ __('days left') }}
                                        </span>
                                    </div>
                                @endif
                            @else
                                <p class="acad-sub">{{ __('Create and activate an academic year to start the setup journey.') }}</p>
                                <button type="button" class="btn primary" wire:click="openCreate('years')">
                                    <x-heroicon-o-plus class="h-4 w-4" />
                                    {{ __('Create academic year') }}
                                </button>
                            @endif
                        </section>

                        {{-- Quick actions --}}
                        <section class="card acad-panel">
                            <div class="acad-panel-head">
                                <h3>{{ __('Quick actions') }}</h3>
                            </div>
                            <div class="acad-quick">
                                <button type="button" class="btn ghost" wire:click="openCreate('levels')">
                                    <x-heroicon-o-plus class="h-4 w-4" /> {{ __('Add a level') }}
                                </button>
                                <button type="button" class="btn ghost" wire:click="openCreate('subjects')">
                                    <x-heroicon-o-plus class="h-4 w-4" /> {{ __('Add a subject') }}
                                </button>
                                <button type="button" class="btn ghost" wire:click="openCreate('classrooms')">
                                    <x-heroicon-o-plus class="h-4 w-4" /> {{ __('Add a classroom') }}
                                </button>
                                <button type="button" class="btn ghost" wire:click="openAssign">
                                    <x-heroicon-o-user class="h-4 w-4" /> {{ __('Assign teachers') }}
                                </button>
                            </div>
                        </section>

                        {{-- Needs attention --}}
                        @if (! empty($suggestions) || ! empty($validationIssues))
                            <section class="card acad-panel">
                                <div class="acad-panel-head">
                                    <h3>{{ __('Needs attention') }}</h3>
                                    <span>{{ count($validationIssues) }} {{ __('issues') }}</span>
                                </div>
                                <div class="acad-attention">
                                    @foreach (array_slice($suggestions, 0, 4) as $suggestion)
                                        <div class="acad-attention-item">
                                            <span class="mark {{ $suggestion['priority'] }}">
                                                @if ($suggestion['priority'] === 'critical')
                                                    <x-heroicon-o-x-circle class="h-4 w-4" />
                                                @elseif ($suggestion['priority'] === 'high')
                                                    <x-heroicon-o-exclamation-triangle class="h-4 w-4" />
                                                @else
                                                    <x-heroicon-o-light-bulb class="h-4 w-4" />
                                                @endif
                                            </span>
                                            <div>
                                                <b>{{ __($suggestion['title']) }}</b>
                                                <small>{{ __($suggestion['description']) }}</small>
                                            </div>
                                            @if ($suggestion['action'] !== '#')
                                                <a href="{{ route($suggestion['action']) }}" class="pill pri">{{ __('Open') }}</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </div>
            </div>
        @else
            {{-- Data table --}}
            <section class="card acad-table-card">
                <div class="acad-toolbar">
                    <label class="acad-search">
                        <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                        <input type="search"
                               wire:model.live.debounce.300ms="search"
                               placeholder="{{ __('Search :noun…', ['noun' => $this->noun]) }}"
                               aria-label="{{ __('Search') }}">
                    </label>

                    <div class="acad-filters">
                        @foreach ($this->filters as $filterOption)
                            <button type="button"
                                    class="filter-chip {{ $filter === $filterOption['key'] ? 'is-active' : '' }}"
                                    wire:click="setFilter('{{ $filterOption['key'] }}')">
                                {{ $filterOption['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="acad-toolbar-spacer"></div>

                    <button type="button" class="btn" wire:click="exportCsv">
                        <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                        {{ __('Export') }}
                    </button>
                </div>

                <div class="acad-table-wrap">
                    <table class="data">
                        <thead>
                            <tr>
                                <th style="width: 2.5rem;">
                                    <input type="checkbox" class="row-check" @checked($allSelected)
                                           wire:click="toggleSelectAll" aria-label="{{ __('Select all') }}">
                                </th>
                                @foreach ($this->columns as $column)
                                    <th class="{{ $column['sortable'] ? 'sortable' : '' }} {{ $sortField === $column['key'] ? 'is-sorted' : '' }}"
                                        @if ($column['sortable']) wire:click="sortBy('{{ $column['key'] }}')" @endif>
                                        {{ $column['label'] }}
                                        @if ($sortField === $column['key'])
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        @endif
                                    </th>
                                @endforeach
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->pagedRows as $row)
                                @php
                                    $isSelected = in_array((int) $row->id, $selectedIds, true);
                                    $avatarClass = ['pri', 'ok', 'warn'][$loop->index % 3];
                                    $initials = mb_strtoupper(mb_substr((string) $row->name, 0, 2));
                                @endphp

                                <tr wire:key="{{ $tab }}-{{ $row->id }}"
                                    class="rise {{ $isSelected ? 'is-selected' : '' }}"
                                    style="animation-delay: {{ $loop->index * 30 }}ms;"
                                    wire:click="openEdit({{ $row->id }})">

                                    <td>
                                        <input type="checkbox" class="row-check" @checked($isSelected)
                                               wire:click.stop="toggleSelect({{ $row->id }})"
                                               aria-label="{{ __('Select :name', ['name' => $row->name]) }}">
                                    </td>

                                    @if ($tab === 'levels')
                                        <td>
                                            <div class="cell-name">
                                                <span class="avatar {{ $avatarClass }}">{{ $initials }}</span>
                                                <div>
                                                    <b>{{ $row->name }}</b>
                                                    <small>{{ $row->sections_count }} {{ trans_choice('stream|streams', $row->sections_count) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="pill pri">{{ $row->code ?: '—' }}</span></td>
                                        <td>
                                            <select class="sel {{ $row->teacher_id ? '' : 'is-empty' }}"
                                                    wire:change="assignTeacher({{ $row->id }}, $event.target.value)"
                                                    @click.stop
                                                    aria-label="{{ __('Teacher for :name', ['name' => $row->name]) }}">
                                                <option value="">{{ __('Unassigned') }}</option>
                                                @foreach ($teacherOptions as $teacherId => $teacherName)
                                                    <option value="{{ $teacherId }}" @selected((int) $row->teacher_id === (int) $teacherId)>{{ $teacherName }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ $row->sections_count }}</td>
                                        <td>
                                            <button type="button"
                                                    class="pill {{ $row->workflow_status === 'active' ? 'ok' : 'warn' }}"
                                                    wire:click.stop="toggleWorkflowStatus({{ $row->id }})"
                                                    title="{{ __('Change stage') }}">
                                                <span class="dot"></span>{{ $row->workflow_status ?: 'pending' }}
                                            </button>
                                        </td>
                                    @elseif ($tab === 'subjects')
                                        <td>
                                            <div class="cell-name">
                                                <span class="avatar {{ $avatarClass }}">{{ $initials }}</span>
                                                <div>
                                                    <b>{{ $row->name }}</b>
                                                    <small>{{ $row->is_elective ? __('Elective subject') : __('Core subject') }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="pill pri">{{ $row->code ?: '—' }}</span></td>
                                        <td><span class="pill">{{ ucfirst($row->type ?: 'theory') }}</span></td>
                                        <td>{{ number_format((float) $row->credit_weight, 2) }}</td>
                                        <td>
                                            <button type="button" class="tog {{ $row->is_elective ? 'on' : '' }}"
                                                    wire:click.stop="toggleSubjectElective({{ $row->id }})"
                                                    aria-label="{{ __('Toggle elective') }}"></button>
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="pill {{ $row->workflow_status === 'active' ? 'ok' : 'warn' }}"
                                                    wire:click.stop="toggleWorkflowStatus({{ $row->id }})"
                                                    title="{{ __('Change stage') }}">
                                                <span class="dot"></span>{{ $row->workflow_status ?: 'pending' }}
                                            </button>
                                        </td>
                                    @elseif ($tab === 'classrooms')
                                        <td>
                                            <div class="cell-name">
                                                <span class="avatar {{ $avatarClass }}">{{ $initials }}</span>
                                                <div>
                                                    <b>{{ $row->name }}</b>
                                                    <small>{{ $row->location ?: __('Main block') }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $row->location ?: '—' }}</td>
                                        <td>
                                            <div class="usage">
                                                <b>{{ $row->capacity }}</b>
                                                <span class="meter"><i style="width: {{ min(100, ((int) $row->capacity / 60) * 100) }}%"></i></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="pill {{ $row->sections_count > 0 ? 'ok' : '' }}">
                                                {{ $row->sections_count }} {{ trans_choice('slot|slots', $row->sections_count) }}
                                            </span>
                                        </td>
                                    @elseif ($tab === 'years')
                                        <td>
                                            <div class="cell-name">
                                                <span class="avatar {{ $avatarClass }}">{{ $initials }}</span>
                                                <div>
                                                    <b>{{ $row->name }}</b>
                                                    <small>{{ $row->terms_count }} {{ trans_choice('term|terms', $row->terms_count) }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $row->start_date?->format('M j, Y') ?: '—' }}</td>
                                        <td>{{ $row->end_date?->format('M j, Y') ?: '—' }}</td>
                                        <td>
                                            @if ($row->is_active)
                                                <span class="pill ok"><span class="dot"></span>{{ __('Current') }}</span>
                                            @else
                                                <button type="button" class="pill pri" wire:click.stop="activateYear({{ $row->id }})">
                                                    {{ __('Set current') }}
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="pill {{ $row->workflow_status === 'active' ? 'ok' : 'warn' }}"
                                                    wire:click.stop="toggleWorkflowStatus({{ $row->id }})"
                                                    title="{{ __('Change stage') }}">
                                                <span class="dot"></span>{{ $row->workflow_status ?: 'draft' }}
                                            </button>
                                        </td>
                                        <td>{{ $row->terms_count }}</td>
                                    @endif

                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="btn ghost icon" title="{{ __('Edit') }}"
                                                    wire:click.stop="openEdit({{ $row->id }})">
                                                <x-heroicon-o-pencil-square class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="btn ghost icon" title="{{ __('Delete') }}"
                                                    wire:click.stop="askDelete({{ $row->id }})">
                                                <x-heroicon-o-trash class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($this->columns) + 2 }}">
                                        <div class="empty">
                                            <div class="empty-icon">
                                                <x-heroicon-o-magnifying-glass class="h-6 w-6" />
                                            </div>
                                            <b>{{ __('No :noun match', ['noun' => $this->noun]) }}</b>
                                            {{ __('Clear the search or add a new :noun.', ['noun' => $this->noun]) }}
                                            <div style="margin-top: 1rem;">
                                                <button type="button" class="btn primary" wire:click="openCreate">
                                                    <x-heroicon-o-plus class="h-4 w-4" />
                                                    {{ __('Add :noun', ['noun' => $this->noun]) }}
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination">
                    <span>{{ __('Showing :first–:last of :total', ['first' => $firstRow, 'last' => $lastRow, 'total' => $this->total]) }}</span>

                    <label class="per-page">
                        {{ __('Per page') }}
                        <select class="sel" wire:model.live="perPage">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>

                    <div class="page-buttons">
                        <button type="button" class="page-btn" wire:click="previousPage"
                                @disabled($currentPage <= 1)>‹</button>
                        @for ($page = $pageStart; $page <= $pageEnd; $page++)
                            <button type="button"
                                    class="page-btn {{ $currentPage === $page ? 'is-active' : '' }}"
                                    wire:click="goToPage({{ $page }})">{{ $page }}</button>
                        @endfor
                        <button type="button" class="page-btn" wire:click="nextPage"
                                @disabled($currentPage >= $this->totalPages)>›</button>
                    </div>
                </div>
            </section>

            @if (! empty($selected))
                <div class="bulk-bar">
                    <b>{{ count($selected) }} {{ __('selected') }}</b>
                    <button type="button" class="btn ghost" wire:click="clearSelection">{{ __('Clear') }}</button>
                    <button type="button" class="btn danger" wire:click="askDeleteSelected">
                        <x-heroicon-o-trash class="h-4 w-4" />
                        {{ __('Delete selected') }}
                    </button>
                </div>
            @endif
        @endif

        {{-- Create / edit drawer --}}
        @if ($drawer === 'form')
            <div class="drawer-wrap">
                <div class="backdrop" wire:click="closeDrawer"></div>

                <form wire:submit="save" class="drawer">
                    <div class="drawer-head">
                        <h3>{{ $editingId ? __('Edit :noun', ['noun' => $this->noun]) : __('New :noun', ['noun' => $this->noun]) }}</h3>
                        <button type="button" class="btn ghost icon" wire:click="closeDrawer" aria-label="{{ __('Close') }}">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="drawer-body">
                        <label class="field">
                            <span>{{ __('Name') }}</span>
                            <input type="text" class="fld" wire:model="form.name" autofocus>
                            @error('form.name') <p class="form-error">{{ $message }}</p> @enderror
                        </label>

                        @if ($tab === 'levels')
                            <label class="field">
                                <span>{{ __('Code') }}</span>
                                <input type="text" class="fld" wire:model="form.code">
                                @error('form.code') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Teacher') }}</span>
                                <select class="fld" wire:model="form.teacher_id">
                                    <option value="">{{ __('Choose…') }}</option>
                                    @foreach ($teacherOptions as $teacherId => $teacherName)
                                        <option value="{{ $teacherId }}">{{ $teacherName }}</option>
                                    @endforeach
                                </select>
                                @error('form.teacher_id') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Class streams') }}</span>
                                <input type="number" class="fld" min="0" max="20" wire:model="form.streams">
                                <p class="hint">{{ __('Missing streams are created automatically; existing streams are kept.') }}</p>
                                @error('form.streams') <p class="form-error">{{ $message }}</p> @enderror
                            </label>
                        @elseif ($tab === 'subjects')
                            <label class="field">
                                <span>{{ __('Code') }}</span>
                                <input type="text" class="fld" wire:model="form.code">
                                @error('form.code') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Type') }}</span>
                                <select class="fld" wire:model="form.type">
                                    <option value="theory">{{ __('Theory') }}</option>
                                    <option value="practical">{{ __('Practical') }}</option>
                                </select>
                                @error('form.type') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Credits') }}</span>
                                <input type="number" step="0.01" min="0" max="99" class="fld" wire:model="form.credit_weight">
                                @error('form.credit_weight') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Elective') }}</span>
                                <button type="button" class="tog {{ ! empty($form['is_elective']) ? 'on' : '' }}"
                                        wire:click="$toggle('form.is_elective')"
                                        aria-label="{{ __('Toggle elective') }}"></button>
                            </label>
                        @elseif ($tab === 'classrooms')
                            <label class="field">
                                <span>{{ __('Building') }}</span>
                                <input type="text" class="fld" wire:model="form.location">
                                @error('form.location') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('Capacity') }}</span>
                                <input type="number" min="1" class="fld" wire:model="form.capacity">
                                @error('form.capacity') <p class="form-error">{{ $message }}</p> @enderror
                            </label>
                        @elseif ($tab === 'years')
                            <label class="field">
                                <span>{{ __('Start date') }}</span>
                                <input type="date" class="fld" wire:model="form.start_date">
                                @error('form.start_date') <p class="form-error">{{ $message }}</p> @enderror
                            </label>

                            <label class="field">
                                <span>{{ __('End date') }}</span>
                                <input type="date" class="fld" wire:model="form.end_date">
                                @error('form.end_date') <p class="form-error">{{ $message }}</p> @enderror
                            </label>
                        @endif
                    </div>

                    <div class="drawer-foot">
                        <button type="button" class="btn" wire:click="closeDrawer">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn primary">{{ $editingId ? __('Save changes') : __('Create :noun', ['noun' => $this->noun]) }}</button>
                    </div>
                </form>
            </div>
        @elseif ($drawer === 'assign')
            <div class="drawer-wrap">
                <div class="backdrop" wire:click="closeDrawer"></div>

                <div class="drawer">
                    <div class="drawer-head">
                        <h3>{{ __('Assign teachers') }}</h3>
                        <button type="button" class="btn ghost icon" wire:click="closeDrawer" aria-label="{{ __('Close') }}">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="drawer-body">
                        <p class="acad-sub">{{ __('Pick a teacher for each level. Changes save as you go.') }}</p>

                        @forelse ($this->unassignedCourses as $course)
                            <div class="assign-row" wire:key="assign-{{ $course->id }}">
                                <b>{{ $course->name }}</b>
                                <select class="sel is-empty"
                                        wire:change="assignTeacher({{ $course->id }}, $event.target.value)"
                                        aria-label="{{ __('Teacher for :name', ['name' => $course->name]) }}">
                                    <option value="">{{ __('Choose…') }}</option>
                                    @foreach ($teacherOptions as $teacherId => $teacherName)
                                        <option value="{{ $teacherId }}">{{ $teacherName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @empty
                            <div class="empty">
                                <div class="empty-icon">
                                    <x-heroicon-o-check class="h-6 w-6" />
                                </div>
                                <b>{{ __('Every level has a teacher.') }}</b>
                                {{ __('Your timetables are ready to be built.') }}
                            </div>
                        @endforelse
                    </div>

                    <div class="drawer-foot">
                        <button type="button" class="btn primary" wire:click="closeDrawer">{{ __('Done') }}</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Delete confirmation --}}
        @if ($confirmingDelete)
            <div class="modal-wrap">
                <div class="backdrop" wire:click="cancelDelete"></div>
                <div class="modal pop">
                    <h3>{{ __('Delete :noun?', ['noun' => $this->noun]) }}</h3>
                    <p>{{ trans_choice('This record will be removed permanently.|:count records will be removed permanently.', count($confirmingDelete['ids'] ?? []), ['count' => count($confirmingDelete['ids'] ?? [])]) }}</p>
                    <div class="modal-actions">
                        <button type="button" class="btn" wire:click="cancelDelete">{{ __('Cancel') }}</button>
                        <button type="button" class="btn danger" wire:click="deleteConfirmed">{{ __('Delete') }}</button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Command palette --}}
        <div class="command-wrap" x-show="cmd" x-cloak @keydown.escape.window="cmd = false">
            <div class="backdrop" @click="cmd = false"></div>
            <div class="command">
                <input x-ref="cq" x-model="cq" type="text" placeholder="{{ __('Type a command or jump to a page…') }}">
                <div class="command-list">
                    <button type="button" class="command-item" @click="cmd = false" wire:click="setTab('overview')">
                        <x-heroicon-o-squares-2x2 class="h-4 w-4" /> {{ __('Go to Overview') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="setTab('levels')">
                        <x-heroicon-o-academic-cap class="h-4 w-4" /> {{ __('Go to Levels') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="setTab('subjects')">
                        <x-heroicon-o-book-open class="h-4 w-4" /> {{ __('Go to Subjects') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="setTab('classrooms')">
                        <x-heroicon-o-home-modern class="h-4 w-4" /> {{ __('Go to Classrooms') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="setTab('years')">
                        <x-heroicon-o-calendar class="h-4 w-4" /> {{ __('Go to Academic years') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="openCreate('levels')">
                        <x-heroicon-o-plus class="h-4 w-4" /> {{ __('New level') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="openCreate('subjects')">
                        <x-heroicon-o-plus class="h-4 w-4" /> {{ __('New subject') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="openCreate('classrooms')">
                        <x-heroicon-o-plus class="h-4 w-4" /> {{ __('New classroom') }}
                    </button>
                    <button type="button" class="command-item" @click="cmd = false" wire:click="openAssign">
                        <x-heroicon-o-user class="h-4 w-4" /> {{ __('Assign teachers') }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Toasts --}}
        <div class="toast-stack">
            <template x-for="toast in toasts" :key="toast.id">
                <div class="toast pop" x-text="toast.message"></div>
            </template>
        </div>
    </div>
</x-filament::page>
