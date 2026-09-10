<?php

namespace App\Filament\App\Resources\PromotionRunResource\Pages;

use App\Filament\App\Resources\PromotionRunResource;
use App\Services\Promotion\PromotionService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Academics\Models\AcademicYear;

class ListPromotionRuns extends ListRecords
{
    protected static string $resource = PromotionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('previewNewRun')
                ->label(__('Preview New Promotion'))
                ->icon('heroicon-o-eye')
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
                ])
                ->action(function (array $data): void {
                    $run = app(PromotionService::class)->preview(
                        current_tenant()?->id ?? auth()->user()?->school_id,
                        (int) $data['source_academic_year_id'],
                        (int) $data['target_academic_year_id'],
                        auth()->id(),
                    );

                    Notification::make()
                        ->title(__('Promotion preview created'))
                        ->body(__('Run #:run reviewed below — commit when ready.', ['run' => $run->id]))
                        ->success()
                        ->send();

                    redirect(PromotionRunResource::getUrl('view', ['record' => $run]));
                }),
        ];
    }
}