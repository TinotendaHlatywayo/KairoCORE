<?php

namespace Tests\Feature;

use App\Filament\Student\Pages\StudentPolls;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollVote;
use Tests\TestCase;

/**
 * Regression tests for the communications fixes:
 *
 * - Published announcements must not vanish the moment they are created. The
 *   bug: `expires_at` defaulted to "today", so every new notice expired
 *   immediately and only broadcast notifications reached anyone.
 * - Open feedback surveys must collect typed responses and enforce one vote.
 */
class CommunicationRegressionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('app.env', 'local');
        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    private function school(): School
    {
        return School::query()->firstOrFail();
    }

    private function student(): User
    {
        return User::query()
            ->whereNotNull('school_id')
            ->where('requested_role', 'student')
            ->firstOrFail();
    }

    public function test_announcement_without_expiry_remains_active(): void
    {
        DB::beginTransaction();

        try {
            Announcement::create([
                'school_id' => $this->school()->id,
                'title' => 'No expiry',
                'content' => '<p>Hello</p>',
                'status' => 'published',
                'published_at' => now()->subMinutes(5),
                'expires_at' => null,
            ]);

            $visible = Announcement::query()->active()->where('title', 'No expiry')->exists();

            $this->assertTrue($visible, 'A published notice with no expiry must stay visible.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_announcement_with_past_expiry_is_hidden(): void
    {
        DB::beginTransaction();

        try {
            Announcement::create([
                'school_id' => $this->school()->id,
                'title' => 'Expired one',
                'content' => '<p>Bye</p>',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'expires_at' => now()->subDay(),
            ]);

            $visible = Announcement::query()->active()->where('title', 'Expired one')->exists();

            $this->assertFalse($visible, 'An expired notice must be hidden.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_student_open_feedback_survey_stores_written_response(): void
    {
        DB::beginTransaction();

        try {
            $student = $this->student();

            $poll = Poll::create([
                'school_id' => $student->school_id,
                'question' => 'Open feedback',
                'type' => 'survey',
                'is_anonymous' => false,
                'show_results' => true,
                'target_roles' => ['student'],
                'created_by' => $student->id,
            ]);

            $this->assertCount(0, $poll->options, 'A survey must be creatable without any options.');

            $this->actingAs($student);

            $page = new StudentPolls;
            $matches = $page->getPollsProperty()->contains(
                fn (array $entry): bool => $entry['poll']->id === $poll->id
            );
            $this->assertTrue($matches, 'The open survey must appear on the student poll page.');

            $page->responses[$poll->id] = '   The canteen is great   ';
            $page->submitSurvey($poll->id);

            $vote = PollVote::query()
                ->where('poll_id', $poll->id)
                ->where('user_id', $student->id)
                ->first();

            $this->assertNotNull($vote, 'Submitting a survey must record a vote.');
            $this->assertSame('The canteen is great', $vote->written_response);
            $this->assertNull($vote->option_id, 'A survey response must not carry an option.');
            $this->assertSame('', $page->responses[$poll->id], 'The textarea must clear after submission.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_student_cannot_vote_twice_on_same_survey(): void
    {
        DB::beginTransaction();

        try {
            $student = $this->student();

            $poll = Poll::create([
                'school_id' => $student->school_id,
                'question' => 'One vote only',
                'type' => 'survey',
                'is_anonymous' => false,
                'show_results' => true,
                'target_roles' => ['student'],
                'created_by' => $student->id,
            ]);

            $this->actingAs($student);

            $page = new StudentPolls;

            $page->responses[$poll->id] = 'First answer';
            $page->submitSurvey($poll->id);
            $page->responses[$poll->id] = 'Second attempt';
            $page->submitSurvey($poll->id);

            $votes = PollVote::query()
                ->where('poll_id', $poll->id)
                ->where('user_id', $student->id)
                ->get();

            $this->assertCount(1, $votes, 'A student may vote only once per poll.');
            $this->assertSame('First answer', $votes->first()->written_response);
        } finally {
            DB::rollBack();
        }
    }
}
