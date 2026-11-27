<div class="demo-persona-switcher">
    @if ($isDemo)
        <div
            x-data="{ open: false }"
            x-on:click.away="open = false"
            x-on:keydown.escape.window="open = false"
            class="relative"
        >
            <button
                type="button"
                @click.stop="open = !open"
                class="flex items-center gap-2 rounded-lg border border-[#D94B39]/30 bg-[#D94B39]/10 px-2.5 py-1.5 text-sm font-semibold text-[#b93b2b] transition hover:bg-[#D94B39]/20 dark:border-[#EF5F4D]/30 dark:bg-[#EF5F4D]/10 dark:text-[#EF5F4D] dark:hover:bg-[#EF5F4D]/20"
                title="{{ __('Switch demo role') }}"
            >
                <span class="max-w-[10rem] truncate rounded bg-[#D94B39] px-1.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider text-white">{{ $currentLabel }}</span>
                <svg class="h-3.5 w-3.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
            </button>

            <div
                x-show="open"
                @click.stop
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 z-[70] mt-2 max-h-[24rem] w-64 max-w-[calc(100vw-1.5rem)] overflow-y-auto rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl dark:border-white/10 dark:bg-gray-900"
                style="display: none;"
            >
                @foreach ($personas as $key => $persona)
                    <button
                        type="button"
                        wire:click="switchTo('{{ $key }}')"
                        @click="open = false"
                        class="block w-full rounded-lg px-3 py-2 text-left text-sm font-medium transition {{ $key === $current ? 'bg-[#D94B39]/10 text-[#b93b2b] dark:bg-[#EF5F4D]/10 dark:text-[#EF5F4D]' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/10' }}"
                    >
                        {{ $persona['label'] }}
                    </button>
                @endforeach
            </div>

            <div class="mt-2 flex items-center gap-2 border-t border-gray-200 pt-2 dark:border-white/10">
                <button
                    type="button"
                    wire:click="resetDemo"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10"
                >
                    {{ __('Reset demo') }}
                </button>
                <button
                    type="button"
                    wire:click="leaveDemo"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-gray-600 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/10"
                >
                    {{ __('End demo') }}
                </button>
            </div>
        </div>
    @endif
</div>
