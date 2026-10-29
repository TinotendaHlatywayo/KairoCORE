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
                TextColumn::make('subscription.next_payment_date')->label(__('Next Billing Date'))->date()->sortable(),
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
                Tables\Actions\Action::make('record_payment')
                    ->label(__('Record Payment'))
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Payment Amount ($)'))
                            ->numeric()
                            ->required()
                            ->default(fn (SaaSInvoice $record): float => (float) $record->total),
                        Forms\Components\TextInput::make('months_covered')
                            ->label(__('Billing Months Covered'))
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->default(fn (SaaSInvoice $record): int => (int) ($record->months_covered ?? 1)),
                        Forms\Components\DatePicker::make('payment_date')
                            ->label(__('Payment Date'))
                            ->required()
                            ->default(now()),
                        Forms\Components\TextInput::make('reference_number')
                            ->label(__('Payment Reference / Slip #'))
                            ->required()
                            ->default(fn (SaaSInvoice $record): string => 'PAY-' . $record->invoice_number),
                        Forms\Components\Select::make('payment_method')
                            ->label(__('Payment Method / Bank'))
                            ->options([
                                'bank_transfer' => __('Direct Bank Transfer'),
                                'cash' => __('Cash Deposit'),
                                'paynow' => __('Paynow Gateway'),
                                'other' => __('Other'),
                            ])
                            ->default('bank_transfer')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Notes / Remarks'))
                            ->rows(2),
                    ])
                    ->action(fn (SaaSInvoice $record, array $data) => static::processRecordPayment($record, $data)),
                Tables\Actions\Action::make('view')
                    ->label(__('View'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (SaaSInvoice $record): string => static::getUrl('view', ['record' => $record])),
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

    public static function processRecordPayment(SaaSInvoice $record, array $data): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data) {
            $months = (int) $data['months_covered'];
            $amount = (float) $data['amount'];
            
            $periodStart = $record->period_start ? \Carbon\Carbon::parse($record->period_start) : now()->startOfDay();
            $periodEnd = $periodStart->copy()->addMonthsNoOverflow($months)->subDay();

            $record->update([
                'months_covered' => $months,
                'total' => $amount,
                'subtotal' => $amount,
                'period_end' => $periodEnd->toDateString(),
                'status' => 'paid',
                'is_locked' => true,
            ]);

            $transaction = \Modules\SaaS\Models\SaaSTransaction::create([
                'school_id' => $record->school_id,
                'saas_invoice_id' => $record->id,
                'payment_gateway_key' => $data['payment_method'] ?? 'bank_transfer',
                'transaction_reference' => $data['reference_number'] ?? ('PAY-'.$record->invoice_number),
                'amount' => $amount,
                'currency' => $record->currency ?? 'USD',
                'status' => 'pending',
            ]);

            app(\Modules\SaaS\Services\SubscriptionManager::class)->processTransactionVerification($transaction);

            $subscription = $record->subscription;
            if ($subscription) {
                $calculatedNext = $periodStart->copy()->addMonthsNoOverflow($months);
                $currentNext = $subscription->next_payment_date ? \Carbon\Carbon::parse($subscription->next_payment_date) : null;
                
                if ($currentNext && $currentNext->gt($calculatedNext)) {
                    $nextDate = $currentNext->copy()->addMonthsNoOverflow($months)->toDateString();
                } else {
                    $nextDate = $calculatedNext->toDateString();
                }

                $subscription->update([
                    'status' => 'active',
                    'last_payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'next_payment_date' => $nextDate,
                    'ends_at' => \Carbon\Carbon::parse($nextDate),
                ]);
            }
        });

        \Filament\Notifications\Notification::make()
            ->title(__('Payment Recorded Successfully'))
            ->body(__('Invoice marked as paid, receipt generated, and next billing date updated.'))
            ->success()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaaSInvoices::route('/'),
            'view' => Pages\ViewSaaSInvoice::route('/{record}/view'),
        ];
    }
}
