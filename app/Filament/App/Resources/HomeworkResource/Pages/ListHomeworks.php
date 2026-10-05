<?php

namespace App\Filament\App\Resources\HomeworkResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\HomeworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHomeworks extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = HomeworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(), Actions\CreateAction::make()];
    }
}
