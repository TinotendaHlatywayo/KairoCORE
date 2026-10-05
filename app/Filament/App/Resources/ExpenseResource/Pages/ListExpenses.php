<?php

namespace App\Filament\App\Resources\ExpenseResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\ExpenseResource;
use App\Filament\App\Widgets\BankAccountSwitcherWidget;
use App\Filament\App\Widgets\ExpenseAnalyticsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BankAccountSwitcherWidget::class,
            ExpenseAnalyticsWidget::class,
        ];
    }
}
