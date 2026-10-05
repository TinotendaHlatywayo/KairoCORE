<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;

class ListKnowledgeAssets extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = KnowledgeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make(),
        ];
    }
}
