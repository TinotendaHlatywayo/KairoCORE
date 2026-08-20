@if ($this->getHeaderWidgets())
    <div class="mb-6">
        <x-filament-widgets::widgets
            :widgets="$this->getVisibleHeaderWidgets()"
            :columns="$this->getHeaderWidgetsColumns()"
        />
    </div>
@endif

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- School Welcomer Card -->
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center gap-x-4">
                <!-- Mascot / Welcome icon -->
                <div class="rounded-full bg-green-50 p-3 dark:bg-green-500/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-8 h-8 text-green-600 dark:text-green-400">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.57 50.57 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.902 59.906 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M12 13.489v3.695m0-13.695v3.695" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-2xl font-bold text-gray-950 dark:text-white">Welcome back, {{ auth()->user()->name }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('You are currently managing the administrative portal for') }} <strong>{{ $school->name }}</strong>{{ __('.') }}</p>
                </div>
            </div>
        </div>

        <!-- Quick Launch Directory Grid -->
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <!-- Link 1: Student Directory -->
            <a href="/workspace/students" class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:ring-green-500 transition-all dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-green-400">
                <h4 class="font-bold text-gray-950 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">{{ __('Manage Students') }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Register new admissions, edit medical profiles, and compile identity cards.') }}</p>
            </a>

            <!-- Link 2: Timetable Builder -->
            <a href="/workspace/timetable-lessons" class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:ring-green-500 transition-all dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-green-400">
                <h4 class="font-bold text-gray-950 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">{{ __('School Timetable') }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Configure period schedules, assign classes, and record daily attendance rosters.') }}</p>
            </a>

            <!-- Link 3: Admissions Queue -->
            <a href="/workspace/applications" class="group rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:ring-green-500 transition-all dark:bg-gray-900 dark:ring-white/10 dark:hover:ring-green-400">
                <h4 class="font-bold text-gray-950 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">{{ __('Online Admissions') }}</h4>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ __('Review incoming public applications, verify documents, and auto-enroll students.') }}</p>
            </a>
        </div>
    </div>
</x-filament-panels::page>