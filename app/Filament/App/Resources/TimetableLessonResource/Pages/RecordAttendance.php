<?php

namespace App\Filament\App\Resources\TimetableLessonResource\Pages;

use App\Filament\App\Resources\TimetableLessonResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\StudentAttendance;
use Modules\Students\Models\Student;
use Modules\Timetables\Models\TimetableLesson;

class RecordAttendance extends Page
{
    protected static string $resource = TimetableLessonResource::class;

    protected static string $view = 'filament.app.resources.timetable-lesson-resource.pages.record-attendance';

    public $lesson;

    public $date;

    public $students = [];

    public $attendanceState = []; // Holds temporary student statuses: [student_id => status]

    public $remarksState = []; // Holds remarks: [student_id => remarks]

    public function mount($record): void
    {
        $this->lesson = TimetableLesson::findOrFail($record);
        $this->date = date('Y-m-d'); // Default to today's date

        $this->loadStudentRecords();
    }

    /**
     * Query students enrolled in this stream and load any existing attendance data.
     */
    public function loadStudentRecords(): void
    {
        $schoolId = app('current_tenant')->id;

        // Fetch students currently enrolled in this lesson's class stream (Form & Class)
        $enrolledStudents = Student::whereHas('enrollments', function ($query) {
            $query->where('academic_year_id', $this->lesson->academic_year_id)
                ->where('course_id', $this->lesson->course_id)
                ->where('section_id', $this->lesson->section_id);
        })->get();

        $this->students = [];
        $this->attendanceState = [];
        $this->remarksState = [];

        foreach ($enrolledStudents as $student) {
            // Check if attendance has already been recorded for this student, lesson, and date
            $existing = StudentAttendance::where('school_id', $schoolId)
                ->where('student_id', $student->id)
                ->where('timetable_lesson_id', $this->lesson->id)
                ->where('date', $this->date)
                ->first();

            $this->students[] = [
                'id' => $student->id,
                'name' => "{$student->first_name} {$student->last_name}",
                'admission_number' => $student->admission_number,
                'gender' => $student->gender,
            ];

            // Default all students to "present" if no attendance has been recorded yet
            $this->attendanceState[$student->id] = $existing ? $existing->status : 'present';
            $this->remarksState[$student->id] = $existing ? $existing->remarks : '';
        }
    }

    /**
     * Toggle the status state for a specific student in memory.
     */
    public function setStatus(int $studentId, string $status): void
    {
        $this->attendanceState[$studentId] = $status;
    }

    /**
     * Save the attendance sheet to the database in a transaction.
     */
    public function save(): void
    {
        $schoolId = app('current_tenant')->id;
        $markedBy = auth()->id();

        DB::beginTransaction();

        try {
            $absentCount = 0;

            foreach ($this->students as $std) {
                $studentId = $std['id'];
                $status = $this->attendanceState[$studentId];
                $remarks = $this->remarksState[$studentId] ?? null;

                StudentAttendance::updateOrCreate(
                    [
                        'school_id' => $schoolId,
                        'student_id' => $studentId,
                        'timetable_lesson_id' => $this->lesson->id,
                        'date' => $this->date,
                    ],
                    [
                        'status' => $status,
                        'remarks' => $remarks,
                        'marked_by_id' => $markedBy,
                    ]
                );

                if ($status === 'absent') {
                    $absentCount++;
                    // Dispatch Parent Absent Notification
                    $this->notifyParent($studentId, $std['name']);
                }
            }

            DB::commit();

            Notification::make()
                ->title(__('Attendance Saved Successfully!'))
                ->body("Student records updated. Dispatched {$absentCount} parent absence alerts.")
                ->success()
                ->send();

        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()
                ->title(__('Save Failed'))
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Simulate real-time Parent Absence Alerts (SMS / Email)
     */
    protected function notifyParent(int $studentId, string $studentName): void
    {
        $studentObj = Student::find($studentId);
        if (! $studentObj) {
            return;
        }

        // Loop through linked guardians and log simulated alert
        foreach ($studentObj->guardians as $guardian) {
            Log::info("Parent Alert: Dispatched SMS to {$guardian->name} ({$guardian->phone}): 'Dear Parent, your child {$studentName} has been marked absent from {$this->lesson->subject->name} during Period {$this->lesson->timeSlot->name} today.'");
        }
    }
}
