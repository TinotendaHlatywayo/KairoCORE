<?php

namespace App\Filament\App\Resources\FeePaymentSubmissionResource\Pages;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Resources\FeePaymentSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListFeePaymentSubmissions extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = FeePaymentSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
        ];
    }
}
