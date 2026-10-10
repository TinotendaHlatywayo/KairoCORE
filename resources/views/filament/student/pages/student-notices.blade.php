<x-filament-panels::page>
    <div class="space-y-6">
        <div class="space-y-4">
            @forelse($notices as $notice)
                @include('filament.app.pages.partials.notice-card', ['notice' => $notice])
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <x-heroicon-o-megaphone class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600"/>
                    <p class="mt-3 text-xs text-slate-400">{{ __('No notices have been published yet.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>