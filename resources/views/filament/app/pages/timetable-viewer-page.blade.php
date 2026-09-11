<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 1. Template Switcher Bar -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center gap-4 justify-between">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Active Template:') }}</label>
                    <select
                        wire:change="selectTemplate($event.target.value)"
                        class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white text-sm font-semibold"
                    >
                        @foreach($templates as $template)
                            <option value="{{ $template['id'] }}" @selected($activeTemplateId === $template['id'])>
                                {{ $template['name'] }}{{ $template['is_active'] ? ' (Active)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($activeTemplateId)
                    @php
                        $isCurrentActive = collect($templates)->firstWhere('id', $activeTemplateId)['is_active'] ?? false;
                    @endphp
                    @if(! $isCurrentActive)
                        <x-filament::button type="button" wire:click="makeActive({{ $activeTemplateId }})" color="success" icon="heroicon-m-check-circle" size="sm">
                            {{ __('Set as Active Timetable') }}
                        </x-filament::button>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 dark:bg-green-500/10 px-3 py-1 text-xs font-bold text-green-800 dark:text-green-400">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                            {{ __('This is the active school timetable') }}
                        </span>
                    @endif
                @endif
            </div>

            @if(count($activeTemplateSummary) > 0)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('School Hours') }}</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $activeTemplateSummary['hours'] }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('Period Length') }}</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $activeTemplateSummary['length'] }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('Tea Break') }}</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $activeTemplateSummary['break'] }}</div>
                    </div>
                    <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-3">
                        <div class="text-[10px] font-bold uppercase text-gray-400 dark:text-gray-500">{{ __('Lunch') }}</div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white mt-0.5">{{ $activeTemplateSummary['lunch'] }}</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 2. VIEW SCOPE TOOLBAR (Class ⇄ Stream) -->
        <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 overflow-visible relative z-30">
            @if(\Modules\Academics\Models\Section::exists())
                <div class="flex flex-wrap items-center gap-4 overflow-visible">
                    <!-- Class | Stream Scope Toggle -->
                    <div class="inline-flex rounded-lg p-1 bg-gray-100 dark:bg-gray-800 ring-1 ring-gray-200 dark:ring-gray-700">
                        <button
                            type="button"
                            wire:click="selectScope('class')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $viewScope === 'class' ? 'bg-white dark:bg-gray-900 text-green-700 dark:text-green-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}"
                        >
                            {{ __('Class View') }}
                        </button>
                        <button
                            type="button"
                            wire:click="selectScope('stream')"
                            class="px-3 py-1.5 text-xs font-bold rounded-md transition-colors {{ $viewScope === 'stream' ? 'bg-white dark:bg-gray-900 text-green-700 dark:text-green-400 shadow-sm' : 'text-gray-500 hover:text-gray-800 dark:hover:text-gray-200' }}"
                        >
                            {{ __('Stream View') }}
                        </button>
                    </div>

                    @if($viewScope === 'class')
                        @php
                            $selectedSection = \Modules\Academics\Models\Section::with(['course', 'classTeacher'])->find($activeFilterClassId);
                        @endphp
                        <div class="relative" x-data="{ open: false }">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Active View Class:') }}</label>
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="inline-flex items-center gap-x-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-white dark:ring-gray-700"
                                >
                                    {{ $selectedSection ? "{$selectedSection->course->name} {$selectedSection->name}" : 'Select Class...' }}
                                    <svg class="-mr-1 h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>

                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-cloak
                                x-transition
                                class="absolute left-0 top-full mt-2 z-[9999] w-80 rounded-xl bg-white p-3 shadow-2xl ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
                            >
                                <input
                                    type="text"
                                    wire:model.live.debounce.150ms="classSearchQuery"
                                    placeholder="{{ __('Type to filter classes...') }}"
                                    class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 mb-2 dark:text-white px-3 py-2"
                                >
                                <div class="overflow-y-auto max-h-56 divide-y divide-gray-100 dark:divide-gray-700">
                                    @php
                                        $filtered = $this->getFilteredSections();
                                        $hasMore = $this->classSearchQuery === '' && \Modules\Academics\Models\Section::where('school_id', app('current_tenant')->id)->count() > $filtered->count();
                                    @endphp
                                    @if($filtered->count() > 0)
                                        @foreach($filtered as $sec)
                                            <button
                                                type="button"
                                                wire:click="selectClass({{ $sec->id }}); open = false;"
                                                class="w-full text-left px-3 py-2 text-xs rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 hover:text-green-700 dark:hover:text-green-400 font-semibold text-gray-700 dark:text-gray-300"
                                            >
                                                {{ $sec->course->name }} {{ $sec->name }}
                                            </button>
                                        @endforeach
                                        @if($hasMore)
                                            <div class="text-center py-2 text-[10px] text-gray-400 italic">{{ __('Kairo CORE. All rights reserved.') }}</div>
                                        @endif
                                    @else
                                        <div class="text-center py-4 text-xs text-gray-400 italic">{{ __('No matching classes found.') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if($selectedSection?->classTeacher)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 dark:bg-green-500/10 px-3 py-1 text-xs font-bold text-green-800 dark:text-green-400">
                                {{ __('Class Teacher:') }} {{ $selectedSection->classTeacher->name }}
                            </span>
                        @endif
                    @else
                        <!-- Stream (Form Level) selector -->
                        <div class="flex items-center gap-3">
                            <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Form Level:') }}</label>
                            <select
                                wire:change="selectStreamCourse($event.target.value)"
                                class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white text-sm font-semibold"
                            >
                                @foreach($this->getAvailableCourses() as $course)
                                    <option value="{{ $course->id }}" @selected($selectedCourseId === $course->id)>
                                        {{ $course->name }} ({{ $course->sections_count }} {{ __('classes') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <span class="text-xs text-gray-500 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                            </svg>
                            {{ __('One merged schedule for every class stream in this form.') }}
                        </span>
                    @endif

                    <div class="ml-auto flex items-center gap-2">
                        @if($viewScope === 'stream')
                            @if($selectedCourseId)
                                <x-filament::button :href="route('tenant.timetable.print-stream', ['course' => $selectedCourseId])" tag="a" color="info" icon="heroicon-o-printer" target="_blank" size="sm">
                                    {{ __('Print Stream') }}
                                </x-filament::button>
                            @endif
                        @elseif($activeFilterClassId)
                            <x-filament::button :href="route('tenant.timetable.print', ['section' => $activeFilterClassId])" tag="a" color="info" icon="heroicon-o-printer" target="_blank" size="sm">
                                {{ __('Print Class') }}
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-danger small fw-semibold">
                    {{ __('⚠ Gaps Detected: Please register at least one Form Level and Class Stream inside your portal before scheduling lessons.') }}
                </div>
            @endif
        </div>

        <!-- 3. Read-only Grid Canvas -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if(count($timeSlots) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-center border-collapse text-sm bg-white dark:bg-gray-900">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold uppercase text-xs">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="p-3" style="width: 150px;">{{ $viewScope === 'stream' ? __('Form / Period') : __('Time Period') }}</th>
                                @foreach($days as $day)
                                    <th class="p-3">{{ ucfirst($day) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
                            @foreach($timeSlots as $slot)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 {{ $slot['is_break'] ? 'bg-amber-500/5 dark:bg-amber-500/5' : '' }}">
                                    <td class="p-3 font-semibold border-r border-gray-200 dark:border-gray-800">
                                        <div class="text-gray-900 dark:text-white font-bold">{{ $slot['name'] }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ date('H:i', strtotime($slot['start_time'])) }} - {{ date('H:i', strtotime($slot['end_time'])) }}
                                        </div>
                                    </td>

                                    @if($viewScope === 'stream')
                                        @foreach($days as $day)
                                            @php
                                                $entries = $streamMatrix[$slot['id'].'|'.$day] ?? [];
                                            @endphp
                                            <td class="p-2 border-r border-gray-200 dark:border-gray-800 align-top">
                                                @if($slot['is_break'])
                                                    <div class="py-2 bg-amber-500/5 text-amber-700 dark:text-amber-400 font-bold rounded-lg border border-dashed border-amber-300 dark:border-amber-500/20 uppercase text-xs">
                                                        {{ $slot['name'] }}
                                                    </div>
                                                @elseif(count($entries) > 0)
                                                    <div class="space-y-1.5">
                                                        @foreach($entries as $entry)
                                                            <div class="p-2 rounded-lg border text-left shadow-sm {{ $entry['color_classes'] }}">
                                                                <div class="text-[10px] font-extrabold uppercase text-gray-500 dark:text-gray-400">{{ $entry['section_label'] }}</div>
                                                                <div class="font-extrabold text-xs uppercase tracking-wide">
                                                                    {{ $entry['subject'] }}
                                                                    <span class="font-semibold text-[10px] opacity-75">({{ $entry['teacher_initials'] }})</span>
                                                                </div>
                                                                @if($entry['room'] && $entry['room'] !== '—')
                                                                    <div class="text-[10px] opacity-75 font-semibold mt-0.5">{{ $entry['room'] }}</div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-300 dark:text-gray-700 border border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-4">
                                                        {{ __('No Lessons') }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    @else
                                        @foreach($days as $day)
                                            @php
                                                $lesson = $matrix[$slot['id']][$day] ?? null;
                                            @endphp
                                            <td class="p-3 border-r border-gray-200 dark:border-gray-800">
                                                @if($slot['is_break'])
                                                    <div class="py-2 bg-amber-500/5 text-amber-700 dark:text-amber-400 font-bold rounded-lg border border-dashed border-amber-300 dark:border-amber-500/20 uppercase text-xs">
                                                        {{ $slot['name'] }}
                                                    </div>
                                                @elseif($lesson)
                                                    <div class="p-3 rounded-lg border text-left shadow-sm {{ $lesson['color_classes'] }}">
                                                        <div class="font-extrabold text-xs uppercase tracking-wide">{{ $lesson['subject'] }}</div>
                                                        <div class="text-[10px] opacity-80 mt-1.5 font-semibold">{{ __('Teacher:') }} <strong>{{ $lesson['teacher'] }}</strong></div>
                                                        <div class="text-[10px] opacity-80 font-semibold">{{ __('Room:') }} <strong>{{ $lesson['room'] }}</strong></div>
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-300 dark:text-gray-700 border border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-4">
                                                        {{ __('No Lessons') }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Time slots are not configured yet for this template. Please configure your settings in the Academic Workspace / Timetables builder.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
