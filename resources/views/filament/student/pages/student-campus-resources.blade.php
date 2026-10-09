<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold tracking-tight text-gray-900 dark:text-white">{{ __('Campus Resources & Library') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ __('Access downloadable study materials, syllabi, curriculum documents, and institutional policies.') }}</p>
        </div>

        @if($resources->isEmpty())
            <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800">
                <x-filament::icon icon="heroicon-o-folder-open" class="w-12 h-12 mx-auto text-gray-400 mb-3" />
                <p class="text-base font-medium text-gray-600 dark:text-gray-300">{{ __('No campus resources available yet.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($resources as $res)
                    <div class="bg-white dark:bg-gray-900 rounded-2xl p-5 shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col justify-between">
                        <div>
                            @if($res->thumbnail_path)
                                <img src="{{ asset('storage/' . $res->thumbnail_path) }}" alt="{{ $res->title }}" class="w-full h-36 object-cover rounded-xl mb-4">
                            @endif
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-primary-50 text-primary-600 dark:bg-primary-950 dark:text-primary-400 uppercase tracking-wider">{{ $res->category }}</span>
                                <span class="text-xs text-gray-400">v{{ $res->version }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">{{ $res->title }}</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-3 mb-4">{{ $res->description }}</p>
                        </div>
                        <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <span class="text-xs text-gray-400">{{ $res->download_count }} {{ __('downloads') }}</span>
                            @if($res->file_path)
                                <x-filament::button wire:click="download({{ $res->id }})" icon="heroicon-o-arrow-down-tray" size="sm">
                                    {{ __('Download') }}
                                </x-filament::button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
