<?php

namespace App\Filament\Admin\Resources\SaaSInvoiceResource\Pages;

use App\Filament\Admin\Resources\SaaSInvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ViewSaaSInvoice extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SaaSInvoiceResource::class;

    protected static string $view = 'filament.admin.resources.saas-invoice.invoice-view';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return __('Invoice').' '.$this->getRecord()->invoice_number;
    }

    public function getSubheading(): ?string
    {
        return __('Preview of the generated PDF document. Use Download PDF to save or print it.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download')
                ->label(__('Download PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('saas.invoice.download', $this->getRecord()->uuid))
                ->openUrlInNewTab(),
        ];
    }
}
