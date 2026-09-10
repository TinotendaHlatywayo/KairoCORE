<?php

namespace App\Filament\App\Resources\ScreeningRunResource\Pages;

use App\Filament\App\Resources\ScreeningRunResource;
use App\Services\Screening\ScreeningService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Academics\Models\AcademicYear;
use Modules\Promotion\Models\PromotionRun;

class ListScreeningRuns extends ListRecords
{
    protected static string $resource = ScreeningRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewNewRun')
                ->label(__('Preview New Screening'))
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->form([
                    Forms\Components\Select::make('source_academic_year_id')
                        ->label(__('Source Academic Year'))
                        ->options(AcademicYear::withoutGlobalScopes()->where('school_id', current_tenant()?->id)->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\Select::make('target_academic_year_id')
                        ->label(__('Target Academic Year'))
                        ->options(AcademicYear::withoutGlobalScopes()->where('school_id', current_tenant()?->id)->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\Select::make('promotion_run_id')
                        ->label(__('Linked Promotion Run (Optional)'))
                        ->options(PromotionRun::withoutGlobalScopes()->where('school_id', current_tenant()?->id)->pluck('id', 'id')
                            ->map(fn($id) => "Run #{$id}"))
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    $run = app(ScreeningService::class)->preview(
                        current_tenant()?->id ?? auth()->user()?->school_id,
                        (int) $data['source_academic_year_id'],
                        (int) $data['target_academic_year_id'],
                        isset($data['promotion_run_id']) ? (int) $data['promotion_run_id'] : null,
                        auth()->id(),
                    );

                    Notification::make()
                        ->title(__('Screening preview created'))
                        ->body(__('Screening run #:run created — review placements below.', ['run' => $run->id]))
                        ->success()
                        ->send();

                    redirect(ScreeningRunResource::getUrl('view', ['record' => $run]));
                }),
        ];
    }
}
