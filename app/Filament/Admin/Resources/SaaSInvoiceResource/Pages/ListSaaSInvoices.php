<?php

namespace App\Filament\Admin\Resources\SaaSInvoiceResource\Pages;

app_path();

use App\Filament\Admin\Resources\SaaSInvoiceResource;
use Filament\Resources\Pages\ListRecords;

class ListSaaSInvoices extends ListRecords
{
    protected static string $resource = SaaSInvoiceResource::class;
}
