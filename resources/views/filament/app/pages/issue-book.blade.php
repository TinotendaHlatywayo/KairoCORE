<x-filament-panels::page>
    <div class="space-y-6">

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- LEFT: Book Search --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('1. Select Resource') }}</h3>

                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="bookSearch"
                           placeholder="{{ __('Type title, author, ISBN, or year...') }}"
                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">

                    @if(count($searchResults) && !$selectedBookId)
                        <div class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900" style="max-height:300px;overflow-y:auto">
                            @foreach($searchResults as $result)
                                <button wire:click="selectBook({{ (int) $result['id'] }})"
                                        class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-indigo-50 dark:border-slate-800 dark:hover:bg-indigo-950/20">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        <x-heroicon-o-book-open class="h-4 w-4"/>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $result['title'] }}</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                            @if($result['authors']) {{ $result['authors'] }} · @endif
                                            {{ $result['category'] }}
                                            @if($result['format']) · {{ $result['format'] }} @endif
                                        </p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-bold {{ $result['available'] > 0 ? 'text-green-600' : 'text-red-500' }}">
                                        {{ $result['available'] }}/{{ $result['total'] }}
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Selected Book + Copies --}}
                @if($selectedBookId)
                    @php $copies = $this->availableCopies; @endphp
                    <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/20">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-green-800 dark:text-green-200">{{ __('Selected') }}: {{ $bookSearch }}</p>
                            <button wire:click="clearBookSelection"
                                    class="text-[11px] font-bold text-green-600 hover:text-green-800 dark:text-green-400">
                                {{ __('Change') }}
                            </button>
                        </div>
                    </div>

                    @if($copies->count())
                        <div class="mt-3">
                            <label class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Available Copies (by Barcode)') }}</label>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3" style="max-height:160px;overflow-y:auto">
                                @foreach($copies as $copy)
                                    <button wire:click="$set('selectedCopyId', {{ (int) $copy->id }})"
                                            class="rounded-lg border px-3 py-2 text-left text-xs transition {{ (int) $selectedCopyId === (int) $copy->id ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500 dark:bg-indigo-950/20' : 'border-slate-200 bg-white hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-800' }}">
                                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $copy->barcode }}</span>
                                        @if($copy->shelf || $copy->rack)
                                            <p class="mt-0.5 text-[10px] text-slate-400">
                                                @if($copy->shelf) {{ __('Shelf') }}: {{ $copy->shelf }} @endif
                                                @if($copy->rack) · {{ __('Rack') }}: {{ $copy->rack }} @endif
                                            </p>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="mt-3 text-xs text-red-500">{{ __('No available copies for this resource.') }}</p>
                    @endif
                @endif
            </div>

            {{-- RIGHT: Recipient Search --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('2. Select Recipient') }}</h3>

                {{-- Recipient Type Toggle --}}
                <div class="mb-3 flex gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                    <button wire:click="switchRecipientType('student')"
                            class="flex-1 rounded-md px-3 py-1.5 text-xs font-bold transition {{ $recipientType === 'student' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500' }}">
                        <x-heroicon-o-academic-cap class="mr-1 inline h-3.5 w-3.5"/>
                        {{ __('Student') }}
                    </button>
                    <button wire:click="switchRecipientType('staff')"
                            class="flex-1 rounded-md px-3 py-1.5 text-xs font-bold transition {{ $recipientType === 'staff' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500' }}">
                        <x-heroicon-o-users class="mr-1 inline h-3.5 w-3.5"/>
                        {{ __('Staff') }}
                    </button>
                </div>

                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="recipientSearch"
                           placeholder="{{ $recipientType === 'student' ? __('Type name, admission number, or student ID...') : __('Type name or email...') }}"
                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">

                    @if(count($recipientResults) && !$selectedStudentId && !$selectedUserId)
                        <div class="absolute z-20 mt-1 w-full rounded-lg border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900" style="max-height:240px;overflow-y:auto">
                            @foreach($recipientResults as $r)
                                @if($recipientType === 'student')
                                    <button wire:click="selectStudentRecipient({{ (int) $r['id'] }})"
                                            class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-indigo-50 dark:border-slate-800 dark:hover:bg-indigo-950/20">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                            <x-heroicon-o-user class="h-4 w-4 text-slate-500"/>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $r['name'] }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $r['number'] }}</p>
                                        </div>
                                    </button>
                                @else
                                    <button wire:click="selectStaffRecipient({{ (int) $r['id'] }})"
                                            class="flex w-full items-center gap-3 border-b border-slate-100 px-4 py-3 text-left transition hover:bg-indigo-50 dark:border-slate-800 dark:hover:bg-indigo-950/20">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                            <x-heroicon-o-briefcase class="h-4 w-4 text-slate-500"/>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $r['name'] }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $r['email'] }}</p>
                                        </div>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($selectedStudentId || $selectedUserId)
                    <div class="mt-3 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/20">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold text-green-800 dark:text-green-200">{{ __('Selected') }}: {{ $recipientSearch }}</p>
                            <button wire:click="clearRecipientSelection"
                                    class="text-[11px] font-bold text-green-600 hover:text-green-800 dark:text-green-400">
                                {{ __('Change') }}
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- BOTTOM: Due Date, Notes, Submit --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-4 text-base font-bold text-slate-900 dark:text-white">{{ __('3. Issue Details') }}</h3>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Return Due Date') }}</label>
                    <input type="date" wire:model.live="dueDate"
                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-600 dark:text-slate-400">{{ __('Notes (optional)') }}</label>
                    <input type="text" wire:model.live="notes"
                           placeholder="{{ __('e.g., For research project') }}"
                           class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3">
                @if($selectedBookId && $selectedCopyId && ($selectedStudentId || $selectedUserId))
                    <button wire:click="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-indigo-500">
                        <x-heroicon-o-check class="h-4 w-4"/>
                        {{ __('Issue Book') }}
                    </button>
                @else
                    <button disabled
                            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white opacity-50">
                        <x-heroicon-o-check class="h-4 w-4"/>
                        {{ __('Issue Book') }}
                    </button>
                @endif
                <button wire:click="resetForm"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {{ __('Reset') }}
                </button>
            </div>
        </div>

    </div>
</x-filament-panels::page>
