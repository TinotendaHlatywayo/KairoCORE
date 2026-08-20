<div class="rounded-xl bg-danger-50 p-4 ring-1 ring-danger-200">
    <div class="flex items-start gap-3">
        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-danger-600" />
        <div class="text-sm">
            <p class="font-semibold text-danger-700">An account with this email already exists</p>
            <p class="mt-1 text-danger-600">
                {{ $conflictingUser?->name }} ({{ $conflictingUser?->email }})
                &mdash; status: <span class="font-medium">{{ $conflictingUser?->accountStatusLabel() }}</span>
                @if ($conflictingUser?->trashed())
                    <span class="font-medium">(previously deleted)</span>
                @endif
            </p>
            <p class="mt-1 text-danger-500">Choose how to proceed below, then submit the form again.</p>
        </div>
    </div>
</div>
