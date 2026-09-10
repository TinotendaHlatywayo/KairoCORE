<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ScreeningRunResource\Pages;
use App\Filament\App\Resources\ScreeningRunResource\RelationManagers;
use App\Services\ModuleVisibilityManager;
use App\Services\Screening\ScreeningService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Modules\Screening\Models\ScreeningRun;

class ScreeningRunResource extends Resource
{
    protected static ?string $model = ScreeningRun::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isPageVisible('academics', 'screening');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Academics');
    }

    public static function getNavigationLabel(): string
    {
        return __('Screening Runs');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Screening Runs');
    }

    public static function getModelLabel(): string
    {
        return __('Screening Run');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('Run Overview'))
                    ->schema([
                        Infolists\Components\TextEntry::make('sourceAcademicYear.name')
                            ->label(__('Source Year')),
                        Infolists\Components\TextEntry::make('targetAcademicYear.name')
                            ->label(__('Target Year')),
                        Infolists\Components\TextEntry::make('promotionRun.id')
                            ->label(__('Linked Promotion Run'))
                            ->formatStateUsing(fn (string $state): string => '#'.$state)
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('status')
                            ->label(__('Status'))
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                ScreeningRun::STATUS_DRAFT => 'gray',
                                ScreeningRun::STATUS_IN_PROGRESS => 'warning',
                                ScreeningRun::STATUS_COMMITTED => 'success',
                                ScreeningRun::STATUS_ARCHIVED => 'danger',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('createdBy.name')
                            ->label(__('Created By'))
                            ->placeholder('-'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('Created At'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('committed_at')
                            ->label(__('Committed At'))
                            ->dateTime()
                            ->placeholder('-'),
                    ])->columns(3),

                Infolists\Components\Section::make(__('Preview Summary'))
                    ->schema([
                        Infolists\Components\TextEntry::make('preview_summary')
                            ->label(__('Decisions'))
                            ->state(fn (ScreeningRun $record): string => sprintf(
                                'Placed: %d  ·  Unplaced: %d  ·  Total: %d',
                                $record->previewSummary()['placed'],
                                $record->previewSummary()['unplaced'],
                                $record->previewSummary()['total'],
                            )),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sourceAcademicYear.name')
                    ->label(__('Source Year'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('targetAcademicYear.name')
                    ->label(__('Target Year'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('promotionRun.id')
                    ->label(__('Promotion Run'))
                    ->formatStateUsing(fn (?string $state): ?string => $state === null ? null : '#'.$state)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ScreeningRun::STATUS_DRAFT => 'gray',
                        ScreeningRun::STATUS_IN_PROGRESS => 'warning',
                        ScreeningRun::STATUS_COMMITTED => 'success',
                        ScreeningRun::STATUS_ARCHIVED => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('placed_count')
                    ->label(__('Placed'))
                    ->state(fn (ScreeningRun $record): int => $record->previewSummary()['placed'])
                    ->alignCenter()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('unplaced_count')
                    ->label(__('Unplaced'))
                    ->state(fn (ScreeningRun $record): int => $record->previewSummary()['unplaced'])
                    ->alignCenter()
                    ->sortable(false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('committed_at')
                    ->label(__('Committed'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('commit')
                    ->label(__('Commit'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === ScreeningRun::STATUS_DRAFT)
                    ->requiresConfirmation()
                    ->action(function (ScreeningRun $record): void {
                        app(ScreeningService::class)->commit($record->id, auth()->id());

                        \Filament\Notifications\Notification::make()
                            ->title(__('Screening run committed'))
                            ->body(__('Placed students were enrolled into their matched streams.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScreeningRuns::route('/'),
            'view' => Pages\ViewScreeningRun::route('/{record}'),
        ];
    }
}