<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;

class ListKnowledgeAssets extends ListRecords
{
    protected static string $resource = KnowledgeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
