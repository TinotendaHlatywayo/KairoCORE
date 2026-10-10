<x-filament-panels::page>
    <div class="space-y-6">

        <div class="space-y-4">
            @forelse($notices as $notice)
                @php
                    $style = $notice->display_style ?? 'card';
                    $canDownload = ($notice->attachment_policy ?? 'view_download') === 'view_download';
                @endphp

                @if($style === 'banner')
                    <div class="rounded-xl bg-indigo-600 px-6 py-5 text-white shadow-lg">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                @if($notice->priority === 'emergency' || $notice->priority === 'critical')
                                    <span class="inline-flex rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">{{ strtoupper($notice->priority) }}</span>
                                @endif
                                <h3 class="text-sm font-extrabold">{{ $notice->title }}</h3>
                            </div>
                            <span class="text-[11px] opacity-80">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="prose-sm [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-2 mt-2 max-w-none text-xs leading-relaxed opacity-95 [&_p]:text-white [&_a]:text-white [&_img]:rounded-lg">
                            {!! $notice->content !!}
                        </div>
                        @include('filament.student.pages.partials.notice-attachments', compact('notice', 'canDownload'))
                    </div>
                @elseif($style === 'ticker')
                    <div class="rounded-xl border border-slate-800 bg-slate-900 px-6 py-5 text-slate-100 shadow-lg">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                @if($notice->priority === 'emergency' || $notice->priority === 'critical')
                                    <span class="inline-flex rounded-full bg-rose-500/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-300">{{ strtoupper($notice->priority) }}</span>
                                @endif
                                <h3 class="text-sm font-extrabold">{{ $notice->title }}</h3>
                            </div>
                            <span class="text-[11px] text-slate-400">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="prose-sm [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-2 mt-2 max-w-none text-xs leading-relaxed text-slate-200 [&_p]:text-slate-200 [&_img]:rounded-lg">
                            {!! $notice->content !!}
                        </div>
                        @include('filament.student.pages.partials.notice-attachments', compact('notice', 'canDownload'))
                    </div>
                @elseif($style === 'popup')
                    <div class="rounded-xl border-2 border-indigo-300 bg-white p-6 shadow-2xl dark:border-indigo-700 dark:bg-slate-900">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                @if(in_array($notice->priority, ['high', 'important', 'critical', 'emergency'], true))
                                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ strtoupper($notice->priority) }}</span>
                                @endif
                                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
                            </div>
                            <span class="text-[11px] text-slate-400">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed mt-2 max-w-none text-xs leading-relaxed text-slate-600 dark:text-slate-300 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg">
                            {!! $notice->content !!}
                        </div>
                        @include('filament.student.pages.partials.notice-attachments', compact('notice', 'canDownload'))
                    </div>
                @else
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
                        <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed mt-2 max-w-none text-xs leading-relaxed text-slate-600 dark:text-slate-300 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg">
                            {!! $notice->content !!}
                        </div>
                        @include('filament.student.pages.partials.notice-attachments', compact('notice', 'canDownload'))
                    </div>
                @endif
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <x-heroicon-o-megaphone class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-600"/>
                    <p class="mt-3 text-xs text-slate-400">{{ __('No notices have been published yet.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>