<?php

namespace App\Filament\App\Resources\PollResource\Pages;

use App\Filament\App\Resources\PollResource;
use Filament\Resources\Pages\EditRecord;

class EditPoll extends EditRecord
{
    protected static string $resource = PollResource::class;

    /** @var int[]|null */
    protected ?array $optionIdsBeforeEdit = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->optionIdsBeforeEdit = $this->record->options()->pluck('id')->sort()->values()->all();

        return $data;
    }

    protected function afterSave(): void
    {
        // "Edited and saved again" means the poll is re-sent to its audience. Any
        // answers cast against the previous content are dropped when the question,
        // the type, or the option list actually changed — stale tallies must never
        // sit on reworded content.
        try {
            $record = $this->record;

            $createdOptions = $record->options()->pluck('id')->sort()->values()->all();

            $optionsChanged = $this->optionIdsBeforeEdit !== null
                && $this->optionIdsBeforeEdit !== $createdOptions;

            if ($record->wasChanged('question') || $record->wasChanged('type') || $optionsChanged) {
                $record->votes()->delete();
            }

            PollResource::broadcastToAudience($record);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
