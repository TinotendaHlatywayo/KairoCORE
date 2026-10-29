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
            Action::make('record_payment')
                ->label(__('Record Payment'))
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\TextInput::make('amount')
                        ->label(__('Payment Amount ($)'))
                        ->numeric()
                        ->required()
                        ->default(fn () => (float) $this->getRecord()->total),
                    \Filament\Forms\Components\TextInput::make('months_covered')
                        ->label(__('Billing Months Covered'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->required()
                        ->default(fn () => (int) ($this->getRecord()->months_covered ?? 1)),
                    \Filament\Forms\Components\DatePicker::make('payment_date')
                        ->label(__('Payment Date'))
                        ->required()
                        ->default(now()),
                    \Filament\Forms\Components\TextInput::make('reference_number')
                        ->label(__('Payment Reference / Slip #'))
                        ->required()
                        ->default(fn () => 'PAY-' . $this->getRecord()->invoice_number),
                    \Filament\Forms\Components\Select::make('payment_method')
                        ->label(__('Payment Method / Bank'))
                        ->options([
                            'bank_transfer' => __('Direct Bank Transfer'),
                            'cash' => __('Cash Deposit'),
                            'paynow' => __('Paynow Gateway'),
                            'other' => __('Other'),
                        ])
                        ->default('bank_transfer')
                        ->required(),
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label(__('Notes / Remarks'))
                        ->rows(2),
                ])
                ->action(function (array $data): void {
                    SaaSInvoiceResource::processRecordPayment($this->getRecord(), $data);
                }),
            Action::make('download')
                ->label(__('Download PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->url(fn () => route('saas.invoice.download', $this->getRecord()->uuid))
                ->openUrlInNewTab(),
        ];
    }
}
