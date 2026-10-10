@php
    $noticeStyle = $notice->display_style ?? 'card';
    $noticeClassName = 'notice-card';
@endphp

@if($noticeStyle === 'banner')
    <div class="rounded-xl bg-indigo-600 px-6 py-5 text-white shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                @if(in_array($notice->priority, ['critical', 'emergency'], true))
                    <span class="inline-flex animate-pulse rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">{{ strtoupper($notice->priority) }}</span>
                @endif
                <h3 class="text-sm font-extrabold">{{ $notice->title }}</h3>
            </div>
            <span class="text-[11px] opacity-80">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
        </div>
        <div class="prose-sm [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-2 mt-2 max-w-none text-xs leading-relaxed opacity-95 [&_p]:text-white [&_a]:text-white [&_img]:rounded-lg">
            {!! $notice->content !!}
        </div>
        @include('filament.components.notice-attachments', ['notice' => $notice])
    </div>
@elseif($noticeStyle === 'ticker')
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900 shadow-lg">
        <div class="flex items-center gap-3 border-b border-slate-800 bg-slate-950/50 px-5 py-3">
            <span class="inline-flex flex-none items-center gap-1.5 rounded-full bg-rose-500/20 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-300">
                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-rose-400"></span>
                {{ __('News Flash') }}
            </span>
            <span class="truncate text-xs font-bold text-slate-100">{{ $notice->title }}</span>
        </div>
        <div class="px-5 py-4">
            <div class="prose-sm [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 max-w-none text-xs leading-relaxed text-slate-200 [&_p]:text-slate-200 [&_img]:rounded-lg">
                {!! $notice->content !!}
            </div>
            @include('filament.components.notice-attachments', ['notice' => $notice])
        </div>
    </div>
@elseif($noticeStyle === 'popup')
    <div class="relative rounded-xl border-2 border-indigo-300 bg-white p-6 shadow-2xl dark:border-indigo-700 dark:bg-slate-900">
        @if(in_array($notice->priority, ['high', 'important', 'critical', 'emergency'], true))
            <span class="absolute -top-2.5 left-4 inline-flex rounded-full bg-rose-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow">{{ strtoupper($notice->priority) }}</span>
        @endif
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
            <span class="text-[11px] text-slate-400">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
        </div>
        <div class="mt-3 border-t border-dashed border-slate-200 pt-3 dark:border-slate-700">
            <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed max-w-none text-xs leading-relaxed text-slate-600 dark:text-slate-300 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg">
                {!! $notice->content !!}
            </div>
            @include('filament.components.notice-attachments', ['notice' => $notice])
        </div>
    </div>
@else
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-2">
                @if(in_array($notice->priority, ['high', 'important', 'critical', 'emergency'], true))
                    <span class="inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ strtoupper($notice->priority) }}</span>
                @endif
                <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
            </div>
            <span class="text-[11px] text-slate-400">{{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}</span>
        </div>
        <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed mt-2 max-w-none text-xs leading-relaxed text-slate-600 dark:text-slate-300 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg">
            {!! $notice->content !!}
        </div>
        @include('filament.components.notice-attachments', ['notice' => $notice])
    </div>
@endif