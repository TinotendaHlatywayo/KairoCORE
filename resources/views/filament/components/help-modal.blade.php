<div class="space-y-5 text-sm text-gray-600 dark:text-gray-300">
    @if (! empty($help['workflow']))
        <div>
            <h4 class="mb-2 font-semibold text-gray-900 dark:text-white">{{ __('Step-by-step workflow') }}</h4>
            <ol class="space-y-2.5">
                @foreach ($help['workflow'] as $step)
                    @php
                        $text = preg_replace('/^\d+\.\s*/', '', (string) $step);
                    @endphp
                    <li class="flex items-start gap-2.5">
                        <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary-50 text-[11px] font-bold text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/30">
                            {{ $loop->iteration }}
                        </span>
                        <span>{!! preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($text)) !!}</span>
                    </li>
                @endforeach
            </ol>
        </div>
    @endif

    @if (! empty($help['details']))
        <div class="space-y-2 border-t border-gray-200 pt-4 text-xs dark:border-gray-700">
            @foreach ($help['details'] as $label => $text)
                <div class="rounded-lg bg-gray-50 px-3 py-2 dark:bg-gray-800/60">
                    <strong class="text-gray-900 dark:text-white">{{ $label }}:</strong>
                    <span>{{ $text }}</span>
                </div>
            @endforeach
        </div>
    @endif
</div>
