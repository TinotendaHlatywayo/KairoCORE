<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Lesson Context Header card -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-950 dark:text-white">
                        {{ $lesson->course->name }} {{ $lesson->section->name }} — {{ $lesson->subject->name }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        {{ __('Teacher:') }} <strong class="text-gray-700 dark:text-gray-300">{{ $lesson->teacher->name }}</strong> | 
                        Period: <strong class="text-gray-700 dark:text-gray-300">{{ $lesson->timeSlot->name }} ({{ date('H:i', strtotime($lesson->timeSlot->start_time)) }} - {{ date('H:i', strtotime($lesson->timeSlot->end_time)) }})</strong> | 
                        Room: <strong class="text-gray-700 dark:text-gray-300">{{ $lesson->classroom->name }}</strong>
                    </p>
                </div>
                <!-- Date selector -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Attendance Date:') }}</label>
                    <input type="date" wire:model.live="date" wire:change="loadStudentRecords" class="rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 text-sm">
                </div>
            </div>
        </div>

        <!-- Student Attendance Sheet Grid -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h4 class="text-md font-bold text-gray-950 dark:text-white mb-4">{{ __('Class Attendance Register') }}</h4>

            @if(count($students) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="p-4">{{ __('Admission ID') }}</th>
                                <th class="p-4">{{ __('Student Name') }}</th>
                                <th class="p-4">{{ __('Gender') }}</th>
                                <th class="p-4 text-center">{{ __('Mark Status') }}</th>
                                <th class="p-4">{{ __('Remarks') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
                            @foreach($students as $index => $std)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="p-4 font-mono font-bold text-green-600 dark:text-green-400">{{ $std['admission_number'] }}</td>
                                    <td class="p-4 font-semibold">{{ $std['name'] }}</td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400">{{ ucfirst($std['gender']) }}</td>
                                    <td class="p-4">
                                        <!-- Interactive button group -->
                                        <div class="flex justify-center gap-1">
                                            <button type="button" wire:click="setStatus({{ $std['id'] }}, 'present')" 
                                                    class="px-3 py-1.5 rounded-md text-xs font-bold border transition-colors {{ $attendanceState[$std['id']] === 'present' ? 'bg-green-600 text-white border-green-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                                {{ __('Present') }}
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $std['id'] }}, 'absent')" 
                                                    class="px-3 py-1.5 rounded-md text-xs font-bold border transition-colors {{ $attendanceState[$std['id']] === 'absent' ? 'bg-red-600 text-white border-red-600 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                                {{ __('Absent') }}
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $std['id'] }}, 'late')" 
                                                    class="px-3 py-1.5 rounded-md text-xs font-bold border transition-colors {{ $attendanceState[$std['id']] === 'late' ? 'bg-amber-500 text-white border-amber-500 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                                {{ __('Late') }}
                                            </button>
                                            <button type="button" wire:click="setStatus({{ $std['id'] }}, 'excused')" 
                                                    class="px-3 py-1.5 rounded-md text-xs font-bold border transition-colors {{ $attendanceState[$std['id']] === 'excused' ? 'bg-gray-500 text-white border-gray-500 shadow-sm' : 'bg-white text-gray-700 border-gray-200 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }}">
                                                {{ __('Excused') }}
                                            </button>
                                        </div>
                                    </td>
                                    <td class="p-4">
                                        <input type="text" wire:model.live="remarksState.{{ $std['id'] }}" placeholder="Add note..." 
                                               class="w-full text-xs rounded border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-6">
                    <x-filament::button wire:click="save" color="success" size="lg">
                        {{ __('Submit Attendance Register') }}
                    </x-filament::button>
                </div>
            @else
                <div class="text-center py-12 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('There are no students enrolled in this Form / Class stream for this calendar year.') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>