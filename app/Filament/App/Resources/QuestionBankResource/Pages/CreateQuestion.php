<?php

namespace App\Filament\App\Resources\QuestionBankResource\Pages;

use App\Filament\App\Resources\QuestionBankResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuestion extends CreateRecord
{
    protected static string $resource = QuestionBankResource::class;

    /**
     * Teachers build quizzes question-by-question: after creating one question
     * they land on a fresh Create form (same quiz context) instead of being
     * bounced back to the list. The list stays reachable via Cancel.
     */
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('create');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Question added — add another below or go back to the list.';
    }

    /**
     * The form does not collect ownership fields; stamp the creator here so
     * the INSERT never fails on the required created_by_id column.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = auth()->id();
        $data['school_id'] ??= app('current_tenant')?->id;

        static::getResource()::normalizeAnswerPayload($data);

        return $data;
    }
}
