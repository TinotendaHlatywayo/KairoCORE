<?php

namespace App\Filament\App\Resources\QuestionBankResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\QuestionBankResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuestions extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = QuestionBankResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
