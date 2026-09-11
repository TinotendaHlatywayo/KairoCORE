<?php

namespace App\Filament\App\Resources\TeacherAssignmentResource\Pages;

use App\Filament\App\Resources\TeacherAssignmentResource;
use App\Support\TeacherOptions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\CourseSubject;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;

class MultiClassTeacherAssignment extends Page
{
    protected static string $resource = TeacherAssignmentResource::class;

    protected static string $view = 'filament.app.resources.teacher-assignments.pages.multi-class-assignment';

    protected static ?string $title = 'Multi-Class Teacher Assignment';

    public function getHeading(): string
    {
        return __('Multi-Class Teacher Assignment');
    }

    public function getBreadcrumbs(): array
    {
        return [
            TeacherAssignmentResource::getUrl('index') => __('Teacher Assignments'),
            $this->getHeading(),
        ];
    }

    public int $step = 1;

    public ?int $teacherId = null;

    public array $subjectIds = [];

    // Scope: whole grades/levels (course-level rows → all streams)
    public array $levelIds = [];

    // Specific streams (per-section rows targeted individually)
    public array $sectionIds = [];

    public int $periodsPerWeek = 4;

    public function nextStep(): void
    {
        if ($this->step === 1 && ! $this->teacherId) {
            Notification::make()->title(__('Please choose a teacher first.'))->warning()->send();

            return;
        }

        if ($this->step === 2 && empty($this->subjectIds)) {
            Notification::make()->title(__('Select at least one subject.'))->warning()->send();

            return;
        }

        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    protected function teacherLabel(?int $id): string
    {
        return $id ? (TeacherOptions::labelFor($id) ?: '') : '';
    }

    public function getTeacherName(): string
    {
        return $this->teacherLabel($this->teacherId);
    }

    public function getSubjects(): Collection
    {
        return Subject::where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->whereIn('id', $this->subjectIds ?: [])
            ->orderBy('name')
            ->get();
    }

    public function getSelectedLevels(): Collection
    {
        return Course::where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->whereIn('id', $this->levelIds ?: [])
            ->orderBy('name')
            ->get();
    }

    public function getSelectedSections(): Collection
    {
        return Section::withoutGlobalScopes()
            ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->whereIn('id', $this->sectionIds ?: [])
            ->with('course')
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'label' => trim(($s->course->name ?? '').' '.$s->name)]);
    }

    public function getAvailableTeachers(): array
    {
        return TeacherOptions::options();
    }

    public function getAvailableSubjects(): array
    {
        return Subject::where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function getAvailableLevels(): array
    {
        return Course::where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->withCount('sections')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn ($c) => [$c->id => $c->name.' ('.$c->sections_count.' streams)'])
            ->all();
    }

    public function getAvailableSections(): array
    {
        return Section::withoutGlobalScopes()
            ->where('school_id', current_tenant()?->id ?? auth()->user()?->school_id ?? 1)
            ->with('course')
            ->orderBy('course_id')
            ->get()
            ->mapWithKeys(fn ($s) => [$s->id => trim(($s->course->name ?? '').' '.$s->name)])
            ->all();
    }

    public function saveAssignments(): void
    {
        if (! $this->teacherId) {
            Notification::make()->title(__('Choose a teacher.'))->danger()->send();

            return;
        }

        if (empty($this->subjectIds)) {
            Notification::make()->title(__('Select at least one subject.'))->danger()->send();

            return;
        }

        if (empty($this->levelIds) && empty($this->sectionIds)) {
            Notification::make()->title(__('Select at least one grade/level or stream.'))->danger()->send();

            return;
        }

        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id ?? 1;
        $created = 0;
        $updated = 0;

        foreach ($this->subjectIds as $subjectId) {
            // Whole-grade scope (section_id = null) → every stream of that level.
            foreach ($this->levelIds as $courseId) {
                $existing = CourseSubject::query()
                    ->withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('course_id', $courseId)
                    ->where('subject_id', $subjectId)
                    ->whereNull('section_id')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'teacher_id' => $this->teacherId,
                        'periods_per_week' => $this->periodsPerWeek,
                    ]);
                    $updated++;
                } else {
                    CourseSubject::withoutGlobalScopes()->create([
                        'school_id' => $schoolId,
                        'course_id' => $courseId,
                        'subject_id' => $subjectId,
                        'section_id' => null,
                        'teacher_id' => $this->teacherId,
                        'role' => 'main',
                        'periods_per_week' => $this->periodsPerWeek,
                    ]);
                    $created++;
                }
            }

            // Specific-stream scope (section_id set).
            foreach ($this->sectionIds as $sectionId) {
                $section = Section::withoutGlobalScopes()->find($sectionId);
                if (! $section) {
                    continue;
                }

                $existing = CourseSubject::query()
                    ->withoutGlobalScopes()
                    ->where('school_id', $schoolId)
                    ->where('course_id', $section->course_id)
                    ->where('subject_id', $subjectId)
                    ->where('section_id', $sectionId)
                    ->first();

                if ($existing) {
                    $existing->update([
                        'teacher_id' => $this->teacherId,
                        'periods_per_week' => $this->periodsPerWeek,
                    ]);
                    $updated++;
                } else {
                    CourseSubject::withoutGlobalScopes()->create([
                        'school_id' => $schoolId,
                        'course_id' => $section->course_id,
                        'subject_id' => $subjectId,
                        'section_id' => $sectionId,
                        'teacher_id' => $this->teacherId,
                        'role' => 'main',
                        'periods_per_week' => $this->periodsPerWeek,
                    ]);
                    $created++;
                }
            }
        }

        Notification::make()
            ->title(__('Assignments saved'))
            ->body(__("{$created} new assignment(s) created and {$updated} existing assignment(s) updated."))
            ->success()
            ->send();

        $this->reset(['teacherId', 'subjectIds', 'levelIds', 'sectionIds', 'periodsPerWeek', 'step']);
        $this->step = 1;
    }

    public function getBreadcrumb(): string
    {
        return 'Multi-Class Assignment';
    }
}
