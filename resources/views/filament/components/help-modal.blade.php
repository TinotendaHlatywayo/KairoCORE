<div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
    @if(!empty($help['summary']))
        <div class="p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
            <p>{{ $help['summary'] }}</p>
        </div>
    @endif

    @if(!empty($help['workflow']))
        <div>
            <h4 class="font-medium text-gray-900 dark:text-white mb-2">Step-by-Step Workflow</h4>
            <ul class="space-y-2">
                @foreach($help['workflow'] as $step)
                    <li class="flex items-start gap-2">
                        <span class="text-primary-600 dark:text-primary-400 font-bold">✓</span>
                        <span>{!! str_replace(['**', '—'], ['<strong>', '—</strong>'], $step) !!}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!empty($help['details']))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 space-y-1 text-xs">
            @foreach($help['details'] as $label => $text)
                <div><strong>{{ $label }}:</strong> {{ $text }}</div>
            @endforeach
        </div>
    @endif
</div>
