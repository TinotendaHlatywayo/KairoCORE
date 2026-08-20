<?php

namespace App\Filament\App\Resources\GradingScaleResource\Pages;

use App\Filament\App\Resources\GradingScaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGradingScale extends EditRecord
{
    protected static string $resource = GradingScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
