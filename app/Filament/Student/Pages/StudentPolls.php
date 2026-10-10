<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollVote;

/**
 * Student-facing Polls & Surveys.
 *
 * Shows every open poll/survey targeted at students (or everyone). A student
 * may cast exactly one vote per poll. Open feedback surveys collect a typed
 * answer; multi-choice polls record one chosen option. Live percentage
 * standings are shown after voting (or once closed) only when the creator
 * enabled "show results" for participants.
 */
class StudentPolls extends Page
{
    protected static string $view = 'filament.student.pages.student-polls';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?string $navigationLabel = 'Polls & Surveys';

    protected static ?string $title = 'Polls & Surveys';

    protected static ?string $slug = 'polls-surveys';

    public array $responses = [];

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
            $this->sendClosedNotification();

            return;
        }

        if (! $poll->options->contains('id', $optionId)) {
            return;
        }

        $this->recordVote($poll, ['option_id' => $optionId]);
    }

    public function submitSurvey(int $pollId): void
    {
        $poll = $this->polls->firstWhere(fn (array $entry) => $entry['poll']->id === $pollId)['poll'] ?? null;

        if (! $poll || $poll->type !== 'survey') {
            return;
        }

        if ($poll->expires_at && $poll->expires_at->lt(now())) {
            $this->sendClosedNotification();

            return;
        }

        $response = trim((string) ($this->responses[$pollId] ?? ''));

        if ($response === '') {
            throw ValidationException::withMessages([
                "responses.{$pollId}" => __('Please type your response before submitting.'),
            ]);
        }

        $this->recordVote($poll, ['written_response' => $response]);
        $this->responses[$pollId] = '';
    }

    private function recordVote(Poll $poll, array $payload): void
    {
        $existing = PollVote::query()
            ->where('poll_id', $poll->id)
            ->where('user_id', auth()->id())
            ->exists();

        if ($existing) {
            return;
        }

        PollVote::create(array_merge([
            'school_id' => $poll->school_id,
            'poll_id' => $poll->id,
            'user_id' => auth()->id(),
        ], $payload));

        Notification::make()
            ->title(__('Response Recorded'))
            ->body(__('Thank you! Your response has been saved.'))
            ->success()
            ->send();
    }

    private function sendClosedNotification(): void
    {
        Notification::make()
            ->title(__('Poll Closed'))
            ->body(__('This poll has closed. Voting is no longer available.'))
            ->warning()
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
