<?php

namespace App\Services\Academic;

use Illuminate\Support\Collection;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Classroom;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Students\Models\Enrollment;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimetableLesson;

class AcademicValidationEngine
{
    protected ?int $schoolId;

    public function __construct(?int $schoolId = null)
    {
        $this->schoolId = $schoolId ?? config('current_tenant_id') ?? auth()->user()?->school_id;
    }

    public function validateAction(string $action, array $params = []): array
    {
        $validationMap = [
            'create_term' => 'validateCreateTerm',
            'delete_academic_year' => 'validateDeleteAcademicYear',
            'delete_subject' => 'validateDeleteSubject',
            'delete_teacher' => 'validateDeleteTeacher',
            'publish_results' => 'validatePublishResults',
            'archive_active_year' => 'validateArchiveActiveYear',
            'delete_form' => 'validateDeleteForm',
            'delete_classroom' => 'validateDeleteClassroom',
            'assign_teacher' => 'validateAssignTeacher',
            'enrol_student' => 'validateEnrolStudent',
            'create_timetable' => 'validateCreateTimetable',
        ];

        $method = $validationMap[$action] ?? null;
        if ($method && method_exists($this, $method)) {
            return $this->$method($params);
        }

        return ['valid' => true, 'errors' => [], 'warnings' => []];
    }

    public function validateCreateTerm(array $params): array
    {
        $errors = [];
        $warnings = [];

        if (! $this->schoolId) {
            $errors[] = 'Unable to determine school context.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $yearId = $params['academic_year_id'] ?? null;
        if (! $yearId) {
            $errors[] = 'Academic year must be selected before creating a term.';
        } else {
            $yearExists = AcademicYear::where('id', $yearId)
                ->where('school_id', $this->schoolId)
                ->exists();
            if (! $yearExists) {
                $errors[] = 'Selected academic year does not exist or is not accessible.';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateDeleteAcademicYear(array $params): array
    {
        $errors = [];
        $warnings = [];

        $yearId = $params['academic_year_id'] ?? $params['id'] ?? null;
        if (! $yearId) {
            $errors[] = 'Academic year ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $year = AcademicYear::where('id', $yearId);
        if ($this->schoolId) {
            $year->where('school_id', $this->schoolId);
        }

        $year = $year->first();
        if (! $year) {
            $errors[] = 'Academic year not found.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($year->is_active) {
            $errors[] = 'Cannot archive the active academic year. Activate another year first.';
        }

        $hasTerms = $year->terms()->exists();
        if ($hasTerms) {
            $warnings[] = 'This academic year has associated terms. Deleting will remove all term data.';
            $errors[] = 'Cannot delete an academic year that has terms. Archive the year instead.';
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateDeleteSubject(array $params): array
    {
        $errors = [];
        $warnings = [];

        $subjectId = $params['subject_id'] ?? $params['id'] ?? null;
        if (! $subjectId) {
            $errors[] = 'Subject ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $subject = Subject::where('id', $subjectId);
        if ($this->schoolId) {
            $subject->where('school_id', $this->schoolId);
        }
        $subject = $subject->first();

        if (! $subject) {
            $errors[] = 'Subject not found.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $inTimetable = TimetableLesson::where('subject_id', $subjectId)->exists();
        if ($inTimetable) {
            $errors[] = 'Cannot delete a subject that is used in timetable entries. Remove from timetable first.';
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateDeleteTeacher(array $params): array
    {
        $errors = [];
        $warnings = [];

        $teacherId = $params['teacher_id'] ?? $params['id'] ?? null;
        if (! $teacherId) {
            $errors[] = 'Teacher ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $assignedCount = Course::where('teacher_id', $teacherId)
            ->orWhere('classroom_teacher_id', $teacherId)
            ->when($this->schoolId, fn ($q) => $q->where('school_id', $this->schoolId))
            ->count();

        if ($assignedCount > 0) {
            $errors[] = "Cannot delete teacher assigned to {$assignedCount} courses/classrooms. Reassign first.";
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validatePublishResults(array $params): array
    {
        $errors = [];
        $warnings = [];

        $assessmentId = $params['assessment_id'] ?? null;
        $courseId = $params['course_id'] ?? null;

        if (! $assessmentId && ! $courseId) {
            $errors[] = 'Assessment or course ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($courseId) {
            $courseQuery = Course::where('id', $courseId);
            if ($this->schoolId) {
                $courseQuery->where('school_id', $this->schoolId);
            }
            $course = $courseQuery->first();

            if (! $course) {
                $errors[] = 'Course not found.';
            } elseif ($course->sections->isEmpty()) {
                $warnings[] = 'This course has no sections. Results may be empty.';
            }
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateArchiveActiveYear(array $params): array
    {
        $errors = [];
        $warnings = [];

        $yearId = $params['academic_year_id'] ?? $params['id'] ?? null;
        if (! $yearId) {
            $errors[] = 'Academic year ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $yearQuery = AcademicYear::where('id', $yearId);
        if ($this->schoolId) {
            $yearQuery->where('school_id', $this->schoolId);
        }

        $year = $yearQuery->first();
        if (! $year) {
            $errors[] = 'Academic year not found.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($year->is_active) {
            $errors[] = 'Cannot archive the active academic year. Activate another year first.';
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateDeleteForm(array $params): array
    {
        $errors = [];
        $warnings = [];

        $formId = $params['form_id'] ?? $params['id'] ?? null;
        if (! $formId) {
            $errors[] = 'Form ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $courseQuery = Course::where('id', $formId);
        if ($this->schoolId) {
            $courseQuery->where('school_id', $this->schoolId);
        }

        $course = $courseQuery->first();
        if (! $course) {
            $errors[] = 'Form not found.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $studentCount = Enrollment::where('course_id', $formId)
            ->whereHas('student', fn ($q) => $q->whereNull('deleted_at'))
            ->count();
        if ($studentCount > 0) {
            $errors[] = "Cannot delete a form with {$studentCount} enrolled students. Transfer students first.";
        }

        $timetableCount = TimetableLesson::where('course_id', $formId)->count();
        if ($timetableCount > 0) {
            $errors[] = "Cannot delete a form used in {$timetableCount} timetable entries.";
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateDeleteClassroom(array $params): array
    {
        $errors = [];
        $warnings = [];

        $classroomId = $params['classroom_id'] ?? $params['id'] ?? null;
        if (! $classroomId) {
            $errors[] = 'Classroom ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $timetableCount = TimetableLesson::where('classroom_id', $classroomId)->count();
        if ($timetableCount > 0) {
            $errors[] = "Cannot delete a classroom used in {$timetableCount} timetable entries.";
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateAssignTeacher(array $params): array
    {
        $errors = [];
        $warnings = [];

        $teacherId = $params['teacher_id'] ?? null;
        $subjectId = $params['subject_id'] ?? null;

        if (! $teacherId && ! $subjectId) {
            $errors[] = 'Teacher or subject ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateEnrolStudent(array $params): array
    {
        $errors = [];
        $warnings = [];

        $studentId = $params['student_id'] ?? null;
        $courseId = $params['course_id'] ?? null;

        if (! $studentId) {
            $errors[] = 'Student ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if (! $courseId) {
            $errors[] = 'Course/Form ID is required.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $studentQuery = Student::where('id', $studentId);
        if ($this->schoolId) {
            $studentQuery->where('school_id', $this->schoolId);
        }

        $student = $studentQuery->first();
        if (! $student) {
            $errors[] = 'Student not found or not accessible.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        if ($student->status !== 'Full') {
            $warnings[] = 'Student has status "'.$student->status.'". This may affect reporting.';
        }

        $courseQuery = Course::where('id', $courseId);
        if ($this->schoolId) {
            $courseQuery->where('school_id', $this->schoolId);
        }

        $course = $courseQuery->first();
        if (! $course) {
            $errors[] = 'Form not found.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateCreateTimetable(array $params): array
    {
        $errors = [];
        $warnings = [];

        $yearId = $params['academic_year_id'] ?? null;
        if (! $yearId) {
            $errors[] = 'Academic year must be selected.';

            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        $coursesExist = $this->schoolId
            ? Course::where('school_id', $this->schoolId)->exists()
            : Course::exists();
        if (! $coursesExist) {
            $errors[] = 'No forms created yet. Create forms before building timetable.';
        }

        $subjectsExist = $this->schoolId
            ? Subject::where('school_id', $this->schoolId)->exists()
            : Subject::exists();
        if (! $subjectsExist) {
            $errors[] = 'No subjects created yet. Configure subjects before building timetable.';
        }

        return ['valid' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function validateAll(): Collection
    {
        $issues = collect();

        $schoolId = $this->schoolId;
        if (! $schoolId) {
            return $issues;
        }

        $activeYear = AcademicYear::where('school_id', $schoolId)
            ->where('is_active', true)
            ->first();

        if (! $activeYear) {
            $issues->push([
                'type' => 'error',
                'category' => 'academic_year',
                'message' => __('No active academic year configured. Create and activate an academic year.'),
                'action' => route('filament.app.resources.academic-years.create'),
            ]);
        }

        if ($activeYear) {
            $termCount = $activeYear->terms()->count();
            if ($termCount === 0) {
                $issues->push([
                    'type' => 'error',
                    'category' => 'terms',
                    'message' => __('No terms configured for the active academic year.'),
                    'action' => route('filament.app.resources.academic-years.edit', $activeYear),
                ]);
            }

            $courseCount = Course::where('school_id', $schoolId)->count();
            if ($courseCount === 0) {
                $issues->push([
                    'type' => 'error',
                    'category' => 'forms',
                    'message' => __('No forms/courses have been created yet.'),
                    'action' => route('filament.app.resources.courses.create'),
                ]);
            } else {
                $sectionCount = Section::where('school_id', $schoolId)->count();
                if ($sectionCount === 0) {
                    $issues->push([
                        'type' => 'warning',
                        'category' => 'streams',
                        'message' => __('No sections/streams have been created yet.'),
                        'action' => route('filament.app.resources.courses.index'),
                    ]);
                }

                $subjectCount = Subject::where('school_id', $schoolId)->count();
                if ($subjectCount === 0) {
                    $issues->push([
                        'type' => 'warning',
                        'category' => 'subjects',
                        'message' => __('No subjects have been configured yet.'),
                        'action' => route('filament.app.resources.subjects.create'),
                    ]);
                }

                $classroomCount = Classroom::where('school_id', $schoolId)->count();
                if ($classroomCount === 0) {
                    $issues->push([
                        'type' => 'info',
                        'category' => 'classrooms',
                        'message' => __('No classrooms defined yet.'),
                        'action' => route('filament.app.resources.classrooms.create'),
                    ]);
                }
            }
        }

        return $issues;
    }
}
