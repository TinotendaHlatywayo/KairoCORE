<?php

declare(strict_types=1);

namespace Modules\Knowledge\Filament\Resources\KnowledgeAssetResource\Pages;

use App\Models\School;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Knowledge\Filament\Resources\KnowledgeAssetResource;
use Modules\Knowledge\Models\KnowledgeAssetCopy;

class CreateKnowledgeAsset extends CreateRecord
{
    protected static string $resource = KnowledgeAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = app('current_tenant');

        $data['school_id'] = $tenant instanceof School ? $tenant->id : null;
        $data['uploaded_by_id'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $asset = $this->getRecord();
        $qty = (int) ($this->data['add_copies_quantity'] ?? 0);

        if ($qty > 0 && $asset->media_type === 'physical') {
            $tenant = app('current_tenant');
            $tenantId = $tenant instanceof School ? $tenant->id : null;

            for ($i = 1; $i <= $qty; $i++) {
                $serialCode = 'REP-'.$asset->id.'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

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
