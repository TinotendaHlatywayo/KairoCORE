<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('No Student Record') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Link your student profile to view your timetable.') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($days as $day => $lessons)
                    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h3 class="mb-3 text-sm font-extrabold uppercase tracking-wide text-indigo-600 dark:text-indigo-400">{{ __(':day', ['day' => ucfirst($day)]) }}</h3>

                        @forelse($lessons as $lesson)
                            <div class="mb-2.5 rounded-lg border border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200">{{ $lesson->subject?->name ?? __('Lesson') }}</p>
                                    @if($lesson->timeSlot)
                                        <span class="shrink-0 text-[10px] font-semibold text-slate-400">
                                            {{ \Carbon\Carbon::parse($lesson->timeSlot->start_time)->format('H:i') }} – {{ \Carbon\Carbon::parse($lesson->timeSlot->end_time)->format('H:i') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">
                                    @if($lesson->classroom)
                                        {{ __('Room:') }} {{ $lesson->classroom->name ?? $lesson->classroom->room_number ?? '—' }}
                                    @endif
                                    @if($lesson->teacher)
                                        · {{ $lesson->teacher->name ?? __('Teacher') }}
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="py-4 text-center text-[11px] text-slate-400">{{ __('No lessons') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>