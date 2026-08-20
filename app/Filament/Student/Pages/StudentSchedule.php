<?php

namespace App\Filament\Student\Pages;

use App\Filament\App\Pages\Schedule;
use Illuminate\Support\Collection;
use Modules\Communication\Models\EventCalendar;

/**
 * Student "My Schedule" — mirrors the staff Schedule experience exactly
 * (Month / Week / Day / Agenda views, toolbar, search & filters, mini
 * calendar, drag & drop, and the same event/task forms with priority,
 * repeats and reminders) with two deliberate differences:
 *
 * 1. Students only ever see school events targeted at students (or events
 *    they created themselves).
 * 2. Students cannot assign tasks to other people — the assignee picker is
 *    hidden because they lack the `tasks.assign` permission, and `saveTask`
 *    fans out only to themselves.
 */
class StudentSchedule extends Schedule
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'My Schedule';

    protected static ?string $title = 'My Schedule';

    protected static ?string $slug = 'my-schedule';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static string $view = 'filament.app.pages.schedule';

    public function homeUrl(): string
    {
        return StudentDashboard::getUrl();
    }

    protected function defaultEventCategory(): string
    {
        return 'academic';
    }

    protected function eventTargetRoles(): ?array
    {
        return ['student'];
    }

    /**
     * Students only see school events aimed at them (no target roles means
     * school-wide) plus anything they created themselves.
     */
    public function getEventsProperty(): Collection
    {
        if (! $this->showEvents) {
            return collect();
        }

        [$start, $end] = $this->resolveRange();

        $userId = auth()->id();

        return EventCalendar::query()
            ->overlappingRange($start, $end)
            ->with(['organizer:id,name', 'creator:id,name'])
            ->search($this->search)
            ->orderBy('start_time')
            ->get()
            ->filter(function (EventCalendar $event) use ($userId) {
                $roles = $event->target_roles ?? [];

                return empty($roles)
                    || in_array('student', $roles, true)
                    || $event->created_by_id === $userId;
            })
            ->values();
    }

    /**
     * Students may only open the event editor for events they created; school
     * events are view-only for them.
     */
    public function editEvent(int $id): void
    {
        $event = EventCalendar::query()->find($id);
        if (! $event || $event->created_by_id !== auth()->id()) {
            return;
        }

        parent::editEvent($id);
    }

    public function moveEvent(int $id, string $date, ?string $time = null): void
    {
        $event = EventCalendar::query()->find($id);
        if (! $event || $event->created_by_id !== auth()->id()) {
            return;
        }

        parent::moveEvent($id, $date, $time);
    }

    public function deleteEvent(int $id): void
    {
        $event = EventCalendar::query()->find($id);
        if (! $event || $event->created_by_id !== auth()->id()) {
            return;
        }

        $this->deleteEventId = $id;
    }

    public function confirmDeleteEvent(): void
    {
        $event = EventCalendar::query()->find($this->deleteEventId);
        if ($event && $event->created_by_id === auth()->id()) {
            $event->delete();
        }
        $this->deleteEventId = null;
        $this->dispatch('eventCalendarUpdated');
    }
}
