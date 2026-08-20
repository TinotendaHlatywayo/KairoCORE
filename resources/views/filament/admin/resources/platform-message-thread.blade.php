<div class="flex flex-col h-[480px] bg-slate-100 dark:bg-slate-950 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-inner">
    
    <!-- Chat Header Bar -->
    <div class="px-5 py-3 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-3">
            <div class="h-9 w-9 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold flex items-center justify-center text-sm ring-2 ring-emerald-500/20">
                {{ __('💬') }}
            </div>
            <div>
                <h4 class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>{{ __('Secure Conversation Thread') }}</span>
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </h4>
                <p class="text-[10px] text-slate-400 font-medium">{{ __('End-to-end encrypted messaging channel') }}</p>
            </div>
        </div>
        <div class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/50">
            {{ __('Active Channel') }}
        </div>
    </div>

    <!-- Message Bubbles Scroll Area -->
    <div id="chat-messages-container" class="flex-1 overflow-y-auto p-6 space-y-4 flex flex-col bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] dark:bg-[radial-gradient(#1f2937_1px,transparent_1px)] [background-size:16px_16px]">
        @forelse($messages as $msg)
            @php
                $isPlatformUser = \Illuminate\Support\Facades\Auth::user()->school_id === null;
                $isMyMessage = ($isPlatformUser && $msg->sender_type === 'platform') || (! $isPlatformUser && $msg->sender_type === 'school');
            @endphp

            <div class="flex flex-col {{ $isMyMessage ? 'items-end' : 'items-start' }} space-y-1">
                <!-- Sender Label -->
                <span class="text-[10px] font-bold text-slate-400 px-1">
                    {{ $msg->sender_label }}
                    @if($msg->sender_type === 'school' && $msg->school)
                        <span class="text-emerald-600 dark:text-emerald-400 font-semibold">({{ $msg->school->name }})</span>
                    @endif
                </span>

                <!-- WhatsApp Bubble -->
                <div @class([
                    'relative max-w-[80%] rounded-2xl px-4 py-3 text-xs shadow-sm leading-relaxed transition-all',
                    'bg-emerald-600 text-white rounded-tr-none shadow-emerald-600/20' => $isMyMessage,
                    'bg-white text-slate-900 border border-slate-200/80 rounded-tl-none dark:bg-slate-900 dark:text-white dark:border-slate-800' => ! $isMyMessage,
                ])>
                    @if($msg->subject)
                        <div class="font-extrabold mb-1.5 pb-1 border-b {{ $isMyMessage ? 'border-emerald-500/50 text-white' : 'border-slate-100 dark:border-slate-800 text-slate-900 dark:text-white' }}">
                            {{ $msg->subject }}
                        </div>
                    @endif

                    <div class="whitespace-pre-wrap font-medium">{{ $msg->body }}</div>

                    <!-- Timestamp & Read Receipts -->
                    <div @class([
                        'flex items-center justify-end gap-1.5 mt-2 text-[9px] font-semibold',
                        'text-emerald-100' => $isMyMessage,
                        'text-slate-400' => ! $isMyMessage,
                    ])>
                        <span>{{ $msg->created_at->format('M d, H:i') }}</span>
                        @if($isMyMessage)
                            <span class="text-emerald-200 font-bold tracking-tighter" title="Read / Delivered">{{ __('✓✓') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center text-slate-400 dark:text-slate-500 py-12 text-xs font-medium">
                {{ __('No messages in this thread yet. Send a message below to start the conversation.') }}
            </div>
        @endforelse
    </div>

    <!-- WhatsApp Quick Reply Footer Note -->
    <div class="px-4 py-3 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
        <span>{{ __('💡 Tip: Use the') }} <strong>{{ __('Reply') }}</strong> {{ __('button on any message row to send a direct message reply.') }}</span>
        <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ __('Secure & Encrypted') }}</span>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('chat-messages-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    });
</script>
