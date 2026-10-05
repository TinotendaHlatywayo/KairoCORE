<?php

namespace App\Filament\App\Resources\CustomRoleResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\CustomRoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomRoles extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = CustomRoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make()
                ->slideOver()
                ->mutateFormDataUsing(fn (array $data): array => CustomRoleResource::dehydratePermissions($data)),
        ];
    }
}
