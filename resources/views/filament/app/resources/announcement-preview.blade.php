<div class="max-h-[80vh] overflow-auto">
    <div class="space-y-6 p-2">
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 p-6 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        @if(in_array($notice->priority, ['important', 'critical', 'emergency'], true))
                            <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                {{ strtoupper($notice->priority) }}
                            </span>
                        @endif
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ __('Announcement') }}
                        </span>
                    </div>
                    <h1 class="text-xl font-extrabold tracking-tight text-slate-900 sm:text-2xl dark:text-white">
                        {{ $notice->title }}
                    </h1>
                    <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500 dark:text-slate-400">
                        @if($notice->published_at)
                            <span>{{ __('Published') }}: {{ $notice->published_at->format('d M Y, H:i') }}</span>
                        @else
                            <span>{{ __('Created') }}: {{ $notice->created_at->format('d M Y, H:i') }}</span>
                        @endif
                        @if($notice->expires_at)
                            <span>{{ __('Expires') }}: {{ $notice->expires_at->format('d M Y, H:i') }}</span>
                        @endif
                        @if($notice->school)
                            <span>{{ $notice->school->name }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="border-t border-slate-200 px-6 py-6 dark:border-slate-800">
                <div class="prose prose-sm sm:prose-base max-w-none prose-headings:font-bold prose-p:leading-relaxed prose-a:text-primary-600 prose-img:rounded-xl dark:prose-invert">
                    {!! $notice->content !!}
                </div>
            </div>
        </div>

        @if(!empty($notice->attachments))
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Attachments') }}</h2>
                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        {{ count($notice->attachments) }} {{ __('file(s)') }}
                    </span>
                </div>
                <div class="grid gap-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($notice->attachments as $attachment)
                        @php
                            $isImage = preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $attachment);
                            $href = asset('storage/'.$attachment);
                            $canDownload = ($notice->attachment_policy ?? 'view_download') === 'view_download';
                        @endphp
                        @if($isImage)
                            <a href="{{ $href }}" target="_blank" rel="noopener" class="group overflow-hidden rounded-xl border border-slate-200 shadow-sm transition hover:shadow-md dark:border-slate-700">
                                <img src="{{ $href }}" alt="Attachment" class="h-48 w-full object-cover transition group-hover:scale-[1.02]">
                            </a>
                        @else
                            <div class="flex flex-col justify-between rounded-xl border border-slate-200 p-4 shadow-sm dark:border-slate-700">
                                <div class="flex items-start gap-3">
                                    <x-heroicon-o-document class="mt-0.5 h-8 w-8 text-slate-400"/>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900 dark:text-white">{{ basename($attachment) }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">{{ __('Document attachment') }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 flex flex-wrap gap-2">
                                    <a href="{{ $href }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700">
                                        <x-heroicon-o-eye class="h-4 w-4"/>
                                        {{ __('View') }}
                                    </a>
                                    @if($canDownload)
                                        <a href="{{ route('communication.attachment.download', ['path' => $attachment]) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700">
                                            <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                                            {{ __('Download') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>