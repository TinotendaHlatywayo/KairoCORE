<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-gray-600 dark:text-gray-400 max-w-3xl">
                {{ __('Browse all website templates, preview their design, then choose one to manage content or open in Visual Studio. This works for every tenant/school.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($premadeTemplates as $key => $template)
                @php
                    $palette = $template['palette'] ?? ($template['theme']['palette'] ?? []);
                    $bg = $palette['background'] ?? '#f8fafc';
                    $card = $palette['card_bg'] ?? '#ffffff';
                    $primary = $palette['primary'] ?? '#1e3a8a';
                    $secondary = $palette['secondary'] ?? '#0284c7';
                    $accent = $palette['accent'] ?? '#f59e0b';
                    $text = $palette['text'] ?? '#0f172a';
                    $isActive = !empty($template['is_active']);
                    $templateId = $template['template_id'] ?? null;
                    $homeId = $template['home_page_id'] ?? null;
                @endphp

                <div class="group relative overflow-hidden rounded-2xl border {{ $isActive ? 'border-primary-500 ring-2 ring-primary-500/30' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-900 shadow-sm hover:shadow-lg transition-all duration-300">
                    @if($isActive)
                        <div class="absolute top-3 right-3 z-10">
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-1 text-[11px] font-bold text-white shadow">
                                <span class="h-1.5 w-1.5 rounded-full bg-white"></span> {{ __('Live') }}
                            </span>
                        </div>
                    @endif

                    {{-- Template design preview --}}
                    <div class="relative h-52 overflow-hidden p-4" style="background: linear-gradient(140deg, {{ $bg }} 0%, {{ $card }} 100%); color: {{ $text }};">
                        <div class="flex items-center justify-between mb-3">
                            <span class="h-2.5 w-20 rounded-full" style="background: {{ $primary }}"></span>
                            <div class="flex gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $secondary }}"></span>
                                <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $accent }}"></span>
                            </div>
                        </div>

                        <div class="rounded-xl p-3 shadow-sm" style="background: {{ $card }}; border: 1px solid {{ $primary }}22;">
                            <div class="flex gap-3 items-center">
                                <div class="flex-1 space-y-2">
                                    <div class="h-2.5 w-4/5 rounded" style="background: {{ $primary }}"></div>
                                    <div class="h-1.5 w-full rounded" style="background: {{ $text }}33"></div>
                                    <div class="h-1.5 w-3/4 rounded" style="background: {{ $text }}22"></div>
                                    <div class="mt-2 flex gap-2">
                                        <span class="h-4 w-16 rounded-md" style="background: {{ $primary }}"></span>
                                        <span class="h-4 w-14 rounded-md" style="background: {{ $accent }}55; border:1px solid {{ $accent }}88"></span>
                                    </div>
                                </div>
                                <div class="w-20 h-16 rounded-lg" style="background: linear-gradient(145deg, {{ $secondary }}55, {{ $accent }}55); border:1px solid {{ $primary }}22"></div>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2">
                            <div class="h-10 rounded-lg" style="background: {{ $card }}; border-top: 3px solid {{ $primary }}"></div>
                            <div class="h-10 rounded-lg" style="background: {{ $card }}; border-top: 3px solid {{ $secondary }}"></div>
                            <div class="h-10 rounded-lg" style="background: {{ $card }}; border-top: 3px solid {{ $accent }}"></div>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 h-10" style="background: linear-gradient(to top, {{ $bg }}, transparent)"></div>
                    </div>

                    <div class="p-4 space-y-3">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight">{{ $template['name'] ?? $key }}</h3>
                            </div>
                            @if(!empty($template['subtitle']))
                                <p class="text-xs font-semibold mt-0.5" style="color: {{ $primary }}">{{ $template['subtitle'] }}</p>
                            @endif
                            @if(!empty($template['description']))
                                <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400 line-clamp-3">{{ $template['description'] }}</p>
                            @endif
                        </div>

                        <div class="flex items-center gap-2 pt-1">
                            <span class="h-3 w-3 rounded-full border border-white shadow" style="background: {{ $primary }}" title="{{ __('Primary') }}"></span>
                            <span class="h-3 w-3 rounded-full border border-white shadow" style="background: {{ $secondary }}" title="{{ __('Secondary') }}"></span>
                            <span class="h-3 w-3 rounded-full border border-white shadow" style="background: {{ $accent }}" title="{{ __('Accent') }}"></span>
                            <span class="ml-auto text-[11px] text-gray-500">{{ __('Full site preview') }}</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 pt-1">
                            @if($templateId)
                                <a href="{{ \App\Filament\App\Pages\WebsiteContentManager::getUrl(['templateId' => $templateId]) }}"
                                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-primary-600 px-3 py-2 text-xs font-bold text-white hover:bg-primary-500 transition">
                                    <x-heroicon-m-pencil-square class="h-4 w-4" />
                                    {{ __('Manage Content') }}
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-400">{{ __('Manage Content') }}</span>
                            @endif

                            @if($homeId)
                                <a href="{{ \App\Filament\App\Pages\VisualCmsBuilder::getUrl(['pageId' => $homeId]) }}"
                                   class="inline-flex items-center justify-center gap-1.5 rounded-xl bg-gray-900 px-3 py-2 text-xs font-bold text-white hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white transition">
                                    <x-heroicon-m-paint-brush class="h-4 w-4" />
                                    {{ __('Open Visual Studio') }}
                                </a>
                            @else
                                <span class="inline-flex items-center justify-center rounded-xl bg-gray-100 px-3 py-2 text-xs font-bold text-gray-400">{{ __('Open Visual Studio') }}</span>
                            @endif
                        </div>

                        @if($templateId)
                            <button type="button" wire:click="applyTemplate({{ $templateId }})"
                                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl border {{ $isActive ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200' }} px-3 py-2 text-xs font-bold transition"
                                    @if($isActive) disabled @endif>
                                <x-heroicon-m-check-circle class="h-4 w-4" />
                                {{ $isActive ? __('Currently Live') : __('Set as Live Template') }}
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @if(empty($premadeTemplates))
            <div class="rounded-xl border border-dashed border-gray-300 dark:border-gray-700 p-10 text-center text-gray-500">
                {{ __('No templates available yet. Use "Create cms website" to generate your website templates.') }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
