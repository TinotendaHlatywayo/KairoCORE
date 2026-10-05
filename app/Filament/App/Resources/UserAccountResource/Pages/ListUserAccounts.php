<?php

namespace App\Filament\App\Resources\UserAccountResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\UserAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserAccounts extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
