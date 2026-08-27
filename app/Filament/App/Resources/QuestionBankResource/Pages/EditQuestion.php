<?php

namespace App\Filament\App\Resources\QuestionBankResource\Pages;

use App\Filament\App\Resources\QuestionBankResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    protected static string $resource = QuestionBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        static::getResource()::normalizeAnswerPayload($data);

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Surface the stored answer on the per-type input for editing.
        if (empty($data['manual_marking'])) {
            $answer = $data['correct_answer'] ?? null;

            $data['tf_correct_answer'] = match ($data['question_type'] ?? null) {
                'true_false' => filter_var(is_array($answer) ? ($answer[0] ?? true) : $answer, FILTER_VALIDATE_BOOLEAN),
                'multiple_choice' => null,
                'multiple_select' => null,
                default => null,
            };

            if (($data['question_type'] ?? null) === 'multiple_choice') {
                $data['mcq_correct'] = is_array($answer) ? ($answer[0] ?? null) : $answer;
            }

            if (($data['question_type'] ?? null) === 'multiple_select') {
                $data['ms_correct'] = is_array($answer) ? array_map('strval', $answer) : ($answer !== null ? [$answer] : []);
            }
        }

        return $data;
    }
}
