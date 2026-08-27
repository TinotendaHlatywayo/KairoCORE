<?php

namespace App\Filament\App\Resources\PlatformInboxResource\Pages;

use App\Filament\App\Resources\PlatformInboxResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Services\PlatformMessagingService;

class ListPlatformInboxes extends ListRecords
{
    protected static string $resource = PlatformInboxResource::class;

    // ── Inline thread reply (composer inside the View Thread modal) ──
    public string $threadReplyBody = '';

    public ?int $threadReplyParentId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }

    /**
     * Send a reply from INSIDE the View Thread modal. The parent message is
     * re-validated against the viewer's own conversations, so a crafted
     * parentId can never reach another school's thread.
     */
    public function sendThreadReply(int $parentId): void
    {
        $body = trim($this->threadReplyBody);

        if ($body === '') {
            throw ValidationException::withMessages([
                'threadReplyBody' => __('Reply message cannot be empty.'),
            ]);
        }

        if (mb_strlen($body) > 5000) {
            throw ValidationException::withMessages([
                'threadReplyBody' => __('Reply must not exceed 5000 characters.'),
            ]);
        }

        $schoolId = Auth::user()?->school_id;
        abort_unless($schoolId !== null, 403);

        // The parent MUST be part of this school's own conversations: either
        // a message the school sent or one delivered to it.
        $receivedIds = PlatformMessage::query()
            ->withoutGlobalScopes()
            ->whereIn('id', function ($query) use ($schoolId) {
                $query->select('message_id')
                    ->from('platform_message_recipients')
                    ->where('school_id', $schoolId);
            })
            ->pluck('id');

        $parent = PlatformMessage::withoutGlobalScopes()
            ->whereKey($parentId)
            ->where(function ($q) use ($schoolId, $receivedIds) {
                $q->where(fn ($q2) => $q2->where('sender_type', 'school')->where('school_id', $schoolId))
                    ->orWhere(fn ($q2) => $q2->where('sender_type', 'platform')->whereIn('id', $receivedIds));
            })
            ->first();

        if (! $parent) {
            abort(403, 'That conversation does not belong to your school.');
        }

        app(PlatformMessagingService::class)->replyFromSchool(
            Auth::user(),
            $parent,
            $body,
        );

        $this->threadReplyBody = '';

        Notification::make()
            ->title(__('Reply sent to Kairo CORE'))
            ->success()
            ->send();

        // Refresh the modal content so the new bubble appears immediately.
        $this->dispatch('$refresh');
    }
}
