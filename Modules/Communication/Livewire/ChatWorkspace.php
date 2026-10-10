<?php

namespace Modules\Communication\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Communication\Models\ChatMessage;
use Modules\Communication\Models\ChatParticipant;
use Modules\Communication\Models\ChatThread;
use Modules\Communication\Services\PlatformChatBridge;

class ChatWorkspace extends Component
{
    use WithFileUploads;

    public ?int $activeThreadId = null;

    public string $messageText = '';

    /**
     * @var mixed
     */
    public $attachment = null;

    public bool $isMuted = false;

    protected $listeners = ['refreshChat' => '$refresh'];

    public function mount(?ChatThread $record = null): void
    {
        if ($record && $record->exists && $this->isParticipantThread($record->id)) {
            $this->activeThreadId = $record->id;
        } else {
            $this->activeThreadId = $this->firstOwnThreadId();
        }

        $this->checkMuteStatus();
    }

    public function selectThread(int $threadId): void
    {
        if (! $this->isParticipantThread($threadId)) {
            return;
        }

        $this->activeThreadId = $threadId;
        $this->messageText = '';
        $this->checkMuteStatus();
    }

    public function isParticipantThread(int $threadId): bool
    {
        $userId = Auth::id();
        $schoolId = Auth::user()->school_id;

        if (! $userId || ! $schoolId) {
            return false;
        }

        return ChatParticipant::where('school_id', $schoolId)
            ->where('thread_id', $threadId)
            ->where('user_id', $userId)
            ->exists();
    }

    public function firstOwnThreadId(): ?int
    {
        $userId = Auth::id();

        return ChatThread::whereHas('users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        })
            ->withExists(['messages as has_messages'])
            ->orderByDesc('has_messages')
            ->orderByDesc('updated_at')
            ->value('id');
    }

    public function checkMuteStatus(): void
    {
        if ($this->activeThreadId) {
            $participant = ChatParticipant::where('school_id', Auth::user()->school_id)
                ->where('thread_id', $this->activeThreadId)
                ->where('user_id', Auth::id())
                ->first();

            $this->isMuted = $participant ? (bool) $participant->is_muted : false;
        }
    }

    public function toggleMute(): void
    {
        if ($this->activeThreadId) {
            $participant = ChatParticipant::where('school_id', Auth::user()->school_id)
                ->where('thread_id', $this->activeThreadId)
                ->where('user_id', Auth::id())
                ->first();

            if ($participant) {
                $participant->update(['is_muted' => ! $participant->is_muted]);
                $this->isMuted = (bool) $participant->is_muted;

                Notification::make()
                    ->title($this->isMuted ? 'Notifications Muted' : 'Notifications Restored')
                    ->success()
                    ->send();
            }
        }
    }

    public function sendMessage(): void
    {
        if (empty(trim($this->messageText)) && ! $this->attachment) {
            return;
        }

        if (! $this->isParticipantThread((int) $this->activeThreadId)) {
            return;
        }

        $thread = ChatThread::findOrFail($this->activeThreadId);

        $attachmentPath = null;
        if ($this->attachment) {
            $attachmentPath = $this->attachment->store('communication/chats', 'public');
        }

        $message = ChatMessage::create([
            'school_id' => $thread->school_id,
            'thread_id' => $thread->id,
            'sender_id' => Auth::id(),
            'message' => $this->messageText,
            'attachments' => $attachmentPath ? [$attachmentPath] : null,
        ]);

        $participants = ChatParticipant::where('thread_id', $thread->id)
            ->where('user_id', '!=', Auth::id())
            ->with(['user'])
            ->get();

        foreach ($participants as $participant) {
            if ($participant->user) {
                Notification::make()
                    ->title(__('New Message from ').Auth::user()->name)
                    ->body($this->messageText)
                    ->sendToDatabase($participant->user);
            }
        }

        // If this thread was started from the platform inbox (KairoCORE), push
        // the reply back so the communicating platform user sees it too.
        PlatformChatBridge::mirrorChatReplyToPlatform($message);

        $this->messageText = '';
        $this->attachment = null;

        $this->dispatch('refreshChat');
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        $userId = Auth::id();

        $threads = ChatThread::where('school_id', $schoolId)
            ->whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->withCount('users')
            ->get();

        $activeThread = $this->activeThreadId
            ? ChatThread::whereHas('users', function ($q) use ($userId) {
                $q->where('users.id', $userId);
            })->with(['messages.sender', 'users'])->find($this->activeThreadId)
            : null;

        return view('modules.communication.chat-workspace', [
            'threads' => $threads,
            'activeThread' => $activeThread,
        ]);
    }
}
