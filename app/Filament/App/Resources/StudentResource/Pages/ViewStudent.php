<?php

namespace App\Filament\App\Resources\StudentResource\Pages;

use App\Filament\App\Actions\RemoveProfilePhotoAction;
use App\Filament\App\Resources\StudentResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    public function getHeaderActions(): array
    {
        return [
            RemoveProfilePhotoAction::make()
                ->photoColumn('photo_path')
                ->visible(fn () => filled($this->getRecord()->photo_path))
                ->record($this->getRecord()),
        ];
    }
}
