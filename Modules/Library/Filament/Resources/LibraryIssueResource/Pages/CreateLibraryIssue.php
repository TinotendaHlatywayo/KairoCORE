<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryIssueResource\Pages;

use App\Models\School;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Modules\Library\Filament\Resources\LibraryIssueResource;

class CreateLibraryIssue extends CreateRecord
{
    protected static string $resource = LibraryIssueResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $tenant = app('current_tenant');

        $data['school_id'] = $tenant instanceof School ? $tenant->id : null;
        $data['issued_by_id'] = Auth::id();
        $data['issued_at'] = now();
        $data['status'] = 'issued';

        return $data;
    }
}
