<?php

namespace App\Filament\App\Resources\SchoolBankAccountResource\Pages;

use App\Filament\App\Resources\SchoolBankAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolBankAccount extends EditRecord
{
    protected static string $resource = SchoolBankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
