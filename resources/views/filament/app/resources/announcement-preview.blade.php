<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">{{ $notice->title }}</h3>
            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $notice->priority }}
            </span>
            @if($notice->display_style)
                <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    {{ $notice->display_style }}
                </span>
            @endif
        </div>
        <span class="text-[11px] text-slate-400">
            {{ $notice->published_at?->format('d M Y H:i') ?? $notice->created_at->format('d M Y') }}
        </span>
    </div>

    @if($notice->school)
        <p class="text-[11px] text-slate-400">{{ $notice->school->name }}</p>
    @endif

    <div class="prose-sm prose-headings:font-bold prose-p:mt-1 prose-p:leading-relaxed max-w-none text-sm leading-relaxed text-slate-700 dark:text-slate-200 [&_p]:mt-1 [&_p]:mb-2 [&_p:first-child]:mt-0 [&_p:last-child]:mb-0 [&_img]:rounded-lg [&_img]:max-w-full">
        {!! $notice->content !!}
    </div>

    @if(! empty($notice->attachments))
        <div>
            <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ __('Attachments') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach($notice->attachments as $attachment)
                    @php
                        $isImage = preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', (string) $attachment);
                        $href = asset('storage/'.$attachment);
                    @endphp
                    @if($isImage)
                        <a href="{{ $href }}" target="_blank" rel="noopener"
                           class="block overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
                            <img src="{{ $href }}" alt="{{ $notice->title }}" class="h-20 w-20 object-cover">
                        </a>
                    @else
                        <a href="{{ $href }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                            <x-heroicon-o-document class="h-3 w-3"/>
                            {{ __('Document') }}
                        </a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>