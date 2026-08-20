<?php

namespace Modules\Communication\Livewire;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Communication\Models\ChatMessage;
use Modules\Communication\Models\ChatParticipant;
use Modules\Communication\Models\ChatThread;

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
        if ($record && $record->exists) {
            $this->activeThreadId = $record->id;
        } else {
            $firstThread = ChatThread::first();
            $this->activeThreadId = $firstThread ? $firstThread->id : null;
        }

        $this->checkMuteStatus();
    }

    public function selectThread(int $threadId): void
    {
        $this->activeThreadId = $threadId;
        $this->messageText = '';
        $this->checkMuteStatus();
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

        $unmutedParticipants = ChatParticipant::where('thread_id', $thread->id)
            ->where('is_muted', false)
            ->where('user_id', '!=', Auth::id())
            ->with(['user'])
            ->get();

        foreach ($unmutedParticipants as $participant) {
            if ($participant->user) {
                Notification::make()
                    ->title("New Message in {$thread->name}")
                    ->body(Auth::user()->name.': '.$this->messageText)
                    ->sendToDatabase($participant->user);
            }
        }

        $this->messageText = '';
        $this->attachment = null;

        $this->dispatch('refreshChat');
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;

        $threads = ChatThread::where('school_id', $schoolId)
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->withCount('users')
            ->get();

        $activeThread = $this->activeThreadId
            ? ChatThread::with(['messages.sender', 'users'])->find($this->activeThreadId)
            : null;

        return view('modules.communication.chat-workspace', [
            'threads' => $threads,
            'activeThread' => $activeThread,
        ]);
    }
}
