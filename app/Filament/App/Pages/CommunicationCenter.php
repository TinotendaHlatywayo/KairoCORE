<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Pages\Page;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\CampusResource;
use Modules\Communication\Models\ChatThread;
use Modules\Communication\Models\EventCalendar;
use Modules\Communication\Models\HelpdeskTicket;
use Modules\Communication\Models\Poll;
use Modules\SaaS\Models\PlatformMessage;

class CommunicationCenter extends Page
{
    use ModuleAwareActiveNavigation;
    use ModulePermissionAccess;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication Center';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Communication Center';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.app.pages.communication-center';

    public function getViewData(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return $this->emptyData();
        }

        $now = now();

        return [
            'kpis' => [
                [
                    'label' => 'Open Announcements',
                    'value' => Announcement::where('status', 'published')
                        ->where(function ($q) use ($now) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                        })
                        ->count(),
                    'icon' => 'heroicon-o-megaphone',
                    'tint' => 'emerald',
                ],
                [
                    'label' => 'Upcoming Events',
                    'value' => EventCalendar::where('start_time', '>=', $now)->count(),
                    'icon' => 'heroicon-o-calendar-days',
                    'tint' => 'blue',
                ],
                [
                    'label' => 'Open Helpdesk Tickets',
                    'value' => HelpdeskTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
                    'icon' => 'heroicon-o-lifebuoy',
                    'tint' => 'amber',
                ],
                [
                    'label' => 'Active Chat Threads',
                    'value' => ChatThread::count(),
                    'icon' => 'heroicon-o-chat-bubble-oval-left-ellipsis',
                    'tint' => 'violet',
                ],
                [
                    'label' => 'Campus Resources',
                    'value' => CampusResource::count(),
                    'icon' => 'heroicon-o-folder',
                    'tint' => 'slate',
                ],
                [
                    'label' => 'Active Polls',
                    'value' => Poll::where(function ($q) use ($now) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
                    })->count(),
                    'icon' => 'heroicon-o-chart-bar-square',
                    'tint' => 'rose',
                ],
            ],
            'recentAnnouncements' => Announcement::latest('published_at')->limit(5)->get(),
            'upcomingEvents' => EventCalendar::where('start_time', '>=', $now)
                ->orderBy('start_time')
                ->limit(5)
                ->get(),
            'recentThreads' => ChatThread::withCount('messages')
                ->latest('updated_at')
                ->limit(5)
                ->get(),
            'platformMessages' => PlatformMessage::withoutTenantScope()
                ->where('recipient_scope', 'all')
                ->orWhere('school_id', $schoolId)
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }

    protected function emptyData(): array
    {
        return [
            'kpis' => [],
            'recentAnnouncements' => collect(),
            'upcomingEvents' => collect(),
            'recentThreads' => collect(),
            'platformMessages' => collect(),
        ];
    }
}
