<?php

namespace Modules\Communication\Services;

use App\Models\User;
use App\Notifications\PlatformMessageNotification;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Modules\Communication\Models\ChatMessage;
use Modules\Communication\Models\ChatParticipant;
use Modules\Communication\Models\ChatThread;
use Modules\SaaS\Models\PlatformMessage;

/**
 * Bridges the two messaging worlds.
 *
 * A platform (KairoCORE) message aimed at specific users lives in the
 * platform_messages inbox, which the student portal never renders. That is why
 * a message sent to a student "does not show". This service mirrors a
 * user-targeted platform message into a private one-to-one chat thread for that
 * student (and mirrors replies in both directions), so the student sees it in
 * their portal Chat and can answer it — without any other user ever seeing it.
 */
class PlatformChatBridge
{
    public static function mirrorPlatformMessage(PlatformMessage $message, User $recipient): void
    {
        if (! $message->sender_user_id || ! $recipient->school_id) {
            return;
        }

        DB::transaction(function () use ($message, $recipient) {
            $schoolId = (int) $recipient->school_id;

            $thread = self::findThread($schoolId, $message->thread_id);

            if (! $thread) {
                $thread = ChatThread::withoutTenantScope()->create([
                    'school_id' => $schoolId,
                    'type' => 'one_to_one',
                    'name' => null,
                    'metadata' => ['platform_thread_id' => $message->thread_id],
                ]);
            }

            if (! self::isParticipant($thread->id, $message->sender_user_id, $schoolId)) {
                self::addParticipant($thread->id, $message->sender_user_id, $schoolId);
            }

            if (! self::isParticipant($thread->id, $recipient->id, $schoolId)) {
                self::addParticipant($thread->id, $recipient->id, $schoolId);
            }

            ChatMessage::withoutTenantScope()->create([
                'school_id' => $schoolId,
                'thread_id' => $thread->id,
                'sender_id' => $message->sender_user_id,
                'message' => (string) $message->body,
                'attachments' => null,
                'reactions' => null,
            ]);

            if ($recipient->isStudent()) {
                Notification::make()
                    ->title(__('New Message from KairoCORE'))
                    ->body(mb_strimwidth((string) $message->body, 0, 120, '…'))
                    ->sendToDatabase($recipient);
            }
        });
    }

    public static function mirrorChatReplyToPlatform(ChatMessage $chatMessage): void
    {
        $thread = ChatThread::withoutTenantScope()->find($chatMessage->thread_id);
        $platformThreadId = $thread?->metadata['platform_thread_id'] ?? null;

        if (! $platformThreadId || ! $thread?->school_id) {
            return;
        }

        $platformMessage = PlatformMessage::create([
            'sender_type' => 'school',
            'sender_user_id' => $chatMessage->sender_id,
            'school_id' => $thread->school_id,
            'recipient_type' => 'platform',
            'recipient_scope' => 'users',
            'thread_id' => $platformThreadId,
            'target_meta' => ['user_ids' => []] + ($thread->metadata ?? []),
            'subject' => __('Re: KairoCORE Message'),
            'body' => (string) $chatMessage->message,
            'priority' => 'normal',
            'channel' => 'platform_message',
        ]);

        User::query()
            ->whereNull('school_id')
            ->get()
            ->each(fn (User $user) => $user->notify(new PlatformMessageNotification($platformMessage)));
    }

    public static function mirrorPlatformReplyToChat(PlatformMessage $reply, int $schoolId): void
    {
        $thread = self::findThread($schoolId, $reply->thread_id);

        if (! $thread || ! $reply->sender_user_id) {
            return;
        }

        ChatMessage::withoutTenantScope()->create([
            'school_id' => $schoolId,
            'thread_id' => $thread->id,
            'sender_id' => $reply->sender_user_id,
            'message' => (string) $reply->body,
            'attachments' => null,
            'reactions' => null,
        ]);

        foreach ($thread->users()->where('users.id', '!=', $reply->sender_user_id)->get() as $recipient) {
            Notification::make()
                ->title(__('New Message from KairoCORE'))
                ->body(mb_strimwidth((string) $reply->body, 0, 120, '…'))
                ->sendToDatabase($recipient);
        }
    }

    private static function findThread(int $schoolId, string $platformThreadId): ?ChatThread
    {
        return ChatThread::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('metadata->platform_thread_id', $platformThreadId)
            ->first();
    }

    private static function isParticipant(int $threadId, int $userId, int $schoolId): bool
    {
        return ChatParticipant::withoutTenantScope()
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->where('school_id', $schoolId)
            ->exists();
    }

    private static function addParticipant(int $threadId, int $userId, int $schoolId): void
    {
        $participant = ChatParticipant::create([
            'school_id' => $schoolId,
            'thread_id' => $threadId,
            'user_id' => $userId,
            'last_read_at' => null,
            'is_muted' => false,
        ]);

        // The participant's `creating` hook overwrites school_id from the
        // current tenant/auth, which is null in the platform panel. Set it
        // explicitly so the row stays inside the student's school.
        if ((int) $participant->school_id !== $schoolId) {
            $participant->forceFill(['school_id' => $schoolId])->save();
        }
    }
}
