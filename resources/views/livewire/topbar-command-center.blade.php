<div
    class="sc-cc-root"
    x-data="{
        open: @entangle('isOpen'),
        now: new Date(),
        hover: null,
        selectedDate: @entangle('selectedDate'),
        preset: @entangle('preset'),
        rangeStart: @entangle('rangeStart'),
        rangeEnd: @entangle('rangeEnd'),
        dnd: @entangle('dnd'),
        addTab: @entangle('addTab'),
        baseRange: @js($this->activeRange),
        init() {
            setInterval(() => { this.now = new Date(); }, 1000);
        },
        pad(n) { return String(n).padStart(2, '0'); },
        get dLabel() {
            return this.now.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        },
        get tLabel() {
            return this.pad(this.now.getHours()) + ':' + this.pad(this.now.getMinutes());
        },
        pretty(iso) {
            if (! iso) return '';
            const [y, m, d] = iso.split('-');
            const date = new Date(y, m - 1, d);
            return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        },
        inRange(d) {
            if (this.rangeStart) {
                const e = this.rangeEnd || this.hover;
                if (! e) return d === this.rangeStart;
                return d >= Math.min(this.rangeStart, e) && d <= Math.max(this.rangeStart, e);
            }
            if (this.baseRange && this.baseRange.start && this.baseRange.end) {
                return d >= this.baseRange.start && d <= this.baseRange.end;
            }
            return false;
        },
        isStart(d) {
            return (this.rangeStart && d === this.rangeStart) || (! this.rangeStart && this.baseRange && d === this.baseRange.start);
        },
        isEnd(d) {
            if (this.rangeEnd && d === this.rangeEnd) return true;
            if (this.rangeStart && ! this.rangeEnd && this.hover && d === this.hover) return true;
            if (! this.rangeStart && this.baseRange && d === this.baseRange.end) return true;
            return false;
        },
        clickDay(d, custom) {
            if (custom) {
                $wire.selectRangeDate(d);
            } else {
                $wire.openAddTask(d);
            }
        },
        openTab(tab) {
            if (tab === 'event') {
                $wire.openAddEvent(this.selectedDate);
            } else {
                $wire.openAddTask(this.selectedDate);
            }
        },
    }"
>
    {{-- ── Centered Date & Time trigger ───────────────────────────────── --}}
    <button
        type="button"
        class="sc-cc-trigger"
        x-on:click="open = !open"
        :title="'Command Center'"
    >
        <svg class="sc-cc-trigger-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2ZM4.5 6.75a1.25 1.25 0 0 1 1.25-1.25h8.5a1.25 1.25 0 0 1 1.25 1.25v8.5a1.25 1.25 0 0 1-1.25 1.25h-8.5a1.25 1.25 0 0 1-1.25-1.25v-8.5Z" clip-rule="evenodd"/>
        </svg>
        <span class="sc-cc-trigger-date" x-text="dLabel">{{ $currentMonth->format('M j') }}</span>
        <span class="sc-cc-trigger-time" x-text="tLabel">{{ $currentMonth->format('H:i') }}</span>

        <span
            x-show="! dnd && {{ $this->badgeCount }} > 0"
            x-cloak
            class="sc-cc-trigger-badge"
            x-text="{{ $this->badgeCount }}"
        ></span>
        <span x-show="dnd" x-cloak class="sc-cc-dnd-dot" title="Do Not Disturb is on"></span>
    </button>

    {{-- ── Split-pane dropdown panel (teleported to body so it floats on top
           of everything, like a smartphone / Ubuntu notifications & calendar
           banner, with a dimmed backdrop) ─────────────────────────────── --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition:enter="sc-cc-overlay-enter"
            x-transition:enter-start="sc-cc-overlay-enter-start"
            x-transition:enter-end="sc-cc-overlay-enter-end"
            x-transition:leave="sc-cc-overlay-leave"
            x-transition:leave-start="sc-cc-overlay-leave-start"
            x-transition:leave-end="sc-cc-overlay-leave-end"
            class="sc-cc-overlay"
        >
            <div class="sc-cc-backdrop" x-on:click="open = false; $wire.close()"></div>
            <div
                x-transition:enter="sc-cc-enter"
                x-transition:enter-start="sc-cc-enter-start"
                x-transition:enter-end="sc-cc-enter-end"
                x-transition:leave="sc-cc-leave"
                x-transition:leave-start="sc-cc-leave-start"
                x-transition:leave-end="sc-cc-leave-end"
                class="sc-cc-panel"
                @click.outside="open = false; $wire.close()"
            >
        <div class="sc-cc-pane sc-cc-pane-tasks">
            {{-- Task Manager header --}}
            <div class="sc-cc-pane-head">
                <div>
                    <h3 class="sc-cc-pane-title">{{ __('Task Manager') }}</h3>
                    <p class="sc-cc-pane-sub" x-show="! dnd">{{ $this->openTaskCount }} open task(s)</p>
                    <p class="sc-cc-pane-sub" x-cloak x-show="dnd">{{ __('Do Not Disturb is on') }}</p>
                </div>
                <span class="sc-cc-chip sc-cc-chip-muted">{{ str($this->user()?->name ?? '')->explode(' ')->first() ?: 'Me' }}</span>
            </div>

            {{-- Quick range presets --}}
            <div class="sc-cc-presets">
                @foreach (\App\Livewire\TopbarCommandCenter::PRESETS() as $key => $label)
                    <button
                        type="button"
                        class="sc-cc-preset"
                        :class="{ 'sc-cc-preset-active': preset === '{{ $key }}' }"
                        x-on:click="$wire.choosePreset('{{ $key }}')"
                    >{{ $label }}</button>
                @endforeach
            </div>

            {{-- Bulk actions --}}
            <div class="sc-cc-actions">
                <label class="sc-cc-dnd" title="Do Not Disturb">
                    <span>
                        <svg class="sc-cc-dnd-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M4.25 2A2.25 2.25 0 0 0 2 4.25v2.882c0 .597.237 1.17.659 1.591l.98.98v.87a2.25 2.25 0 0 1-.659 1.591l-.98.98A2.25 2.25 0 0 0 2 16.74v.51c0 .414.336.75.75.75h16.5a.75.75 0 0 0 .75-.75v-.51a2.25 2.25 0 0 0-.659-1.591l-.98-.98a2.25 2.25 0 0 1-.659-1.591v-.87l.98-.98a2.25 2.25 0 0 0 .659-1.591V4.25A2.25 2.25 0 0 0 17.75 2H4.25Zm3 4.25a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Zm0 4a.75.75 0 0 0 0 1.5h6.5a.75.75 0 0 0 0-1.5h-6.5Z" clip-rule="evenodd"/></svg>
                        {{ __('Do Not Disturb') }}
                    </span>
                    <span
                        class="sc-cc-switch"
                        :class="{ 'sc-cc-switch-on': dnd }"
                        role="switch"
                        :aria-checked="dnd"
                        x-on:click.stop="dnd = ! dnd; $wire.setDnd(dnd)"
                    ><span class="sc-cc-switch-knob"></span></span>
                </label>

                <div class="sc-cc-action-btns">
                    @if ($this->canClear)
                        <button type="button" class="sc-cc-btn sc-cc-btn-ghost" x-on:click="$wire.clearAllTasks()">
                            <svg class="sc-cc-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482 41.03 41.03 0 0 0-2.365-.298V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4Z" clip-rule="evenodd"/></svg>
                            {{ __('Clear All') }}
                        </button>
                    @endif
                    <button type="button" class="sc-cc-btn sc-cc-btn-ghost" x-on:click="$wire.clearNotifications()">
                        <svg class="sc-cc-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6ZM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3Z"/></svg>
                        {{ __('Clear Notifications') }}
                    </button>
                    <button
                        type="button"
                        class="sc-cc-btn sc-cc-btn-ghost"
                        :class="{ 'sc-cc-btn-active': $wire.showNotificationHistory }"
                        x-on:click="$wire.toggleNotificationHistory()"
                        title="{{ __('View notification history for the past 30 days') }}"
                    >
                        <svg class="sc-cc-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .199.079.39.22.53l3 3a.75.75 0 1 0 1.06-1.06L10.75 10.9V5Z" clip-rule="evenodd"/></svg>
                        {{ __('History (30 days)') }}
                    </button>
                </div>
            </div>

            {{-- Task list --}}
            <div class="sc-cc-list">
                @forelse ($this->tasks as $task)
                    @php
                        $taskCat = $this->assigneeCategory($task);
                    @endphp
                    <div
                        class="sc-cc-task sc-cc-task-{{ $taskCat }}"
                        :class="{ 'sc-cc-task-done': {{ $task->isDone() ? 'true' : 'false' }} }"
                    >
                        <button
                            type="button"
                            class="sc-cc-task-check"
                            :class="{ 'sc-cc-task-check-on': {{ $task->isDone() ? 'true' : 'false' }} }"
                            x-on:click="$wire.toggleTaskDone({{ $task->id }})"
                            :title="'{{ $task->isDone() ? 'Mark as open' : 'Mark as done' }}'"
                        >
                            <svg x-show="{{ $task->isDone() ? 'true' : 'false' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/></svg>
                        </button>

                        <div
                            class="sc-cc-task-body"
                            role="button"
                            tabindex="0"
                            :title="'Open in Schedule'"
                            x-on:click="Livewire.navigate('{{ $this->scheduleUrl() }}?task={{ $task->id }}')"
                            x-on:keydown.enter="Livewire.navigate('{{ $this->scheduleUrl() }}?task={{ $task->id }}')"
                        >
                            <p class="sc-cc-task-title">{{ $task->title }}</p>
                            <p class="sc-cc-task-meta">
                                <span class="sc-cc-task-tag sc-cc-task-tag-{{ $taskCat }}">{{ $this->assigneeCategoryLabel($taskCat) }}</span>
                                @if ($task->due_date)
                                    <span class="sc-cc-task-due">
                                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Z"/></svg>
                                        {{ $task->due_date->format('M j') }}@if ($task->due_time), {{ $task->due_time }}@endif
                                    </span>
                                @endif
                                @if ($task->assigned_to_id && $task->assigned_to_id !== $this->user()?->id)
                                    <span class="sc-cc-task-assignee">
                                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 0 0-13.074.003Z"/></svg>
                                        {{ $task->assignee->name }}
                                    </span>
                                @endif
                            </p>
                            @if ($task->description)
                                <p class="sc-cc-task-desc">{{ $task->description }}</p>
                            @endif
                        </div>

                        <button
                            type="button"
                            class="sc-cc-task-del"
                            x-on:click="$wire.deleteTask({{ $task->id }})"
                            title="Delete task"
                        >
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                        </button>
                    </div>
                @empty
                    <div class="sc-cc-empty">
                        <svg class="sc-cc-empty-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-11.25a.75.75 0 0 0-1.5 0v4.5c0 .199.079.39.22.53l3 3a.75.75 0 1 0 1.06-1.06L10.75 10.9V6.75Z" clip-rule="evenodd"/></svg>
                        <p>{{ __('No tasks here yet.') }}</p>
                        <p class="sc-cc-empty-sub">{{ __('Click any date on the calendar to add a task.') }}</p>
                    </div>
                @endforelse
            </div>

            {{-- Notifications --}}
            @if ($this->unreadNotifications->isNotEmpty() || $this->unreadNotificationCount > 0 || $this->showNotificationHistory)
                <div class="sc-cc-notif-head">
                    <span class="sc-cc-notif-title">
                        <svg class="sc-cc-notif-bell" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6Zm0 16a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3Z" clip-rule="evenodd"/></svg>
                        {{ $this->showNotificationHistory ? __('Notification History (30 days)') : 'Notifications' }}
                        @if (! $this->dnd && $this->unreadNotificationCount > 0)
                            <span class="sc-cc-notif-count">{{ $this->unreadNotificationCount }}</span>
                        @endif
                    </span>
                    <span class="sc-cc-notif-actions">
                        @if (! $this->showNotificationHistory)
                            <button type="button" class="sc-cc-btn-link" x-on:click="$wire.markNotificationsRead()">{{ __('Mark all read') }}</button>
                        @else
                            <button type="button" class="sc-cc-btn-link" x-on:click="$wire.toggleNotificationHistory()">{{ __('Back') }}</button>
                        @endif
                    </span>
                </div>
                <div class="sc-cc-list sc-cc-list-notif">
                    @if ($this->showNotificationHistory)
                        {{-- History filters: category + age window --}}
                        <div class="sc-cc-filter-bar">
                            <select wire:model.live="historyCategory" class="sc-cc-filter-select" aria-label="{{ __('Filter by type') }}">
                                <option value="all">{{ __('All types') }}</option>
                                <option value="chat">{{ __('Chat messages') }}</option>
                                <option value="registration">{{ __('Registrations') }}</option>
                                <option value="system">{{ __('System & tasks') }}</option>
                            </select>
                            <select wire:model.live="historyDays" class="sc-cc-filter-select" aria-label="{{ __('Filter by period') }}">
                                <option value="7">{{ __('Last 7 days') }}</option>
                                <option value="14">{{ __('Last 14 days') }}</option>
                                <option value="30">{{ __('Last 30 days') }}</option>
                            </select>
                        </div>
                    @endif
                    @foreach ($this->showNotificationHistory ? $this->notificationHistory : $this->unreadNotifications as $notification)
                        @php
                            $nType = $notification->type;
                            $nChip = 'New';
                            $nClass = 'sc-cc-notif-info';
                            if ($nType === \App\Notifications\PlatformMessageNotification::class) {
                                // Distinct CHAT presentation so conversations are
                                // instantly recognizable next to system notices.
                                $nTitle = ($notification->data['subject'] ?? 'New message');
                                $nSub = trim(($notification->data['sender_label'] ?? '').' · '.($notification->data['preview'] ?? ''));
                                $nChip = ($notification->data['sender_type'] ?? '') === 'platform' ? 'Chat ↓' : 'Chat ↑';
                                $nClass = 'sc-cc-notif-chat';
                            } elseif ($nType === \App\Notifications\UserRegistrationApprovalNotification::class) {
                                $nTitle = 'New '.($notification->data['requested_role_label'] ?? 'registration').' registration';
                                $nSub = $notification->data['user_name'] ?? '';
                                $nChip = 'Action required';
                                $nClass = 'sc-cc-notif-warn';
                            } elseif ($nType === \App\Notifications\SchoolRegisteredNotification::class) {
                                $nTitle = 'New school awaiting approval';
                                $nSub = trim(($notification->data['school_name'] ?? '').' · '.($notification->data['country'] ?? '').' · '.($notification->data['contact_email'] ?? ''));
                                $nChip = 'Action required';
                                $nClass = 'sc-cc-notif-warn';
                            } elseif ($nType === \App\Notifications\NewApplicationNotification::class) {
                                $nTitle = 'New admission application';
                                $nSub = $notification->data['applicant_name'] ?? '';
                                $nClass = 'sc-cc-notif-info';
                            } elseif ($nType === \App\Notifications\TaskAssignedNotification::class) {
                                $nTitle = 'New task assigned to you';
                                $nSub = $notification->data['task_title'] ?? '';
                                $nChip = 'Task';
                                $nClass = 'sc-cc-notif-task';
                            } elseif ($nType === \App\Notifications\ProfilePhotoRejectedNotification::class) {
                                $nTitle = $notification->data['subject'] ?? 'Profile photo removed';
                                $nSub = trim(($notification->data['reason'] ?? '').' · Please upload a new photo.');
                                $nChip = 'Photo';
                                $nClass = 'sc-cc-notif-warn';
                            } elseif ($nType === \App\Notifications\TaskReminderNotification::class || $nType === \App\Notifications\TaskOverdueNotification::class) {
                                $nTitle = str_contains($nType, 'Overdue') ? 'Task is overdue' : 'Task reminder';
                                $nSub = $notification->data['task_title'] ?? '';
                                $nChip = $nType === \App\Notifications\TaskOverdueNotification::class ? 'Overdue' : 'Reminder';
                                $nClass = 'sc-cc-notif-task';
                            } elseif ($nType === \App\Notifications\EventReminderNotification::class) {
                                $nTitle = 'Event starting soon';
                                $nSub = $notification->data['event_title'] ?? '';
                                $nChip = 'Event';
                                $nClass = 'sc-cc-notif-event';
                            } else {
                                $nTitle = $notification->data['title'] ?? 'Notification';
                                $nSub = $notification->data['message'] ?? $notification->data['body'] ?? '';
                                $nChip = 'Info';
                                $nClass = 'sc-cc-notif-info';
                            }
                        @endphp
                        @php
                            $nUrl = $this->notificationUrl($notification);
                        @endphp
                        @if ($nType === \App\Notifications\PlatformMessageNotification::class)
                            {{-- Chat notifications navigate (SPA) into the message inbox --}}
                            <a
                                href="{{ $nUrl ?? '#' }}"
                                class="sc-cc-notif {{ $this->showNotificationHistory && $notification->read_at ? 'sc-cc-notif-read' : '' }} {{ $nClass }}"
                            >
                                <span class="sc-cc-notif-chat-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z"/></svg>
                                </span>
                                <span class="sc-cc-notif-body">
                                    <span class="sc-cc-notif-msg">{{ $nTitle }}</span>
                                    <span class="sc-cc-notif-sub">
                                        {{ $nSub }}
                                        &middot; {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </span>
                                <span class="sc-cc-notif-chip">{{ $nChip }}</span>
                            </a>
                        @else
                            <a
                                href="{{ $nUrl ?? '#' }}"
                                class="sc-cc-notif {{ $this->showNotificationHistory && $notification->read_at ? 'sc-cc-notif-read' : '' }}"
                                :class="'{{ $nClass }}'"
                            >
                                <span class="sc-cc-notif-dot"></span>
                                <span class="sc-cc-notif-body">
                                    <span class="sc-cc-notif-msg">{{ $nTitle }}</span>
                                    <span class="sc-cc-notif-sub">
                                        {{ $nSub }}
                                        &middot; {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </span>
                                <span class="sc-cc-notif-chip">{{ $nChip }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>

        <div class="sc-cc-pane sc-cc-pane-cal">
            {{-- Calendar header + nav --}}
            <div class="sc-cc-cal-head">
                <h3 class="sc-cc-pane-title" x-text="'{{ $currentMonth->format('F Y') }}'">{{ $currentMonth->format('F Y') }}</h3>
                <div class="sc-cc-cal-nav">
                    <button type="button" class="sc-cc-cal-nav-btn" x-on:click="$wire.previousMonth()" title="Previous month" aria-label="Previous month">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd"/></svg>
                    </button>
                    <button type="button" class="sc-cc-cal-today" x-on:click="$wire.goToCurrentMonth()">{{ __('Today') }}</button>
                    <button type="button" class="sc-cc-cal-nav-btn" x-on:click="$wire.nextMonth()" title="Next month" aria-label="Next month">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
            </div>

            {{-- Weekday header --}}
            <div class="sc-cc-cal-week">
                @foreach ($this->calendarDays['weekdays'] as $wd)
                    <span class="sc-cc-cal-wd">{{ $wd }}</span>
                @endforeach
            </div>

            {{-- Day grid --}}
            <div class="sc-cc-cal-grid">
                @foreach ($this->calendarDays['days'] as $cell)
                    @if (! $cell)
                        <span class="sc-cc-day sc-cc-day-blank"></span>
                    @else
                        <button
                            type="button"
                            class="sc-cc-day"
                            :class="{
                                'sc-cc-day-today': {{ $cell['isToday'] ? 'true' : 'false' }},
                                'sc-cc-day-past': {{ $cell['isPast'] ? 'true' : 'false' }},
                                'sc-cc-day-range': inRange('{{ $cell['date'] }}'),
                                'sc-cc-day-start': isStart('{{ $cell['date'] }}'),
                                'sc-cc-day-end': isEnd('{{ $cell['date'] }}'),
                                'sc-cc-day-custom': !! rangeStart,
                            }"
                            data-date="{{ $cell['date'] }}"
                            x-on:mouseenter="hover = '{{ $cell['date'] }}'"
                            x-on:mouseleave="hover = null"
                            x-on:click="clickDay('{{ $cell['date'] }}', !! rangeStart || preset === 'custom')"
                            :title="preset === 'custom' || rangeStart ? 'Select range' : 'Add a task'"
                        >
                            <span class="sc-cc-day-num">{{ $cell['day'] }}</span>
                            @if ($cell['hasEvents'] || $cell['hasTasks'])
                                <span class="sc-cc-day-marks">
                                    @if ($cell['hasEvents'])
                                        <span class="sc-cc-day-dot" @if ($cell['eventColor']) style="background: {{ $cell['eventColor'] }};" @endif></span>
                                    @endif
                                    @if ($cell['hasTasks'])
                                        <span class="sc-cc-day-dot sc-cc-day-dot-task"></span>
                                    @endif
                                </span>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>

            {{-- Today / No events footer --}}
            <div class="sc-cc-cal-foot">
                @php
                    $todayCell = collect($this->calendarDays['days'])->firstWhere('isToday');
                @endphp
                @if ($todayCell && ($todayCell['hasEvents'] || $todayCell['hasTasks']))
                    <span class="sc-cc-cal-foot-dot"></span>
                    <span class="sc-cc-cal-foot-text">
                        {{ $todayCell['hasTasks'] ? $todayCell['taskCount'].' task(s)' : '' }}
                        {{ $todayCell['hasEvents'] && $todayCell['hasTasks'] ? '&middot;' : '' }}
                        {{ $todayCell['hasEvents'] ? 'events scheduled' : '' }}
                        for today
                    </span>
                @else
                    <span class="sc-cc-cal-foot-dot sc-cc-cal-foot-dot-muted"></span>
                    <span class="sc-cc-cal-foot-text">{{ __('No events today') }}</span>
                @endif
            </div>

            {{-- Links to full pages --}}
            <div class="sc-cc-links">
                <a href="{{ $this->scheduleUrl() }}" class="sc-cc-link">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 17 6.75v8.5A2.75 2.75 0 0 1 14.25 18H5.75A2.75 2.75 0 0 1 3 15.25v-8.5A2.75 2.75 0 0 1 5.75 4H5V2.75A.75.75 0 0 1 5.75 2Z" clip-rule="evenodd" fill-rule="evenodd"/></svg>
                    {{ __('Open Schedule') }}
                </a>
                <a href="{{ $this->myDayUrl() }}" class="sc-cc-link">
                    <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    {{ __('Open My Day') }}
                </a>
            </div>

            {{-- Nested "Quick Add" slide panel (Task / Event tabs + day agenda) --}}
            <div
                x-show="selectedDate"
                x-cloak
                x-transition:enter="sc-cc-add-enter"
                x-transition:enter-start="sc-cc-add-enter-start"
                x-transition:enter-end="sc-cc-add-enter-end"
                x-transition:leave="sc-cc-add-leave"
                x-transition:leave-start="sc-cc-add-leave-start"
                x-transition:leave-end="sc-cc-add-leave-end"
                class="sc-cc-add"
            >
                <div class="sc-cc-add-head">
                    <div>
                        <h4 class="sc-cc-add-title">{{ __('Quick Add') }}</h4>
                        <p class="sc-cc-add-date" x-text="pretty(selectedDate)"></p>
                    </div>
                    <button type="button" class="sc-cc-add-close" x-on:click="selectedDate = null; $wire.closeAddTask()" title="Close" aria-label="Close">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                    </button>
                </div>

                {{-- Day agenda --}}
                <div class="sc-cc-agenda">
                    @forelse (collect($this->dayAgenda['events'])->merge($this->dayAgenda['tasks']) as $agendaItem)
                        @if (isset($agendaItem['all_day']))
                            <a
                                href="{{ $this->scheduleUrl() }}?event={{ $agendaItem['id'] }}"
                                wire:navigate
                                class="sc-cc-agenda-row"
                                :title="'Open event in Schedule'"
                            >
                                <span class="sc-cc-agenda-time">@if ($agendaItem['all_day']) All day @else {{ $agendaItem['start_time'] }} @endif</span>
                                <span class="sc-cc-agenda-title">{{ $agendaItem['title'] }}</span>
                                @if ($agendaItem['color'])<span class="sc-cc-agenda-dot" style="background: {{ $agendaItem['color'] }};"></span>@endif
                            </a>
                        @else
                            <a
                                href="{{ $this->scheduleUrl() }}?task={{ $agendaItem['id'] }}"
                                wire:navigate
                                class="sc-cc-agenda-row"
                                :title="'Open task in Schedule'"
                            >
                                <span class="sc-cc-agenda-time">{{ $agendaItem['due_time'] ?? '—' }}</span>
                                <span class="sc-cc-agenda-title">{{ $agendaItem['title'] }}</span>
                                <span class="sc-cc-agenda-badge">{{ $agendaItem['done'] ? 'Done' : 'Task' }}</span>
                            </a>
                        @endif
                    @empty
                        <span class="sc-cc-agenda-empty">{{ __('Nothing scheduled for this day.') }}</span>
                    @endforelse
                </div>

                {{-- Tab switcher --}}
                <div class="sc-cc-tabs">
                    <button
                        type="button"
                        class="sc-cc-tab"
                        :class="{ 'sc-cc-tab-active': addTab === 'task' }"
                        x-on:click="openTab('task')"
                    >{{ __('Add Task') }}</button>
                    <button
                        type="button"
                        class="sc-cc-tab"
                        :class="{ 'sc-cc-tab-active': addTab === 'event' }"
                        x-on:click="openTab('event')"
                    >{{ __('Add Event') }}</button>
                </div>

                <form wire:submit="saveTask" class="sc-cc-add-form" x-show="addTab === 'task'">
                    <label class="sc-cc-field">
                        <span class="sc-cc-field-label">{{ __('Task title') }}</span>
                        <input
                            type="text"
                            wire:model="taskTitle"
                            placeholder="e.g. Mark term exams"
                            class="sc-cc-input"
                            x-ref="taskTitle"
                            maxlength="255"
                        />
                        @error('taskTitle')
                            <span class="sc-cc-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="sc-cc-field">
                        <span class="sc-cc-field-label">Notes (optional)</span>
                        <textarea
                            wire:model="taskDescription"
                            rows="2"
                            placeholder="Add a short description"
                            class="sc-cc-input sc-cc-textarea"
                        ></textarea>
                    </label>

                    @if ($this->canAssign)
                        <label class="sc-cc-field">
                            <span class="sc-cc-field-label">{{ __('Assign to') }}</span>
                            <x-assignee-picker
                                name="taskAssigneeId"
                                :options="$this->assigneeOptions"
                                :selected-id="$this->taskAssigneeId"
                            />
                        </label>
                    @endif

                    <label class="sc-cc-field">
                        <span class="sc-cc-field-label">Due time (optional)</span>
                        <input type="time" wire:model="taskDueTime" class="sc-cc-input" />
                    </label>

                    <div class="sc-cc-add-actions">
                        <button
                            type="button"
                            class="sc-cc-btn sc-cc-btn-ghost"
                            x-on:click="selectedDate = null; $wire.closeAddTask()"
                        >{{ __('Cancel') }}</button>
                        <button type="submit" class="sc-cc-btn sc-cc-btn-primary">
                            <svg class="sc-cc-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                            {{ __('Save Task') }}
                        </button>
                    </div>
                </form>

                <form wire:submit="saveEvent" class="sc-cc-add-form" x-show="addTab === 'event'">
                    <label class="sc-cc-field">
                        <span class="sc-cc-field-label">{{ __('Event title') }}</span>
                        <input
                            type="text"
                            wire:model="eventTitle"
                            placeholder="e.g. PTA meeting"
                            class="sc-cc-input"
                            maxlength="255"
                        />
                        @error('eventTitle')
                            <span class="sc-cc-error">{{ $message }}</span>
                        @enderror
                    </label>

                    <div class="sc-cc-row">
                        <label class="sc-cc-field">
                            <span class="sc-cc-field-label">{{ __('Start time') }}</span>
                            <input type="time" wire:model="eventStart" class="sc-cc-input" />
                        </label>
                        <label class="sc-cc-field">
                            <span class="sc-cc-field-label">{{ __('End time') }}</span>
                            <input type="time" wire:model="eventEnd" class="sc-cc-input" />
                        </label>
                        <label class="sc-cc-field">
                            <span class="sc-cc-field-label">{{ __('Color') }}</span>
                            <input type="color" wire:model="eventColor" class="sc-cc-input sc-cc-color" />
                        </label>
                    </div>

                    <div class="sc-cc-add-actions">
                        <button
                            type="button"
                            class="sc-cc-btn sc-cc-btn-ghost"
                            x-on:click="selectedDate = null; $wire.closeAddTask()"
                        >{{ __('Cancel') }}</button>
                        <button type="submit" class="sc-cc-btn sc-cc-btn-primary sc-cc-btn-event">
                            <svg class="sc-cc-btn-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 17 6.75v8.5A2.75 2.75 0 0 1 14.25 18H5.75A2.75 2.75 0 0 1 3 15.25v-8.5A2.75 2.75 0 0 1 5.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm.25 3.5a.75.75 0 0 0-.75.75v.5c0 .414.336.75.75.75h8c.414 0 .75-.336.75-.75v-.5a.75.75 0 0 0-.75-.75h-8Z"/></svg>
                            {{ __('Add Event') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </div>
        </div>
    </template>
</div>
