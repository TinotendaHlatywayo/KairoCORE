<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\SchoolBankAccountResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Finance\Models\SchoolBankAccount;

class SchoolBankAccountResource extends Resource
{
    use ModulePermissionAccess;

    protected static ?string $model = SchoolBankAccount::class;

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'School Bank Accounts';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bank Account Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->required()
                            ->placeholder(__('e.g. Steward Bank'))
                            ->datalist([
                                'Steward Bank',
                                'CBZ Bank',
                                'CABS',
                                'FBC Bank',
                                'NMB Bank',
                                'Stanbic Bank',
                                'Standard Chartered Bank',
                                'Ecobank',
                                'First Capital Bank',
                                'Fidelity Bank',
                                'ZB Bank',
                                'BancABC',
                            ]),
                        Forms\Components\TextInput::make('account_name')
                            ->required()
                            ->placeholder(__('e.g. Kairo CORE Technologies Pvt Ltd')),
                        Forms\Components\TextInput::make('account_number')
                            ->required()
                            ->placeholder(__('e.g. 1002345678')),
                        Forms\Components\TextInput::make('branch_code')
                            ->placeholder(__('e.g. 2105')),
                        Forms\Components\TextInput::make('swift_code')
                            ->placeholder(__('e.g. SBICZWHAXXX')),
                        Forms\Components\Toggle::make('is_default')
                            ->label(__('Set as default bank account'))
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bank_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('branch_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label(__('Default')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label(__('Active')),
            ])
            ->defaultSort('is_default', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
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
            'index' => Pages\ListSchoolBankAccounts::route('/'),
            'create' => Pages\CreateSchoolBankAccount::route('/create'),
            'edit' => Pages\EditSchoolBankAccount::route('/{record}/edit'),
        ];
    }
}
