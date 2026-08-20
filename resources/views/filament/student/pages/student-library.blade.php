<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Search & Filters --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="flex-1">
                    <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('Search') }}</label>
                    <div class="relative">
                        <x-heroicon-m-magnifying-glass class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"/>
                        <input type="text" wire:model.live.debounce.500ms="search"
                               placeholder="{{ __('Search by title, author, subject...') }}"
                               class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                    </div>
                </div>
                <div class="flex gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('Category') }}</label>
                        <select wire:model.live="categoryFilter"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="">{{ __('All Categories') }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->name }}">{{ $cat->name }} ({{ $cat->books_count }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('Classification') }}</label>
                        <select wire:model.live="mediaFilter"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="">{{ __('All Types') }}</option>
                            <option value="physical">{{ __('Physical') }}</option>
                            <option value="digital">{{ __('Digital') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold text-slate-500 dark:text-slate-400">{{ __('Per page') }}</label>
                        <select wire:model.live="perPage"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="12">12</option>
                            <option value="24">24</option>
                            <option value="48">48</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category chips --}}
        @if($categories->count())
            <div class="flex flex-wrap gap-2">
                <button wire:click="$set('categoryFilter', '')"
                        class="rounded-full px-3 py-1 text-[11px] font-bold transition {{ blank($categoryFilter) ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    {{ __('All') }}
                </button>
                @foreach($categories as $cat)
                    <button wire:click="$set('categoryFilter', '{{ $cat->name }}')"
                            class="rounded-full px-3 py-1 text-[11px] font-bold transition {{ $categoryFilter === $cat->name ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        {{ $cat->name }}
                        <span class="ml-1 inline-flex items-center justify-center rounded-full bg-white/20 px-1.5 text-[9px]">{{ $cat->books_count }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Tabs --}}
        <div class="flex gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800 w-fit">
            <button wire:click="$set('tab', 'books')"
                    class="rounded-md px-4 py-1.5 text-xs font-bold transition {{ $tab === 'books' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                <x-heroicon-o-book-open class="mr-1 inline h-3.5 w-3.5"/>
                {{ __('Books & Resources') }}
            </button>
            <button wire:click="$set('tab', 'knowledge')"
                    class="rounded-md px-4 py-1.5 text-xs font-bold transition {{ $tab === 'knowledge' ? 'bg-white text-slate-900 shadow dark:bg-slate-700 dark:text-white' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400' }}">
                <x-heroicon-o-academic-cap class="mr-1 inline h-3.5 w-3.5"/>
                {{ __('Knowledge Hub') }}
            </button>
        </div>

        {{-- Tab Content: Books --}}
        @if($tab === 'books')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($this->books as $book)
                    <div class="group flex flex-col rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                        <div class="mb-3 flex h-32 items-center justify-center overflow-hidden rounded-lg bg-slate-50 dark:bg-slate-950/40">
                            @if($book->cover_image_path)
                                <img src="{{ resolve_public_asset_path($book->cover_image_path) }}"
                                     alt="{{ $book->title }}"
                                     class="h-full w-full object-cover">
                            @elseif($book->media_type === 'digital' && $book->external_url)
                                <x-heroicon-o-play-circle class="h-10 w-10 text-green-400"/>
                            @else
                                <x-heroicon-o-book-open class="h-10 w-10 text-indigo-300 dark:text-indigo-600"/>
                            @endif
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">{{ $book->title }}</h4>
                        @if($book->authors->count())
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                                {{ $book->authors->pluck('name')->join(', ') }}
                            </p>
                        @endif

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($book->category)
                                <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    {{ $book->category->name }}
                                </span>
                            @endif
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $book->media_type === 'digital' ? 'bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300' }}">
                                {{ ucfirst($book->media_type ?? 'Book') }}
                            </span>
                            @if($book->format)
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $book->format->name }}
                                </span>
                            @endif
                        </div>

                        @if($book->description)
                            <p class="mt-2 line-clamp-2 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">{{ $book->description }}</p>
                        @endif

                        <div class="mt-auto pt-3">
                            @if($book->media_type === 'digital' && ($book->external_url || $book->file_path))
                                <a href="{{ $book->external_url ?? asset('storage/'.$book->file_path) }}"
                                   target="_blank" rel="noopener"
                                   class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-indigo-500">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3"/>
                                    {{ __('Access Resource') }}
                                </a>
                            @elseif($book->media_type === 'physical')
                                @php
                                    $total = $book->getTotalCopiesCount();
                                    $available = $book->getAvailableCopiesCount();
                                @endphp
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-semibold {{ $available > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                                        {{ $available }}/{{ $total }} {{ __('available') }}
                                    </span>
                                    @if($available > 0)
                                        <span class="text-slate-400">{{ __('Visit library') }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-[11px] font-semibold text-slate-400">{{ __('Physical copy') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <x-heroicon-o-book-open class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600"/>
                        <p class="mt-3 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('No resources found.') }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('Try adjusting your search or filters.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->books->links() }}
            </div>
        @endif

        {{-- Tab Content: Knowledge Hub --}}
        @if($tab === 'knowledge')
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @forelse($this->knowledgeAssets as $asset)
                    <div class="group flex flex-col rounded-xl border border-slate-100 bg-white p-4 shadow-sm transition hover:border-purple-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-purple-700">
                        <div class="mb-3 flex h-32 items-center justify-center overflow-hidden rounded-lg bg-purple-50/50 dark:bg-purple-900/10">
                            @if($asset->cover_image_path)
                                <img src="{{ resolve_public_asset_path($asset->cover_image_path) }}"
                                     alt="{{ $asset->title }}"
                                     class="h-full w-full object-cover">
                            @else
                                <x-heroicon-o-academic-cap class="h-10 w-10 text-purple-300 dark:text-purple-600"/>
                            @endif
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 dark:text-white line-clamp-2">{{ $asset->title }}</h4>
                        @if($asset->subtitle)
                            <p class="mt-0.5 text-[11px] text-slate-400 line-clamp-1">{{ $asset->subtitle }}</p>
                        @endif
                        @if($asset->authors->count())
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400 line-clamp-1">
                                {{ $asset->authors->pluck('name')->join(', ') }}
                            </p>
                        @endif

                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @if($asset->category)
                                <span class="inline-flex rounded-full bg-purple-50 px-2 py-0.5 text-[10px] font-bold text-purple-600 dark:bg-purple-900/30 dark:text-purple-300">
                                    {{ $asset->category->name }}
                                </span>
                            @endif
                            @if($asset->subtype)
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($asset->subtype) }}
                                </span>
                            @endif
                            <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $asset->media_type === 'digital' ? 'bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-300' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300' }}">
                                {{ ucfirst($asset->media_type) }}
                            </span>
                        </div>

                        @if($asset->abstract_description)
                            <p class="mt-2 line-clamp-2 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">{{ $asset->abstract_description }}</p>
                        @endif

                        <div class="mt-auto pt-3">
                            @if($asset->media_type === 'digital' && ($asset->external_url || $asset->file_path))
                                <a href="{{ $asset->external_url ?? asset('storage/'.$asset->file_path) }}"
                                   target="_blank" rel="noopener"
                                   class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-purple-500">
                                    <x-heroicon-o-arrow-top-right-on-square class="h-3 w-3"/>
                                    {{ __('Access') }}
                                </a>
                            @else
                                <span class="text-[11px] font-semibold text-slate-400">{{ __('Physical copy') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-12 text-center">
                        <x-heroicon-o-academic-cap class="mx-auto h-12 w-12 text-slate-300 dark:text-slate-600"/>
                        <p class="mt-3 text-sm font-semibold text-slate-500 dark:text-slate-400">{{ __('No knowledge assets found.') }}</p>
                        <p class="mt-1 text-xs text-slate-400">{{ __('Try adjusting your search or filters.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $this->knowledgeAssets->links() }}
            </div>
        @endif

    </div>
</x-filament-panels::page>
