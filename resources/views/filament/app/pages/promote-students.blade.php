<x-filament-panels::page>
    <form wire:submit.prevent="promote" class="space-y-6">
        
        {{ $this->form }}

        <!-- Active Screening Gate Notifications (EduSys Style but Dark Mode Ready) -->
        @if(count($activeRulesSummary) > 0)
            <div class="rounded-xl border border-warning-600/20 bg-warning-50 p-4 dark:bg-warning-500/10">
                <div class="flex gap-x-3">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-warning-600 dark:text-warning-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                    <div>
                        <h4 class="text-sm font-bold text-warning-800 dark:text-warning-400">{{ __('Active Performance Screening Gates') }}</h4>
                        <ul class="list-disc list-inside text-xs text-warning-700 dark:text-warning-500 mt-2 space-y-1">
                            @foreach($activeRulesSummary as $rule)
                                <li>{{ $rule }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <!-- Filament-styled Standalone Card wrapper -->
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-950 dark:text-white">{{ __('Promotion Candidates Directory') }}</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Review eligible student records below. Unchecked students will remain in their current class.') }}</p>
                </div>
            </div>

            @if(count($students) > 0)
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-left border-collapse bg-white dark:bg-gray-900 text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold">
                            <tr class="border-b border-gray-200 dark:border-gray-700">
                                <th class="p-4" style="width: 50px;">
                                    <input type="checkbox" onclick="toggleAll(this)" checked class="rounded border-gray-300 text-green-600 focus:ring-green-500 dark:bg-gray-800">
                                </th>
                                <th class="p-4">{{ __('Admission Number') }}</th>
                                <th class="p-4">{{ __('Full Name') }}</th>
                                <th class="p-4">{{ __('Gender') }}</th>
                                <th class="p-4">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 text-gray-900 dark:text-gray-100">
                            @foreach($students as $student)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="p-4">
                                        <input type="checkbox" wire:model.live="selectedStudents" value="{{ $student['id'] }}" class="student-checkbox rounded border-gray-300 text-green-600 focus:ring-green-500 dark:bg-gray-800">
                                    </td>
                                    <td class="p-4 font-mono font-bold text-green-600 dark:text-green-400">{{ $student['admission_number'] }}</td>
                                    <td class="p-4 font-semibold">{{ $student['first_name'] }} {{ $student['last_name'] }}</td>
                                    <td class="p-4 text-gray-500 dark:text-gray-400">{{ ucfirst($student['gender']) }}</td>
                                    <td class="p-4">
                                        <span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 bg-green-50 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20">
                                            {{ ucfirst($student['status']) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mt-4">
                    <x-filament::button type="submit" color="success">
                        Execute Bulk Promotion ({{ count($selectedStudents) }})
                    </x-filament::button>
                </div>
            @else
                <div class="text-center py-12 rounded-lg border border-dashed border-gray-300 dark:border-gray-700">
                    <p class="text-gray-500 dark:text-gray-400">{{ __('Configure your source settings above to load eligible student records.') }}</p>
                </div>
            @endif
        </div>
    </form>

    <script>
        function toggleAll(master) {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            const selectedIds = [];
            checkboxes.forEach((cb) => {
                cb.checked = master.checked;
                if (cb.checked) selectedIds.push(parseInt(cb.value));
            });
            @this.set('selectedStudents', selectedIds);
        }
    </script>
</x-filament-panels::page>