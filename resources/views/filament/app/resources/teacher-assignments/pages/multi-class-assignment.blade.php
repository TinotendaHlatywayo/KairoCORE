<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Stepper -->
        <div class="flex items-center gap-2 text-xs font-bold">
            @foreach([1 => '1. Teacher', 2 => '2. Subjects', 3 => '3. Classes'] as $n => $label)
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center rounded-full w-6 h-6 text-white {{ $step === $n ? 'bg-green-600' : ($step > $n ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-700') }}">
                        {{ $step > $n ? '✓' : $n }}
                    </span>
                    <span class="{{ $step === $n ? 'text-gray-900 dark:text-white' : 'text-gray-400' }}">{{ $label }}</span>
                    @if(! $loop->last)
                        <span class="w-6 border-t border-gray-300 dark:border-gray-600"></span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            @if($step === 1)
                <h4 class="text-md font-bold text-gray-950 dark:text-white mb-1">{{ __('Who is the teacher?') }}</h4>
                <p class="text-xs text-gray-500 mb-4">{{ __('A subject specialist who will teach the chosen subject(s) across several classes or whole grades.') }}</p>

                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Teacher') }}</label>
                <select wire:model="teacherId" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white text-sm">
                    <option value="">— Select Teacher —</option>
                    @foreach($this->getAvailableTeachers() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <div class="text-xs text-gray-400 mt-1">{{ __('Selected:') }} <strong>{{ $this->getTeacherName() }}</strong></div>

            @elseif($step === 2)
                <h4 class="text-md font-bold text-gray-950 dark:text-white mb-1">{{ __('Which subject(s) does this teacher cover?') }}</h4>
                <p class="text-xs text-gray-500 mb-4">{{ __('A teacher can teach more than one subject. Each subject assignment will be created for the classes you pick next.') }}</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($this->getAvailableSubjects() as $id => $name)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm cursor-pointer hover:border-green-400">
                            <input type="checkbox" wire:model="subjectIds" value="{{ $id }}" class="rounded border-gray-300 text-green-600">
                            {{ $name }}
                        </label>
                    @endforeach
                </div>
                <div class="text-xs text-gray-400 mt-2">{{ count($subjectIds) }} {{ __('subject(s) selected') }}</div>

            @else
                <h4 class="text-md font-bold text-gray-950 dark:text-white mb-1">{{ __('Which classes/grades will they teach?') }}</h4>
                <p class="text-xs text-gray-500 mb-4">{{ __('Pick whole grades to cover every stream in that grade, and/or tick specific streams.') }}</p>

                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Entire Grades / Levels (all streams)') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 mb-4">
                    @foreach($this->getAvailableLevels() as $id => $name)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm cursor-pointer hover:border-green-400">
                            <input type="checkbox" wire:model="levelIds" value="{{ $id }}" class="rounded border-gray-300 text-green-600">
                            {{ $name }}
                        </label>
                    @endforeach
                </div>

                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Specific Streams') }}</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                    @foreach($this->getAvailableSections() as $id => $name)
                        <label class="flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm cursor-pointer hover:border-green-400">
                            <input type="checkbox" wire:model="sectionIds" value="{{ $id }}" class="rounded border-gray-300 text-green-600">
                            {{ $name }}
                        </label>
                    @endforeach
                </div>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('Periods per Week (per class)') }}</label>
                        <input type="number" wire:model="periodsPerWeek" min="1" class="w-full rounded-lg border-gray-300 dark:bg-gray-800 dark:border-gray-700 dark:text-white text-sm">
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="text-xs text-gray-500">
                    @if($step === 3)
                        {{ __('Scope:') }}
                        {{ count($levelIds) }} {{ __('grade(s)') }} · {{ count($sectionIds) }} {{ __('stream(s)') }} ·
                        {{ count($subjectIds) }} {{ __('subject(s)') }}
                        <span class="ml-1 text-gray-400">({{ $this->getTeacherName() }})</span>
                    @endif
                </div>
                <div class="flex gap-2">
                    @if($step > 1)
                        <x-filament::button type="button" wire:click="previousStep" color="gray">
                            {{ __('Back') }}
                        </x-filament::button>
                    @endif
                    @if($step < 3)
                        <x-filament::button type="button" wire:click="nextStep" color="info">
                            {{ __('Continue') }}
                        </x-filament::button>
                    @else
                        <x-filament::button type="button" wire:click="saveAssignments" color="success">
                            {{ __('Save Assignments') }}
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
