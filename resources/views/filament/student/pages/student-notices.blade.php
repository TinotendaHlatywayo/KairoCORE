<x-filament-panels::page>
    <div class="space-y-6">

        <div class="space-y-4">
            @forelse($notices as $notice)
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            @if($notice->priority === 'high')
                                <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ __('HIGH PRIORITY') }}</span>
                            @endif
                            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
                    </div>
                    <p class="mt-2 text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $notice->content }}</p>

                    @if(! empty($notice->attachments))
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($notice->attachments as $attachment)
                                <a href="{{ asset('storage/'.$attachment) }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                    <x-heroicon-o-paper-clip class="h-3 w-3"/>
                                    {{ __('Attachment') }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <x-heroicon-o-megaphone class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600"/>
                    <p class="mt-3 text-xs text-slate-400">{{ __('No notices have been published yet.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>