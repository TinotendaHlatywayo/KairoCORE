<?php

namespace Modules\SaaS\Services;

use App\Models\School;
use App\Models\User;
use App\Notifications\PlatformMessageNotification;
use Illuminate\Support\Facades\DB;
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
     * @param  array<int>  $schoolIds  Explicit target school ids (for 'single'/'selected').
     * @param  array|null  $targetMeta  Snapshot of the criteria used for targeting (for audit).
     */
    public function sendFromPlatform(
        User $actor,
        string $subject,
        string $body,
        string $priority = 'normal',
        string $scope = 'all',
        array $schoolIds = [],
        ?array $targetMeta = null,
    ): PlatformMessage {
        return DB::transaction(function () use ($actor, $subject, $body, $priority, $scope, $schoolIds, $targetMeta) {
            $message = PlatformMessage::create([
                'sender_type' => 'platform',
                'sender_user_id' => $actor->id,
                'school_id' => null,
                'recipient_type' => 'school',
                'recipient_scope' => $scope,
                'target_meta' => $targetMeta,
                'subject' => $subject,
                'body' => $body,
                'priority' => $priority,
            ]);

            $this->createRecipients($message, $schoolIds);

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
    public function replyFromPlatform(User $actor, PlatformMessage $parent, string $body): PlatformMessage
    {
        return DB::transaction(function () use ($actor, $parent, $body) {
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
            ]);

            if ($schoolId) {
                $this->createRecipients($message, [(int) $schoolId]);
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
     */
    protected function createRecipients(PlatformMessage $message, array $schoolIds): void
    {
        $schoolIds = array_values(array_unique(array_filter(array_map('intval', $schoolIds))));

        if (empty($schoolIds)) {
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

        PlatformMessageRecipient::insert($rows);

        $schoolIdChunks = array_chunk($schoolIds, 100);
        foreach ($schoolIdChunks as $chunk) {
            User::query()
                ->whereIn('school_id', $chunk)
                ->get()
                ->each(fn (User $user) => $user->notify(new PlatformMessageNotification($message)));
        }
    }

    protected function notifyPlatformUsers(PlatformMessage $message): void
    {
        User::query()
            ->whereNull('school_id')
            ->get()
            ->each(fn (User $user) => $user->notify(new PlatformMessageNotification($message)));
    }
}
