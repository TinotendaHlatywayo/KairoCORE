<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Student Profile Not Linked Yet') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    {{ __('Your account is not yet linked to a student record. Please contact the school administration to link your profile.') }}
                </p>
            </div>
        @else

            <!-- Welcome banner -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-600 text-lg font-bold text-white">
                            {{ strtoupper(substr($student->first_name ?? 'S', 0, 1)) . strtoupper(substr($student->last_name ?? 'T', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-slate-400 dark:text-slate-500">{{ __('Welcome back') }}</p>
                            <h2 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $student->full_name }}</h2>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('Student No:') }} <span class="font-mono font-semibold">{{ $student->student_id_number }}</span>
                                · {{ __('Admission:') }} <span class="font-mono font-semibold">{{ $student->admission_number }}</span>
                                · {{ $student->currentEnrollment?->course?->name ?? __('Not Enrolled') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ \App\Filament\Student\Resources\HomeworkResource::getUrl('index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-500">
                            <x-heroicon-o-academic-cap class="h-4 w-4"/>
                            {{ __('My Homework') }}
                        </a>
                        <a href="{{ \App\Filament\Student\Pages\StudentFees::getUrl() }}" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3.5 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-500">
                            <x-heroicon-o-credit-card class="h-4 w-4"/>
                            {{ __('My Fees') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stat cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <a href="{{ \App\Filament\Student\Resources\HomeworkResource::getUrl('index') }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Upcoming Homework') }}</p>
                        <x-heroicon-o-clipboard-document-list class="h-4 w-4 text-indigo-500"/>
                    </div>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $upcoming->count() }}</p>
                </a>
                <a href="{{ \App\Filament\Student\Pages\StudentFees::getUrl() }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-emerald-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Outstanding Balance') }}</p>
                        <x-heroicon-o-banknotes class="h-4 w-4 text-emerald-500"/>
                    </div>
                    <p class="mt-1 text-2xl font-extrabold text-rose-600 dark:text-rose-400">${{ number_format($unpaidBalance, 2) }}</p>
                </a>
                <a href="{{ \App\Filament\Student\Pages\StudentFees::getUrl() }}" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:border-amber-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-amber-700">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ __('Pending Payments') }}</p>
                        <x-heroicon-o-clock class="h-4 w-4 text-amber-500"/>
                    </div>
                    <p class="mt-1 text-2xl font-extrabold text-slate-900 dark:text-white">{{ $pendingPayments }}</p>
                </a>
            </div>

            <!-- Upcoming homework -->
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('Upcoming Homework') }}</h3>
                    <a href="{{ \App\Filament\Student\Resources\HomeworkResource::getUrl('index') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">{{ __('View all') }} →</a>
                </div>

                <div class="space-y-3">
                    @forelse($upcoming as $hw)
                        <div class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3.5 dark:border-slate-800 dark:bg-slate-950/40">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">
                                <x-heroicon-o-book-open class="h-4 w-4"/>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $hw->title }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $hw->subject?->name ?? __('General') }} · {{ __('Due') }} {{ $hw->due_date?->format('d M Y') }}</p>
                            </div>
                            @if($hw->submission_status === 'submitted')
                                <span class="inline-flex shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ __('SUBMITTED') }}</span>
                            @elseif($hw->submission_status === 'overdue')
                                <span class="inline-flex shrink-0 rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">{{ __('OVERDUE') }}</span>
                            @else
                                <span class="inline-flex shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">{{ __('PENDING') }}</span>
                            @endif
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-slate-400">{{ __('No homework due right now. Enjoy your free time!') }}</p>
                    @endforelse
                </div>
            </div>

        @endif
    </div>
</x-filament-panels::page>