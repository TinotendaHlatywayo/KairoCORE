<?php

namespace App\Filament\App\Resources\SchoolBankAccountResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\SchoolBankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSchoolBankAccounts extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = SchoolBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
