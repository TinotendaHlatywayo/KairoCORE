<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollVote;

/**
 * Student-facing Polls & Surveys.
 *
 * Shows every open poll/survey targeted at students (or everyone). A student
 * may cast exactly one vote per poll, after which live percentage standings
 * are displayed.
 */
class StudentPolls extends Page
{
    protected static string $view = 'filament.student.pages.student-polls';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Polls & Surveys';

    protected static ?string $title = 'Polls & Surveys';

    protected static ?string $slug = 'polls-surveys';

    public static function getNavigationLabel(): string
    {
        return __('Polls & Surveys');
    }

    public function vote(int $pollId, int $optionId): void
    {
        $poll = $this->polls->firstWhere(fn (array $entry) => $entry['poll']->id === $pollId)['poll'] ?? null;

        if (! $poll) {
            return;
        }

        if ($poll->expires_at && $poll->expires_at->lt(now())) {
            Notification::make()
                ->title(__('Poll Closed'))
                ->body(__('This poll has closed. Voting is no longer available.'))
                ->warning()
                ->send();

            return;
        }

        if (! $poll->options->contains('id', $optionId)) {
            return;
        }

        $existing = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($existing) {
            return;
        }

        PollVote::create([
            'school_id' => $poll->school_id,
            'poll_id' => $poll->id,
            'option_id' => $optionId,
            'user_id' => auth()->id(),
        ]);

        Notification::make()
            ->title(__('Vote Recorded'))
            ->body(__('Thank you! Your response has been saved.'))
            ->success()
            ->send();
    }

    public function getPollsProperty(): Collection
    {
        return Poll::query()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->with('options.votes')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (Poll $poll) {
                $roles = $poll->target_roles ?? [];

                return empty($roles) || in_array('student', $roles, true);
            })
            ->map(function (Poll $poll) {
                $myVote = PollVote::query()
                    ->where('poll_id', $poll->id)
                    ->where('user_id', auth()->id())
                    ->first();

                return [
                    'poll' => $poll,
                    'myVote' => $myVote,
                    'hasVoted' => $myVote !== null,
                    'totalVotes' => $poll->votes->count(),
                ];
            })
            ->values();
    }

    public function hasVotedForPoll(int $pollId): bool
    {
        return PollVote::query()
            ->where('poll_id', $pollId)
            ->where('user_id', auth()->id())
            ->exists();
    }

    public function getHasVotedProperty(): bool
    {
        return false;
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        return [
            'student' => $student,
            'polls' => $this->polls,
        ];
    }
}
