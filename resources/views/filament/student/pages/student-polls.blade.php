<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('No Student Record') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to participate in polls and surveys.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @forelse($polls as $item)
                    @php
                        $poll = $item['poll'];
                        $hasVoted = $item['hasVoted'];
                        $myVote = $item['myVote'];
                        $totalVotes = $item['totalVotes'];
                        $isClosed = $poll->expires_at && $poll->expires_at->lt(now());
                    @endphp

                    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                {{ $poll->type === 'survey' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' : ($poll->type === 'election' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300') }}">
                                {{ ucfirst($poll->type) }}
                            </span>
                            <span class="text-[11px] text-slate-400">
                                {{ $isClosed ? __('Closed') : ($poll->expires_at ? __('Closes').' '.$poll->expires_at->format('d M Y') : __('Open')) }}
                                @if ($poll->is_anonymous) · {{ __('Anonymous') }} @endif
                            </span>
                        </div>

                        <h3 class="mt-3 text-sm font-extrabold text-slate-900 dark:text-white">{{ $poll->question }}</h3>
                        @if ($poll->description)
                            <p class="mt-1 text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $poll->description }}</p>
                        @endif

                        {{-- Vote options --}}
                        <div class="mt-4 space-y-2">
                            @foreach ($poll->options as $option)
                                @if ($hasVoted || $isClosed)
                                    @php
                                        $count = $option->votes->count();
                                        $pct = $totalVotes > 0 ? round(($count / $totalVotes) * 100) : 0;
                                        $isMine = $myVote && $myVote->option_id === $option->id;
                                    @endphp
                                    <div class="rounded-lg border p-3 {{ $isMine ? 'border-indigo-300 bg-indigo-50 dark:border-indigo-700 dark:bg-indigo-950/40' : 'border-slate-100 bg-slate-50/60 dark:border-slate-800 dark:bg-slate-950/40' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
                                                {{ $option->option_value }}
                                                @if ($isMine) <span class="ml-1 text-[10px] font-semibold text-indigo-600 dark:text-indigo-300">{{ __('Your vote') }}</span> @endif
                                            </p>
                                            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400">{{ $pct }}% · {{ $count }}</span>
                                        </div>
                                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                                            <div class="h-full rounded-full {{ $isMine ? 'bg-indigo-500' : 'bg-emerald-400' }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @else
                                    <button
                                        type="button"
                                        wire:click="vote({{ $poll->id }}, {{ $option->id }})"
                                        class="flex w-full items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 text-left text-xs font-bold text-slate-700 transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-slate-700 dark:text-slate-200 dark:hover:border-indigo-600 dark:hover:bg-indigo-950/40"
                                    >
                                        <span class="flex h-4 w-4 items-center justify-center rounded-full border border-slate-300 dark:border-slate-600"></span>
                                        {{ $option->option_value }}
                                    </button>
                                @endif
                            @endforeach
                        </div>

                        @if (! $hasVoted && ! $isClosed)
                            <p class="mt-3 text-[10px] text-slate-400">{{ __('Tap an option to cast your vote. You can only vote once.') }}</p>
                        @else
                            <p class="mt-3 text-[10px] text-slate-400">{{ __('Total responses:') }} {{ $totalVotes }}</p>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full rounded-xl border border-slate-200 bg-white p-10 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <x-heroicon-o-chart-bar class="mx-auto h-10 w-10 text-slate-300 dark:text-slate-600"/>
                        <p class="mt-3 text-xs text-slate-400">{{ __('No open polls or surveys are available for you right now.') }}</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>