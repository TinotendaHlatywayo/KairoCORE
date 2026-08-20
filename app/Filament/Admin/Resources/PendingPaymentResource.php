<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PendingPaymentResource\Pages;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSTransaction;
use Modules\SaaS\Services\SubscriptionManager;

class PendingPaymentResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Billing & Subscriptions');
    }

    protected static ?string $model = SaaSManualSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Billing & Subscriptions';

    protected static ?string $navigationLabel = 'Manual Confirmations';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return Auth::check()
            && $user !== null
            && $user->school_id === null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(SaaSManualSubmission::query()->where('status', 'pending'))
            ->columns([
                Tables\Columns\TextColumn::make('school.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('reference_number')->searchable(),
                Tables\Columns\TextColumn::make('amount')->money('USD'),
                Tables\Columns\TextColumn::make('payment_date')->date(),
                Tables\Columns\TextColumn::make('bank_name')->label(__('Source Bank')),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Approve & Credit'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(fn (SaaSManualSubmission $record) => __('Approve & Credit — ').$record->school?->name)
                    ->form(static::details())
                    ->visible(fn (SaaSManualSubmission $record) => $record->status === 'pending')
                    ->action(function (SaaSManualSubmission $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => 'approved',
                                'reviewed_by_id' => Auth::id(),
                                'reviewed_at' => now(),
                            ]);

                            $transaction = SaaSTransaction::create([
                                'school_id' => $record->school_id,
                                'saas_invoice_id' => $record->saas_invoice_id,
                                'payment_gateway_key' => 'manual_bank',
                                'transaction_reference' => $record->reference_number,
                                'amount' => $record->amount,
                                'currency' => $record->currency,
                                'status' => 'completed',
                                'processed_at' => now(),
                            ]);

                            app(SubscriptionManager::class)->processTransactionVerification($transaction);
                        });

                        Notification::make()
                            ->title(__('Payment Approved'))
                            ->body(__('The school subscription has been credited.'))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label(__('Reject Submission'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(fn (SaaSManualSubmission $record) => __('Reject Submission — ').$record->school?->name)
                    ->form([
                        ...static::details(),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label(__('Rejection Explanation Notes'))
                            ->placeholder(__('Enter explanation text context here if rejecting...'))
                            ->required(),
                    ])
                    ->visible(fn (SaaSManualSubmission $record) => $record->status === 'pending')
                    ->action(function (SaaSManualSubmission $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->update([
                                'status' => 'rejected',
                                'rejection_reason' => $data['rejection_reason'],
                                'reviewed_by_id' => Auth::id(),
                                'reviewed_at' => now(),
                            ]);
                        });

                        Notification::make()
                            ->title(__('Submission Rejected'))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    /**
     * @return array<int, Forms\Components\Placeholder>
     */
    protected static function details(): array
    {
        return [
            Forms\Components\Placeholder::make('school_name')
                ->label(__('Requesting School'))
                ->content(fn (SaaSManualSubmission $record) => $record->school?->name ?? 'N/A'),
            Forms\Components\Placeholder::make('payment_details')
                ->label(__('Stated Details'))
                ->content(fn (SaaSManualSubmission $record) => '$'.number_format($record->amount, 2).' via '.$record->bank_name.' (Ref: '.$record->reference_number.')'),
            Forms\Components\Placeholder::make('notes')
                ->label(__('Payer Notes'))
                ->content(fn (SaaSManualSubmission $record) => $record->notes ?? 'None'),
            Forms\Components\FileUpload::make('receipt_file_path')
                ->label(__('Payer Document (Receipt)'))
                ->disabled()
                ->openable()
                ->downloadable(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingPayments::route('/'),
        ];
    }
}
