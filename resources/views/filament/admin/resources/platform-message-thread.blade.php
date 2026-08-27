{{-- WhatsApp/Gmail-style conversation thread used by BOTH the platform
     console (Tenant Messages) and the tenant inbox (Platform Messages).

     $viewerSchoolId: when set (tenant inbox), messages from OTHER schools in
     a broadcast thread are hidden so each school only sees its own side of
     the conversation. NULL on the platform side = show everything. --}}
@php
    $viewerSchoolId = $viewerSchoolId ?? null;
    $visibleMessages = $messages->filter(function ($msg) use ($viewerSchoolId) {
        if ($viewerSchoolId === null) {
            return true;
        }

        if ($msg->sender_type === 'school') {
            return (int) $msg->school_id === (int) $viewerSchoolId;
        }

        return true;
    })->values();
@endphp

<div class="flex flex-col h-[540px] bg-white dark:bg-slate-950 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-xl">

    <!-- Chat Header Bar -->
    <div class="px-5 py-3.5 bg-slate-900 text-white dark:bg-slate-900 border-b border-slate-800 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-emerald-500/20 text-emerald-400 font-bold flex items-center justify-center text-base ring-2 ring-emerald-500/30 shadow-sm">
                💬
            </div>
            <div>
                <h4 class="text-xs font-bold text-white dark:text-white flex items-center gap-2 tracking-wide">
                    <span>{{ __('Conversation Thread') }}</span>
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                </h4>
                <p class="text-[10px] font-medium text-slate-300 dark:text-slate-300">
                    {{ $visibleMessages->count() }} {{ __('messages in thread') }}
                    @if($viewerSchoolId !== null)
                        · {{ __('Secure channel with') }} {{ platform_name() }}
                    @endif
                </p>
            </div>
        </div>
        <div class="text-[10px] font-bold px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
            {{ __('Encrypted') }}
        </div>
    </div>

    <!-- Message Bubbles Scroll Area -->
    <div x-data x-init="$nextTick(() => { const c = document.getElementById('chat-messages-container'); if (c) { c.scrollTop = c.scrollHeight; } })"
         id="chat-messages-container"
         class="flex-1 overflow-y-auto p-6 space-y-4 flex flex-col bg-slate-50 dark:bg-[radial-gradient(#1f2937_1px,transparent_1px)] [background-size:16px_16px]">
        @forelse($visibleMessages as $msg)
            @php
                $isPlatformUser = \Illuminate\Support\Facades\Auth::user()->school_id === null;
                $isMyMessage = ($isPlatformUser && $msg->sender_type === 'platform') || (! $isPlatformUser && $msg->sender_type === 'school');
            @endphp

            <div class="flex flex-col {{ $isMyMessage ? 'items-end' : 'items-start' }} space-y-1">
                <!-- Sender Label with explicit dark/light contrast -->
                <span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 px-1">
                    {{ $msg->sender_label }}
                    @if($msg->sender_type === 'school' && $msg->school && $isPlatformUser)
                        <span class="text-emerald-700 dark:text-emerald-400 font-semibold">({{ $msg->school->name }})</span>
                    @endif
                </span>

                <!-- Chat Bubble:
                     - My messages (sent by me): Emerald background with explicit pure white text
                     - Other's messages (received): Clean white background in light mode, dark slate in dark mode, with explicit dark slate text (#0f172a) in light mode and pure white text in dark mode.
                -->
                <div @class([
                    'relative max-w-[85%] rounded-2xl px-4 py-3 text-xs shadow-md leading-relaxed transition-all',
                    'bg-emerald-600 text-white rounded-tr-none shadow-emerald-600/20 font-medium' => $isMyMessage,
                    'bg-white text-slate-900 border border-slate-300 rounded-tl-none dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700 font-medium' => ! $isMyMessage,
                ])>
                    @if($msg->subject)
                        <div @class([
                            'font-bold text-xs mb-1.5 pb-1.5 border-b tracking-wide',
                            $isMyMessage ? 'border-emerald-400/60 text-white' : 'border-slate-200 dark:border-slate-800 text-slate-900 dark:text-white',
                        ])>
                            {{ $msg->subject }}
                        </div>
                    @endif

                    <div class="whitespace-pre-wrap leading-relaxed text-[11.5px]" style="{{ $isMyMessage ? 'color: #ffffff !important;' : 'color: #0f172a !important;' }}">
                        {{ $msg->body }}
                    </div>

                    <!-- Timestamp & Read Receipts -->
                    <div @class([
                        'flex items-center justify-end gap-1.5 mt-2.5 text-[9px] font-semibold',
                        $isMyMessage ? 'text-emerald-100' : 'text-slate-500 dark:text-slate-400',
                    ])>
                        <span>{{ $msg->created_at->format('M d, H:i') }}</span>
                        @if($isMyMessage)
                            <span class="font-bold tracking-tighter" title="{{ __('Delivered & Read') }}">✓✓</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-slate-600 dark:text-slate-400 py-12 text-xs font-semibold">
                {{ __('No messages in this thread yet. Send a message below to start the conversation.') }}
            </div>
        @endforelse
    </div>

    <!-- Inline Reply Composer -->
    @if (($canReply ?? true) && isset($threadParentId))
        <form wire:submit="sendThreadReply({{ $threadParentId }})" class="p-4 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 flex items-end gap-3 shadow-lg">
            <textarea
                wire:model="threadReplyBody"
                rows="2"
                maxlength="5000"
                placeholder="{{ __('Type your reply…') }}"
                class="flex-1 resize-none text-xs rounded-xl border-2 border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-950 focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 px-3.5 py-2.5 font-medium text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 shadow-sm"
            ></textarea>
            <button
                type="submit"
                class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-md shadow-emerald-600/30 transition disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                style="color: #ffffff !important; background-color: #059669 !important;"
                wire:loading.attr="disabled"
                wire:target="sendThreadReply"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4 text-white" style="color: #ffffff !important;" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>
                <span wire:loading.remove wire:target="sendThreadReply" style="color: #ffffff !important;">{{ __('Send') }}</span>
                <span wire:loading wire:target="sendThreadReply" style="color: #ffffff !important;">{{ __('Sending…') }}</span>
            </button>
        </form>
        @error('threadReplyBody')
            <div class="px-4 pb-2 bg-white dark:bg-slate-900 text-[11px] font-semibold text-red-500">{{ $message }}</div>
        @enderror
    @else
        <div class="px-4 py-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-[11px] font-medium text-slate-700 dark:text-slate-300 flex items-center justify-between">
            <span>{{ __('💡 Tip: Use the Reply button on any row for direct actions.') }}</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ __('Secure & Encrypted') }}</span>
        </div>
    @endif

</div>
