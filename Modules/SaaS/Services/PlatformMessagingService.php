<?php

namespace Modules\SaaS\Services;

use App\Mail\SaaS\PlatformMessageMail;
use App\Models\School;
use App\Models\User;
use App\Notifications\PlatformMessageNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Models\PlatformMessageRecipient;

/**
 * Central service for all platform<->tenant messaging.
 *
 * Handles targeting (all / selected / single tenant), reply threading,
 * recipient tracking, notification dispatch and idempotent delivery.
 */
class PlatformMessagingService
{
    /**
     * Platform (super admin) -> tenant(s).
     *
     * @param  User|null  $actor  The sending admin, or NULL for system messages
     *                            (e.g. automated billing notices).
     * @param  array<int>  $schoolIds  Explicit target school ids (for 'single'/'selected').
     * @param  array|null  $targetMeta  Snapshot of the criteria used for targeting (for audit).
     */
    public function sendFromPlatform(
        ?User $actor,
        string $subject,
        string $body,
        string $priority = 'normal',
        string $scope = 'all',
        array $schoolIds = [],
        ?array $targetMeta = null,
        string $channel = 'platform_message',
        array $userIds = [],
    ): PlatformMessage {
        return DB::transaction(function () use ($actor, $subject, $body, $priority, $scope, $schoolIds, $targetMeta, $channel, $userIds) {
            $message = PlatformMessage::create([
                'sender_type' => 'platform',
                'sender_user_id' => $actor?->id,
                'school_id' => null,
                'recipient_type' => 'school',
                'recipient_scope' => $scope,
                'target_meta' => $targetMeta,
                'subject' => $subject,
                'body' => $body,
                'priority' => $priority,
                'channel' => $channel,
            ]);

            $this->createRecipients($message, $schoolIds, $channel, $userIds);

            return $message;
        });
    }

    /**
     * Tenant (permitted school user) -> platform super admin.
     */
    public function sendFromSchool(
        User $actor,
        string $subject,
        string $body,
        string $priority = 'normal',
    ): PlatformMessage {
        return DB::transaction(function () use ($actor, $subject, $body, $priority) {
            $message = PlatformMessage::create([
                'sender_type' => 'school',
                'sender_user_id' => $actor->id,
                'school_id' => $actor->school_id,
                'recipient_type' => 'platform',
                'recipient_scope' => 'single',
                'subject' => $subject,
                'body' => $body,
                'priority' => $priority,
            ]);

            $this->notifyPlatformUsers($message);

            return $message;
        });
    }

    /**
     * Reply to an existing conversation thread, sent by the platform to the school.
     */
    public function replyFromPlatform(User $actor, PlatformMessage $parent, string $body, string $channel = 'platform_message'): PlatformMessage
    {
        return DB::transaction(function () use ($actor, $parent, $body, $channel) {
            // Resolve WHO this thread belongs to. The parent itself may be a
            // platform-originated message (school_id = NULL — e.g. replying
            // from your own outbox), so fall back to any sibling message in
            // the thread, then to recipient tracking rows.
            $schoolId = $parent->school_id
                ?? PlatformMessage::withoutGlobalScopes()
                    ->where('thread_id', $parent->thread_id)
                    ->whereNotNull('school_id')
                    ->value('school_id');

            if (! $schoolId) {
                $schoolId = PlatformMessageRecipient::query()
                    ->whereIn('message_id', function ($q) use ($parent) {
                        $q->select('id')
                            ->from((new PlatformMessage)->getTable())
                            ->where('thread_id', $parent->thread_id);
                    })
                    ->orderByDesc('school_id')
                    ->value('school_id');
            }

            $message = PlatformMessage::create([
                'sender_type' => 'platform',
                'sender_user_id' => $actor->id,
                'school_id' => null,
                'recipient_type' => 'school',
                'recipient_scope' => 'single',
                'thread_id' => $parent->thread_id,
                'subject' => 'Re: '.($parent->subject ?? 'Conversation'),
                'body' => $body,
                'priority' => $parent->priority,
                'channel' => $channel,
            ]);

            if ($schoolId) {
                $this->createRecipients($message, [(int) $schoolId], $channel);
            }

            return $message;
        });
    }

    /**
     * Reply to an existing conversation thread, sent by the school to the platform.
     */
    public function replyFromSchool(User $actor, PlatformMessage $parent, string $body): PlatformMessage
    {
        return DB::transaction(function () use ($actor, $parent, $body) {
            $message = PlatformMessage::create([
                'sender_type' => 'school',
                'sender_user_id' => $actor->id,
                'school_id' => $actor->school_id,
                'recipient_type' => 'platform',
                'recipient_scope' => 'single',
                'thread_id' => $parent->thread_id,
                'subject' => 'Re: '.($parent->subject ?? 'Conversation'),
                'body' => $body,
                'priority' => $parent->priority,
            ]);

            $this->notifyPlatformUsers($message);

            return $message;
        });
    }

    public function markPlatformMessageRead(PlatformMessage $message, User $actor): void
    {
        if (! $message->isToPlatform() && ! $message->isRead) {
            $message->update(['is_read' => true, 'read_at' => now()]);
        }
    }

    /**
     * Creates delivery/read-tracking rows for every target school and notifies
     * each school's users. Bulk insert makes broadcast delivery idempotent and fast.
     *
     * When $userIds is non-empty the message is targeted at those specific
     * users only: tracking rows are still recorded per school (so the tenant
     * inbox threads correctly) but only the selected users are notified.
     *
     * @param  array<int>  $userIds
     */
    protected function createRecipients(PlatformMessage $message, array $schoolIds, string $channel = 'platform_message', array $userIds = []): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));

        $targetUsers = collect();
        if (! empty($userIds)) {
            $targetUsers = User::withoutTenantScope()
                ->whereIn('id', $userIds)
                ->get(['id', 'school_id', 'name', 'email']);

            foreach ($targetUsers as $user) {
                if ($user->school_id) {
                    $schoolIds[] = (int) $user->school_id;
                }
            }
        }

        $schoolIds = array_values(array_unique(array_filter(array_map('intval', $schoolIds))));

        if (empty($schoolIds) && $targetUsers->isEmpty()) {
            return;
        }

        $rows = [];
        foreach ($schoolIds as $schoolId) {
            $rows[] = [
                'message_id' => $message->id,
                'school_id' => $schoolId,
                'status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            PlatformMessageRecipient::insert($rows);
        }

        if (in_array($channel, ['platform_message', 'both'], true)) {
            if ($targetUsers->isNotEmpty()) {
                $targetUsers->each(fn (User $user) => $user->notify(new PlatformMessageNotification($message)));
            } else {
                $schoolIdChunks = array_chunk($schoolIds, 100);
                foreach ($schoolIdChunks as $chunk) {
                    User::query()
                        ->whereIn('school_id', $chunk)
                        ->get()
                        ->each(fn (User $user) => $user->notify(new PlatformMessageNotification($message)));
                }
            }
        }

        if (in_array($channel, ['email', 'both'], true)) {
            if ($targetUsers->isNotEmpty()) {
                $this->emailUsers($message, $targetUsers);
            } else {
                $this->emailSchools($message, $schoolIds);
            }
        }
    }

    /**
     * Emails a platform message directly to the selected tenant users.
     *
     * @param  Collection<int, User>  $users
     */
    protected function emailUsers(PlatformMessage $message, $users): void
    {
        foreach ($users as $user) {
            if (blank($user->email)) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new PlatformMessageMail(
                    (string) ($message->subject ?? ''),
                    (string) ($message->body ?? ''),
                    (string) ($message->school?->name ?? ''),
                ));
            } catch (\Throwable $e) {
                Log::warning('Platform message email to user failed', [
                    'user_id' => $user->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Emails a platform message to the address that registered each target
     * school and records the delivery outcome on the message row.
     *
     * @param  array<int>  $schoolIds
     */
    protected function emailSchools(PlatformMessage $message, array $schoolIds): void
    {
        $schools = School::query()
            ->whereIn('id', $schoolIds)
            ->get(['id', 'name', 'email_address']);

        $sent = 0;
        $errors = [];

        foreach ($schools as $school) {
            $recipient = $school->email_address;

            if (! $recipient) {
                continue;
            }

            try {
                Mail::to($recipient)->send(new PlatformMessageMail(
                    (string) ($message->subject ?? ''),
                    (string) ($message->body ?? ''),
                    (string) $school->name,
                ));
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = "{$school->name}: {$e->getMessage()}";
                Log::warning('Platform message email failed', [
                    'school_id' => $school->id,
                    'message_id' => $message->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message->forceFill([
            'email_sent_at' => $sent > 0 ? now() : null,
            'email_error' => $errors === [] ? null : implode("\n", $errors),
        ])->save();
    }

    protected function notifyPlatformUsers(PlatformMessage $message): void
    {
        $inbox = platform_tenant_message_email();

        try {
            Mail::to($inbox)->send(new PlatformMessageMail(
                subjectLine: (string) ($message->subject ?? 'New message from tenant'),
                messageBody: (string) ($message->body ?? ''),
                schoolName: (string) ($message->school?->name ?? 'Tenant'),
            ));
        } catch (\Throwable $e) {
            Log::warning('Platform super admin message email notification failed: '.$e->getMessage());
        }

        // In-app notification center: every platform super admin sees the
        // tenant's message in their notification centre.
        User::query()
            ->whereNull('school_id')
            ->get()
            ->each(fn (User $user) => $user->notify(new PlatformMessageNotification($message)));
    }
}
