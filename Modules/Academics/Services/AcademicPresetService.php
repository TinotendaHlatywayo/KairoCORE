<?php

namespace Modules\Academics\Services;

use App\Services\TerminologyService;
use Illuminate\Support\Facades\App;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Subject;

class AcademicPresetService
{
    protected TerminologyService $terminologyService;

    public function __construct(TerminologyService $terminologyService)
    {
        $this->terminologyService = $terminologyService;
    }

    public function applyPreset(string $region): void
    {
        if (! App::has('current_tenant')) {
            return;
        }

        switch ($region) {
            case 'zimbabwe':
                $this->setupZimbabwe();
                break;
            case 'south_africa':
                $this->setupSouthAfrica();
                break;
            case 'united_states':
                $this->setupUnitedStates();
                break;
        }
    }

    protected function setupZimbabwe(): void
    {
        $schoolId = App::make('current_tenant')->id;

        // 1. Terminology Overrides
        $this->terminologyService->set('label.course', 'Form / Level');
        $this->terminologyService->set('label.section', 'Class Stream');
        $this->terminologyService->set('label.term', 'Term');
        $this->terminologyService->set('label.exam_assessment', 'CALA / Exam');

        // 2. Standard Grade / Form Levels
        $levels = [
            ['name' => 'ECD A', 'code' => 'ECDA'],
            ['name' => 'ECD B', 'code' => 'ECDB'],
            ['name' => 'Grade 1', 'code' => 'G1'],
            ['name' => 'Grade 2', 'code' => 'G2'],
            ['name' => 'Grade 3', 'code' => 'G3'],
            ['name' => 'Grade 4', 'code' => 'G4'],
            ['name' => 'Grade 5', 'code' => 'G5'],
            ['name' => 'Grade 6', 'code' => 'G6'],
            ['name' => 'Grade 7', 'code' => 'G7'],
            ['name' => 'Form 1', 'code' => 'F1'],
            ['name' => 'Form 2', 'code' => 'F2'],
            ['name' => 'Form 3', 'code' => 'F3'],
            ['name' => 'Form 4', 'code' => 'F4'],
            ['name' => 'Lower Six (Form 5)', 'code' => 'L6'],
            ['name' => 'Upper Six (Form 6)', 'code' => 'U6'],
        ];

        foreach ($levels as $level) {
            Course::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'code' => $level['code'],
                ],
                ['name' => $level['name']]
            );
        }

        // 3. National Curriculum Core Subjects (MoPSE / ZIMSEC)
        $subjects = [
            ['name' => 'Mathematics', 'code' => 'MATH', 'type' => 'theory'],
            ['name' => 'English Language', 'code' => 'ENG', 'type' => 'theory'],
            ['name' => 'Heritage Studies', 'code' => 'HERI', 'type' => 'theory'],
            ['name' => 'Shona Language', 'code' => 'SHON', 'type' => 'theory'],
            ['name' => 'Ndebele Language', 'code' => 'NDEB', 'type' => 'theory'],
            ['name' => 'Combined Science', 'code' => 'COMSCI', 'type' => 'both'],
            ['name' => 'Agriculture', 'code' => 'AGRIC', 'type' => 'both'],
            ['name' => 'Family & Religious Studies', 'code' => 'FRS', 'type' => 'theory'],
            ['name' => 'Business Studies', 'code' => 'BUS', 'type' => 'theory'],
            ['name' => 'Principles of Accounts', 'code' => 'ACC', 'type' => 'theory'],
        ];

        foreach ($subjects as $sub) {
            Subject::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'code' => $sub['code'],
                ],
                [
                    'name' => $sub['name'],
                    'type' => $sub['type'],
                    'credit_weight' => 1.00,
                ]
            );
        }
    }

    protected function setupSouthAfrica(): void
    {
        $schoolId = App::make('current_tenant')->id;

        $this->terminologyService->set('label.course', 'Grade');
        $this->terminologyService->set('label.section', 'Class');
        $this->terminologyService->set('label.term', 'Term');

        $levels = [
            ['name' => 'Grade R', 'code' => 'GR'],
            ['name' => 'Grade 1', 'code' => 'G1'],
            ['name' => 'Grade 7', 'code' => 'G7'],
            ['name' => 'Grade 10', 'code' => 'G10'],
            ['name' => 'Grade 11', 'code' => 'G11'],
            ['name' => 'Grade 12 (Matric)', 'code' => 'G12'],
        ];

        foreach ($levels as $level) {
            Course::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'code' => $level['code'],
                ],
                ['name' => $level['name']]
            );
        }
    }

    protected function setupUnitedStates(): void
    {
        $schoolId = App::make('current_tenant')->id;

        $this->terminologyService->set('label.course', 'Grade');
        $this->terminologyService->set('label.section', 'Section');
        $this->terminologyService->set('label.term', 'Semester');

        $levels = [
            ['name' => 'Kindergarten', 'code' => 'KG'],
            ['name' => '1st Grade', 'code' => 'G1'],
            ['name' => '5th Grade', 'code' => 'G5'],
            ['name' => '9th Grade (Freshman)', 'code' => 'G9'],
            ['name' => '12th Grade (Senior)', 'code' => 'G12'],
        ];

        foreach ($levels as $level) {
            Course::updateOrCreate(
                [
                    'school_id' => $schoolId,
                    'code' => $level['code'],
                ],
                ['name' => $level['name']]
            );
        }
    }
}
