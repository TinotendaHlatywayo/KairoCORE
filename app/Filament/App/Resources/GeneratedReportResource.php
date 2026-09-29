<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\GeneratedReportResource\Pages;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Reports\Models\GeneratedReport;
use Modules\Reports\Services\ReportAuditService;

class GeneratedReportResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Reports & Intelligence');
    }

    protected static ?string $model = GeneratedReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    protected static ?string $navigationLabel = 'Report Archive';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 3;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Compiled by and timestamp ride along as a sub-line rather than
                // three separate columns. Both are constant-width metadata that
                // never needs its own header, and folding them into this cell is
                // what keeps the table inside the viewport on a laptop screen.
                TextColumn::make('name')
                    ->label(__('Report'))
                    ->description(fn (GeneratedReport $record) => collect([
                        $record->generator?->name ?: __('System Account'),
                        $record->created_at?->format('M d, Y H:i'),
                    ])->implode(' · '))
                    ->searchable(['name'])
                    ->sortable()
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('format')
                    ->label(__('Format'))
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => $state === 'pdf' ? 'danger' : 'success'),

                TextColumn::make('record_count')
                    ->label(__('Records'))
                    ->numeric()
                    ->sortable(),

                // "Source data has changed" is the important half of this badge,
                // so the long phrasing moves to the tooltip rather than pushing
                // the column wide.
                TextColumn::make('data_validated')
                    ->label(__('Accuracy'))
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn (GeneratedReport $record) => match (true) {
                        $record->validated_at === null => 'Not verified',
                        $record->data_validated => 'Verified',
                        default => 'Source changed',
                    })
                    ->color(fn (GeneratedReport $record) => match (true) {
                        $record->validated_at === null => 'gray',
                        $record->data_validated => 'success',
                        default => 'warning',
                    })
                    ->tooltip(fn (GeneratedReport $record) => match (true) {
                        $record->validated_at === null => 'Re-run to confirm this report still matches live source data',
                        $record->data_validated => 'Verified '.$record->validated_at->format('M d, Y H:i').' — still matches live source data',
                        default => 'Source data changed after compilation on '.$record->validated_at->format('M d, Y H:i').' — regenerate to refresh',
                    }),

                // A millisecond timing is a diagnostic, not a decision input, so
                // it is one toggle away rather than always on screen.
                TextColumn::make('execution_ms')
                    ->label(__('Execution'))
                    ->formatStateUsing(fn ($state) => $state === null ? '—' : number_format((int) $state).' ms')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('Download'))
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->tooltip(__('Download this file'))
                    ->color('primary')
                    ->iconButton()
                    ->visible(fn (GeneratedReport $record) => $record->status === 'completed' && ! empty($record->file_path))
                    ->url(fn (GeneratedReport $record) => asset('storage/'.$record->file_path))
                    ->openUrlInNewTab(),

                Action::make('verify')
                    ->label(__('Verify data accuracy'))
                    ->icon('heroicon-o-shield-check')
                    ->tooltip(fn (GeneratedReport $record) => $record->validated_at ? __('Re-verify data') : __('Verify data accuracy'))
                    ->color('info')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading(__('Verify data accuracy'))
                    ->modalDescription(__('Re-runs the underlying query against the current database and compares the compiled checksum. Flags the report if source data changed after it was generated.'))
                    ->visible(fn (GeneratedReport $record) => $record->status === 'completed' && $record->data_checksum !== null)
                    ->action(function (GeneratedReport $record) {
                        $valid = app(ReportAuditService::class)->verify($record);

                        Notification::make()
                            ->{$valid ? 'success' : 'warning'}()
                            ->title($valid ? __('Data verified') : __('Source data has changed'))
                            ->body($valid
                                ? __('The report still matches the current database.')
                                : __('This report was compiled from older data. Regenerate it to reflect the current database.'))
                            ->send();
                    }),

                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGeneratedReports::route('/'),
        ];
    }
}
