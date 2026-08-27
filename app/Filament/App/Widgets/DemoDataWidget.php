<?php

namespace App\Filament\App\Widgets;

use App\Services\DummyDataSeeder;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Modules\Academics\Models\AcademicReport;
use Modules\Students\Models\Student;

/**
 * Main-dashboard toggle for the demonstration dataset.
 *
 * Seeds exactly the same playground data as the "Pre-load Demonstration Data"
 * option during school registration (both use DummyDataSeeder), and wipes it
 * again on demand. Only rows tagged TEST-STU-* are ever removed.
 */
class DemoDataWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.demo-data-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public bool $busy = false;

    public function getHasDemoDataProperty(): bool
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return false;
        }

        return Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->exists();
    }

    public function seed(): void
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId || $this->busy) {
            return;
        }

        $this->busy = true;

        try {
            $result = app(DummyDataSeeder::class)->seed($schoolId);

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
            report($e);

            Notification::make()
                ->title(__('Seeding failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->busy = false;
        }
    }

    public function wipe(): void
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId || $this->busy) {
            return;
        }

        $this->busy = true;

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
            $this->busy = false;
        }
    }

    public function getDemoStatsProperty(): array
    {
        $schoolId = current_tenant()?->id;

        if (! $schoolId) {
            return ['students' => 0, 'reports' => 0];
        }

        $studentIds = Student::withoutGlobalScopes()
            ->where('school_id', $schoolId)
            ->where('student_id_number', 'LIKE', 'TEST-STU-%')
            ->pluck('id');

        return [
            'students' => $studentIds->count(),
            'reports' => AcademicReport::withoutGlobalScopes()->whereIn('student_id', $studentIds)->count(),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check() && current_tenant() !== null;
    }
}
