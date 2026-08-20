<x-filament-panels::page>
    <div class="sc-todo">
        {{-- ═══ HEADER ═══ --}}
        <div class="sc-todo-head">
            <div>
                <h2 class="sc-todo-title">{{ __('My Day') }}</h2>
                <p class="sc-todo-sub">{{ $this->today['label'] }} &middot; {{ $this->overdueTasks->count() }} overdue · {{ $this->myDayTasks->count() }} due today</p>
            </div>
            <div class="sc-todo-head-actions">
                <a href="{{ \App\Filament\App\Pages\Dashboard::getUrl() }}" class="sc-btn-ghost sc-todo-home" title="Back to home" aria-label="Back to home">
                    <x-filament::icon icon="heroicon-o-home" class="w-4 h-4" />
                    <span>{{ __('Home') }}</span>
                </a>
                <button type="button" class="sc-btn-primary is-task" wire:click="openTaskModal">
                    <x-filament::icon icon="heroicon-o-plus" class="w-4 h-4" /> {{ __('Add Task') }}
                </button>
            </div>
        </div>

        {{-- ═══ TABS ═══ --}}
        <div class="sc-todo-tabs">
            @foreach (\App\Filament\App\Pages\MyDay::TABS as $key => $label)
                <button
                    wire:key="tab-{{ $key }}"
                    type="button"
                    class="sc-todo-tab {{ $tab === $key ? 'is-active' : '' }}"
                    wire:click="setTab('{{ $key }}')"
                >
                    {{ $label }}
                    @if ($this->tabCounts[$key] > 0)
                        <span class="sc-todo-tab-count">{{ $this->tabCounts[$key] }}</span>
                    @endif
                </button>
            @endforeach

            <div class="sc-todo-searchwrap">
                <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-4 h-4" />
                <input
                    type="search"
                    class="sc-sched-search"
                    placeholder="Search tasks…"
                    wire:model.live.debounce.300ms="search"
                />
                @if ($search)
                    <button type="button" class="sc-sched-clear" wire:click="resetFilters" title="Clear search">{{ __('✕') }}</button>
                @endif
            </div>
        </div>

        {{-- ═══ BODY ═══ --}}
        <div class="sc-todo-grid">
            <section class="sc-todo-list">
                @if ($tab === 'my_day')
                    {{-- OVERDUE --}}
                    @if ($this->overdueTasks->isNotEmpty())
                        <div class="sc-todo-section">
                            <div class="sc-todo-section-label is-danger">
                                <span class="sc-todo-section-dot"></span>
                                {{ __('OVERDUE') }}
                            </div>
                            @foreach ($this->overdueTasks as $task)
                                <div wire:key="od-{{ $task->id }}" class="sc-task-row is-overdue">
                                    <button class="sc-check" wire:click="toggleTaskDone({{ $task->id }})" type="button">{{ __('☐') }}</button>
                                    <div class="sc-task-main">
                                        <span class="sc-task-title">{{ $task->title }}</span>
                                        <span class="sc-task-meta">Due {{ $task->due_date?->format('j M') }}{{ $task->due_time ? ' at '.$task->due_time : '' }}</span>
                                    </div>
                                    @if ($task->important)<span class="sc-star">{{ __('★') }}</span>@endif
                                    <span class="sc-priority-dot is-{{ $task->priority }}"></span>
                                    <div class="sc-task-actions">
                                        <button class="sc-iconbtn-sm" wire:click="toggleTaskImportant({{ $task->id }})" aria-label="Toggle important">{{ __('★') }}</button>
                                        <button class="sc-iconbtn-sm" wire:click="editTask({{ $task->id }})" aria-label="Edit">
                                            <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                                        </button>
                                        <button class="sc-iconbtn-sm" wire:click="deleteTask({{ $task->id }})" aria-label="Delete">
                                            <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- TODAY --}}
                    <div class="sc-todo-section">
                        <div class="sc-todo-section-label">TODAY'S TASKS</div>
                        @forelse ($this->activeTasks as $task)
                            <div wire:key="td-{{ $task->id }}" class="sc-task-row">
                                <button class="sc-check" wire:click="toggleTaskDone({{ $task->id }})" type="button">{{ __('☐') }}</button>
                                <div class="sc-task-main">
                                    <span class="sc-task-title">{{ $task->title }}</span>
                                    <span class="sc-task-meta">
                                        {{ $task->due_time ? 'Due at '.$task->due_time : 'Due today' }}
                                        @if ($task->assigned_to_id && $task->assigned_to_id !== auth()->id())
                                            · assigned to {{ $task->assignee?->name }}
                                        @endif
                                    </span>
                                </div>
                                @if ($task->important)<span class="sc-star">{{ __('★') }}</span>@endif
                                <span class="sc-priority-dot is-{{ $task->priority }}"></span>
                                <div class="sc-task-actions">
                                    <button class="sc-iconbtn-sm" wire:click="toggleTaskImportant({{ $task->id }})" aria-label="Toggle important">{{ __('★') }}</button>
                                    <button class="sc-iconbtn-sm" wire:click="editTask({{ $task->id }})" aria-label="Edit">
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                                    </button>
                                    <button class="sc-iconbtn-sm" wire:click="deleteTask({{ $task->id }})" aria-label="Delete">
                                        <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="sc-empty">
                                <div class="sc-empty-title">You're all caught up!</div>
                                <p>{{ __('No tasks due today. Add a task or enjoy the free time.') }}</p>
                            </div>
                        @endforelse
                    </div>
                @elseif ($tab === 'completed')
                    <div class="sc-todo-section">
                        <div class="sc-todo-section-label">{{ __('COMPLETED') }}</div>
                        @forelse ($this->activeTasks as $task)
                            <div wire:key="co-{{ $task->id }}" class="sc-task-row is-done">
                                <button class="sc-check checked" wire:click="toggleTaskDone({{ $task->id }})" type="button">{{ __('☑') }}</button>
                                <div class="sc-task-main">
                                    <span class="sc-task-title">{{ $task->title }}</span>
                                    <span class="sc-task-meta">Completed {{ $task->completed_at?->diffForHumans() }}</span>
                                </div>
                                <div class="sc-task-actions">
                                    <button class="sc-iconbtn-sm" wire:click="editTask({{ $task->id }})" aria-label="Edit">
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                                    </button>
                                    <button class="sc-iconbtn-sm" wire:click="deleteTask({{ $task->id }})" aria-label="Delete">
                                        <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="sc-empty">
                                <div class="sc-empty-title">{{ __('Nothing completed yet') }}</div>
                                <p>{{ __('Tasks you check off will show up here.') }}</p>
                            </div>
                        @endforelse
                    </div>
                @else
                    <div class="sc-todo-section">
                        <div class="sc-todo-section-label">
                            {{ match ($tab) {
                                'important' => 'IMPORTANT',
                                'assigned' => 'ASSIGNED TO ME',
                                'mine' => 'MY TASKS',
                                default => 'TASKS',
                            } }}
                        </div>
                        @forelse ($this->activeTasks as $task)
                            <div wire:key="{{ $tab }}-{{ $task->id }}" class="sc-task-row {{ $task->isOverdue() ? 'is-overdue' : '' }}">
                                <button class="sc-check" wire:click="toggleTaskDone({{ $task->id }})" type="button">{{ __('☐') }}</button>
                                <div class="sc-task-main">
                                    <span class="sc-task-title">{{ $task->title }}</span>
                                    <span class="sc-task-meta">
                                        @if ($task->due_date)
                                            Due {{ $task->due_date->format('j M') }}{{ $task->due_time ? ' at '.$task->due_time : '' }}
                                        @else
                                            No due date
                                        @endif
                                        @if ($tab === 'assigned' && $task->creator_id !== auth()->id())
                                            · by {{ $task->creator?->name }}
                                        @endif
                                    </span>
                                </div>
                                @if ($task->important)<span class="sc-star">{{ __('★') }}</span>@endif
                                <span class="sc-priority-dot is-{{ $task->priority }}"></span>
                                <div class="sc-task-actions">
                                    <button class="sc-iconbtn-sm" wire:click="toggleTaskImportant({{ $task->id }})" aria-label="Toggle important">{{ __('★') }}</button>
                                    <button class="sc-iconbtn-sm" wire:click="editTask({{ $task->id }})" aria-label="Edit">
                                        <x-filament::icon icon="heroicon-o-pencil-square" class="w-3.5 h-3.5" />
                                    </button>
                                    <button class="sc-iconbtn-sm" wire:click="deleteTask({{ $task->id }})" aria-label="Delete">
                                        <x-filament::icon icon="heroicon-o-trash" class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="sc-empty">
                                <div class="sc-empty-title">{{ __('Nothing here yet') }}</div>
                                <p>Tasks you {{ $tab === 'assigned' ? 'are assigned' : 'created' }} will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                @endif
            </section>

            {{-- ═══ RIGHT COLUMN ═══ --}}
            <aside class="sc-todo-aside">
                <div class="sc-todo-card">
                    <div class="sc-todo-card-title">TODAY'S SCHEDULE</div>
                    @forelse ($this->todaySchedule as $event)
                        <div wire:key="ev-{{ $event->id }}" class="sc-todo-sched-row">
                            <span class="sc-day-swatch" style="background: {{ $event->color ?: 'var(--sc-primary-600)' }}"></span>
                            <div class="sc-todo-sched-body">
                                <span class="sc-todo-sched-title">{{ $event->title }}</span>
                                <span class="sc-todo-sched-time">
                                    {{ $event->all_day ? 'All day' : $event->start_time->format('H:i').' – '.$event->end_time?->format('H:i') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="sc-empty">{{ __('No events today.') }}</div>
                    @endforelse
                </div>

                <div class="sc-todo-card">
                    <div class="sc-todo-card-title">{{ __('AT A GLANCE') }}</div>
                    <div class="sc-todo-counts">
                        <div class="sc-todo-count">
                            <span class="sc-todo-count-num">{{ $this->tabCounts['my_day'] }}</span>
                            <span>{{ __('Due today') }}</span>
                        </div>
                        <div class="sc-todo-count">
                            <span class="sc-todo-count-num">{{ $this->overdueTasks->count() }}</span>
                            <span>{{ __('Overdue') }}</span>
                        </div>
                        <div class="sc-todo-count">
                            <span class="sc-todo-count-num">{{ $this->tabCounts['important'] }}</span>
                            <span>{{ __('Important') }}</span>
                        </div>
                        <div class="sc-todo-count">
                            <span class="sc-todo-count-num">{{ $this->tabCounts['assigned'] }}</span>
                            <span>{{ __('Assigned') }}</span>
                        </div>
                    </div>
                    <a href="{{ \App\Filament\App\Pages\Schedule::getUrl() }}" class="sc-todo-link">
                        {{ __('Open full Schedule') }}
                        <x-filament::icon icon="heroicon-o-arrow-right" class="w-3.5 h-3.5" />
                    </a>
                </div>
            </aside>
        </div>

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

        {{-- ═══ DELETE CONFIRMATION ═══ --}}
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
