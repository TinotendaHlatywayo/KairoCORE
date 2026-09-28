<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Finance\Models\SchoolBankAccount;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Models\ProcurementOrder;
use Modules\Inventory\Services\ProcurementPipelineService;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        $record = $this->getRecord();

        return $infolist
            ->schema([
                Section::make(__('Purchase Order (LPO)'))
                    ->columns(4)
                    ->schema([
                        TextEntry::make('order_number')->label(__('Order Number')),
                        TextEntry::make('supplier.name')->label(__('Supplier')),
                        TextEntry::make('order_date')->label(__('Order Date'))->date(),
                        TextEntry::make('status')->label(__('Status'))->badge(),
                        TextEntry::make('total_amount')->label(__('Total Amount'))->money('USD'),
                        TextEntry::make('refunded_amount')->label(__('Refunded (missing items)'))->money('USD')->placeholder('-'),
                        TextEntry::make('expected_delivery_date')->label(__('Expected Delivery'))->date()->placeholder('-'),
                    ]),
                Section::make(__('Ordered Items and Goods Received'))
                    ->description(__('Every item on this order is listed with the quantity ordered on the left and the quantity received on the right.'))
                    ->schema([
                        ViewEntry::make('grn_comparison')
                            ->label(__('Comparison'))
                            ->view('modules.inventory.po-grn-comparison')
                            ->viewData([
                                'po' => $record,
                                'rows' => $record->receivingComparison(),
                            ]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Actions\Action::make('approve')
                ->label(__('Approve'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(fn (): bool => in_array($record->status, ['draft', 'sent']))
                ->modalHeading(__('Approve Purchase Order?'))
                ->modalDescription(__('On approval the order total is deducted from the selected bank account and a paid expense is recorded.'))
                ->modalSubmitActionLabel(__('Approve & Deduct Funds'))
                ->fillForm(fn (): array => [
                    'bank_account_id' => PurchaseOrderResource::previousBankAccountId($record->school_id),
                ])
                ->form(fn (): array => [
                    Forms\Components\Section::make(__('Order Summary'))
                        ->schema([
                            Forms\Components\TextInput::make('order_number')
                                ->default($record->order_number)
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('supplier')
                                ->default($record->supplier?->name)
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\DatePicker::make('order_date')
                                ->default($record->order_date)
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('total_amount')
                                ->default(number_format((float) $record->total_amount, 2))
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(false),
                            Forms\Components\TextInput::make('item_count')
                                ->label(__('Number of Items'))
                                ->default($record->items()->count())
                                ->disabled()
                                ->dehydrated(false),
                        ])->columns(2),
                    Forms\Components\Select::make('bank_account_id')
                        ->label(__('Deduct From Bank Account'))
                        ->options(fn (): array => SchoolBankAccount::query()
                            ->where('school_id', $record->school_id)
                            ->where('is_active', true)
                            ->orderByDesc('is_default')
                            ->get()
                            ->mapWithKeys(fn ($account) => [$account->id => trim(implode(' — ', array_filter([
                                $account->bank_name,
                                $account->account_name,
                                $account->account_number,
                            ])))])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data) use ($record): void {
                    try {
                        app(ProcurementPipelineService::class)->approveOrder($record, (int) $data['bank_account_id']);
                    } catch (\RuntimeException $e) {
                        Notification::make()
                            ->title(__('Purchase order cannot be approved'))
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('Purchase Order approved'))
                        ->body(__(':total deducted from the selected bank account.', ['total' => '$'.number_format((float) $record->total_amount, 2)]))
                        ->success()
                        ->send();

                    $this->redirect(PurchaseOrderResource::getUrl('view', ['record' => $record]));
                }),
            Actions\Action::make('refund_missing')
                ->label(__('Refund Missing Items'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn (): bool => app(ProcurementPipelineService::class)->outstandingRefundValue($record) > 0)
                ->modalHeading(__('Refund missing / short-delivered items?'))
                ->modalDescription(fn (): string => __('The supplier refunds the value of items that were ordered but never received.'))
                ->modalSubmitActionLabel(__('Record Refund'))
                ->form(fn (): array => [
                    Forms\Components\Placeholder::make('refund_summary')
                        ->label(__('Refund Amount'))
                        ->content(fn (): string => '$'.number_format(app(ProcurementPipelineService::class)->outstandingRefundValue($record), 2)),
                    Forms\Components\Select::make('bank_account_id')
                        ->label(__('Credit Back To Bank Account'))
                        ->options(fn (): array => SchoolBankAccount::query()
                            ->where('school_id', $record->school_id)
                            ->where('is_active', true)
                            ->orderByDesc('is_default')
                            ->get()
                            ->mapWithKeys(fn ($account) => [$account->id => trim(implode(' — ', array_filter([
                                $account->bank_name,
                                $account->account_name,
                                $account->account_number,
                            ])))])
                            ->all())
                        ->default(fn (): ?int => $record->bank_account_id
                            ?? PurchaseOrderResource::previousBankAccountId($record->school_id))
                        ->searchable()
                        ->preload()
                        ->required(),
                ])
                ->action(function (array $data) use ($record): void {
                    $amount = app(ProcurementPipelineService::class)->refundOrder($record, (int) $data['bank_account_id']);

                    if ($amount > 0) {
                        Notification::make()
                            ->title(__('Refund recorded'))
                            ->body(__(':amount credited back to the bank account for missing items.', ['amount' => '$'.number_format($amount, 2)]))
                            ->success()
                            ->send();
                    }

                    $this->redirect(PurchaseOrderResource::getUrl('view', ['record' => $record]));
                }),
            Actions\Action::make('print')
                ->label(__('Print Comparison PDF'))
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->action(fn () => static::streamComparisonPdf($this->getRecord())),
            Actions\EditAction::make(),
        ];
    }

    public static function streamComparisonPdf(ProcurementOrder $order)
    {
        $pdf = Pdf::loadView('modules.inventory.po-grn-comparison-pdf', [
            'school' => current_tenant(),
            'po' => $order->load('supplier'),
            'rows' => $order->receivingComparison(),
            'primaryColor' => '#5b4fe9',
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $order->order_number.'-GRN-Comparison.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}
