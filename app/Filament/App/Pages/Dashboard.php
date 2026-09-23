<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\UserRoleStatisticsWidget;
use App\Models\School;
use App\Services\DummyDataSeeder;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Cache;
use Modules\Academics\Models\AcademicReport;
use Modules\Students\Models\Student;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.app.pages.dashboard';

    public $school = null;

    public bool $hasDemoData = false;

    public array $demoStats = ['students' => 0, 'reports' => 0];

    public function mount(): void
    {
        $this->school = current_tenant() ?? School::where('id', auth()->user()?->school_id ?? 0)->first();
        $this->loadDemoDataStatus();
    }

    protected function loadDemoDataStatus(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        if (! $schoolId) {
            $this->hasDemoData = false;
            $this->demoStats = ['students' => 0, 'reports' => 0];

            return;
        }

        $studentIds = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->pluck('id');

        $this->hasDemoData = $studentIds->isNotEmpty();
        $this->demoStats = [
            'students' => $studentIds->count(),
            'reports' => AcademicReport::withoutGlobalScopes()->whereIn('student_id', $studentIds)->count(),
        ];
    }

    public bool $isSeeding = false;

    public int $seedProgress = 0;

    public string $seedStage = '';

    public function pollProgress(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        if (! $schoolId || ! $this->isSeeding) {
            return;
        }

        $data = Cache::get("seed_progress_{$schoolId}");

        if ($data) {
            $this->seedProgress = $data['percent'] ?? 0;
            $this->seedStage = $data['message'] ?? '';

            if ($this->seedProgress >= 100) {
                $this->isSeeding = false;
                $this->loadDemoDataStatus();
            }
        }
    }

    public function seedDemoData(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        if (! $schoolId) {
            return;
        }

        $this->isSeeding = true;
        $this->seedProgress = 5;
        $this->seedStage = __('Initializing Academic Structure & Terms');
        Cache::put("seed_progress_{$schoolId}", ['message' => $this->seedStage, 'percent' => 5], 600);
        $this->dispatch('seed-started');

        try {
            $result = app(DummyDataSeeder::class)->seed($schoolId, function ($message, $percent = null) use ($schoolId) {
                if ($percent !== null) {
                    Cache::put("seed_progress_{$schoolId}", ['message' => $message, 'percent' => $percent], 600);
                }
            });

            Cache::put("seed_progress_{$schoolId}", ['message' => __('Demonstration data seeded successfully!'), 'percent' => 100], 600);
            $this->seedProgress = 100;
            $this->seedStage = __('Demonstration data seeded successfully!');
            $this->dispatch('seed-finished');

            Notification::make()
                ->title(__('Demo data seeded'))
                ->body(__(':students students across :sections class streams, with :reports academic reports and full assessment marks.', [
                    'students' => $result['students'] ?? 0,
                    'sections' => $result['sections'] ?? 0,
                    'reports' => $result['reports'] ?? 0,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->dispatch('seed-finished');
            report($e);

            Notification::make()
                ->title(__('Seeding failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSeeding = false;
            $this->loadDemoDataStatus();
        }
    }

    public function wipeDemoData(): void
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        if (! $schoolId) {
            return;
        }

        try {
            $removed = app(DummyDataSeeder::class)->wipe($schoolId);

            Notification::make()
                ->title(__('Demo data wiped'))
                ->body(__(':count demonstration students (and their enrollments, marks and reports) were removed.', [
                    'count' => $removed,
                ]))
                ->success()
                ->send();
        } catch (\Throwable $e) {
            report($e);

            Notification::make()
                ->title(__('Wipe failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->loadDemoDataStatus();
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [
            UserRoleStatisticsWidget::class,
        ];
    }
}
