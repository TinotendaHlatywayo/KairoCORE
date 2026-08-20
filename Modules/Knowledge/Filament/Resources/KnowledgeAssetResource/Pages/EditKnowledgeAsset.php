<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages;

use App\Models\School;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;
use Modules\Knowledge\Models\KnowledgeAssetCopy;

class EditKnowledgeAsset extends EditRecord
{
    protected static string $resource = KnowledgeAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $asset = $this->getRecord();
        $qty = (int) ($this->data['add_copies_quantity'] ?? 0);

        if ($qty > 0 && $asset->media_type === 'physical') {
            $tenant = app('current_tenant');
            $tenantId = $tenant instanceof School ? $tenant->id : null;

            $existingCount = KnowledgeAssetCopy::where('knowledge_asset_id', $asset->id)->count();

            for ($i = 1; $i <= $qty; $i++) {
                $nextIndex = $existingCount + $i;
                $serialCode = 'REP-'.$asset->id.'-'.str_pad((string) $nextIndex, 4, '0', STR_PAD_LEFT);

                KnowledgeAssetCopy::create([
                    'school_id' => $tenantId,
                    'knowledge_asset_id' => $asset->id,
                    'barcode' => $serialCode,
                    'qr_code' => $serialCode,
                    'condition' => 'excellent',
                    'status' => 'available',
                ]);
            }
        }
    }
}
