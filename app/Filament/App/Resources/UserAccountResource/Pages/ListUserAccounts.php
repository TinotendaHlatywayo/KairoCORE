<?php

namespace App\Filament\App\Resources\UserAccountResource\Pages;

use App\Filament\App\Resources\UserAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserAccounts extends ListRecords
{
    protected static string $resource = UserAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
