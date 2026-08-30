<?php

namespace App\Filament\App\Resources\HostelAttendanceResource\Pages;

use App\Filament\App\Resources\HostelAttendanceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHostelAttendance extends CreateRecord
{
    protected static string $resource = HostelAttendanceResource::class;

    protected array $learners = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->learners = $data['learners'] ?? [];
        unset($data['learners']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->learners as $row) {
            if (empty($row['student_id'])) {
                continue;
            }

            $this->record->students()->create([
                'student_id' => $row['student_id'],
                'status' => filter_var($row['is_present'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'present' : 'absent',
                'remarks' => $row['remarks'] ?? null,
            ]);
        }
    }
}
