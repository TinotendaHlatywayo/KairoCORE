<?php

namespace App\Filament\Admin\Resources\PlatformExpenseResource\Pages;

use App\Filament\Admin\Resources\PlatformExpenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlatformExpenses extends ListRecords
{
    protected static string $resource = PlatformExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Record Expense')),
        ];
    }
}
