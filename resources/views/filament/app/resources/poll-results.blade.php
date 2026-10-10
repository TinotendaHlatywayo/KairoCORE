@php
    /** @var \Modules\Communication\Models\Poll $poll */
    $total = $poll->votes()->count();
    $primary = '#EE5D4B';
    $dark = '#1E2A38';
@endphp

<div class="space-y-4">
    <p class="text-xs text-slate-500 dark:text-slate-400">
        {{ __('Total responses:') }} <strong class="text-slate-700 dark:text-slate-200">{{ $total }}</strong>
        @if($poll->is_anonymous)
            <span class="ml-1 font-semibold text-slate-400">· {{ __('Anonymous') }}</span>
        @endif
    </p>

    @if($poll->options->isNotEmpty())
        <div class="space-y-3">
            @foreach($poll->options as $option)
                @php
                    $count = $option->votes()->count();
                    $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                @endphp
                <div>
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $option->option_value }}</span>
                        <span class="font-bold text-slate-500 dark:text-slate-400">{{ $count }} · {{ $pct }}%</span>
                    </div>
                    <div class="mt-1 h-2.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full" style="width: {{ max($pct, $count > 0 ? 3 : 0) }}%; background-color: {{ $primary }};"></div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xs text-slate-500 dark:text-slate-400">
            {{ __('This is an open feedback survey — participants submit their own written answers.') }}
        </p>
    @endif

    @if($poll->type === 'survey' || $poll->options->isEmpty())
        @php
            $written = $poll->votes()
                ->whereNotNull('written_response')
                ->with('user')
                ->get();
        @endphp
        @if($written->isNotEmpty())
            <div class="space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('Written Responses') }}</p>
                @foreach($written as $vote)
                    <div class="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-900">
                        <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-200">{{ $vote->written_response }}</p>
                        @unless($poll->is_anonymous)
                            <p class="mt-1 text-[11px] font-semibold text-slate-400">{{ $vote->user?->name ?? __('Unknown respondent') }}</p>
                        @endunless
                    </div>
                @endforeach
            </div>
        @endif
    @endif

    @if($total === 0)
        <p class="text-xs text-slate-400">{{ __('No votes have been recorded for this poll yet.') }}</p>
    @endif
</div>