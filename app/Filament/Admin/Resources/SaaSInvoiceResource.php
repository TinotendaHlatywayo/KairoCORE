<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SaaSInvoiceResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\SaaSInvoice;

class SaaSInvoiceResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Subscriptions');
    }

    protected static ?string $model = SaaSInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Invoices';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DatePicker::make('due_date')
                    ->label(__('Due Date'))
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')->searchable()->sortable(),
                TextColumn::make('school.name')->label(__('Institution'))->searchable()->sortable(),
                TextColumn::make('total')->money('USD')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'warning',
                        'partially_paid' => 'info',
                        'void' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('issue_date')->date()->sortable(),
                TextColumn::make('due_date')->date()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => __('Paid'),
                        'unpaid' => __('Unpaid'),
                        'partially_paid' => __('Partially Paid'),
                        'void' => __('Void'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading(fn (SaaSInvoice $record) => __('Invoice').' #'.$record->invoice_number)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Close'))
                    ->modalContent(fn (SaaSInvoice $record) => view(
                        'filament.admin.resources.saas-invoice-view',
                        ['invoice' => $record]
                    )),
                Tables\Actions\Action::make('download_pdf')
                    ->label(__('Download PDF'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (SaaSInvoice $record) => route('saas.invoice.download', $record->uuid), shouldOpenInNewTab: true),
                Tables\Actions\Action::make('download_receipt')
                    ->label(__('Receipt'))
                    ->icon('heroicon-o-document-check')
                    ->color('info')
                    ->visible(fn (SaaSInvoice $record): bool => $record->receipt !== null)
                    ->url(fn (SaaSInvoice $record) => route('saas.receipt.download', $record->receipt->uuid), shouldOpenInNewTab: true),
                Tables\Actions\EditAction::make()
                    ->label(__('Edit Due Date')),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaaSInvoices::route('/'),
        ];
    }
}
