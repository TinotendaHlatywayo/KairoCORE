<div class="space-y-3">
    <p class="text-sm text-gray-500">
        These are the supporting documents attached to {{ $student->full_name }} ({{ $student->student_id_number }}).
    </p>

    @if($documents->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-center">
            <p class="text-sm text-gray-500">{{ __('No documents have been uploaded for this student yet.') }}</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 rounded-xl border border-gray-200 overflow-hidden">
            @foreach($documents as $document)
                <div class="flex items-center justify-between gap-3 px-4 py-3 bg-white">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-800 truncate">{{ $document->document_type_label }}</p>
                        <p class="text-xs text-gray-400 truncate">{{ $document->original_name ?? 'Document file' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($document->exists)
                            <a href="{{ $document->download_url }}" target="_blank"
                               class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                {{ __('Download') }}
                            </a>
                        @else
                            <span class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-400">{{ __('File missing') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
