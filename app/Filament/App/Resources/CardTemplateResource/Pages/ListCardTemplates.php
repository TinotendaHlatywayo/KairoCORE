<?php

namespace App\Filament\App\Resources\CardTemplateResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\CardTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCardTemplates extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = CardTemplateResource::class;

    protected static ?string $title = 'ID Card Templates';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    public function getHeading(): string
    {
        return __('ID Card Templates');
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return array_filter([
            Actions\CreateAction::make(),
            Actions\Action::make('bulkPrint')
                ->label(__('Bulk Print ID Cards'))
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('students.print-cards', [
                    'scope' => 'all',
                    'layout' => 'pvc',
                ]))
                ->openUrlInNewTab(),
            $this->getHelpAction(),
        ]);
    }
}
