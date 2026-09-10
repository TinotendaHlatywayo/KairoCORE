<x-filament-panels::page>
    <div class="space-y-6">
            <!-- School Welcomer Card with Demo Data on Right -->
            <div class="relative overflow-visible rounded-2xl border border-gray-200/90 bg-gradient-to-r from-white via-white to-primary-50/20 p-6 shadow-lg shadow-gray-950/5 dark:border-gray-800 dark:from-gray-900 dark:via-gray-900 dark:to-primary-950/20 grid grid-cols-1 md:grid-cols-3 gap-6 items-center w-full">
                <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-primary-500 via-primary-400 to-emerald-500 rounded-t-2xl"></div>
                <div class="md:col-span-2 flex items-center gap-x-4 min-w-0 w-full pt-1">
                    <!-- Mascot / Welcome icon -->
                    <div class="rounded-2xl bg-primary-500/10 p-4 dark:bg-primary-400/10 text-primary-600 dark:text-primary-400 shrink-0 ring-1 ring-primary-500/20 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.902 59.99 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489b50.702 50.702 0 0 1 7.74-3.342M12 13.489v3.695m0-13.695v3.695" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300 mb-1.5 ring-1 ring-primary-500/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                            {{ __('Administrative Workspace') }}
                        </div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white leading-tight">{!! __('Welcome back, :name', ['name' => auth()->user()->name]) !!}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 break-words">{{ __('You are currently managing portal operations for') }} <strong class="text-gray-900 dark:text-white font-semibold">{{ $school->name }}</strong>{{ __('.') }}</p>
                    </div>
                </div>
                <div class="md:col-span-1 flex flex-col gap-3 w-full shrink-0">
                    <!-- Demo data status card (its own card) -->
                    <div class="rounded-xl border border-gray-200/80 bg-gray-50/60 dark:border-gray-800 dark:bg-gray-900 p-3.5 shadow-sm w-full">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                 style="background: {{ $hasDemoData ? 'rgba(16,185,129,0.12)' : 'rgba(245,158,11,0.14)' }};">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.8" stroke="currentColor"
                                     style="width: 1.2rem; height: 1.2rem; color: {{ $hasDemoData ? '#059669' : '#d97706' }};">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-800 dark:text-gray-100">
                                    {{ $hasDemoData ? __('Demonstration data is active') : __('Seed demonstration data') }}
                                </h3>
                                <p class="mt-0.5 text-sm leading-relaxed text-slate-500 dark:text-gray-400">
                                    @if ($hasDemoData)
                                        {{ __(':students demo students with :reports academic reports, enrollments and marks are loaded across the system.', [
                                            'students' => $demoStats['students'],
                                            'reports' => $demoStats['reports'],
                                        ]) }}
                                    @else
                                        {{ __('Populate the whole system with test data which include students, classes, assessments, marks and reports for testing.') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Single dynamic demo data action button -->
                    @php
                        $isWipe = $hasDemoData;
                        $btnLabel = $isWipe ? __('Wipe Demo Data') : __('Seed Demo Data');
                        $btnLoading = $isWipe ? 'wipeDemoData' : 'seedDemoData';
                        $btnAction = $isWipe ? 'wipeDemoData' : 'seedDemoData';
                        $gradient = $isWipe
                            ? 'linear-gradient(135deg, #dc2626, #ef4444)'
                            : 'linear-gradient(135deg, #d97706, #f59e0b)';
                        $shadow = $isWipe
                            ? 'rgba(220,38,38,.45)'
                            : 'rgba(245,158,11,.45)';
                    @endphp
                    <div x-data="{ 
                        seeding: @js($isSeeding), 
                        progress: @js($seedProgress), 
                        stage: @js($seedStage ?: __('Initializing...'))
                    }"
                    @if($isSeeding) wire:poll.500ms="pollProgress" @endif
                    class="w-full space-y-3">
                        <button type="button"
                                wire:click="{{ $btnAction }}"
                                wire:loading.attr="disabled"
                                wire:target="{{ $btnLoading }}"
                                @if($isWipe) x-on:click="if (!confirm(@js(__('Remove ALL demonstration students, enrollments, marks and reports? Real records are never touched.')))) return false;" @endif
                                class="inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-60 w-full"
                                style="background: {{ $gradient }}; box-shadow: 0 4px 14px -4px {{ $shadow }};">
                            @if($isWipe)
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-.422 3.096-.422-3.096m0 0-.365-2.674a2.25 2.25 0 0 1 2.235-2.55h4.584a2.25 2.25 0 0 1 2.235 2.55l-.365 2.674m-8.324 0H6.18m11.64 0-1.36 9.93A2.25 2.25 0 0 1 14.22 21H9.78a2.25 2.25 0 0 1-2.24-2.044L6.18 12.096M19.41 5.25h-5.32m-3.66 0H4.59m3.658 0V4.687c0-.62.504-1.124 1.125-1.124h2.25c.621 0 1.125.504 1.125 1.124v.563" />
                                </svg>
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                </svg>
                            @endif
                            <span wire:loading.remove wire:target="{{ $btnLoading }}">{{ $btnLabel }}</span>
                            <span wire:loading wire:target="{{ $btnLoading }}">{{ $isWipe ? __('Removing...') : __('Seeding...') }}</span>
                        </button>

                        <div x-show="seeding || @js($isSeeding)" x-cloak class="space-y-1.5 rounded-lg bg-amber-50 dark:bg-gray-900/80 p-3 border border-amber-200/60 dark:border-amber-900/40">
                            <div class="flex justify-between text-xs font-semibold text-amber-800 dark:text-amber-300">
                                <span>{{ $seedStage ?: __('Initializing Academic Structure...') }}</span>
                                <span>{{ $seedProgress }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-800 overflow-hidden shadow-inner">
                                <div class="bg-gradient-to-r from-amber-500 via-emerald-500 to-primary-600 h-2.5 rounded-full transition-all duration-300 ease-out" style="width: {{ $seedProgress }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Launch Directory Grid (Each card stands on its own with explicit border and shadow) -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Link 1: Student Directory -->
                <a href="/workspace/students" class="group rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all hover:border-primary-400 hover:shadow-md hover:ring-2 hover:ring-primary-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-500/60 dark:hover:ring-primary-500/20 flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ __('Manage Students') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ __('Register new admissions, edit medical profiles, and compile identity cards.') }}</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 dark:text-primary-400">
                        <span>{{ __('Open directory') }}</span>
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>

                <!-- Link 2: Timetable Builder -->
                <a href="/workspace/timetable-lessons" class="group rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all hover:border-primary-400 hover:shadow-md hover:ring-2 hover:ring-primary-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-500/60 dark:hover:ring-primary-500/20 flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ __('School Timetable') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ __('Configure period schedules, assign classes, and record daily attendance rosters.') }}</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 dark:text-primary-400">
                        <span>{{ __('Open timetables') }}</span>
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>

                <!-- Link 3: Admissions Queue -->
                <a href="/workspace/applications" class="group rounded-2xl border border-gray-200/80 bg-white p-6 shadow-sm transition-all hover:border-primary-400 hover:shadow-md hover:ring-2 hover:ring-primary-100 dark:border-gray-800 dark:bg-gray-900 dark:hover:border-primary-500/60 dark:hover:ring-primary-500/20 flex flex-col justify-between">
                    <div>
                        <h4 class="font-bold text-lg text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">{{ __('Online Admissions') }}</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2 leading-relaxed">{{ __('Review incoming public applications, verify documents, and auto-enroll students.') }}</p>
                    </div>
                    <div class="mt-4 flex items-center text-xs font-semibold text-primary-600 dark:text-primary-400">
                        <span>{{ __('Open admissions') }}</span>
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </a>
            </div>
        </div>
    </x-filament-panels::page>
