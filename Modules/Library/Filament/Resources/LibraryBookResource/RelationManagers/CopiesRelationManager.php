<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Resources\LibraryBookResource\RelationManagers;

use App\Models\School;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CopiesRelationManager extends RelationManager
{
    protected static string $relationship = 'copies';

    protected static ?string $title = 'Registered Physical Copies / Serials';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('barcode')
                    ->label(__('Custom Copy Barcode'))
                    ->required()
                    ->maxLength(255)
                    ->unique(
                        table: 'library_book_copies',
                        column: 'barcode',
                        ignoreRecord: true, // Corrected parameter name
                        modifyRuleUsing: function ($rule) {
                            $tenant = app('current_tenant');
                            $tenantId = $tenant instanceof School ? $tenant->id : null;

                            return $rule->where('school_id', $tenantId);
                        }
                    ),

                Forms\Components\TextInput::make('qr_code')
                    ->label(__('Custom Copy QR Code'))
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('condition')
                    ->options([
                        'excellent' => __('Excellent'),
                        'good' => __('Good'),
                        'fair' => __('Fair'),
                        'poor' => __('Poor'),
                        'damaged' => __('Damaged / Broken'),
                    ])
                    ->required(),

                Forms\Components\Select::make('status')
                    ->options([
                        'available' => __('Available for Borrowing'),
                        'issued' => __('Currently Checked Out'),
                        'maintenance' => __('Out for Maintenance'),
                        'lost' => __('Lost / Missing'),
                        'written_off' => __('Written Off / Disposed'),
                    ])
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('barcode')
            ->columns([
                Tables\Columns\TextColumn::make('barcode')
                    ->label(__('Barcode'))
                    ->searchable(),

                Tables\Columns\TextColumn::make('condition')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'excellent' => 'success',
                        'good' => 'success',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        'damaged' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'issued' => 'info',
                        'lost' => 'danger',
                        'written_off' => 'gray',
                        default => 'warning',
                    }),

                Tables\Columns\TextColumn::make('shelf')
                    ->label(__('Location'))
                    ->placeholder(__('Not Assigned')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $tenant = app('current_tenant');
                        $data['school_id'] = $tenant instanceof School ? $tenant->id : null;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
