<?php

// Modules/Library/Filament/Resources/LibraryBookResource/Pages/ListLibraryBooks.php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryBookResource\Pages;

use App\Filament\App\Concerns\HasCsvBulkActions;
use App\Services\Csv\LibraryBookCsvService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Library\Filament\Resources\LibraryBookResource;

class ListLibraryBooks extends ListRecords
{
    use HasCsvBulkActions;

    protected static string $resource = LibraryBookResource::class;

    protected static function csvService(): string
    {
        return LibraryBookCsvService::class;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            ...$this->csvBulkActions(),
        ];
    }
}
