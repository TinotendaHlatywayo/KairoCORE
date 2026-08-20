<x-filament-panels::page>
    <div class="grid grid-cols-2 gap-4 bg-white p-4 rounded-xl shadow-sm border">
        <x-filament::input.wrapper label="Select Paper">
            <select wire:model.live="selectedPaperId" class="w-full border-none bg-transparent focus:ring-0">
                <option value="">{{ __('-- Select Paper --') }}</option>
                @foreach($exam->papers as $paper)
                    <option value="{{ $paper->id }}">{{ $paper->subject->name }} - {{ $paper->name }}</option>
                @endforeach
            </select>
        </x-filament::input.wrapper>

        <x-filament::input.wrapper label="Select Class Section">
            <select wire:model.live="selectedSectionId" class="w-full border-none bg-transparent focus:ring-0">
                <option value="">{{ __('-- Select Class --') }}</option>
                @foreach(\Modules\Academics\Models\Section::all() as $section)
                    <option value="{{ $section->id }}">{{ $section->course->name }} {{ $section->name }}</option>
                @endforeach
            </select>
        </x-filament::input.wrapper>
    </div>

    @if($selectedPaperId && $selectedSectionId)
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <table class="w-full text-left divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">{{ __('Student') }}</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">Mark / {{ \Modules\Academics\Models\ExamPaper::find($selectedPaperId)->max_mark }}</th>
                        <th class="px-6 py-3 text-xs font-bold text-gray-500 uppercase">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach(\Modules\Students\Models\Student::whereHas('enrollments', fn($q) => $q->where('section_id', $selectedSectionId))->get() as $student)
                        @php $existingMark = \Modules\Academics\Models\ExamMark::where('student_id', $student->id)->where('exam_paper_id', $selectedPaperId)->first()?->marks_obtained; @endphp
                        <tr>
                            <td class="px-6 py-4">{{ $student->first_name }} {{ $student->last_name }}</td>
                            <td class="px-6 py-4">
                                <input type="number" 
                                    value="{{ $existingMark }}"
                                    wire:blur="saveMark({{ $student->id }}, $event.target.value)"
                                    class="w-32 border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                >
                            </td>
                            <td class="px-6 py-4">
                                <div wire:loading wire:target="saveMark({{ $student->id }}, $event.target.value)">
                                    <span class="text-primary-600 text-xs animate-pulse">{{ __('Saving...') }}</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>