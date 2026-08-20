<?php

namespace App\Livewire\Academics;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Modules\Academics\Models\ExamMark;
use Modules\Academics\Models\ExamPaper;
use Modules\Academics\Models\Section;
use Modules\Students\Models\Student;

class MarkEntryGrid extends Component
{
    public $examPaperId;

    public $sectionId;

    public $marks = []; // Format: [student_id => mark]

    protected $rules = [
        'marks.*' => 'nullable|numeric|min:0',
    ];

    public function mount($examPaperId = null)
    {
        $this->examPaperId = $examPaperId;
    }

    // Fetches students in the selected class and their existing marks
    public function getStudentsProperty()
    {
        if (! $this->sectionId || ! $this->examPaperId) {
            return [];
        }

        $paper = ExamPaper::find($this->examPaperId);

        // Fetch students enrolled in this section
        $students = Student::whereHas('enrollments', function ($q) {
            $q->where('section_id', $this->sectionId);
        })->get();

        // Pre-load existing marks into the array
        $existingMarks = ExamMark::where('exam_paper_id', $this->examPaperId)
            ->whereIn('student_id', $students->pluck('id'))
            ->pluck('marks_obtained', 'student_id');

        foreach ($students as $student) {
            $this->marks[$student->id] = $existingMarks[$student->id] ?? null;
        }

        return $students;
    }

    public function saveMark($studentId)
    {
        $paper = ExamPaper::find($this->examPaperId);
        $markValue = $this->marks[$studentId];

        // Validation: Mark cannot exceed paper maximum
        if ($markValue > $paper->max_mark) {
            $this->addError("marks.$studentId", "Max marks is {$paper->max_mark}");

            return;
        }

        ExamMark::updateOrCreate(
            [
                'school_id' => auth()->user()->school_id,
                'student_id' => $studentId,
                'exam_paper_id' => $this->examPaperId,
            ],
            [
                'marks_obtained' => $markValue,
                'marked_by_id' => Auth::id(),
            ]
        );

        session()->flash('message', 'Mark updated successfully.');
    }

    public function render()
    {
        return view('livewire.academics.mark-entry-grid', [
            'papers' => ExamPaper::all(),
            'sections' => Section::all(),
            'students' => $this->students,
        ]);
    }
}
