<?php

namespace App\Filament\Admin\Resources\SchoolResource\RelationManagers;

use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'School Users / Administrators';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->label(fn (string $context): string => $context === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('school_id')
                    ->label(__('Institution ID'))
                    ->sortable()
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('requested_role')
                    ->label(__('Role'))
                    ->formatStateUsing(fn (?string $state) => $state ? User::REGISTRATION_ROLES[$state] ?? ucwords(str_replace('_', ' ', $state)) : '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('Registered On')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Add New User'))
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['password'] = Hash::make($data['password']);
                        $data['school_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Edit / Reset PW'))
                    ->mutateFormDataUsing(function (array $data): array {
                        if (filled($data['password'] ?? null)) {
                            $data['password'] = Hash::make($data['password']);
                        } else {
                            unset($data['password']);
                        }
                        $data['school_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
