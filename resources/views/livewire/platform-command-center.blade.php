<div class="pcc-root">
    <button type="button" class="pcc-trigger" wire:click="toggle">
        <span class="pcc-trigger-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
        </span>
        <span class="pcc-trigger-text">
            <span class="pcc-trigger-date">{{ now()->format('D, M j') }}</span>
            <span class="pcc-trigger-time"
                  x-data="{ t: '' }"
                  x-init="t = new Date().toTimeString().slice(0, 8); setInterval(() => { t = new Date().toTimeString().slice(0, 8); }, 1000)"
                  x-text="t">{{ now()->format('H:i:s') }}</span>
        </span>
        @if (($this->unreadCount + $this->openTaskCount) > 0)
            <span class="pcc-badge">{{ $this->unreadCount + $this->openTaskCount }}</span>
        @endif
    </button>

    @if ($isOpen)
        <div class="pcc-overlay" wire:click.self="close">
            <div class="pcc-panel" wire:click.stop>
                <div class="pcc-head">
                    <div class="pcc-tabs">
                        <button type="button" class="pcc-tab {{ $tab === 'tasks' ? 'pcc-tab-active' : '' }}" wire:click="setTab('tasks')">
                            {{ __('Tasks') }}
                            @if ($this->openTaskCount > 0)
                                <span class="pcc-tab-badge">{{ $this->openTaskCount }}</span>
                            @endif
                        </button>
                        <button type="button" class="pcc-tab {{ $tab === 'notifications' ? 'pcc-tab-active' : '' }}" wire:click="setTab('notifications')">
                            {{ __('Notifications') }}
                            @if ($this->unreadCount > 0)
                                <span class="pcc-tab-badge pcc-tab-badge-red">{{ $this->unreadCount }}</span>
                            @endif
                        </button>
                    </div>
                    <div class="pcc-head-actions">
                        @if ($tab === 'tasks')
                            @if ($showAddTask)
                                <button type="button" class="pcc-link" wire:click="closeAddTask">{{ __('Cancel') }}</button>
                            @else
                                <button type="button" class="pcc-link" wire:click="openAddTask">+ {{ __('New task') }}</button>
                            @endif
                        @else
                            @if ($showHistory)
                                <button type="button" class="pcc-link" wire:click="toggleHistory">{{ __('Back') }}</button>
                            @else
                                <button type="button" class="pcc-link" wire:click="markAllRead">{{ __('Mark all read') }}</button>
                                <button type="button" class="pcc-link" wire:click="toggleHistory">{{ __('History') }}</button>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($tab === 'tasks')
                    @if ($showAddTask)
                        <div class="pcc-form">
                            <label class="pcc-field">
                                <span class="pcc-label">{{ __('Title') }}</span>
                                <input type="text" class="pcc-input" wire:model="taskTitle" placeholder="{{ __('What needs doing?') }}" autofocus>
                                @error('taskTitle') <span class="pcc-error">{{ $message }}</span> @enderror
                            </label>
                            <label class="pcc-field">
                                <span class="pcc-label">{{ __('Details (optional)') }}</span>
                                <textarea class="pcc-input" rows="2" wire:model="taskDescription"></textarea>
                            </label>
                            <div class="pcc-field-row">
                                <label class="pcc-field">
                                    <span class="pcc-label">{{ __('Due date') }}</span>
                                    <input type="date" class="pcc-input" wire:model="taskDate">
                                </label>
                                <label class="pcc-field">
                                    <span class="pcc-label">{{ __('Time') }}</span>
                                    <input type="time" class="pcc-input" wire:model="taskTime">
                                </label>
                            </div>
                            <label class="pcc-field">
                                <span class="pcc-label">{{ __('Assign to') }}</span>
                                <select class="pcc-input" wire:model.live="taskSchoolId">
                                    <option value="">{{ __('Just me') }}</option>
                                    @foreach ($this->schools as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('taskSchoolId') <span class="pcc-error">{{ $message }}</span> @enderror
                            </label>
                            @if ($taskSchoolId)
                                <label class="pcc-field">
                                    <span class="pcc-label">{{ __('School administrator') }}</span>
                                    <select class="pcc-input" wire:model="taskAssigneeId">
                                        @forelse ($this->schoolAdmins as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @empty
                                            <option value="">{{ __('No active administrator') }}</option>
                                        @endforelse
                                    </select>
                                </label>
                            @endif
                            <button type="button" class="pcc-primary" wire:click="saveTask" wire:loading.attr="disabled">
                                {{ $taskSchoolId ? __('Send to school') : __('Add task') }}
                            </button>
                        </div>
                    @else
                        <div class="pcc-list">
                            @php($hasTasks = $this->personalTasks->isNotEmpty() || $this->dispatchedTasks->isNotEmpty())

                            @foreach ($this->personalTasks as $task)
                                <div class="pcc-task {{ $task->isDone() ? 'pcc-task-done' : '' }}">
                                    <button type="button" class="pcc-check" wire:click="toggleTaskDone({{ $task->id }})">{{ $task->isDone() ? '✓' : '' }}</button>
                                    <span class="pcc-task-body">
                                        <span class="pcc-task-title">{{ $task->title }}</span>
                                        @if ($task->due_date || $task->due_time)
                                            <span class="pcc-task-meta">{{ $task->due_date?->format('d M') }} {{ $task->due_time ? '· '.$task->due_time : '' }}</span>
                                        @endif
                                    </span>
                                    <button type="button" class="pcc-task-x" wire:click="deleteTask({{ $task->id }})">&times;</button>
                                </div>
                            @endforeach

                            @if ($this->dispatchedTasks->isNotEmpty())
                                <div class="pcc-section-label">{{ __('Sent to schools') }}</div>
                                @foreach ($this->dispatchedTasks as $task)
                                    <div class="pcc-task {{ $task->isDone() ? 'pcc-task-done' : '' }}">
                                        <span class="pcc-check pcc-check-static">{{ $task->isDone() ? '✓' : '' }}</span>
                                        <span class="pcc-task-body">
                                            <span class="pcc-task-title">{{ $task->title }}</span>
                                            <span class="pcc-task-meta">
                                                {{ $task->school?->name }}
                                                @if ($task->assignee) · {{ $task->assignee->name }} @endif
                                                @if ($task->due_date) · {{ $task->due_date->format('d M') }} @endif
                                            </span>
                                        </span>
                                        <button type="button" class="pcc-task-x" wire:click="deleteTask({{ $task->id }})">&times;</button>
                                    </div>
                                @endforeach
                            @endif

                            @unless ($hasTasks)
                                <div class="pcc-empty">
                                    <span class="pcc-empty-icon">✓</span>
                                    <span>{{ __('No tasks yet. Click "New task" to add one.') }}</span>
                                </div>
                            @endunless
                        </div>

                        <div class="pcc-foot">
                            <span class="pcc-foot-note">{{ now()->format('l, d M Y') }}</span>
                            @if ($this->openTaskCount > 0)
                                <button type="button" class="pcc-link pcc-link-danger" wire:click="clearTasks">{{ __('Clear tasks') }}</button>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="pcc-list">
                        @php($items = $showHistory ? $this->history : $this->notifications)
                        @forelse ($items as $notification)
                            @php($url = $this->notificationUrl($notification))
                            <div class="pcc-item {{ $notification->read_at ? '' : 'pcc-item-unread' }}" wire:click="markRead('{{ $notification->id }}')" @if ($url) role="link" @endif>
                                <span class="pcc-item-icon">{{ $this->iconFor($notification->type) }}</span>
                                <span class="pcc-item-body">
                                    <span class="pcc-item-title">{{ $this->titleFor($notification) }}</span>
                                    @if ($preview = $this->previewFor($notification))
                                        <span class="pcc-item-preview">{{ \Illuminate\Support\Str::limit($preview, 110) }}</span>
                                    @endif
                                    <span class="pcc-item-time">{{ $notification->created_at->diffForHumans() }}</span>
                                </span>
                                @if (! $notification->read_at)
                                    <span class="pcc-dot"></span>
                                @endif
                            </div>
                        @empty
                            <div class="pcc-empty">
                                <span class="pcc-empty-icon">✓</span>
                                <span>{{ __('You are all caught up. No notifications.') }}</span>
                            </div>
                        @endforelse
                    </div>

                    <div class="pcc-foot">
                        <span class="pcc-foot-note">{{ now()->format('l, d M Y') }}</span>
                        <button type="button" class="pcc-link pcc-link-danger" wire:click="clearNotifications">{{ __('Clear notifications') }}</button>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <style>
        .pcc-root { position: relative; display: inline-flex; align-items: center; }
        .pcc-trigger {
            display: inline-flex; align-items: center; gap: .5rem;
            padding: .375rem .625rem; border-radius: .75rem; cursor: pointer;
            border: 1px solid rgba(100,116,139,.25);
            background: linear-gradient(135deg, rgba(99,102,241,.10), rgba(6,182,212,.10));
            color: inherit; transition: all .2s ease;
        }
        .dark .pcc-trigger { border-color: rgba(148,163,184,.22); background: linear-gradient(135deg, rgba(99,102,241,.18), rgba(6,182,212,.16)); }
        .pcc-trigger:hover { transform: translateY(-1px); box-shadow: 0 6px 18px -8px rgba(79,70,229,.55); }
        .pcc-trigger-icon { display: inline-flex; width: 1.1rem; height: 1.1rem; color: #6366f1; }
        .pcc-trigger-icon svg { width: 100%; height: 100%; }
        .dark .pcc-trigger-icon { color: #a5b4fc; }
        .pcc-trigger-text { display: flex; flex-direction: column; line-height: 1.05; text-align: left; }
        .pcc-trigger-date { font-size: .625rem; font-weight: 700; letter-spacing: .02em; opacity: .75; }
        .pcc-trigger-time { font-size: .8rem; font-weight: 800; font-variant-numeric: tabular-nums; }
        .pcc-badge {
            position: absolute; top: -.35rem; right: -.35rem; min-width: 1.1rem; height: 1.1rem;
            padding: 0 .3rem; border-radius: 999px; background: #ef4444; color: #fff;
            font-size: .625rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center;
            box-shadow: 0 0 0 2px var(--gray-50, #f8fafc);
        }
        .dark .pcc-badge { box-shadow: 0 0 0 2px #0f172a; }
        .pcc-overlay { position: fixed; inset: 0; z-index: 60; background: rgba(15,23,42,.35); backdrop-filter: blur(2px); }
        .dark .pcc-overlay { background: rgba(0,0,0,.55); }
        .pcc-panel {
            position: absolute; top: 3.5rem; left: 50%; transform: translateX(-50%);
            width: min(26rem, calc(100vw - 2rem));
            max-height: min(34rem, calc(100vh - 6rem)); display: flex; flex-direction: column;
            background: #fff; border: 1px solid rgba(100,116,139,.2); border-radius: 1rem;
            box-shadow: 0 24px 60px -18px rgba(15,23,42,.4); overflow: hidden;
        }
        .dark .pcc-panel { background: #0f172a; border-color: rgba(148,163,184,.2); }
        .pcc-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .55rem .75rem; border-bottom: 1px solid rgba(100,116,139,.16); }
        .pcc-tabs { display: inline-flex; gap: .25rem; background: rgba(100,116,139,.1); padding: .2rem; border-radius: .6rem; }
        .pcc-tab { border: 0; background: none; cursor: pointer; font-size: .72rem; font-weight: 700; padding: .3rem .6rem; border-radius: .45rem; color: inherit; opacity: .65; display: inline-flex; align-items: center; gap: .3rem; }
        .pcc-tab-active { background: #fff; opacity: 1; box-shadow: 0 1px 3px rgba(15,23,42,.12); }
        .dark .pcc-tab-active { background: rgba(148,163,184,.2); }
        .pcc-tab-badge { min-width: 1rem; height: 1rem; padding: 0 .25rem; border-radius: 999px; background: #4f46e5; color: #fff; font-size: .6rem; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
        .pcc-tab-badge-red { background: #ef4444; }
        .pcc-title { margin: 0; font-size: .875rem; font-weight: 800; }
        .pcc-sub { margin: .1rem 0 0; font-size: .7rem; opacity: .65; font-weight: 600; }
        .pcc-head-actions { display: flex; align-items: center; gap: .35rem; }
        .pcc-link { background: none; border: 0; cursor: pointer; font-size: .7rem; font-weight: 700; color: #4f46e5; padding: .2rem .35rem; border-radius: .4rem; }
        .pcc-link:hover { background: rgba(79,70,229,.1); }
        .pcc-link-danger { color: #e11d48; }
        .pcc-link-danger:hover { background: rgba(225,29,72,.1); }
        .dark .pcc-link { color: #a5b4fc; }
        .dark .pcc-link-danger { color: #fb7185; }
        .pcc-list { flex: 1; overflow-y: auto; padding: .35rem; }
        .pcc-item {
            display: flex; align-items: flex-start; gap: .65rem; padding: .65rem .7rem; margin: .15rem 0;
            border-radius: .7rem; text-decoration: none; color: inherit; cursor: pointer; position: relative; transition: background .15s ease;
        }
        .pcc-item:hover { background: rgba(99,102,241,.08); }
        .pcc-item-unread { background: rgba(99,102,241,.06); }
        .pcc-item-icon { font-size: 1rem; line-height: 1.3; }
        .pcc-item-body { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
        .pcc-item-title { font-size: .78rem; font-weight: 700; }
        .pcc-item-preview { font-size: .72rem; opacity: .72; line-height: 1.35; }
        .pcc-item-time { font-size: .64rem; font-weight: 600; opacity: .5; }
        .pcc-dot { position: absolute; top: .8rem; right: .7rem; width: .45rem; height: .45rem; border-radius: 999px; background: #4f46e5; }
        .pcc-empty { display: flex; flex-direction: column; align-items: center; gap: .4rem; padding: 2.5rem 1rem; font-size: .78rem; font-weight: 600; opacity: .6; }
        .pcc-empty-icon { font-size: 1.4rem; color: #10b981; }
        .pcc-foot { display: flex; align-items: center; justify-content: space-between; padding: .6rem 1rem; border-top: 1px solid rgba(100,116,139,.16); }
        .pcc-foot-note { font-size: .65rem; font-weight: 600; opacity: .55; }
        .pcc-task { display: flex; align-items: flex-start; gap: .55rem; padding: .55rem .7rem; margin: .15rem 0; border-radius: .7rem; }
        .pcc-task:hover { background: rgba(99,102,241,.08); }
        .pcc-task-done { opacity: .55; }
        .pcc-task-done .pcc-task-title { text-decoration: line-through; }
        .pcc-check { flex: none; width: 1.05rem; height: 1.05rem; border-radius: .3rem; border: 1.5px solid rgba(100,116,139,.5); background: none; cursor: pointer; font-size: .7rem; font-weight: 800; color: #10b981; display: inline-flex; align-items: center; justify-content: center; margin-top: .1rem; }
        .pcc-check-static { cursor: default; }
        .pcc-task-body { display: flex; flex-direction: column; gap: .1rem; min-width: 0; flex: 1; }
        .pcc-task-title { font-size: .78rem; font-weight: 700; }
        .pcc-task-meta { font-size: .66rem; opacity: .62; font-weight: 600; }
        .pcc-task-x { flex: none; background: none; border: 0; cursor: pointer; font-size: 1rem; line-height: 1; color: rgba(148,163,184,.9); padding: 0 .2rem; }
        .pcc-task-x:hover { color: #e11d48; }
        .pcc-section-label { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; opacity: .5; padding: .6rem .7rem .2rem; }
        .pcc-form { padding: .8rem; display: flex; flex-direction: column; gap: .6rem; overflow-y: auto; }
        .pcc-field { display: flex; flex-direction: column; gap: .25rem; }
        .pcc-field-row { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; }
        .pcc-label { font-size: .66rem; font-weight: 700; opacity: .65; }
        .pcc-input { width: 100%; border: 1px solid rgba(100,116,139,.3); border-radius: .55rem; padding: .45rem .6rem; font-size: .78rem; background: transparent; color: inherit; }
        .pcc-input:focus { outline: 2px solid rgba(99,102,241,.5); outline-offset: 0; border-color: transparent; }
        .dark .pcc-input { border-color: rgba(148,163,184,.3); }
        .pcc-error { font-size: .66rem; font-weight: 700; color: #e11d48; }
        .pcc-primary { margin-top: .2rem; border: 0; border-radius: .6rem; padding: .55rem .8rem; font-size: .78rem; font-weight: 800; color: #fff; cursor: pointer; background: linear-gradient(135deg, #6366f1, #06b6d4); }
        .pcc-primary:hover { filter: brightness(1.05); }
    </style>
</div>