<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 1. Active Schedule Specifications Summary Card -->
        @if(count($activeTemplateSummary) > 0)
            <div class="rounded-xl border border-green-600/20 bg-green-50 p-6 dark:bg-green-500/10 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="flex gap-x-3">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-600 dark:text-green-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <div>
                            <h4 class="text-md font-bold text-green-950 dark:text-green-400">{{ __('Active School Schedule:') }} <span class="underline">{{ $activeTemplateSummary['name'] }}</span></h4>
                            <div class="text-xs text-green-800 dark:text-green-500 mt-2 grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-1">
                                <div>{{ __('• Lessons Duration:') }} <strong>{{ $activeTemplateSummary['hours'] }}</strong></div>
                                <div>{{ __('• Period Length:') }} <strong>{{ $activeTemplateSummary['length'] }}</strong></div>
                                <div>{{ __('• Tea Break:') }} <strong>{{ $activeTemplateSummary['break'] }}</strong></div>
                                <div>{{ __('• Lunch Break:') }} <strong>{{ $activeTemplateSummary['lunch'] }}</strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-warning-600/20 bg-warning-50 p-4 dark:bg-warning-500/10">
                <div class="flex gap-x-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-warning-600 dark:text-warning-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-warning-800 dark:text-warning-400">{{ __('Active Schedule Template Not Selected') }}</h4>
                        <p class="text-xs text-warning-700 dark:text-warning-500 mt-1">Please configure your timetable parameters below, enter a template name, check "Set as School's Active Timetable", and compile.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- 2. Generator Options Form -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <form wire:submit.prevent="generateSlots" class="space-y-4">
                <h4 class="text-md font-bold text-gray-950 dark:text-white">{{ __('Auto-Generate School Period Blocks') }}</h4>
                {{ $this->form }}
                <div class="flex justify-between items-center pt-3 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        @if($data['template_lifecycle'] === 'existing' && filled($data['active_template_id'] ?? null))
                            <x-filament::button type="button" wire:click="deleteTemplate" color="danger" size="sm">
                                {{ __('Delete Selected Template') }}
                            </x-filament::button>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <x-filament::button type="button" wire:click="autoGenerateLessons" color="info" icon="heroicon-m-sparkles">
                            {{ __('Auto-Generate All Lessons') }}
                        </x-filament::button>
                        <x-filament::button type="submit" color="success">
                            {{ __('Compile & Generate Time Slots') }}
                        </x-filament::button>
                    </div>
                </div>
            </form>
        </div>

        <!-- 3. SEARCHABLE CLASS COMBOBOX -->
        <div class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 flex justify-between items-center">
            @if(\Modules\Academics\Models\Section::exists())
                <div class="relative" x-data="{ open: @entangle('isSearchOpen') }">
                    <div class="flex items-center gap-3">
                        <label class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ __('Active View Class:') }}</label>
                        @php
                            $selectedSection = \Modules\Academics\Models\Section::with('course')->find($activeFilterClassId);
                        @endphp
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

                    <!-- Searchable dropdown panel -->
                    <div 
                        x-show="open" 
                        @click.away="open = false" 
                        class="absolute left-0 z-50 mt-2 w-64 origin-top-left rounded-xl bg-white p-3 shadow-lg ring-1 ring-black/5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
                        style="display: none;"
                    >
                        <input 
                            type="text" 
                            wire:model.live.debounce.150ms="classSearchQuery" 
                            placeholder="Type to filter classes..." 
                            class="w-full text-xs rounded-lg border-gray-300 dark:bg-gray-900 dark:border-gray-700 mb-2 dark:text-white"
                        >
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
                <p class="text-xs text-gray-500">{{ __('Drag scheduled blocks between periods to reschedule instantly.') }}</p>
            @else
                <div class="text-danger small fw-semibold">
                    {{ __('⚠ Gaps Detected: Please register at least one Form Level and Class Stream inside your portal before scheduling lessons.') }}
                </div>
            @endif
        </div>

        <!-- 4. Grid Canvas -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if(count($timeSlots) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
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
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 {{ $slot['is_break'] ? 'bg-amber-500/5 dark:bg-amber-500/5' : '' }}">
                                    <!-- Time Period Cell -->
                                    <td class="p-3 font-semibold border-r border-gray-200 dark:border-gray-800">
                                        <div class="text-gray-900 dark:text-white font-bold">{{ $slot['name'] }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">
                                            {{ date('H:i', strtotime($slot['start_time'])) }} - {{ date('H:i', strtotime($slot['end_time'])) }}
                                        </div>
                                    </td>

                                    <!-- Scheduled Days -->
                                    @foreach($days as $day)
                                        @php
                                            $lesson = $matrix[$slot['id']][$day] ?? null;
                                        @endphp
                                        <td 
                                            wire:key="slot_{{ $slot['id'] }}_{{ $day }}"
                                            class="p-3 border-r border-gray-200 dark:border-gray-800"
                                            x-on:dragover.prevent=""
                                            x-on:drop="
                                                const lessonId = event.dataTransfer.getData('text/plain');
                                                if(lessonId) {
                                                    @this.call('moveLesson', lessonId, {{ $slot['id'] }}, '{{ $day }}');
                                                }
                                            "
                                        >
                                            @if($slot['is_break'])
                                                <div class="py-2 bg-amber-500/5 text-amber-700 dark:text-amber-400 font-bold rounded-lg border border-dashed border-amber-300 dark:border-amber-500/20 uppercase text-xs">
                                                    {{ $slot['name'] }}
                                                </div>
                                            @elseif($lesson)
                                                <!-- Dynamic Theme-Compliant Draggable Card -->
                                                <div 
                                                    wire:key="lesson_{{ $lesson['id'] }}"
                                                    draggable="true"
                                                    x-on:dragstart="event.dataTransfer.setData('text/plain', '{{ $lesson['id'] }}')"
                                                    wire:click="openEditLessonModal({{ $lesson['id'] }})"
                                                    class="p-3 rounded-lg border text-left shadow-sm relative group cursor-pointer transition-all hover:scale-[1.02] {{ $lesson['color_classes'] }}"
                                                >
                                                    <div class="flex justify-between items-start">
                                                        <span class="font-extrabold text-xs uppercase tracking-wide">{{ $lesson['subject'] }}</span>
                                                        <button 
                                                            type="button" 
                                                            wire:click.stop="deleteLesson({{ $lesson['id'] }})"
                                                            class="text-red-500 hover:text-red-700 opacity-0 group-hover:opacity-100 transition-opacity"
                                                        >
                                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div class="text-[10px] opacity-80 mt-1.5 font-semibold">{{ __('Teacher:') }} <strong>{{ $lesson['teacher'] }}</strong></div>
                                                    <div class="text-[10px] opacity-80 font-semibold">{{ __('Room:') }} <strong>{{ $lesson['room'] }}</strong></div>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-300 dark:text-gray-700 border border-dashed border-gray-200 dark:border-gray-800 rounded-lg py-4">
                                                    {{ __('Empty Slot') }}
                                                </div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
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

    <!-- INLINE CARD EDITOR MODAL -->
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" aria-hidden="true" wire:click="$set('isEditModalOpen', false)"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">{{ __('&#8203;') }}</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-900 rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-width sm:w-full sm:max-w-lg border border-gray-200 dark:border-gray-800">
                    <div class="bg-white dark:bg-gray-900 px-6 pt-6 pb-4">
                        <h3 class="text-lg font-bold text-gray-950 dark:text-white border-b border-gray-100 dark:border-gray-800 pb-3" id="modal-title">
                            {{ __('Edit Scheduled Lesson Details') }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1">{{ __('Subject') }}</label>
                                <select wire:model="editSubjectId" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                                    @foreach(\Modules\Academics\Models\Subject::all() as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }} ({{ $sub->code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1">{{ __('Teacher') }}</label>
                                <select wire:model="editTeacherId" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                                    @foreach(\App\Models\User::whereNotNull('school_id')->get() as $teach)
                                        <option value="{{ $teach->id }}">{{ $teach->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-1">{{ __('Classroom') }}</label>
                                <select wire:model="editClassroomId" class="w-full text-sm rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
                                    @foreach(\Modules\Academics\Models\Classroom::all() as $room)
                                        <option value="{{ $room->id }}">{{ $room->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-800 px-6 py-4 flex justify-end gap-2 border-t border-gray-100 dark:border-gray-800">
                        <x-filament::button type="button" wire:click="$set('isEditModalOpen', false)" color="gray">
                            {{ __('Cancel') }}
                        </x-filament::button>
                        <x-filament::button type="button" wire:click="saveLessonEdits" color="success">
                            {{ __('Save Changes') }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>