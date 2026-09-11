<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Actions\RemoveProfilePhotoAction;
use App\Filament\App\Resources\StudentResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Students\Models\Enrollment;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    public function getHeaderActions(): array
    {
        return [
            RemoveProfilePhotoAction::make()
                ->photoColumn('photo_path')
                ->visible(fn () => filled($this->getRecord()->photo_path))
                ->record($this->getRecord()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $student = $this->record;
        $currentEnrollment = $student->currentEnrollment;

        if ($currentEnrollment) {
            $data['academic_year_id'] = $currentEnrollment->academic_year_id;
            $data['course_id'] = $currentEnrollment->course_id;
            $data['section_id'] = $currentEnrollment->section_id;
            $data['roll_number'] = $currentEnrollment->roll_number;
        }

        $data['fee_waiver_id'] = $student->waivers()->first()?->id;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $academicYearId = $data['academic_year_id'] ?? null;
        $courseId = $data['course_id'] ?? null;
        $sectionId = $data['section_id'] ?? null;
        $rollNumber = $data['roll_number'] ?? null;
        $waiverId = $data['fee_waiver_id'] ?? null;

        unset($data['academic_year_id'], $data['course_id'], $data['section_id'], $data['roll_number'], $data['fee_waiver_id']);

        // Update core student columns
        $record->update($data);

        // Update enrollment
        if ($academicYearId && $courseId && $sectionId) {
            Enrollment::updateOrCreate(
                [
                    'school_id' => $record->school_id,
                    'student_id' => $record->id,
                    'academic_year_id' => $academicYearId,
                ],
                [
                    'course_id' => $courseId,
                    'section_id' => $sectionId,
                    'roll_number' => $rollNumber,
                ]
            );
        }

        // Update fee waiver / scholarship
        if ($waiverId) {
            $record->waivers()->sync([$waiverId]);
            $waiver = \Modules\Finance\Models\FeeWaiver::find($waiverId);
            if ($waiver) {
                $invoice = \Modules\Finance\Models\Invoice::where('student_id', $record->id)
                    ->where('status', '!=', 'paid')
                    ->latest('id')
                    ->first();
                if ($invoice) {
                    $subtotal = (float) $invoice->subtotal_amount;
                    $discount = 0.00;
                    if ($waiver->type === 'percentage') {
                        $discount = round($subtotal * ((float) $waiver->value / 100), 2);
                    } else {
                        $discount = min($subtotal, (float) $waiver->value);
                    }
                    $invoice->update([
                        'fee_waiver_id' => $waiver->id,
                        'discount_amount' => $discount,
                    ]);
                }
            }
        } else {
            $record->waivers()->detach();
            $invoice = \Modules\Finance\Models\Invoice::where('student_id', $record->id)
                ->where('status', '!=', 'paid')
                ->latest('id')
                ->first();
            if ($invoice) {
                $invoice->update([
                    'fee_waiver_id' => null,
                    'discount_amount' => 0.00,
                ]);
            }
        }

        return $record;
    }
}
