<?php

namespace App\Filament\App\Resources\CustomRoleResource\Pages;

use App\Filament\App\Resources\CustomRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomRoles extends ListRecords
{
    protected static string $resource = CustomRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
}
