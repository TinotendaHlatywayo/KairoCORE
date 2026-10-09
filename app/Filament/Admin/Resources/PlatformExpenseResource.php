<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PlatformExpenseResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Models\PlatformExpense;

/**
 * KairoCORE's own operating expenses (not tenant scoped). Supports one-time
 * and recurring costs; the scheduler materialises recurring occurrences.
 */
class PlatformExpenseResource extends Resource
{
    protected static ?string $model = PlatformExpense::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'KairoCORE Expenses';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Expense Details'))
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label(__('Expense'))
                        ->required()
                        ->maxLength(255)
                        ->placeholder(__('e.g., VM hosting, SMTP relay, domain renewal')),
                    Forms\Components\TextInput::make('category')
                        ->label(__('Category'))
                        ->maxLength(100)
                        ->datalist(PlatformExpense::SUGGESTED_CATEGORIES)
                        ->helperText(__('Pick a suggestion or type your own.')),
                    Forms\Components\TextInput::make('amount')
                        ->numeric()
                        ->prefix('$')
                        ->required(),
                    Forms\Components\TextInput::make('currency')
                        ->default('USD')
                        ->maxLength(3)
                        ->required(),
                    Forms\Components\DatePicker::make('expense_date')
                        ->label(__('Expense date'))
                        ->default(now())
                        ->required(),
                    Forms\Components\Select::make('payment_method')
                        ->label(__('Payment method'))
                        ->options([
                            'bank_transfer' => __('Bank Transfer'),
                            'card' => __('Card'),
                            'cash' => __('Cash'),
                            'mobile_money' => __('Mobile Money'),
                            'paynow' => __('Paynow'),
                            'paypal' => __('PayPal'),
                            'other' => __('Other'),
                        ])
                        ->native(false),
                    Forms\Components\TextInput::make('vendor')
                        ->label(__('Vendor / Payee'))
                        ->maxLength(255),
                    Forms\Components\TextInput::make('reference')
                        ->label(__('Reference / Receipt #'))
                        ->maxLength(100),
                    Forms\Components\Textarea::make('description')
                        ->label(__('Notes'))
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make(__('Recurrence'))
                ->description(__('Recurring expenses are templates: a scheduler generates each due occurrence automatically.'))
                ->schema([
                    Forms\Components\Toggle::make('is_recurring')
                        ->label(__('This is a recurring expense'))
                        ->live()
                        ->default(false),
                    Forms\Components\Select::make('recurrence')
                        ->label(__('Repeats'))
                        ->options(PlatformExpense::RECURRENCES)
                        ->native(false)
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->required(fn (Get $get): bool => (bool) $get('is_recurring')),
                    Forms\Components\DatePicker::make('next_due_date')
                        ->label(__('Next occurrence'))
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->helperText(__('Defaults to one interval after the expense date.')),
                    Forms\Components\DatePicker::make('recurrence_ends_at')
                        ->label(__('Stop recurring after'))
                        ->visible(fn (Get $get): bool => (bool) $get('is_recurring'))
                        ->helperText(__('Optional. No occurrences are generated beyond this date.')),
                ])->columns(2),

            Forms\Components\Section::make(__('Status'))
                ->schema([
                    Forms\Components\Toggle::make('is_paid')
                        ->label(__('Paid'))
                        ->live()
                        ->default(false),
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label(__('Paid at'))
                        ->visible(fn (Get $get): bool => (bool) $get('is_paid')),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('expense_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('expense_date')->label(__('Date'))->date()->sortable(),
                Tables\Columns\TextColumn::make('title')->label(__('Expense'))->searchable()->sortable()->weight('bold')
                    ->description(fn (PlatformExpense $record): ?string => $record->vendor),
                Tables\Columns\TextColumn::make('category')->badge()->placeholder(__('—'))->toggleable(),
                Tables\Columns\TextColumn::make('amount')->money('USD')->sortable(),
                Tables\Columns\IconColumn::make('is_recurring')->label(__('Recurring'))->boolean()->toggleable(),
                Tables\Columns\TextColumn::make('recurrence')
                    ->label(__('Repeats'))
                    ->formatStateUsing(fn (?string $state, PlatformExpense $record): string => $record->recurrenceLabel())
                    ->placeholder(__('One-time'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('next_due_date')
                    ->label(__('Next due'))
                    ->date()
                    ->placeholder(__('—'))
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_paid')->label(__('Paid'))->boolean()->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')->label(__('Paid')),
                Tables\Filters\TernaryFilter::make('is_recurring')->label(__('Recurring')),
                Tables\Filters\SelectFilter::make('category')
                    ->label(__('Category'))
                    ->options(fn (): array => PlatformExpense::query()
                        ->whereNotNull('category')
                        ->distinct()
                        ->orderBy('category')
                        ->pluck('category', 'category')
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('toggle_paid')
                    ->label(fn (PlatformExpense $record): string => $record->is_paid ? __('Mark unpaid') : __('Mark paid'))
                    ->icon(fn (PlatformExpense $record): string => $record->is_paid ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (PlatformExpense $record): string => $record->is_paid ? 'gray' : 'success')
                    ->action(function (PlatformExpense $record): void {
                        $record->update([
                            'is_paid' => ! $record->is_paid,
                            'paid_at' => $record->is_paid ? null : now(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlatformExpenses::route('/'),
            'create' => Pages\CreatePlatformExpense::route('/create'),
            'edit' => Pages\EditPlatformExpense::route('/{record}/edit'),
        ];
    }
}
