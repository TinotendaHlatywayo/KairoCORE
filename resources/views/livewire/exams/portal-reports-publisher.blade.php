<div class="space-y-6">
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Publish to Student Portal') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Choose the scope, then publish report cards and/or individual assessments for students to view.') }}
        </x-slot>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Scope') }}</label>
                <select wire:model.change="scope" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="school">{{ __('Whole School') }}</option>
                    <option value="course">{{ __('Class') }}</option>
                    <option value="section">{{ __('Stream') }}</option>
                </select>
            </div>

            @if($scope === 'course')
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Class') }}</label>
                    <select wire:model.change="courseId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">—</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}">{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if($scope === 'section')
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Stream') }}</label>
                    <select wire:model.change="sectionId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                        <option value="">—</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Term (optional)') }}</label>
                <select wire:model.change="termId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <option value="">{{ __('All Terms') }}</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-center dark:border-slate-800 dark:bg-slate-900">
                <p class="text-2xl font-bold text-slate-800 dark:text-white">{{ number_format($candidateReports) }}</p>
                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('In Scope') }}</p>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-900 dark:bg-emerald-950/30">
                <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ number_format($publishedReports) }}</p>
                <p class="mt-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ __('Published') }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-center dark:border-amber-900 dark:bg-amber-950/30">
                <p class="text-2xl font-bold text-amber-700 dark:text-amber-300">{{ number_format($unpublishedReports) }}</p>
                <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">{{ __('Not Published') }}</p>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
            <x-filament::button wire:click="publishReports" color="success" wire:loading.attr="disabled">
                {{ __('Publish Report Cards to Portal') }}
            </x-filament::button>
            <x-filament::button wire:click="unpublishReports" color="warning" wire:loading.attr="disabled">
                {{ __('Unpublish Report Cards') }}
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('Assessments in the Portal') }}
        </x-slot>
        <x-slot name="description">
            {{ __('Students only see continuous-assessment marks for assessments whose status is Published. Mark "other" assessments here to release them to the student portal.') }}
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Assessment') }}</th>
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Subject') }}</th>
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Max Mark') }}</th>
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Weight') }}</th>
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Included in Report Card') }}</th>
                        <th class="py-2.5 pr-3 font-semibold">{{ __('Status') }}</th>
                        <th class="py-2.5 text-right font-semibold">{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assessmentTypes as $assessmentType)
                        @php($isPublished = in_array($assessmentType->id, $publishedTypeIds))
                        @php($isIncluded = in_array($assessmentType->id, $includedIds))
                        <tr class="border-b border-slate-50 dark:border-slate-800/60">
                            <td class="py-3 pr-3 font-semibold text-slate-700 dark:text-slate-300">{{ $assessmentType->name }}</td>
                            <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $assessmentType->subject?->name ?? '—' }}</td>
                            <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $assessmentType->max_mark ?? '—' }}</td>
                            <td class="py-3 pr-3 text-slate-500 dark:text-slate-400">{{ $assessmentType->weight_percentage ? $assessmentType->weight_percentage.'%' : '—' }}</td>
                            <td class="py-3 pr-3">
                                @if($isIncluded)
                                    <span class="inline-flex rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">{{ __('Yes') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ __('Other (portal only)') }}</span>
                                @endif
                            </td>
                            <td class="py-3 pr-3">
                                @if($isPublished)
                                    <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('Published') }}</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ ucfirst($assessmentType->status) }}</span>
                                @endif
                            </td>
                            <td class="py-3 text-right">
                                @if($isPublished)
                                    <x-filament::button size="xs" color="warning" wire:click="unpublishAssessmentType({{ $assessmentType->id }})">
                                        {{ __('Unpublish') }}
                                    </x-filament::button>
                                @else
                                    <x-filament::button size="xs" color="success" wire:click="publishAssessmentType({{ $assessmentType->id }})">
                                        {{ __('Publish') }}
                                    </x-filament::button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-xs text-slate-400">{{ __('No assessment types yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</div>