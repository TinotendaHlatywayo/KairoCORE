<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\JournalEntryResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\JournalEntry;

class JournalEntryResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Finance');
    }

    protected static ?string $model = JournalEntry::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Journal Entries';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('General Ledger Journal Entry')
                    ->description(__('Record double-entry journal transactions. Total debits must equal total credits.'))
                    ->schema([
                        Forms\Components\DatePicker::make('entry_date')
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('reference_number')
                            ->label(__('Reference / Voucher #'))
                            ->maxLength(100),
                        Forms\Components\Textarea::make('narration')
                            ->required()
                            ->columnSpanFull()
                            ->placeholder(__('Explanation of the financial transaction...')),
                        Forms\Components\Select::make('status')
                            ->options([
                                'posted' => __('Posted'),
                                'draft' => __('Draft'),
                                'void' => __('Void'),
                            ])
                            ->default('posted')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Journal Line Items (Debits & Credits)')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->relationship('lineItems')
                            ->schema([
                                Forms\Components\Select::make('account_id')
                                    ->label(__('Account'))
                                    ->options(Account::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->columnSpan(4),
                                Forms\Components\TextInput::make('debit')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('credit')
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0)
                                    ->required()
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('memo')
                                    ->placeholder(__('Line memo'))
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->defaultItems(2)
                            ->required()
                            ->minItems(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('entry_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('reference_number')->label(__('Ref #'))->searchable(),
                Tables\Columns\TextColumn::make('narration')->limit(60)->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'posted',
                        'warning' => 'draft',
                        'danger' => 'void',
                    ]),
                Tables\Columns\TextColumn::make('user.name')->label(__('Accountant'))->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'posted' => __('Posted'),
                        'draft' => __('Draft'),
                        'void' => __('Void'),
                    ]),
            ])
            ->actions([
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
            'index' => Pages\ListJournalEntries::route('/'),
            'create' => Pages\CreateJournalEntry::route('/create'),
            'edit' => Pages\EditJournalEntry::route('/{record}/edit'),
        ];
    }
}
