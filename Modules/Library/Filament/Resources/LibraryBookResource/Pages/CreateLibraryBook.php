<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryBookResource\Pages;

use App\Models\School;
use Filament\Resources\Pages\CreateRecord;
use Modules\Library\Filament\Resources\LibraryBookResource;
use Modules\Library\Models\LibraryBookCopy;

class CreateLibraryBook extends CreateRecord
{
    protected static string $resource = LibraryBookResource::class;

    protected function afterCreate(): void
    {
        $book = $this->getRecord();
        $qty = (int) ($this->data['add_copies_quantity'] ?? 0);

        if ($qty > 0 && $book->media_type === 'physical') {
            $tenant = app('current_tenant');
            $tenantId = $tenant instanceof School ? $tenant->id : null;

            for ($i = 1; $i <= $qty; $i++) {
                $serialCode = 'BC-'.$book->id.'-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT);

                LibraryBookCopy::create([
                    'school_id' => $tenantId,
                    'library_book_id' => $book->id,
                    'barcode' => $serialCode,
                    'qr_code' => $serialCode,
                    'condition' => 'excellent',
                    'status' => 'available',
                ]);
            }
        }
    }
}
