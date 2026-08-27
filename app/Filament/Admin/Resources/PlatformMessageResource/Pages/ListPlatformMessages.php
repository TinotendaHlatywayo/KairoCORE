<?php

namespace App\Filament\Admin\Resources\PlatformMessageResource\Pages;

use App\Filament\Admin\Resources\PlatformMessageResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\SaaS\Models\PlatformMessage;
use Modules\SaaS\Services\PlatformMessagingService;

class ListPlatformMessages extends ListRecords
{
    protected static string $resource = PlatformMessageResource::class;

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
     * Platform-side reply sent from INSIDE the View Thread modal. Only super
     * admins can land here (resource canAccess), and the parent must exist.
     * The parent id arrives as an action parameter so hydration can never
     * lose it.
     */
    public function sendThreadReply(int $parentId): void
    {
        $actor = Auth::user();
        abort_unless($actor && $actor->school_id === null, 403);

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

        $parent = PlatformMessage::withoutGlobalScopes()->find($parentId);

        if (! $parent) {
            abort(404, 'That conversation no longer exists.');
        }

        app(PlatformMessagingService::class)->replyFromPlatform($actor, $parent, $body);

        $this->threadReplyBody = '';

        Notification::make()
            ->title(__('Reply sent'))
            ->success()
            ->send();

        $this->dispatch('$refresh');
    }
}
