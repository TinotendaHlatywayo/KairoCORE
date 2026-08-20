@props([
    'issues' => [],
    'fileHeaders' => [],
])

<div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
    <p class="text-sm font-semibold text-gray-900">{{ __('Column match report') }}</p>
    <p class="mt-0.5 text-xs text-gray-500">Your file columns: {{ implode(', ', $fileHeaders) ?: '—' }}</p>

    @forelse ($issues as $issue)
        <div
            class="mt-3 rounded-lg border p-3 text-xs {{ $issue['type'] === 'error' ? 'border-danger-200 bg-danger-50 text-danger-700' : ($issue['type'] === 'warning' ? 'border-warning-200 bg-warning-50 text-warning-700' : 'border-success-200 bg-success-50 text-success-700') }}"
        >
            <div class="flex items-start gap-2">
                @if ($issue['type'] === 'error')
                    <x-heroicon-o-x-circle class="h-4 w-4 shrink-0" />
                @elseif ($issue['type'] === 'warning')
                    <x-heroicon-o-exclamation-triangle class="h-4 w-4 shrink-0" />
                @else
                    <x-heroicon-o-check-circle class="h-4 w-4 shrink-0" />
                @endif
                <div>
                    <p class="font-medium">{{ $issue['title'] }}</p>
                    @if (! empty($issue['items']))
                        <ul class="mt-1 list-disc space-y-0.5 pl-4">
                            @foreach ($issue['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <p class="mt-3 text-xs text-gray-500">{{ __('Everything checks out.') }}</p>
    @endforelse
</div>
