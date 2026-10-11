<?php

namespace App\Filament\Admin\Resources\SaaSInvoiceResource\Pages;

app_path();

use App\Filament\Admin\Resources\SaaSInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListSaaSInvoices extends ListRecords
{
    protected static string $resource = SaaSInvoiceResource::class;

    protected static ?string $title = 'Invoices';

    public function getSubheading(): ?string
    {
        return __('Subscription invoices issued to each institution.');
    }
}
