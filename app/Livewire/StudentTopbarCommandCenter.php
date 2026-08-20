<?php

namespace App\Livewire;

use App\Filament\Student\Pages\StudentSchedule;
use App\Models\UserTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Communication\Models\EventCalendar;

/**
 * Student topbar Command Center — the same live Date & Time trigger and
 * Task Manager / calendar dropdown as the staff workspace, adapted for the
 * student portal:
 *
 * - Events shown are only those visible to students (school-wide or targeted
 *   at the "student" role, plus events the student created).
 * - Events created from the dropdown are academic and student-targeted.
 * - Links in the footer point at the student schedule (no "My Day" page).
 * - The assignee picker stays hidden (students cannot assign tasks to others).
 */
class StudentTopbarCommandCenter extends TopbarCommandCenter
{
    public function scheduleUrl(): string
    {
        return StudentSchedule::getUrl();
    }

    public function myDayUrl(): string
    {
        return StudentSchedule::getUrl();
    }

    /**
     * Students only see school events aimed at them plus their own events.
     */
    protected function monthEvents(): Collection
    {
        $start = Carbon::parse($this->month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $userId = auth()->id();

        return EventCalendar::query()
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('start_time', [$start, $end]);
                $query->orWhere(function ($query) use ($start) {
                    $query->where('start_time', '<', $start)
                        ->where('end_time', '>=', $start);
                });
            })
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
     * Events in the per-day agenda panel are filtered the same way.
     */
    public function getDayAgendaProperty(): array
    {
        $user = $this->user();
        $date = $this->selectedDate;

        if (! $user || ! $date) {
            return ['date' => null, 'label' => '', 'events' => [], 'tasks' => []];
        }

        $day = Carbon::parse($date)->startOfDay();
        $userId = $user->id;

        $events = EventCalendar::query()
            ->overlappingRange($day, $day->copy()->endOfDay())
            ->orderBy('start_time')
            ->get()
            ->filter(function (EventCalendar $event) use ($userId) {
                $roles = $event->target_roles ?? [];

                return empty($roles)
                    || in_array('student', $roles, true)
                    || $event->created_by_id === $userId;
            })
            ->map(function (EventCalendar $event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'color' => $event->color,
                    'location' => $event->location,
                    'start_time' => $event->start_time->format('H:i'),
                    'end_time' => $event->end_time->format('H:i'),
                    'all_day' => $event->all_day,
                ];
            })
            ->values();

        $tasks = $this->userTasks()->where('due_date', $date)->values();

        return [
            'date' => $date,
            'label' => $day->format('D, M j'),
            'events' => $events,
            'tasks' => $tasks,
        ];
    }

    /**
     * Students create academic events that only reach other students.
     */
    public function saveEvent(): void
    {
        $user = $this->user();
        if (! $user || ! $this->selectedDate) {
            return;
        }

        $this->validate([
            'eventTitle' => ['required', 'string', 'max:255'],
            'eventStart' => ['nullable', 'date_format:H:i'],
            'eventEnd' => ['nullable', 'date_format:H:i'],
        ]);

        EventCalendar::create([
            'school_id' => $user->school_id,
            'created_by_id' => $user->id,
            'organizer_id' => $user->id,
            'title' => trim($this->eventTitle),
            'category' => 'academic',
            'start_time' => $this->selectedDate.' '.($this->eventStart ?? '09:00').':00',
            'end_time' => $this->selectedDate.' '.($this->eventEnd ?? '10:00').':00',
            'color' => $this->eventColor,
            'target_roles' => ['student'],
        ]);

        $this->eventTitle = null;
        $this->eventStart = null;
        $this->eventEnd = null;
        $this->selectedDate = null;

        $this->dispatch('eventCalendarUpdated');
    }

    /**
     * Personal tasks for the agenda panel (identical to the base component's
     * month-task query shape, scoped to the current user).
     */
    protected function userTasks(): Collection
    {
        $user = $this->user();
        if (! $user) {
            return collect();
        }

        return UserTask::query()
            ->visibleTo($user->id)
            ->orderBy('due_time')
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'due_time' => $task->due_time,
                    'done' => $task->isDone(),
                    'important' => $task->important,
                    'priority' => $task->priority,
                ];
            })
            ->values();
    }
}
