<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Dashboard Header controls -->
        <div class="flex flex-wrap justify-between items-center gap-4 fi-section rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-center gap-4">
                
                <!-- 1. Active School-Wide Template Selector -->
                <div class="flex items-center gap-3 border-r border-gray-100 dark:border-gray-800 pr-4">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Active School Schedule:') }}</label>
                    @if(\Modules\Timetables\Models\TimetableTemplate::exists())
                        <select wire:model.change="activeSchoolTemplateId" wire:change="switchActiveTemplate" class="rounded-lg border-gray-200 dark:bg-gray-800 dark:border-gray-700 text-sm focus:border-green-500 focus:ring-green-500">
                            <option value="">{{ __('-- Select Active Schedule --') }}</option>
                            @foreach(\Modules\Timetables\Models\TimetableTemplate::all() as $tmpl)
                                <option value="{{ $tmpl->id }}">{{ $tmpl->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <span class="text-xs text-gray-400 italic">{{ __('No saved templates found. Create one in the Visual Builder.') }}</span>
                    @endif
                </div>

                <!-- 2. VIEW SCOPE FILTER -->
                <div class="flex items-center gap-3 border-r border-gray-100 dark:border-gray-800 pr-4">
                    <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('View Scope:') }}</label>
                    <div class="flex items-center gap-2">
                        @foreach([
                            'school' => __('Whole School'),
                            'stream' => __('Whole Stream (Form)'),
                            'class' => __('Specific Class'),
                        ] as $key => $label)
                            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                <input 
                                    type="radio" 
                                    wire:model="viewScope" 
                                    value="{{ $key }}" 
                                    @if($key === 'school') wire:click="selectScope('school')" @endif
                                    @if($key === 'stream') wire:click="selectScope('stream')" @endif
                                    @if($key === 'class') wire:click="selectScope('class')" @endif
                                    class="h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                >
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- 3. STREAM SELECTOR (visible when scope = stream) -->
                @if($viewScope === 'stream')
                    <div class="flex items-center gap-3 border-r border-gray-100 dark:border-gray-800 pr-4">
                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Select Stream:') }}</label>
                        <select 
                            wire:model="selectedCourseId" 
                            wire:change="selectCourse({{ $selectedCourseId }})"
                            class="rounded-lg border-gray-200 dark:bg-gray-800 dark:border-gray-700 text-sm focus:border-green-500 focus:ring-green-500"
                        >
                            <option value="">{{ __('-- Select Stream --') }}</option>
                            @foreach($this->getAvailableCourses() as $course)
                                <option value="{{ $course->id }}">{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- 4. CLASS SELECTOR (visible when scope = class) -->
                @if($viewScope === 'class')
                    @if(\Modules\Academics\Models\Section::where('school_id', app('current_tenant')->id)->exists())
                        <div class="relative" x-data="{ open: @entangle('isSearchOpen') }">
                            <div class="flex items-center gap-3">
                                <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Select Class:') }}</label>
                                @php
                                    $selectedSection = \Modules\Academics\Models\Section::find($activeFilterClassId);
                                @endphp
                                <!-- Dynamic Trigger Button -->
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

                            <!-- Dropdown panel -->
                            <div 
                                x-show="open" 
                                @click.away="open = false" 
                                class="absolute left-0 z-50 mt-2 w-64 origin-top-left rounded-xl bg-white p-3 shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
                                style="display: none;"
                            >
                                <!-- Search box -->
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.150ms="classSearchQuery" 
                                    placeholder="Type to filter classes..." 
                                    class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 mb-2 dark:text-white"
                                >
                                
                                <!-- Search list -->
                                <div class="overflow-y-auto max-h-48 divide-y divide-gray-100 dark:divide-gray-700">
                                    @php $filtered = $this->getFilteredSections(); @endphp
                                    @if($filtered->count() > 0)
                                        @foreach($filtered as $sec)
                                            <button 
                                                type="button" 
                                                wire:click="selectClass({{ $sec->id }})" 
                                                class="w-full text-left px-3 py-2 text-xs rounded-lg hover:bg-green-50 dark:hover:bg-green-500/10 hover:text-green-700 dark:hover:text-green-400 font-semibold text-gray-700 dark:text-gray-300"
                                            >
                                                {{ $sec->course->name }} {{ $sec->name }}
                                            </button>
                                        @endforeach
                                    @else
                                        <div class="text-center py-4 text-xs text-gray-400 italic">{{ __('No matching classes found.') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <span class="text-xs text-gray-400 italic">{{ __('Please register classes under forms first.') }}</span>
                    @endif
                @endif
            </div>
            
            <div class="flex gap-2">
                @if($activeFilterClassId)
                    <x-filament::button :href="route('tenant.timetable.print', ['section' => $activeFilterClassId])" tag="a" color="info" icon="heroicon-o-printer" target="_blank">
                        {{ __('Print Timetable') }}
                    </x-filament::button>
                @endif

                <x-filament::button :href="route('filament.app.resources.timetable-lessons.create')" tag="a" color="success">
                    {{ __('Schedule New Lesson') }}
                </x-filament::button>
            </div>
        </div>

        <!-- High-contrast weekly scheduler grid -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if(count($timeSlots) > 0)
                @if($viewScope === 'class' && $activeFilterClassId)
                    <div class="mb-4 flex items-center gap-2 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800/40 px-4 py-2.5 text-xs text-blue-800 dark:text-blue-300">
                        <x-heroicon-o-arrows-right-left class="h-4 w-4 shrink-0"/>
                        <span>{{ __('Drag a lesson onto another cell to move it, or onto an occupied cell to exchange the two lessons.') }}</span>
                    </div>
                @endif

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800" x-data="{ dragId: null, dragOver: null }">
                    <table class="w-full text-center border-collapse text-sm bg-white dark:bg-gray-900">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-bold uppercase text-xs">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="p-3" style="width: 150px;">{{ __('Time Period') }}</th>
                                @foreach($days as $day)
                                    <th class="p-3">{{ ucfirst($day) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
                            @foreach($timeSlots as $slot)
                                @if(!$slot['is_break'])
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20">
                                    <!-- Time column -->
                                    <td class="p-3 font-semibold border-r border-gray-200 dark:border-gray-800">
                                        <div class="text-gray-900 dark:text-white font-bold">{{ $slot['name'] }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ date('H:i', strtotime($slot['start_time'])) }} - {{ date('H:i', strtotime($slot['end_time'])) }}
                                        </div>
                                    </td>

                                    <!-- Scheduled lessons per day -->
                                    @foreach($days as $day)
                                        @php
                                            $lesson = $matrix[$slot['id']][$day] ?? null;
                                            $cellKey = $day.'-'.$slot['id'];
                                        @endphp
                                        <td
                                            class="p-3 border-r border-gray-200 dark:border-gray-800 transition-colors"
                                            :class="dragOver === '{{ $cellKey }}' ? 'bg-green-50 dark:bg-green-950/30' : ''"
                                            @dragover.prevent="dragOver = '{{ $cellKey }}'"
                                            @dragleave="if (dragOver === '{{ $cellKey }}') dragOver = null"
                                            @drop.prevent="if (dragId) { $wire.swapLesson(dragId, {{ $slot['id'] }}, '{{ $day }}'); } dragId = null; dragOver = null;"
                                        >
                                            @if($lesson)
                                                <div
                                                    class="p-3 rounded-lg border text-left shadow-sm relative group cursor-grab active:cursor-grabbing transition {{ $lesson['color_classes'] }}"
                                                    :class="dragId == {{ $lesson['id'] }} ? 'opacity-40' : ''"
                                                    draggable="true"
                                                    @dragstart="dragId = {{ $lesson['id'] }}"
                                                    @dragend="dragId = null; dragOver = null"
                                                    title="{{ __('Drag to move or exchange this lesson') }}"
                                                >
                                                    <div class="flex justify-between items-start">
                                                        <span class="font-extrabold text-xs uppercase tracking-wide">{{ $lesson['subject'] }}</span>
                                                    </div>
                                                    <div class="text-[10px] opacity-80 mt-1.5 font-semibold">{{ __('Teacher:') }} <strong>{{ $lesson['teacher'] }}</strong></div>
                                                    <div class="text-[10px] opacity-80 font-semibold">{{ __('Room:') }} <strong>{{ $lesson['room'] }}</strong></div>

                                                    <div class="mt-3 pt-2 border-t border-black/10 dark:border-white/10 flex justify-end">
                                                        <a
                                                            href="{{ route('filament.app.resources.timetable-lessons.record-attendance', ['record' => $lesson['id']]) }}"
                                                            class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider text-green-700 dark:text-green-400 hover:opacity-80"
                                                        >
                                                            {{ __('Take Attendance &rarr;') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <div
                                                    class="text-xs text-gray-300 dark:text-gray-700 border border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-4 transition"
                                                    :class="dragOver === '{{ $cellKey }}' ? 'text-green-600 dark:text-green-400 border-green-400 dark:border-green-600' : ''"
                                                >
                                                    {{ __('Empty Slot') }}
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Time slots are not configured yet. Please configure your settings under Time Slots.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>