<div class="space-y-4">
    <div class="border-b border-slate-200 pb-4 dark:border-slate-800">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
            <span class="text-[11px] text-slate-400">
                {{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}
            </span>
        </div>
        @if($notice->school)
            <p class="mt-1 text-[11px] text-slate-400">{{ $notice->school->name }}</p>
        @endif
    </div>

    <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed max-w-none text-sm leading-relaxed text-slate-700 dark:text-slate-200 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg [&_img]:max-w-full">
        {!! $notice->content !!}
    </div>

    @include('filament.components.notice-attachments', ['notice' => $notice])
</div>