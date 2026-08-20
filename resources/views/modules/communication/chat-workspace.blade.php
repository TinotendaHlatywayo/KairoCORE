<div class="flex h-screen bg-gray-100 dark:bg-gray-900 overflow-hidden rounded-xl shadow-lg border border-gray-200 dark:border-gray-800" 
     style="height: calc(100vh - 120px);"
     x-data="{
         showCompressor: false,
         originalFile: null,
         originalSize: 0,
         selectedFileName: '',
         initCompressor(event) {
             const file = event.target.files[0];
             if (!file) return;
             
             this.originalFile = file;
             this.originalSize = (file.size / 1024 / 1024).toFixed(2);
             this.selectedFileName = file.name;

             if (file.type.match('image.*') && file.size > 2 * 1024 * 1024) {
                 this.showCompressor = true;
             } else {
                 @this.upload('attachment', file);
             }
         },
         compressImage(level) {
             const reader = new FileReader();
             reader.readAsDataURL(this.originalFile);
             reader.onload = (e) => {
                 const img = new Image();
                 img.src = e.target.result;
                 img.onload = () => {
                     const canvas = document.createElement('canvas');
                     const ctx = canvas.getContext('2d');
                     
                     const width = img.width * level;
                     const height = img.height * level;
                     canvas.width = width;
                     canvas.height = height;
                     
                     ctx.drawImage(img, 0, 0, width, height);
                     
                     canvas.toBlob((blob) => {
                         const compressedFile = new File([blob], this.selectedFileName, {
                             type: 'image/jpeg',
                             lastModified: Date.now()
                         });
                         
                         @this.upload('attachment', compressedFile);
                         this.showCompressor = false;
                     }, 'image/jpeg', level);
                 };
             };
         }
     }">

    <!-- Left Pane: Chats Sidebar -->
    <div class="w-1/3 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
            <h2 class="text-md font-bold text-gray-800 dark:text-gray-100 tracking-tight">{{ __('Conversations') }}</h2>
        </div>

        <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/50">
            @if($threads->isEmpty())
                <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('No active discussion rooms found.') }}</div>
            @else
                @foreach($threads as $thread)
                    <div wire:click="selectThread({{ $thread->id }})" 
                         class="flex items-center px-4 py-3 cursor-pointer transition-all hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ $activeThreadId == $thread->id ? 'bg-indigo-50/70 dark:bg-gray-700 border-l-4 border-indigo-500' : '' }}">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    {{ $thread->name }}
                                </p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $thread->updated_at->format('h:i A') }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate pr-2">
                                    @if($thread->messages->isNotEmpty())
                                        {{ $thread->messages->first()->message }}
                                    @else
                                        No messages sent yet.
                                    @endif
                                </p>
                                <span class="bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300 text-xxs font-bold px-2 py-0.5 rounded-full">
                                    {{ $thread->users_count }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- Right Pane: Active Chat Room -->
    <div class="w-2/3 flex flex-col bg-gray-50 dark:bg-gray-900">
        @if($activeThread)
            <!-- Active Chat Header -->
            <div class="px-6 py-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between shadow-sm">
                <div>
                    <h3 class="text-sm font-bold text-gray-800 dark:text-gray-100">{{ $activeThread->name }}</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 truncate max-w-lg">
                        {{ $activeThread->users->pluck('name')->implode(', ') }}
                    </p>
                </div>
                
                <button wire:click="toggleMute" class="p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 transition-colors">
                    @if($isMuted)
                        <svg class="w-5.5 h-5.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Unmute Notifications">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                        </svg>
                    @else
                        <svg class="w-5.5 h-5.5 text-gray-400 hover:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" title="Mute Notifications">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        </svg>
                    @endif
                </button>
            </div>

            <!-- Messages Stream (High Contrast Wallpaper Canvas) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-4" style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-blend-mode: overlay; background-color: rgba(243, 244, 246, 0.96);">
                @if($activeThread->messages->isEmpty())
                    <div class="flex justify-center items-center h-full">
                        <span class="bg-white/90 dark:bg-gray-800/90 border border-gray-200/50 dark:border-gray-700/50 text-gray-500 dark:text-gray-400 px-4 py-2 rounded-lg shadow-sm text-xs tracking-tight">
                            {{ __('Secure end-to-end conversation initialized.') }}
                        </span>
                    </div>
                @else
                    @foreach($activeThread->messages as $msg)
                        @php
                            $isSelf = $msg->sender_id === Auth::id();
                            $isSystemAlert = str_contains($msg->message, 'SYSTEM BULLETIN:');
                        @endphp

                        @if($isSystemAlert)
                            <!-- DESIGN: Centered Warning Callout Box for System Bulletins -->
                            <div class="flex justify-center w-full my-3">
                                <div class="bg-red-50/90 dark:bg-red-950/95 border border-red-200/60 dark:border-red-900/60 rounded-xl p-3 text-center max-w-lg shadow-sm">
                                    <span class="text-sm font-semibold text-red-800 dark:text-red-200">
                                        {{ $msg->message }}
                                    </span>
                                    <span class="block text-xxs text-red-500 dark:text-red-400 mt-1 font-medium">
                                        Broadcasted at {{ $msg->created_at->format('h:i A') }}
                                    </span>
                                </div>
                            </div>
                        @else
                            <!-- DESIGN: High-Contrast Conversation Bubbles -->
                            <div class="flex {{ $isSelf ? 'justify-end' : 'justify-start' }}">
                                @if($isSelf)
                                    <!-- Outgoing Bubble (Locked to Purple/Blue with clear White text) -->
                                    <div class="max-w-md rounded-xl p-3 shadow-md rounded-tr-none text-white" 
                                         style="background-color: #4f46e5; color: #ffffff !important;">
                                        <p class="text-sm leading-relaxed" style="color: #ffffff !important;">{{ $msg->message }}</p>
                                        
                                        @if($msg->attachments)
                                            <div class="mt-2 p-2 bg-black bg-opacity-20 rounded flex items-center space-x-2">
                                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <a href="{{ asset('storage/' . $msg->attachments[0]) }}" target="_blank" class="text-xs underline text-white font-semibold">{{ __('Download Attachment') }}</a>
                                            </div>
                                        @endif

                                        <p class="text-right text-xxs opacity-75 mt-1" style="color: #ffffff !important; font-size: 9px;">
                                            {{ $msg->created_at->format('h:i A') }}
                                        </p>
                                    </div>
                                @else
                                    <!-- Incoming Bubble (Slate White in Light, Dark Slate in Dark Theme) -->
                                    <div class="max-w-md rounded-xl p-3 shadow-md rounded-tl-none bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/50">
                                        <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400 mb-1 tracking-tight">{{ $msg->sender->name }}</p>
                                        <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $msg->message }}</p>
                                        
                                        @if($msg->attachments)
                                            <div class="mt-2 p-2 bg-gray-50 dark:bg-gray-900 rounded flex items-center space-x-2 border border-gray-100 dark:border-gray-800">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                <a href="{{ asset('storage/' . $msg->attachments[0]) }}" target="_blank" class="text-xs underline text-indigo-600 dark:text-indigo-400 font-semibold">{{ __('Download Attachment') }}</a>
                                            </div>
                                        @endif

                                        <p class="text-right text-xxs opacity-75 mt-1 text-gray-400 dark:text-gray-500" style="font-size: 9px;">
                                            {{ $msg->created_at->format('h:i A') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            <!-- Footer Composer Input Panel (Slack-Style Layout) -->
            <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 shadow-lg">
                <form wire:submit.prevent="sendMessage" class="flex items-center space-x-3">
                    
                    <!-- 1. Explicit File Attachment Button (Left side, highly visible) -->
                    <label class="cursor-pointer p-3 rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-500 dark:text-gray-300 transition-colors flex items-center justify-center shadow-sm" title="Upload File">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                        </svg>
                        <input type="file" @change="initCompressor($event)" class="hidden">
                    </label>

                    <!-- 2. Text Input Capsule -->
                    <div class="flex-1 relative flex items-center bg-gray-100 dark:bg-gray-700 rounded-full px-4 border border-gray-200 dark:border-gray-600">
                        <input type="text" 
                               wire:model="messageText" 
                               placeholder="Type your message here..." 
                               class="w-full py-3 bg-transparent text-gray-900 dark:text-gray-100 focus:outline-none text-sm placeholder-gray-400 dark:placeholder-gray-500"
                               style="color: #111827 !important; background-color: transparent !important;"
                               onfocus="this.style.color='#111827';">
                    </div>

                    <!-- 3. High-Contrast Indigo Send Button (Right side, circular) -->
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-full focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-md transition-all flex items-center justify-center" title="Send Message">
                        <svg class="w-5 h-5 text-white transform rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                        </svg>
                    </button>
                </form>
                @if($attachment)
                    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center space-x-2">
                        <span>📎 Ready to send: {{ $attachment->getClientOriginalName() }}</span>
                        <button type="button" wire:click="$set('attachment', null)" class="text-red-500 underline">{{ __('Cancel') }}</button>
                    </div>
                @endif
            </div>
        @else
            <!-- Standard Placeholder State -->
            <div class="flex-1 flex flex-col justify-center items-center text-center p-8 bg-gray-50 dark:bg-gray-900">
                <svg class="w-16 h-16 text-gray-300 dark:text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">{{ __('Welcome to the Message Hub') }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mt-1">{{ __('Select an active chat group on the sidebar or click Create Chat to start communicating with staff and parents instantly.') }}</p>
            </div>
        @endif
    </div>

    <!-- CLIENT SIDE COMPRESSION MODAL DIALOG -->
    <div x-show="showCompressor" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black bg-opacity-50"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white dark:bg-gray-800 rounded-lg max-w-md w-full p-6 shadow-xl border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center space-x-2">
                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ __('Image Exceeds 2MB Limit') }}</span>
            </h3>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3 font-medium">
                {{ __('The selected image') }} <strong class="text-gray-800 dark:text-white" x-text="selectedFileName"></strong> is too large (<span x-text="originalSize"></span> MB). Choose compression level:
            </p>

            <div class="mt-5 space-y-3">
                <button type="button" @click="compressImage(0.75)" class="w-full py-2.5 px-4 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-white rounded-lg text-sm font-semibold transition-colors flex justify-between items-center">
                    <span>Compress (Lite - 25% Reduction)</span>
                    <span class="text-xs text-gray-500">{{ __('75% Quality') }}</span>
                </button>
                <button type="button" @click="compressImage(0.50)" class="w-full py-2.5 px-4 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950 dark:hover:bg-indigo-900 text-indigo-700 dark:text-indigo-300 rounded-lg text-sm font-semibold transition-colors flex justify-between items-center">
                    <span>Compress (Recommended - 50% Reduction)</span>
                    <span class="text-xs text-indigo-500">{{ __('50% Quality') }}</span>
                </button>
                <button type="button" @click="compressImage(0.25)" class="w-full py-2.5 px-4 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950 dark:hover:bg-amber-900 text-amber-700 dark:text-amber-300 rounded-lg text-sm font-semibold transition-colors flex justify-between items-center">
                    <span>Compress (Max - 75% Reduction)</span>
                    <span class="text-xs text-amber-500">{{ __('25% Quality') }}</span>
                </button>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="button" @click="showCompressor = false; originalFile = null;" class="py-2 px-4 bg-white hover:bg-gray-100 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400 text-sm rounded-lg border border-gray-200 dark:border-gray-700 transition-colors">
                    {{ __('Cancel Upload') }}
                </button>
            </div>
        </div>
    </div>
</div>