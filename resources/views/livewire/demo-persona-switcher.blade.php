<div class="demo-persona-switcher">
    @if ($isDemo)
        <div
            x-data="{ open: false }"
            x-on:click.away="open = false"
            x-on:keydown.escape.window="open = false; $refs.trigger.focus()"
            class="relative"
        >
            <button
                x-ref="trigger"
                type="button"
                @click.stop="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="menu"
                aria-label="{{ __('Demo controls. Viewing as :role', ['role' => $currentLabel]) }}"
                title="{{ __('Demo controls') }}"
                class="demo-persona-trigger group inline-flex h-10 items-center gap-2 rounded-xl border border-[#D94B39]/25 bg-white/85 px-2 text-left shadow-sm backdrop-blur transition duration-150 hover:border-[#D94B39]/45 hover:bg-white hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] focus-visible:ring-offset-2 dark:border-[#EF5F4D]/25 dark:bg-slate-900/70 dark:hover:bg-slate-900 dark:focus-visible:ring-[#EF5F4D]"
            >
                <span class="demo-persona-mark flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-[#D94B39]/12 text-[#D94B39] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2.75 4.75 5.5v5.25c0 4.25 2.8 8.2 7.25 9.5 4.45-1.3 7.25-5.25 7.25-9.5V5.5L12 2.75Z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4.25-4.5"/>
                    </svg>
                </span>
                <span class="demo-persona-copy min-w-0">
                    <span class="demo-persona-eyebrow block text-[9px] font-extrabold uppercase leading-none tracking-[0.18em] text-slate-400">
                        {{ __('Demo mode') }}
                    </span>
                    <span class="demo-persona-role mt-1 block max-w-[10rem] truncate text-[13px] font-bold leading-none text-slate-700 dark:text-slate-100">
                        {{ $currentLabel }}
                    </span>
                </span>
                <svg class="demo-persona-chevron h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-150"
                     :class="open ? 'rotate-180' : ''"
                     fill="none"
                     viewBox="0 0 24 24"
                     stroke="currentColor"
                     stroke-width="2.5"
                     aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                @click.stop
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1 scale-[0.97]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                class="demo-persona-menu absolute right-0 z-[70] mt-2 w-[19rem] max-w-[calc(100vw-1rem)] origin-top-right overflow-hidden rounded-2xl border border-slate-200/90 bg-white/95 p-2 shadow-2xl shadow-slate-900/10 backdrop-blur dark:border-white/10 dark:bg-slate-900/95"
                style="display: none;"
                role="menu"
                aria-label="{{ __('Demo controls') }}"
            >
                <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5 dark:bg-white/5">
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ __('Demo workspace') }}</p>
                        <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ __('Viewing as :role', ['role' => $currentLabel]) }}</p>
                    </div>
                    <span class="shrink-0 rounded-full bg-[#D94B39]/10 px-2 py-1 text-[10px] font-extrabold uppercase tracking-wider text-[#B93A2B] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]">
                        {{ __('Live') }}
                    </span>
                </div>

                <p class="px-3 pb-1.5 pt-4 text-[10px] font-extrabold uppercase tracking-[0.18em] text-slate-400">
                    {{ __('Switch role') }}
                </p>
                <div class="demo-persona-roles max-h-64 space-y-0.5 overflow-y-auto">
                    @foreach ($personas as $key => $persona)
                        @php($active = $key === $current)
                        <button
                            type="button"
                            role="menuitemradio"
                            :aria-checked="{{ $active ? 'true' : 'false' }}"
                            wire:click="switchTo('{{ $key }}')"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-60"
                            @click="open = false"
                            class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] {{ $active ? 'bg-[#D94B39]/10 text-[#B93A2B] dark:bg-[#EF5F4D]/15 dark:text-[#EF5F4D]' : 'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-white/10' }}"
                        >
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $active ? 'bg-[#D94B39]/15 text-[#B93A2B] dark:bg-[#EF5F4D]/20 dark:text-[#EF5F4D]' : 'bg-slate-100 text-slate-400 dark:bg-white/10 dark:text-slate-400' }}">
                                @if ($active)
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/>
                                    </svg>
                                @else
                                    <span class="h-1.5 w-1.5 rounded-full bg-current opacity-60"></span>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1 truncate">{{ $persona['label'] }}</span>
                            @if ($active)
                                <span class="shrink-0 text-[10px] font-extrabold uppercase tracking-wider">{{ __('Active') }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                <div class="mt-2 border-t border-slate-200 pt-2 dark:border-white/10">
                    <button
                        type="button"
                        role="menuitem"
                        wire:click="resetDemo"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60"
                        @click="open = false"
                        class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] dark:hover:bg-white/10"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-400/10 dark:text-amber-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 9.25A5.5 5.5 0 1 1 4.5 12"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.75 4.75v4.5h4.5"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-700 dark:text-slate-100">{{ __('Reset demo') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Start again with fresh sample data') }}</span>
                        </span>
                    </button>
                    <button
                        type="button"
                        role="menuitem"
                        wire:click="leaveDemo"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60"
                        @click="open = false"
                        class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 py-2 text-left transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#D94B39] dark:hover:bg-red-500/10"
                    >
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 4.75H6.25A1.5 1.5 0 0 0 4.75 6.25v7.5a1.5 1.5 0 0 0 1.5 1.5h9.5a1.5 1.5 0 0 0 1.5-1.5v-7.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 12.75h7m0 0-2.25-2.25M17 12.75l-2.25 2.25"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-slate-700 dark:text-slate-100">{{ __('End demo') }}</span>
                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ __('Leave the demo and return home') }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
