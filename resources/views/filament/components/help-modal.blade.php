<div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
    <div>
        <h4 class="font-medium text-gray-900 dark:text-white">Workflow</h4>
        <p class="mt-1 whitespace-pre-line">{{ $help['workflow'] }}</p>
    </div>
    @if(!empty($help['tips']))
        <div class="rounded-lg bg-primary-50 p-3 text-primary-900 dark:bg-primary-950 dark:text-primary-200">
            <span class="font-semibold">Tip:</span> {{ $help['tips'] }}
        </div>
    @endif
</div>
