<?php

namespace App\Filament\App\Resources\PollResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\PollResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPolls extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make()->label(__('New Poll/Survey')),
        ];
    }
}