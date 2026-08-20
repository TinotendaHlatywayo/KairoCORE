<div class="p-6 bg-white border rounded-lg shadow-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <!-- Paper Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Select Exam Paper') }}</label>
            <select wire:model.live="examPaperId" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('-- Choose Paper --') }}</option>
                @foreach($papers as $paper)
                    <option value="{{ $paper->id }}">{{ $paper->exam->name }} - {{ $paper->subject->name }} ({{ $paper->name }})</option>
                @endforeach
            </select>
        </div>

        <!-- Section Selection -->
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Select Class/Stream') }}</label>
            <select wire:model.live="sectionId" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('-- Choose Class --') }}</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}">{{ $section->course->name }} {{ $section->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if($examPaperId && $sectionId)
    <div class="overflow-x-auto border rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Student Name') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Adm No.') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mark (Max: {{ \Modules\Academics\Models\ExamPaper::find($examPaperId)->max_mark }})</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($this->students as $student)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $student->first_name }} {{ $student->last_name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $student->admission_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input 
                            type="number" 
                            wire:model.blur="marks.{{ $student->id }}"
                            wire:change="saveMark({{ $student->id }})"
                            class="w-24 px-2 py-1 border rounded @error('marks.'.$student->id) border-red-500 @enderror"
                        >
                        @error('marks.'.$student->id) 
                            <span class="block text-xs text-red-500">{{ $message }}</span> 
                        @enderror
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div wire:loading wire:target="marks.{{ $student->id }}">
                             <span class="text-blue-500 text-xs">{{ __('Saving...') }}</span>
                        </div>
                        <div wire:loading.remove wire:target="marks.{{ $student->id }}">
                            @if(isset($marks[$student->id]))
                                <span class="text-green-600 text-xs font-bold">{{ __('✓ Saved') }}</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <div class="p-10 text-center text-gray-400 border-2 border-dashed rounded-lg">
            {{ __('Please select an Exam Paper and a Class to start entering marks.') }}
        </div>
    @endif
</div>