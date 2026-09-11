<?php

namespace App\Filament\App\Resources\ScreeningRunResource\Pages;

use App\Filament\App\Resources\ScreeningRunResource;
use App\Services\Screening\ScreeningService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Subject;
use Modules\Academics\Models\Term;
use Modules\Promotion\Models\PromotionRun;

class ListScreeningRuns extends ListRecords
{
    protected static string $resource = ScreeningRunResource::class;

    protected function getHeaderActions(): array
    {
        $schoolId = current_tenant()?->id ?? auth()->user()?->school_id;

        return [
            Actions\Action::make('previewNewRun')
                ->label(__('Preview New Screening'))
                ->icon('heroicon-o-funnel')
                ->color('primary')
                ->form([
                    Forms\Components\Section::make(__('Run Setup'))
                        ->schema([
                            Forms\Components\Select::make('source_academic_year_id')
                                ->label(__('Source Academic Year'))
                                ->options(AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('name', 'id'))
                                ->required(),

                            Forms\Components\Select::make('target_academic_year_id')
                                ->label(__('Target Academic Year'))
                                ->options(AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('name', 'id'))
                                ->required(),

                            Forms\Components\Select::make('promotion_run_id')
                                ->label(__('Linked Promotion Run (Optional)'))
                                ->options(PromotionRun::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('id', 'id')
                                    ->map(fn ($id) => "Run #{$id}"))
                                ->nullable(),
                        ])->columns(3),

                    Forms\Components\Section::make(__('Screening Criteria'))
                        ->description(__('Define how student scores are calculated before screening begins.'))
                        ->schema([
                            Forms\Components\Radio::make('score_basis')
                                ->label(__('Score Basis'))
                                ->options([
                                    'overall' => __('Overall average across all subjects'),
                                    'subjects' => __('Only the selected subject(s)'),
                                ])
                                ->default('overall')
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('subject_ids')
                                ->label(__('Subject(s) to screen on'))
                                ->helperText(__('Scoring is based on the average of these subject marks.'))
                                ->multiple()
                                ->searchable()
                                ->options(Subject::where('school_id', $schoolId)->pluck('name', 'id'))
                                ->required(fn (Forms\Get $get) => $get('score_basis') === 'subjects')
                                ->visible(fn (Forms\Get $get) => $get('score_basis') === 'subjects')
                                ->columnSpanFull(),

                            Forms\Components\Radio::make('academic_year_mode')
                                ->label(__('Which academic years should the marks come from?'))
                                ->options([
                                    'current' => __('Current academic year'),
                                    'selected' => __('I want to choose the academic years'),
                                ])
                                ->default('current')
                                ->live()
                                ->columnSpanFull(),

                            Forms\Components\Select::make('academic_year_ids')
                                ->label(__('Academic Year(s)'))
                                ->multiple()
                                ->searchable()
                                ->options(AcademicYear::withoutGlobalScopes()->where('school_id', $schoolId)->pluck('name', 'id'))
                                ->required(fn (Forms\Get $get) => $get('academic_year_mode') === 'selected')
                                ->visible(fn (Forms\Get $get) => $get('academic_year_mode') === 'selected'),

                            Forms\Components\Select::make('term_ids')
                                ->label(__('Term(s) (leave empty for all terms)'))
                                ->multiple()
                                ->searchable()
                                ->options(
                                    fn () => Term::where('school_id', $schoolId)
                                        ->with('academicYear')
                                        ->get()
                                        ->groupBy(fn (Term $term) => $term->academicYear?->name ?? __('Academic Year'))
                                        ->map(fn ($terms) => $terms->pluck('name', 'id'))
                                        ->toArray()
                                )
                                ->columnSpan(2),
                        ])->columns(2),
                ])
                ->action(function (array $data): void {
                    $run = app(ScreeningService::class)->preview(
                        current_tenant()?->id ?? auth()->user()?->school_id,
                        (int) $data['source_academic_year_id'],
                        (int) $data['target_academic_year_id'],
                        isset($data['promotion_run_id']) ? (int) $data['promotion_run_id'] : null,
                        auth()->id(),
                        $data,
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
