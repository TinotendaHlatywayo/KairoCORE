<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\EnterpriseReportTemplateResource\Pages;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Reports\Models\EnterpriseReportTemplate;
use Modules\Reports\Services\ReportExecutionService;

class EnterpriseReportTemplateResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Reports & Intelligence');
    }

    protected static ?string $model = EnterpriseReportTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Reports & Intelligence';

    protected static ?string $navigationLabel = 'Saved Templates';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Template Reference'))
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('module')
                    ->label(__('Domain Module'))
                    ->badge()
                    ->color('info'),

                TextColumn::make('orientation')
                    ->label(__('Alignment'))
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                // Live toggle switches directly inside the grid view
                ToggleColumn::make('is_pinned')
                    ->label(__('Pinned')),

                ToggleColumn::make('is_favorite')
                    ->label(__('Favorite')),

                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->placeholder(__('System Admin')),

                TextColumn::make('created_at')
                    ->label(__('Date Saved'))
                    ->date('M d, Y'),
            ])
            ->actions([
                Action::make('generate_pdf')
                    ->label(__('Compile PDF'))
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (EnterpriseReportTemplate $record) {
                        $report = app(ReportExecutionService::class)->execute($record, 'pdf', [], Auth::id());

                        if ($report->status === 'completed') {
                            Notification::make()
                                ->title(__('Compilation Successful'))
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title(__('Compilation Failed'))
                                ->danger()
                                ->body($report->error_message)
                                ->send();
                        }
                    }),

                DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnterpriseReportTemplates::route('/'),
        ];
    }
}
