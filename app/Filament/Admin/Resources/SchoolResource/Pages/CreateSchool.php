<?php

namespace App\Filament\Admin\Resources\SchoolResource\Pages;

use App\Filament\Admin\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;

    /**
     * Module Visibility switches picked on the create form. They are not a
     * column on schools, so they are held here until the record exists and
     * written to system_settings in afterCreate().
     *
     * @var array<string, bool>
     */
    protected array $pendingModuleToggles = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->pendingModuleToggles] = SchoolResource::splitModuleToggles($data);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record !== null && $this->pendingModuleToggles !== []) {
            SchoolResource::writeModuleToggles($this->pendingModuleToggles, (int) $this->record->id);
        }

        $this->pendingModuleToggles = [];
    }
}
