<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TenantHealthResource\Pages;
use App\Models\School;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Recovery\Services\TelemetryService;

class TenantHealthResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Tenants');
    }

    protected static ?string $model = School::class;

    protected static ?string $navigationGroup = 'Tenants';

    protected static ?string $navigationLabel = 'Tenant Diagnostics';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'tenant-healths';

    public static function canAccess(): bool
    {
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('School Entity'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subdomain')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('health_rating')
                    ->label(__('Platform Health Score'))
                    ->state(function (School $record) {
                        $report = app(TelemetryService::class)->generateHealthReport($record->id);

                        return $report['score'].'% ('.$report['status'].')';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Excellent') => 'success',
                        str_contains($state, 'Good') => 'info',
                        str_contains($state, 'Needs Attention') => 'warning',
                        default => 'danger',
                    }),
                Tables\Columns\TextColumn::make('recommendations')
                    ->label(__('Urgent Diagnostics'))
                    ->state(function (School $record) {
                        $report = app(TelemetryService::class)->generateHealthReport($record->id);

                        return ! empty($report['recommendations'])
                            ? implode(' • ', $report['recommendations'])
                            : 'No actions required.';
                    })
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('inspect_subdomain')
                    ->label(__('Visit Tenant Site'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (School $record) => "http://{$record->subdomain}.".parse_url(config('app.url'), PHP_URL_HOST).'/workspace')
                    ->openUrlInNewTab(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenantHealths::route('/'),
        ];
    }
}
