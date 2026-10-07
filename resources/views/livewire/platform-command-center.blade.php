{{-- Platform (super admin) notification centre: live date/time + notifications.
     Fully self-contained styling so it renders identically in light and dark
     mode without depending on a Tailwind rebuild. --}}
<div
    class="pcc-root"
    x-data="{
        open: @entangle('isOpen'),
        now: new Date(),
        init() { setInterval(() => { this.now = new Date(); }, 1000); },
        pad(n) { return String(n).padStart(2, '0'); },
        get dLabel() { return this.now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' }); },
        get tLabel() { return this.pad(this.now.getHours()) + ':' + this.pad(this.now.getMinutes()) + ':' + this.pad(this.now.getSeconds()); },
    }"
>
    <button type="button" class="pcc-trigger" x-on:click="open = ! open" title="{{ __('Notifications') }}">
        <svg class="pcc-trigger-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M10 2a6 6 0 0 0-6 6v3.586l-.707.707A1 1 0 0 0 4 14h12a1 1 0 0 0 .707-1.707L16 11.586V8a6 6 0 0 0-6-6ZM10 18a3 3 0 0 1-3-3h6a3 3 0 0 1-3 3Z" clip-rule="evenodd"/>
        </svg>
        <span class="pcc-trigger-text">
            <span class="pcc-trigger-date" x-text="dLabel">{{ now()->format('D, M j') }}</span>
            <span class="pcc-trigger-time" x-text="tLabel">{{ now()->format('H:i:s') }}</span>
        </span>
        <span class="pcc-badge" x-show="{{ $this->unreadCount }} > 0" x-cloak x-text="{{ $this->unreadCount }}"></span>
    </button>

    <template x-teleport="body">
        <div class="pcc-overlay" x-show="open" x-cloak x-on:click.self="open = false; $wire.close()">
            <div class="pcc-panel" x-on:click.outside="open = false; $wire.close()">
                <div class="pcc-head">
                    <div class="pcc-head-text">
                        <h3 class="pcc-title">{{ $showHistory ? __('Notification History') : __('Notifications') }}</h3>
                        <p class="pcc-sub">
                            @if ($showHistory)
                                {{ __('Last :days days', ['days' => $historyDays]) }}
                            @else
                                {{ __(':count unread', ['count' => $this->unreadCount]) }}
                            @endif
                        </p>
                    </div>
                    <div class="pcc-head-actions">
                        @if (! $showHistory && $this->unreadCount > 0)
                            <button type="button" class="pcc-link" x-on:click="$wire.markAllRead()">{{ __('Mark all read') }}</button>
                        @endif
                        <button type="button" class="pcc-link" x-on:click="$wire.toggleHistory()">
                            {{ $showHistory ? __('Back') : __('History') }}
                        </button>
                    </div>
                </div>

                <div class="pcc-list">
                    @php($items = $showHistory ? $this->history : $this->notifications)
                    @forelse ($items as $notification)
                        @php($url = $this->notificationUrl($notification))
                        @if ($url)
                            <a href="{{ $url }}" class="pcc-item {{ $notification->read_at ? '' : 'pcc-item-unread' }}" wire:navigate x-on:click="open = false; $wire.close()">
                        @else
                            <div class="pcc-item {{ $notification->read_at ? '' : 'pcc-item-unread' }}" x-on:click="$wire.markRead('{{ $notification->id }}')">
                        @endif
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
                        @if ($url)
                            </a>
                        @else
                            </div>
                        @endif
                    @empty
                        <div class="pcc-empty">
                            <span class="pcc-empty-icon">✓</span>
                            <span>{{ __('You are all caught up. No notifications.') }}</span>
                        </div>
                    @endforelse
                </div>

                <div class="pcc-foot">
                    <span class="pcc-foot-note">{{ now()->format('l, d M Y') }}</span>
                    <button type="button" class="pcc-link pcc-link-danger" x-on:click="$wire.clearNotifications()">{{ __('Clear notifications') }}</button>
                </div>
            </div>
        </div>
    </template>

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
            .pcc-trigger-icon { width: 1.1rem; height: 1.1rem; color: #6366f1; }
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
                position: absolute; top: 3.75rem; right: 1rem; width: min(24rem, calc(100vw - 2rem));
                max-height: min(32rem, calc(100vh - 6rem)); display: flex; flex-direction: column;
                background: #fff; border: 1px solid rgba(100,116,139,.2); border-radius: 1rem;
                box-shadow: 0 24px 60px -18px rgba(15,23,42,.4); overflow: hidden;
            }
            .dark .pcc-panel { background: #0f172a; border-color: rgba(148,163,184,.2); }
            .pcc-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .9rem 1rem; border-bottom: 1px solid rgba(100,116,139,.16); }
            .pcc-title { margin: 0; font-size: .875rem; font-weight: 800; }
            .pcc-sub { margin: .1rem 0 0; font-size: .7rem; opacity: .65; font-weight: 600; }
            .pcc-head-actions { display: flex; align-items: center; gap: .5rem; }
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
        </style>
</div>