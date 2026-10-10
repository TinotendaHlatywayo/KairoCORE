@php
    $isImage = fn (string $path): bool => (bool) preg_match('/\.(jpe?g|png|gif|webp|svg)$/i', $path);
    $canDownload = $canDownload ?? (($notice->attachment_policy ?? 'view_download') === 'view_download');
@endphp

@if(! empty($notice->attachments))
    <div class="mt-3">
        <p class="mb-2 text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Attachments') }}</p>
        <div class="flex flex-wrap gap-2">
            @foreach($notice->attachments as $attachment)
                @php
                    $isImg = $isImage($attachment);
                    $href = asset('storage/'.$attachment);
                @endphp
                @if($isImg)
                    <a href="{{ $href }}" target="_blank" rel="noopener"
                       class="block overflow-hidden rounded-lg border {{ $canDownload ? 'border-slate-200 dark:border-slate-700' : 'border-dashed border-indigo-200 dark:border-indigo-700' }}"
                       title="{{ $canDownload ? __('Open image') : __('Preview only — downloading is not allowed') }}">
                        <img src="{{ $href }}" alt="{{ $notice->title }}" class="h-20 w-20 object-cover">
                    </a>
                @else
                    <div class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2.5 py-1 text-[11px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <x-heroicon-o-document class="h-3 w-3"/>
                        <a href="{{ $href }}" target="_blank" rel="noopener" class="hover:underline">
                            {{ __('View') }}
                        </a>
                        @if($canDownload)
                            <a href="{{ route('communication.attachment.download', ['path' => $attachment]) }}"
                               class="inline-flex items-center gap-0.5 text-indigo-600 hover:underline dark:text-indigo-400">
                                <x-heroicon-o-arrow-down-tray class="h-3 w-3"/>
                                {{ __('Download') }}
                            </a>
                        @else
                            <span class="inline-flex items-center gap-0.5 text-slate-400" title="{{ __('View only — downloading is not allowed') }}">
                                <x-heroicon-o-lock-closed class="h-3 w-3"/>
                                {{ __('View Only') }}
                            </span>
                        @endif
                    </div>
                @endif
            @endforeach
        </div>
    </div>
@endif