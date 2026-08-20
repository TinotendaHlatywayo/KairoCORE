<?php

namespace App\Filament\Student\Pages;

use App\Filament\Student\Resources\HomeworkResource;
use Filament\Pages\Page;
use Modules\Hostels\Models\HostelAllocation;

class StudentResidence extends Page
{
    protected static string $view = 'filament.student.pages.student-residence';

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Boarding';

    protected static ?string $navigationLabel = 'My Residence';

    protected static ?string $title = 'My Residence';

    protected static ?string $slug = 'my-residence';

    public static function getNavigationLabel(): string
    {
        return __('My Residence');
    }

    protected function getViewData(): array
    {
        $student = HomeworkResource::currentStudent();

        $allocation = null;

        if ($student) {
            $allocation = HostelAllocation::where('student_id', $student->id)
                ->with(['bed.room.hostel', 'bed.room.floor', 'bed.room.wing', 'academicYear'])
                ->latest()
                ->first();
        }

        return [
            'student' => $student,
            'allocation' => $allocation,
        ];
    }
}
