<?php

namespace App\Filament\Admin\Resources\SaaSPlanResource\Pages;

use App\Filament\Admin\Resources\SaaSPlanResource;
use Filament\Resources\Pages\ListRecords;

class ListSaaSPlans extends ListRecords
{
    protected static string $resource = SaaSPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make()
                ->label(__('New Subscription Plan'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
