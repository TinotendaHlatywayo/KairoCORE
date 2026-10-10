<?php

namespace App\Filament\App\Resources\PollResource\Pages;

use App\Filament\App\Resources\PollResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        try {
            PollResource::broadcastToAudience($this->record);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}