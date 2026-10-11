<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PendingPaymentResource\Pages;
use App\Mail\SaaS\SaaSCustomReceiptMail;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSReceipt;
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
                    ->form(static::approvalForm())
                    ->visible(fn (SaaSManualSubmission $record) => $record->status === 'pending')
                    ->action(function (SaaSManualSubmission $record, array $data) {
                        $amount = (float) $data['amount'];
                        $receiptMode = $data['receipt_mode'] ?? 'system';
                        $customPath = static::firstUploadPath($data['custom_receipt'] ?? null);

                        $transaction = DB::transaction(function () use ($record, $amount, $receiptMode) {
                            $record->update([
                                'status' => 'approved',
                                'amount' => $amount,
                                'reviewed_by_id' => Auth::id(),
                                'reviewed_at' => now(),
                            ]);

                            $transaction = SaaSTransaction::create([
                                'school_id' => $record->school_id,
                                'saas_invoice_id' => $record->saas_invoice_id,
                                'payment_gateway_key' => 'manual_bank',
                                'transaction_reference' => $record->reference_number,
                                'amount' => $amount,
                                'currency' => $record->currency,
                                'status' => 'completed',
                                'processed_at' => now(),
                            ]);

                            // Suppress the system PDF email when a custom receipt
                            // will be emailed instead.
                            app(SubscriptionManager::class)->processTransactionVerification(
                                $transaction,
                                $receiptMode !== 'custom'
                            );

                            return $transaction;
                        });

                        if ($receiptMode === 'custom' && $customPath) {
                            static::sendCustomReceipt($record, $transaction, $customPath);
                        }

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

    /**
     * Approval form: review the submission, set the amount to credit and pick
     * between the generated system receipt and an uploaded custom receipt.
     *
     * @return array<int, Forms\Components\Component>
     */
    protected static function approvalForm(): array
    {
        return [
            ...static::details(),
            Forms\Components\TextInput::make('amount')
                ->label(__('Credit Amount'))
                ->helperText(__('Defaults to the amount the school stated. Adjust it if the actual deposit differs.'))
                ->numeric()
                ->minValue(0.01)
                ->required()
                ->default(fn (SaaSManualSubmission $record) => $record->amount),
            Forms\Components\Radio::make('receipt_mode')
                ->label(__('Receipt'))
                ->options([
                    'system' => __('Generate the system receipt (default)'),
                    'custom' => __('Upload a custom receipt'),
                ])
                ->default('system')
                ->required()
                ->live(),
            Forms\Components\FileUpload::make('custom_receipt')
                ->label(__('Custom Receipt File'))
                ->helperText(__('Emailed to the school instead of the generated PDF receipt.'))
                ->disk('public')
                ->directory('saas/manual-receipts')
                ->acceptedFileTypes(['application/pdf', 'image/png', 'image/jpeg'])
                ->maxSize(10240)
                ->visible(fn (callable $get) => $get('receipt_mode') === 'custom')
                ->required(fn (callable $get) => $get('receipt_mode') === 'custom'),
        ];
    }

    /**
     * FileUpload state can arrive as a string path or an array depending on the
     * Filament version, so normalise it to the first stored path.
     */
    protected static function firstUploadPath(mixed $value): ?string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Email the school the uploaded custom receipt for an approved payment.
     */
    protected static function sendCustomReceipt(SaaSManualSubmission $record, SaaSTransaction $transaction, string $path): void
    {
        try {
            $receipt = SaaSReceipt::where('saas_transaction_id', $transaction->id)->first();

            if (! $receipt) {
                return;
            }

            $recipientEmail = $record->school?->email_address
                ?? 'admin@'.$record->school?->subdomain.'.schoolcore.test';

            Mail::to($recipientEmail)->send(new SaaSCustomReceiptMail(
                $receipt,
                'public',
                $path,
                basename($path),
            ));
        } catch (\Throwable $e) {
            Log::warning('Failed to email custom manual receipt: '.$e->getMessage());
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPendingPayments::route('/'),
        ];
    }
}
