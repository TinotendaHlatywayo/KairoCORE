<?php

namespace App\Filament\App\Resources\GradingScaleResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\GradingScaleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGradingScales extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = GradingScaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
