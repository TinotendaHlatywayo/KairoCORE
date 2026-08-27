<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ModuleAwareActiveNavigation;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Models\CustomRole;
use Modules\Admin\Models\SystemAuditLog;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;

class AdministrationDashboard extends Page
{
    use ModuleAwareActiveNavigation;

    protected static ?string $navigationIcon = 'heroicon-o-cog-8-tooth';

    protected static ?string $navigationGroup = 'System Administration';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Overview';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'modules.admin.dashboard';

    // Strictly hides "Overview" from the navigation menu [1]

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.view_module');
    }

    public function getViewData(): array
    {
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            return [
                'kpis' => [],
                'recentLogs' => collect(),
                'term_name' => 'No Active Term',
                'term_progress' => 0,
                'term_days_left' => 0,
                'chart_primary' => '#15803d',
                'chart_accent' => '#eab308',
            ];
        }

        // Live, time-dependent greetings [1]
        $hour = (int) date('H');
        $greeting = 'Good morning';
        if ($hour >= 12 && $hour < 17) {
            $greeting = 'Good afternoon';
        } elseif ($hour >= 17) {
            $greeting = 'Good evening';
        }

        // Extract color variables matching the active design theme
        $theme = SystemSetting::get('branding', 'theme', 'emerald_heritage');
        $chartPrimary = '#15803d';
        $chartAccent = '#eab308';
        if ($theme === 'digital_cobalt') {
            $chartPrimary = '#3b82f6';
            $chartAccent = '#8b5cf6';
        } elseif ($theme === 'obsidian_gold') {
            $chartPrimary = '#18181b';
            $chartAccent = '#d4af37';
        } elseif ($theme === 'dev_choice_1') {
            $chartPrimary = '#4f46e5';
            $chartAccent = '#06b6d4';
        } elseif ($theme === 'dev_choice_2') {
            $chartPrimary = '#c026d3';
            $chartAccent = '#a855f7';
        } elseif ($theme === 'dev_choice_3') {
            $chartPrimary = '#0891b2';
            $chartAccent = '#10b981';
        } elseif ($theme === 'dev_choice_4') {
            $chartPrimary = '#1b263b';
            $chartAccent = '#f05438';
        }

        $totalUsers = User::where('school_id', $schoolId)->count();
        $rolesCount = CustomRole::where('school_id', $schoolId)->count();
        $failedLogins = SystemAuditLog::where('school_id', $schoolId)
            ->where('action', 'like', '%Login%')
            ->where('outcome', 'failed')
            ->count();

        // Calculate Term Progress dynamically [1.2]
        $termProgress = 0;
        $termDaysLeft = 0;
        $termName = 'Term 1';

        $activeYear = DB::table('academic_years')
            ->where('school_id', $schoolId)
            ->where('is_active', 1)
            ->first();

        if ($activeYear) {
            $activeTerm = DB::table('terms')
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $activeYear->id)
                ->where('start_date', '<=', date('Y-m-d'))
                ->where('end_date', '>=', date('Y-m-d'))
                ->first()
                ?? DB::table('terms')
                    ->where('school_id', $schoolId)
                    ->where('academic_year_id', $activeYear->id)
                    ->latest('start_date')
                    ->first();

            if ($activeTerm) {
                $termName = $activeTerm->name;
                $start = Carbon::parse($activeTerm->start_date);
                $end = Carbon::parse($activeTerm->end_date);
                $today = Carbon::today();

                $totalDays = $start->diffInDays($end) ?: 1;

                if ($today->lt($start)) {
                    $termProgress = 0;
                    $termDaysLeft = $start->diffInDays($end);
                } elseif ($today->gt($end)) {
                    $termProgress = 100;
                    $termDaysLeft = 0;
                } else {
                    $daysCompleted = $start->diffInDays($today);
                    $termProgress = (int) round(($daysCompleted / $totalDays) * 100);
                    $termDaysLeft = $today->diffInDays($end);
                }
            }
        }

        $schoolId = current_tenant()?->id;
        $profileSchoolName = $schoolId ? SystemSetting::get('profile', 'school_name') : null;
        $schoolName = $profileSchoolName ?: (current_tenant()?->name ?? 'Kairo Demo Academy');

        return [
            'greeting' => $greeting,
            'user_name' => $user ? $user->name : 'System Admin',
            'school_name' => $schoolName,
            'chart_primary' => $chartPrimary,
            'chart_accent' => $chartAccent,
            'term_name' => $termName,
            'term_progress' => $termProgress,
            'term_days_left' => $termDaysLeft,
            'kpis' => [
                [
                    'label' => 'Total Users',
                    'value' => $totalUsers,
                    'icon' => 'heroicon-o-users',
                    'subtext' => 'Registered school staff',
                    'sparkline' => '0,15 20,25 40,10 60,30 80,20 100,28',
                ],
                [
                    'label' => 'Custom User Roles',
                    'value' => $rolesCount,
                    'icon' => 'heroicon-o-finger-print',
                    'subtext' => 'Configured access groups',
                    'sparkline' => '0,5 20,10 40,25 60,15 80,30 100,35',
                ],
                [
                    'label' => 'Access Checkpoints',
                    'value' => $failedLogins,
                    'icon' => 'heroicon-o-shield-exclamation',
                    'subtext' => 'Failed attempts logged',
                    'sparkline' => '0,30 20,25 40,28 60,5 80,8 100,2',
                ],
            ],
            'recentLogs' => SystemAuditLog::where('school_id', $schoolId)
                ->with('user')
                ->latest()
                ->limit(5)
                ->get(),
        ];
    }
}
