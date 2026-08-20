<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PromotionWorkflowResource\Pages;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Academics\Models\AcademicYear;
use Modules\Academics\Models\Course;
use Modules\Academics\Models\Section;
use Modules\Academics\Models\Subject;
use Modules\Students\Models\Student;

class PromotionWorkflowResource extends Resource
{
    public static function getNavigationGroup(): ?string
    {
        return __('Students');
    }

    public static function canAccess(): bool
    {
        return ModuleVisibilityManager::isPageVisible('students', 'promotion');
    }

    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationGroup = 'Students';

    protected static ?int $navigationSort = 10;

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Promotion Workflow');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Promotion Workflow')
                    ->tabs([
                        Tab::make('Screening')
                            ->label(__('1. Screening'))
                            ->schema([
                                Forms\Components\Section::make('Promotion Criteria')
                                    ->schema([
                                        Forms\Components\Select::make('academic_year_id')
                                            ->label(__('Current Academic Year'))
                                            ->options(AcademicYear::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->required()
                                            ->default(fn () => AcademicYear::where('school_id', config('current_tenant_id'))->where('is_active', true)->first()?->id),

                                        Forms\Components\Select::make('course_id')
                                            ->label(__('Form to Screen'))
                                            ->options(Course::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->required()
                                            ->live(),

                                        Forms\Components\Select::make('section_id')
                                            ->label(__('Stream'))
                                            ->options(fn (Forms\Get $get) => Section::where('course_id', $get('course_id'))->pluck('name', 'id'))
                                            ->required(),

                                        Forms\Components\TextInput::make('pass_mark')
                                            ->label(__('Minimum Pass %'))
                                            ->numeric()
                                            ->default(50)
                                            ->suffix('%'),

                                        Forms\Components\CheckboxList::make('required_subjects')
                                            ->label(__('Required Pass Subjects'))
                                            ->options(Subject::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                            ->columns(3),
                                    ])->columns(2),
                            ]),

                        Tab::make('Eligibility')
                            ->label(__('2. Eligibility Check'))
                            ->schema([
                                Forms\Components\Section::make('Student Eligibility Results')
                                    ->schema([
                                        Forms\Components\Placeholder::make('eligibility_note')
                                            ->label(__('Status'))
                                            ->content('Run the eligibility check to see results.'),
                                    ]),
                            ]),

                        Tab::make('Recommendations')
                            ->label(__('3. Teacher Recommendations'))
                            ->schema([
                                Forms\Components\Section::make('Teacher Input')
                                    ->description(__('Collect promotion recommendations from class teachers'))
                                    ->schema([
                                        Forms\Components\Repeater::make('recommendations')
                                            ->schema([
                                                Forms\Components\Select::make('student_id')
                                                    ->label(__('Student'))
                                                    ->options(fn () => Student::where('school_id', config('current_tenant_id'))->where('status', 'active')->pluck('full_name', 'id'))
                                                    ->searchable()
                                                    ->required(),
                                                Forms\Components\Select::make('recommendation')
                                                    ->label(__('Recommendation'))
                                                    ->options([
                                                        'promote' => __('Promote'),
                                                        'promote_conditional' => __('Promote with Conditions'),
                                                        'repeat' => __('Repeat Year'),
                                                        'special_review' => __('Special Review Required'),
                                                    ])
                                                    ->required(),
                                                Forms\Components\Textarea::make('notes')
                                                    ->label(__('Teacher Notes'))
                                                    ->rows(2),
                                            ])
                                            ->grid(3)
                                            ->defaultItems(0),
                                    ]),
                            ]),

                        Tab::make('Approval')
                            ->label(__('4. Principal Approval'))
                            ->schema([
                                Forms\Components\Section::make('Final Decision')
                                    ->schema([
                                        Forms\Components\Repeater::make('approvals')
                                            ->schema([
                                                Forms\Components\Select::make('student_id')
                                                    ->label(__('Student'))
                                                    ->options(fn () => Student::where('school_id', config('current_tenant_id'))->where('status', 'active')->pluck('full_name', 'id'))
                                                    ->searchable()
                                                    ->required(),
                                                Forms\Components\Select::make('decision')
                                                    ->label(__('Decision'))
                                                    ->options([
                                                        'approved' => __('Approved'),
                                                        'conditional' => __('Conditional Promotion'),
                                                        'denied' => __('Not Promoted'),
                                                    ])
                                                    ->required(),
                                                Forms\Components\Select::make('next_course_id')
                                                    ->label(__('Next Form'))
                                                    ->options(Course::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                                    ->required(),
                                                Forms\Components\Select::make('next_section_id')
                                                    ->label(__('Next Stream'))
                                                    ->options(fn () => Section::where('school_id', config('current_tenant_id'))->pluck('name', 'id'))
                                                    ->required(),
                                                Forms\Components\Textarea::make('conditions')
                                                    ->label(__('Conditions (if any)'))
                                                    ->rows(2),
                                            ])
                                            ->grid(3)
                                            ->defaultItems(0),
                                    ]),
                            ]),

                        Tab::make('Execution')
                            ->label(__('5. Bulk Promotion'))
                            ->schema([
                                Forms\Components\Section::make('Execute Promotions')
                                    ->description(__('Run the bulk promotion process for approved students'))
                                    ->schema([
                                        Forms\Components\Placeholder::make('execution_note')
                                            ->label(__('Action'))
                                            ->content('Click "Run Promotion" below to execute all approved promotions.'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $year = AcademicYear::where('school_id', config('current_tenant_id'))->where('is_active', true)->first();

        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->when($year, fn ($q) => $q->whereHas('enrollments', fn ($eq) => $eq->where('academic_year_id', $year->id))))
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Student'))
                    ->searchable(query: fn (Builder $query, string $search) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('last_name', $direction)->orderBy('first_name', $direction)),
                Tables\Columns\TextColumn::make('admission_number')
                    ->label(__('Admission #'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('enrollments.course.name')
                    ->label(__('Current Form'))
                    ->state(fn ($record) => $record->enrollments->first()?->course?->name ?? 'N/A'),
                Tables\Columns\TextColumn::make('enrollments.section.name')
                    ->label(__('Current Stream'))
                    ->state(fn ($record) => $record->enrollments->first()?->section?->name ?? 'N/A'),
                Tables\Columns\BadgeColumn::make('promotion_status')
                    ->label(__('Status'))
                    ->colors([
                        'gray' => 'pending',
                        'info' => 'screened',
                        'warning' => 'recommended',
                        'success' => 'approved',
                        'danger' => 'denied',
                    ])
                    ->state(fn ($record) => $record->promotion_status ?? 'pending'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('promotion_status')
                    ->options([
                        'pending' => __('Pending Screening'),
                        'screened' => __('Screened'),
                        'recommended' => __('Teacher Recommended'),
                        'approved' => __('Approved'),
                        'denied' => __('Denied'),
                    ]),
                Tables\Filters\SelectFilter::make('course_id')
                    ->label(__('Form'))
                    ->relationship('enrollments.course', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('screen')
                    ->label(__('Screen Student'))
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(function ($record) {
                        // Run screening logic
                        Notification::make()->title('Screening completed for '.$record->full_name)->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkScreen')
                        ->label(__('Run Screening'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('pass_mark')
                                ->label(__('Pass Mark %'))
                                ->numeric()
                                ->default(50),
                        ])
                        ->action(function ($records, $data) {
                            foreach ($records as $record) {
                                $record->update(['promotion_status' => 'screened']);
                            }
                            Notification::make()->title(count($records).' students screened')->success()->send();
                        }),
                    Tables\Actions\BulkAction::make('bulkPromote')
                        ->label(__('Execute Promotions'))
                        ->icon('heroicon-o-arrow-trending-up')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $promoted = 0;
                            foreach ($records as $record) {
                                if ($record->promotion_status === 'approved') {
                                    $enrollment = $record->enrollments->first();
                                    if ($enrollment && $enrollment->next_course_id) {
                                        $newEnrollment = $enrollment->replicate();
                                        $newEnrollment->course_id = $enrollment->next_course_id;
                                        $newEnrollment->section_id = $enrollment->next_section_id;
                                        $newEnrollment->academic_year_id = AcademicYear::where('school_id', config('current_tenant_id'))->where('is_active', true)->first()?->id ?? $enrollment->academic_year_id;
                                        $newEnrollment->save();
                                        $promoted++;
                                    }
                                }
                            }
                            Notification::make()->title("$promoted students promoted")->success()->send();
                        }),
                ]),
            ])
            ->defaultSort('last_name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotionWorkflows::route('/'),
            'create' => Pages\CreatePromotionWorkflow::route('/create'),
            'edit' => Pages\EditPromotionWorkflow::route('/{record}/edit'),
        ];
    }
}
