<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\FeePaymentSubmissionResource\Pages;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\StudentPaymentSubmission;
use Modules\Finance\Services\StudentFeePaymentService;

class FeePaymentSubmissionResource extends Resource
{
    use ModulePermissionAccess;

    protected static ?string $model = StudentPaymentSubmission::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Payment Proofs';

    public static function getNavigationLabel(): string
    {
        return __('Payment Proofs');
    }

    public static function getModelLabel(): string
    {
        return __('Payment Proof');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Payment Proofs');
    }

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Submitted'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('student.full_name')
                    ->label(__('Student'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label(__('Invoice'))
                    ->searchable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('gateway')
                    ->label(__('Method'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'paynow' ? __('Paynow') : __('Bank Deposit'))
                    ->color(fn ($state) => $state === 'paynow' ? 'success' : 'info'),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Amount'))
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label(__('Reviewed'))
                    ->dateTime('d M Y H:i')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('viewProof')
                    ->label(__('View Proof'))
                    ->icon('heroicon-o-document-magnifying-glass')
                    ->iconButton()
                    ->color('info')
                    ->url(fn ($record) => \Storage::disk('public')->url($record->proof_file_path))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->gateway === 'manual' && $record->proof_file_path),

                Tables\Actions\Action::make('approve')
                    ->label(__('Approve'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === StudentPaymentSubmission::STATUS_PENDING)
                    ->action(function (StudentPaymentSubmission $record) {
                        if (StudentFeePaymentService::approve($record, auth()->id())) {
                            Notification::make()
                                ->title(__('Payment Approved'))
                                ->body(__('The payment has been credited to the invoice.'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Unable to Approve'))
                                ->body(__('The submission has already been processed.'))
                                ->warning()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reject')
                    ->label(__('Reject'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('reason')
                            ->label(__('Rejection Reason'))
                            ->required(),
                    ])
                    ->visible(fn ($record) => $record->status === StudentPaymentSubmission::STATUS_PENDING)
                    ->action(function (StudentPaymentSubmission $record, array $data) {
                        StudentFeePaymentService::reject($record, $data['reason'], auth()->id());

                        Notification::make()
                            ->title(__('Payment Rejected'))
                            ->danger()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeePaymentSubmissions::route('/'),
        ];
    }
}
