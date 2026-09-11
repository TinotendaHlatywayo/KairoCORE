<?php

declare(strict_types=1);

namespace Modules\Inventory\Filament\Resources\PurchaseOrderResource\Pages;

use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Modules\Inventory\Filament\Resources\PurchaseOrderResource;
use Modules\Inventory\Models\ProcurementOrder;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
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
                        TextEntry::make('expected_delivery_date')->label(__('Expected Delivery'))->date()->placeholder('-'),
                    ]),
                Section::make(__('Goods Received vs Purchase Order'))
                    ->schema([
                        ViewEntry::make('grn_comparison')
                            ->label(__('Comparison'))
                            ->view('modules.inventory.po-grn-comparison')
                            ->viewData(['po' => $this->getRecord()]),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
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
            'po' => $order->load(['supplier', 'items.inventoryItem', 'grns.items']),
            'primaryColor' => '#5b4fe9',
        ])->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $order->order_number.'-GRN-Comparison.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }
}