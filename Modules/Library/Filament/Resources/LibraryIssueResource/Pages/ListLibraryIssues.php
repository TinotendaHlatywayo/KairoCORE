<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryIssueResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Services\Csv\LibraryIssueCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\LibraryIssueResource;

class ListLibraryIssues extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = LibraryIssueResource::class;

    protected static function csvService(): string
    {
        return LibraryIssueCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
