<x-filament-panels::page>
    @if(!$this->gamificationEnabled)
        <div class="text-center py-12">
            <x-heroicon-o-trophy class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
            <h3 class="text-lg font-medium text-gray-500">Gamification not enabled</h3>
            <p class="text-sm text-gray-400 mt-1">Your school has not enabled gamification features yet.</p>
        </div>
    @else
        {{-- XP & Level --}}
        @if($stats['xp'] ?? null)
        <div class="fi-card p-6 rounded-xl bg-gradient-to-r from-primary-500 to-primary-600 text-white mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">Level</div>
                    <div class="text-3xl font-bold">{{ $stats['xp']->current_level_name ?? 'Explorer' }}</div>
                    <div class="text-sm opacity-80 mt-1">Level {{ $stats['xp']->current_level ?? 1 }}</div>
                </div>
                <div class="text-right">
                    <div class="text-sm opacity-80">Total XP</div>
                    <div class="text-4xl font-bold">{{ number_format($stats['xp']->total_xp ?? 0) }}</div>
                </div>
            </div>
            @php
                $levels = \Modules\DigitalAssessment\Models\GamificationSettings::defaultLevels();
                $currentLevel = collect($levels)->firstWhere('level', $stats['xp']->current_level ?? 1);
                $nextLevel = collect($levels)->firstWhere('level', ($stats['xp']->current_level ?? 1) + 1);
                $currentXp = $stats['xp']->total_xp ?? 0;
                $prevThreshold = $currentLevel['xp_threshold'] ?? 0;
                $nextThreshold = $nextLevel['xp_threshold'] ?? 99999;
                $progress = $nextThreshold > $prevThreshold
                    ? min(100, (($currentXp - $prevThreshold) / ($nextThreshold - $prevThreshold)) * 100)
                    : 100;
            @endphp
            @if($nextLevel)
            <div class="mt-4">
                <div class="flex justify-between text-xs opacity-80 mb-1">
                    <span>{{ $currentLevel['name'] }}</span>
                    <span>{{ $nextLevel['name'] }} ({{ number_format($nextThreshold) }} XP)</span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2.5">
                    <div class="bg-white rounded-full h-2.5 transition-all duration-500" style="width: {{ $progress }}%"></div>
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Quick Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-center">
                <div class="text-2xl font-bold text-primary-600">{{ $stats['completed_assessments'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Completed</div>
            </div>
            <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-center">
                <div class="text-2xl font-bold text-warning-600">{{ $stats['perfect_scores'] ?? 0 }}</div>
                <div class="text-sm text-gray-500">Perfect Scores</div>
            </div>
            <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-center">
                <div class="text-2xl font-bold text-success-600">{{ $stats['badges']?->count() ?? 0 }}</div>
                <div class="text-sm text-gray-500">Badges</div>
            </div>
            <div class="fi-card p-4 rounded-xl bg-white dark:bg-gray-900 shadow-sm text-center">
                <div class="text-2xl font-bold text-info-600">{{ $stats['streak']?->current_streak ?? 0 }}</div>
                <div class="text-sm text-gray-500">Day Streak</div>
            </div>
        </div>

        {{-- Streak --}}
        @if(($stats['streak'] ?? null) && ($stats['streak']->current_streak > 0 || $stats['streak']->longest_streak > 0))
        <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
            <h3 class="text-lg font-semibold mb-3">Daily Streak</h3>
            <div class="flex items-center gap-6">
                <div class="text-center">
                    <div class="text-3xl font-bold text-orange-500">{{ $stats['streak']->current_streak }}</div>
                    <div class="text-sm text-gray-500">Current Streak</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-gray-400">{{ $stats['streak']->longest_streak }}</div>
                    <div class="text-sm text-gray-500">Longest Streak</div>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-blue-500">{{ $stats['streak']->total_active_days }}</div>
                    <div class="text-sm text-gray-500">Total Active Days</div>
                </div>
            </div>
        </div>
        @endif

        {{-- Badges --}}
        @if(($stats['badges'] ?? collect())->count() > 0)
        <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
            <h3 class="text-lg font-semibold mb-4">Earned Badges</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($stats['badges'] as $lb)
                <div class="text-center p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="text-3xl mb-2">{{ $lb->badge?->icon ?? '🏆' }}</div>
                    <div class="font-medium text-sm">{{ $lb->badge?->name ?? 'Badge' }}</div>
                    <div class="text-xs text-gray-400 mt-1">{{ $lb->earned_at->diffForHumans() }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Achievements --}}
        @if(($stats['achievements'] ?? collect())->count() > 0)
        <div class="fi-card p-6 rounded-xl bg-white dark:bg-gray-900 shadow-sm mb-6">
            <h3 class="text-lg font-semibold mb-4">Achievements</h3>
            <div class="space-y-3">
                @foreach($stats['achievements'] as $la)
                <div class="flex items-center gap-3 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                    <div class="text-2xl">{{ $la->achievement?->icon ?? '⭐' }}</div>
                    <div class="flex-1">
                        <div class="font-medium text-sm">{{ $la->achievement?->name ?? 'Achievement' }}</div>
                        <div class="text-xs text-gray-400">{{ $la->achievement?->description }}</div>
                    </div>
                    <div class="text-xs text-gray-400">{{ $la->earned_at->diffForHumans() }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if(!($stats['badges'] ?? collect())->count() && !($stats['achievements'] ?? collect())->count())
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-flag class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
            <p>Complete assessments to earn badges and achievements!</p>
        </div>
        @endif
    @endif
</x-filament-panels::page>
