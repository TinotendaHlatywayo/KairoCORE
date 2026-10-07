<?php

namespace App\Livewire;

use App\Notifications\PlatformMessageNotification;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Platform (super admin) notification centre.
 *
 * Sister of the tenant "Command Center": a live Date & Time trigger sits in the
 * platform topbar and opens a dropdown listing the admin's notifications with
 * timestamps. Unlike the tenant version it is scoped to platform admins only
 * (users with no school_id) and deliberately stays lean — no tenant task
 * manager or calendar.
 */
class PlatformCommandCenter extends Component
{
    public bool $isOpen = false;

    public bool $showHistory = false;

    public int $historyDays = 30;

    protected $listeners = ['$refresh' => '$refresh', 'notificationSent' => '$refresh'];

    public function toggle(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    protected function user(): ?\App\Models\User
    {
        return auth()->user();
    }

    /**
     * Newest unread/undismissed notifications shown in the dropdown.
     */
    public function getNotificationsProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->whereNull('cleared_at')
            ->latest()
            ->limit(8)
            ->get();
    }

    public function getUnreadCountProperty(): int
    {
        $user = $this->user();

        if (! $user) {
            return 0;
        }

        return $user->unreadNotifications()
            ->whereNull('cleared_at')
            ->count();
    }

    /**
     * Older notifications (past N days) shown when History is toggled on.
     */
    public function getHistoryProperty(): Collection
    {
        $user = $this->user();

        if (! $user) {
            return collect();
        }

        return $user->notifications()
            ->where('created_at', '>=', now()->subDays($this->historyDays)->startOfDay())
            ->latest()
            ->limit(50)
            ->get();
    }

    public function markAllRead(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $user->unreadNotifications()
            ->whereNull('cleared_at')
            ->update(['read_at' => now()]);
    }

    public function markRead(string $id): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        $user->notifications()
            ->whereKey($id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function clearNotifications(): void
    {
        $user = $this->user();

        if (! $user) {
            return;
        }

        // Mark as cleared rather than deleting, so no notification is ever
        // permanently lost (mirrors the tenant Command Center behaviour).
        $user->notifications()
            ->whereNull('cleared_at')
            ->update(['cleared_at' => now(), 'read_at' => now()]);

        $this->showHistory = false;
    }

    public function toggleHistory(): void
    {
        $this->showHistory = ! $this->showHistory;
    }

    public function notificationUrl($notification): ?string
    {
        return data_get($notification->data, 'url') ?: null;
    }

    public function titleFor($notification): string
    {
        return (string) (
            data_get($notification->data, 'subject')
            ?? data_get($notification->data, 'title')
            ?? __('Notification')
        );
    }

    public function previewFor($notification): string
    {
        return (string) (
            data_get($notification->data, 'preview')
            ?? data_get($notification->data, 'body')
            ?? data_get($notification->data, 'message')
            ?? ''
        );
    }

    public function iconFor(string $type): string
    {
        return match ($type) {
            PlatformMessageNotification::class => '💬',
            default => '🔔',
        };
    }

    public function render()
    {
        return view('livewire.platform-command-center');
    }
}