<?php

namespace Tests\Feature;

use App\Filament\App\Resources\AnnouncementResource;
use App\Filament\App\Resources\PollResource\Pages\CreatePoll;
use App\Filament\App\Resources\PollResource\Pages\EditPoll;
use App\Filament\App\Resources\PollResource\Pages\ViewPoll;
use App\Filament\Student\Pages\StudentPolls;
use App\Mail\AnnouncementPublishedMail;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Admin\Models\CustomRole;
use Modules\Communication\Models\Announcement;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollOption;
use Modules\Communication\Models\PollVote;
use Tests\TestCase;

/**
 * End-to-end verification of the polls & surveys requirements:
 *
 *  - An Open Feedback Survey needs no Choice Parameters at all.
 *  - A quick multi-choice poll still insists on at least two choices.
 *  - Results are private by default (creator/school admins only) and are only
 *    shown to participants when the creator enables "show results".
 *  - Editing a poll re-sends it and clears answers cast against changed content.
 *  - The results screen renders real tallies, and anonymous polls hide names.
 *  - Announcements broadcast over the email channel never blow up (the 500
 *    regression) and still write in-app notifications.
 */
class PollCreationAndResultsTest extends TestCase
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

        Mail::fake();
    }

    private function school(): School
    {
        $school = School::create([
            'name' => 'Poll Test High',
            'subdomain' => 'polltest'.Str::random(6),
            'status' => 'active',
        ]);

        App::instance('current_tenant', $school);

        return $school;
    }

    private function user(School $school, string $role, string $roleKey, bool $admin = false): User
    {
        $roleModel = null;

        if ($admin) {
            $roleModel = CustomRole::where('school_id', $school->id)->where('role_key', $roleKey)->first()
                ?? CustomRole::create([
                    'school_id' => $school->id,
                    'name' => $roleKey,
                    'role_key' => $roleKey,
                    'permissions' => ['*'],
                    'is_system' => true,
                ]);
        }

        return User::create([
            'school_id' => $school->id,
            'name' => $role.' '.Str::random(5),
            'email' => Str::random(10).'@polltest.test',
            'password' => Hash::make('Password@1'),
            'account_status' => User::STATUS_ACTIVE,
            'requested_role' => $role,
            'custom_role_id' => $roleModel?->id,
        ]);
    }

    public function test_open_feedback_survey_needs_no_choice_parameters(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $admin = $this->user($school, 'administrator', 'administrator', admin: true);

            Livewire::actingAs($admin)
                ->test(CreatePoll::class)
                ->fillForm([
                    'question' => 'How is the term going?',
                    'type' => 'survey',
                    'description' => 'Free text welcome',
                    'expires_at' => now()->addDays(7)->format('Y-m-d H:i'),
                    'options' => [],
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $poll = Poll::withoutTenantScope()
                ->where('question', 'How is the term going?')
                ->where('school_id', $school->id)
                ->first();

            $this->assertNotNull($poll, 'The survey must be created.');
            $this->assertSame('survey', $poll->type);
            $this->assertCount(0, $poll->options, 'A survey must not require any predefined choices.');
            $this->assertFalse((bool) $poll->show_results, 'Results must be private unless the creator opts in.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_quick_multi_choice_poll_still_needs_at_least_two_choices(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $admin = $this->user($school, 'administrator', 'administrator', admin: true);

            Livewire::actingAs($admin)
                ->test(CreatePoll::class)
                ->fillForm([
                    'question' => 'Lunch menu vote',
                    'type' => 'poll',
                    'expires_at' => now()->addDays(3)->format('Y-m-d H:i'),
                    'options' => [],
                ])
                ->call('create')
                ->assertHasFormErrors(['options']);

            $this->assertSame(0, Poll::withoutTenantScope()->where('school_id', $school->id)->count());
        } finally {
            DB::rollBack();
        }
    }

    public function test_results_are_private_to_participants_until_the_creator_opt_in(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $student = $this->user($school, 'student', 'student');
            $poll = Poll::create([
                'school_id' => $school->id,
                'question' => 'Favorite sport',
                'type' => 'poll',
                'show_results' => false,
                'target_roles' => ['student'],
                'expires_at' => now()->addDays(3),
            ]);

            $option = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Soccer']);
            PollVote::create([
                'school_id' => $school->id,
                'poll_id' => $poll->id,
                'user_id' => $student->id,
                'option_id' => $option->id,
            ]);

            $this->actingAs($student);
            $page = new StudentPolls;
            $entry = $page->getPollsProperty()->firstWhere(fn (array $e): bool => $e['poll']->id === $poll->id);

            $this->assertTrue($entry['hasVoted']);
            $this->assertFalse((bool) $entry['poll']->show_results);

            // The blade computes $canSeeResults = (hasVoted || isClosed) && show_results.
            $canSeeResults = ($entry['hasVoted'] || $entry['poll']->expires_at?->isPast() === true) && $entry['poll']->show_results;
            $this->assertFalse($canSeeResults, 'Private results must not leak to a participant.');

            $poll->update(['show_results' => true]);

            $page = new StudentPolls;
            $entry = $page->getPollsProperty()->firstWhere(fn (array $e): bool => $e['poll']->id === $poll->id);
            $canSeeResults = ($entry['hasVoted'] || $entry['poll']->expires_at?->isPast() === true) && $entry['poll']->show_results;
            $this->assertTrue($canSeeResults, 'When the creator opts in, participants must see the tallies.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_results_reset_when_the_edit_changes_content_and_resend_happens(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $creator = $this->user($school, 'administrator', 'administrator', admin: true);
            $voter = $this->user($school, 'teaching_staff', 'teaching_staff');

            $poll = Poll::create([
                'school_id' => $school->id,
                'question' => 'Original question',
                'type' => 'poll',
                'show_results' => false,
                'target_roles' => ['teaching_staff'],
                'expires_at' => now()->addDays(3),
            ]);

            $optionA = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Option A']);
            $optionB = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Option B']);

            PollVote::create([
                'school_id' => $school->id,
                'poll_id' => $poll->id,
                'user_id' => $voter->id,
                'option_id' => $optionA->id,
            ]);

            $page = Livewire::actingAs($creator)
                ->test(EditPoll::class, ['record' => $poll->getRouteKey()]);

            $page->fillForm([
                'question' => 'Reworded question',
                'type' => 'poll',
                'is_anonymous' => false,
                'show_results' => false,
                'expires_at' => now()->addDays(3)->format('Y-m-d H:i'),
                'options' => [
                    "record-{$optionA->id}" => ['id' => $optionA->id, 'option_value' => 'Option A'],
                    "record-{$optionB->id}" => ['id' => $optionB->id, 'option_value' => 'Option B'],
                ],
            ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('Reworded question', $poll->fresh()->question);
            $this->assertSame(0, PollVote::where('poll_id', $poll->id)->count(),
                'Answers cast against the old question must be cleared on edit.');

            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $voter->id,
                'notifiable_type' => User::class,
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }

    public function test_incidental_edit_keeps_existing_responses(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $creator = $this->user($school, 'administrator', 'administrator', admin: true);
            $voter = $this->user($school, 'teaching_staff', 'teaching_staff');

            $poll = Poll::create([
                'school_id' => $school->id,
                'question' => 'Stable question',
                'type' => 'poll',
                'show_results' => false,
                'target_roles' => ['teaching_staff'],
                'expires_at' => now()->addDays(3),
            ]);

            $optionA = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Option A']);
            $optionB = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Option B']);

            PollVote::create([
                'school_id' => $school->id,
                'poll_id' => $poll->id,
                'user_id' => $voter->id,
                'option_id' => $optionA->id,
            ]);

            $page = Livewire::actingAs($creator)
                ->test(EditPoll::class, ['record' => $poll->getRouteKey()]);

            $page->fillForm([
                'question' => 'Stable question',
                'type' => 'poll',
                'is_anonymous' => false,
                'show_results' => true,
                'expires_at' => now()->addDays(7)->format('Y-m-d H:i'),
                'options' => [
                    "record-{$optionA->id}" => ['id' => $optionA->id, 'option_value' => 'Option A'],
                    "record-{$optionB->id}" => ['id' => $optionB->id, 'option_value' => 'Option B'],
                ],
            ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame(1, PollVote::where('poll_id', $poll->id)->count(),
                'Touching only the expiry date must never wipe existing responses.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_creator_sees_live_results_with_actual_tallies(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $creator = $this->user($school, 'administrator', 'administrator', admin: true);
            $studentA = $this->user($school, 'student', 'student');
            $studentB = $this->user($school, 'student', 'student');

            $poll = Poll::create([
                'school_id' => $school->id,
                'question' => 'Class size preference',
                'type' => 'poll',
                'is_anonymous' => false,
                'show_results' => false,
                'target_roles' => ['student'],
                'expires_at' => now()->addDays(2),
                'created_by' => $creator->id,
            ]);

            $optionA = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Small groups']);
            $optionB = PollOption::create(['poll_id' => $poll->id, 'option_value' => 'Whole class']);

            PollVote::create(['school_id' => $school->id, 'poll_id' => $poll->id, 'user_id' => $studentA->id, 'option_id' => $optionA->id]);
            PollVote::create(['school_id' => $school->id, 'poll_id' => $poll->id, 'user_id' => $studentB->id, 'option_id' => $optionA->id]);

            Livewire::actingAs($creator)
                ->test(ViewPoll::class, ['record' => $poll->getRouteKey()])
                ->assertSee('Live Voting Results')
                ->assertSee('Small groups')
                ->assertSee('Whole class')
                ->assertSee('Total responses:');
        } finally {
            DB::rollBack();
        }
    }

    public function test_anonymous_poll_hides_respondent_names_but_shows_tallies(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $respondent = $this->user($school, 'student', 'student');

            $anonymous = Poll::create([
                'school_id' => $school->id,
                'question' => 'Anonymous opinion',
                'type' => 'survey',
                'is_anonymous' => true,
                'show_results' => false,
                'target_roles' => ['student'],
                'expires_at' => now()->addDays(2),
            ]);

            PollVote::create([
                'school_id' => $school->id,
                'poll_id' => $anonymous->id,
                'user_id' => $respondent->id,
                'written_response' => 'The noise level in the lab is too high.',
            ]);

            $html = view('filament.app.resources.poll-results', ['poll' => $anonymous])->render();

            $this->assertStringContainsString('The noise level in the lab is too high.', $html);
            $this->assertStringNotContainsString($respondent->name, $html, 'Anonymous polls must never reveal who answered.');

            $named = Poll::create([
                'school_id' => $school->id,
                'question' => 'Named opinion',
                'type' => 'survey',
                'is_anonymous' => false,
                'show_results' => false,
                'target_roles' => ['student'],
                'expires_at' => now()->addDays(2),
            ]);

            PollVote::create([
                'school_id' => $school->id,
                'poll_id' => $named->id,
                'user_id' => $respondent->id,
                'written_response' => 'Lunch portions are generous.',
            ]);

            $html = view('filament.app.resources.poll-results', ['poll' => $named])->render();

            $this->assertStringContainsString($respondent->name, $html, 'Named polls must attribute answers so creators can follow up.');
        } finally {
            DB::rollBack();
        }
    }

    public function test_announcement_broadcast_over_email_channel_never_500s(): void
    {
        DB::beginTransaction();

        try {
            $school = $this->school();
            $emailRecipient = $this->user($school, 'teaching_staff', 'teaching_staff');

            $notice = Announcement::create([
                'school_id' => $school->id,
                'title' => 'Exam venues',
                'content' => '<p>Candidates report to Hall B.</p>',
                'status' => 'published',
                'published_at' => now(),
                'expires_at' => null,
                'visibility' => ['teaching_staff'],
                'channel' => 'both',
            ]);

            AnnouncementResource::broadcastToAudience($notice);

            Mail::assertSent(AnnouncementPublishedMail::class, fn ($mail) => $mail->hasTo($emailRecipient->email));

            $this->assertDatabaseHas('notifications', [
                'notifiable_id' => $emailRecipient->id,
                'notifiable_type' => User::class,
            ], 'mysql');
        } finally {
            DB::rollBack();
        }
    }
}
