<?php

namespace App\Filament\App\Resources\ClassroomResource\Pages;

use App\Filament\App\Resources\ClassroomResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClassroom extends EditRecord
{
    protected static string $resource = ClassroomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
