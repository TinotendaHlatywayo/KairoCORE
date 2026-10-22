<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\SaaS\Models\PlatformAnnouncement;
use Modules\SaaS\Services\PlatformAnnouncementService;

class PlatformAnnouncementBanner extends Component
{
    /**
     * The id of the announcement being shown.
     */
    public ?int $announcementId = null;

    /**
     * The announcement's headline.
     */
    public ?string $title = null;

    /**
     * The announcement's body.
     */
    public ?string $content = null;

    /**
     * The announcement's style: info, warning, danger, maintenance.
     */
    public string $type = 'info';

    /**
     * Whether the announcement was dismissed by the reader.
     */
    public bool $dismissed = false;

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return;
        }

        $announcement = app(PlatformAnnouncementService::class)->bannerFor($user);

        if (! $announcement instanceof PlatformAnnouncement) {
            return;
        }

        if ($this->isDismissed($announcement->id)) {
            return;
        }

        $this->announcementId = $announcement->id;
        $this->title = $announcement->title;
        $this->content = $announcement->content;
        $this->type = $announcement->type ?: 'info';
    }

    /**
     * Hide the banner for the rest of the session.
     */
    public function dismiss(): void
    {
        if ($this->announcementId === null) {
            return;
        }

        $dismissed = $this->dismissedAnnouncements();

        if (! in_array($this->announcementId, $dismissed, true)) {
            $dismissed[] = $this->announcementId;
        }

        session(['platform_announcements_dismissed' => array_values(array_unique($dismissed))]);

        $this->dismissed = true;
    }

    public function render()
    {
        return view('components.platform-announcement-banner');
    }

    /**
     * The announcements this session has already hidden.
     *
     * @return array<int, int>
     */
    protected function dismissedAnnouncements(): array
    {
        return array_values(array_filter(array_map('intval', (array) session('platform_announcements_dismissed', []))));
    }

    protected function isDismissed(?int $announcementId): bool
    {
        if ($announcementId === null) {
            return false;
        }

        return in_array($announcementId, $this->dismissedAnnouncements(), true);
    }
}
