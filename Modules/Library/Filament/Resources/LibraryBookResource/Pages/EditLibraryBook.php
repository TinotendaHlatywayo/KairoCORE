<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryBookResource\Pages;

use App\Models\School;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Modules\Library\Filament\Resources\LibraryBookResource;
use Modules\Library\Models\LibraryBookCopy;

class EditLibraryBook extends EditRecord
{
    protected static string $resource = LibraryBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $book = $this->getRecord();
        $qty = (int) ($this->data['add_copies_quantity'] ?? 0);

        if ($qty > 0 && $book->media_type === 'physical') {
            $tenant = app('current_tenant');
            $tenantId = $tenant instanceof School ? $tenant->id : null;

            $existingCount = LibraryBookCopy::where('library_book_id', $book->id)->count();

            for ($i = 1; $i <= $qty; $i++) {
                $nextIndex = $existingCount + $i;
                $serialCode = 'BC-'.$book->id.'-'.str_pad((string) $nextIndex, 4, '0', STR_PAD_LEFT);

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
