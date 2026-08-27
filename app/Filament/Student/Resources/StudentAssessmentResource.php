<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\StudentAssessmentResource\Pages;
use App\Models\Scopes\TenantScope;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\DigitalAssessment\Enums\AssessmentStatus;
use Modules\DigitalAssessment\Models\DigitalAssessment;
use Modules\DigitalAssessment\Services\AttemptService;
use Modules\Students\Models\Student;

class StudentAssessmentResource extends Resource
{
    protected static ?string $model = DigitalAssessment::class;

    protected static ?string $navigationGroup = 'Academics';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Digital Assessments';

    protected static ?string $modelLabel = 'Assessment';

    protected static ?string $pluralModelLabel = 'Assessments';

    protected static ?string $slug = 'digital-assessments';

    public static function getNavigationLabel(): string
    {
        return __('Digital Assessments');
    }

    protected static array $currentStudentCache = [];

    public static function currentStudent(): ?Student
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();
        $cacheKey = 'da.current_student.' . $user->id;

        if (array_key_exists($cacheKey, static::$currentStudentCache)) {
            return static::$currentStudentCache[$cacheKey];
        }

        $student = static::resolveStudent($user);

        return static::$currentStudentCache[$cacheKey] = $student;
    }

    protected static function resolveStudent($user): ?Student
    {
        $student = Student::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $user->id)
            ->first();

        if ($student) {
            return $student;
        }

        if ($user->username) {
            $student = Student::withoutGlobalScope(TenantScope::class)
                ->where('school_id', $user->school_id)
                ->where(fn ($q) => $q->where('student_id_number', $user->username)
                    ->orWhere('admission_number', $user->username))
                ->first();

            if ($student) {
                $student->update(['user_id' => $user->id]);

                return $student;
            }
        }

        $parts = preg_split('/\s+/', trim($user->name), 2);
        $firstName = $parts[0] ?? '';
        $lastName = $parts[1] ?? '';

        if ($firstName && $lastName) {
            $student = Student::withoutGlobalScope(TenantScope::class)
                ->where('school_id', $user->school_id)
                ->where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->whereNull('user_id')
                ->first();

            if ($student) {
                $student->update(['user_id' => $user->id]);

                return $student;
            }
        }

        if ($user->email) {
            $student = Student::withoutGlobalScope(TenantScope::class)
                ->where('school_id', $user->school_id)
                ->where('parent_email', $user->email)
                ->whereNull('user_id')
                ->first();

            if ($student) {
                $student->update(['user_id' => $user->id]);

                return $student;
            }
        }

        return null;
    }

    public static function getEloquentQuery(): Builder
    {
        $student = static::currentStudent();

        $query = parent::getEloquentQuery();

        if (! $student) {
            return $query->whereRaw('1 = 0');
        }

        $sectionIds = $student->enrollments()->pluck('section_id')->filter()->unique();

        return $query
            ->whereIn('status', [AssessmentStatus::Published, AssessmentStatus::Active])
            ->where(function ($q) use ($sectionIds) {
                $q->whereNull('section_id')
                    ->orWhereIn('section_id', $sectionIds);
            })
            ->with(['subject', 'questions'])
            ->withCount('questions');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('Assessment'))
                    ->searchable()
                    ->description(fn (DigitalAssessment $record) => $record->description)
                    ->wrap(),

                Tables\Columns\TextColumn::make('assessment_category')
                    ->label(__('Category'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof \BackedEnum ? $state->label() : (\Modules\DigitalAssessment\Enums\AssessmentCategory::tryFrom($state)?->label() ?? $state))
                    ->color(fn ($state) => $state instanceof \BackedEnum ? $state->color() : (\Modules\DigitalAssessment\Enums\AssessmentCategory::tryFrom($state)?->color() ?? 'gray')),

                Tables\Columns\TextColumn::make('questions_count')
                    ->label(__('Questions'))
                    ->counts('questions'),

                Tables\Columns\TextColumn::make('duration_minutes')
                    ->label(__('Duration'))
                    ->formatStateUsing(fn ($state) => $state ? $state . ' min' : '—'),

                Tables\Columns\TextColumn::make('total_marks')
                    ->label(__('Marks')),

                Tables\Columns\TextColumn::make('attempt_status')
                    ->label(__('My Status'))
                    ->badge()
                    ->getStateUsing(function (DigitalAssessment $record) {
                        $student = static::currentStudent();
                        if (! $student) {
                            return 'unknown';
                        }

                        $bestAttempt = $record->attempts()
                            ->where('student_id', $student->id)
                            ->orderByDesc('percentage')
                            ->first();

                        if (! $bestAttempt) {
                            return 'not_started';
                        }

                        if ($bestAttempt->status->isComplete()) {
                            return $bestAttempt->hasPassed() ? 'passed' : 'completed';
                        }

                        return 'in_progress';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'passed' => 'success',
                        'completed' => 'warning',
                        'in_progress' => 'info',
                        'not_started' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'passed' => __('Passed'),
                        'completed' => __('Completed'),
                        'in_progress' => __('In Progress'),
                        'not_started' => __('Not Started'),
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label(fn (DigitalAssessment $record) => static::getStartLabel($record))
                    ->icon(fn (DigitalAssessment $record) => static::getStartIcon($record))
                    ->color(fn (DigitalAssessment $record) => static::getStartColor($record))
                    ->disabled(fn (DigitalAssessment $record) => ! $record->isAvailable())
                    ->url(fn (DigitalAssessment $record) => static::getStartUrl($record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('viewResult')
                    ->label(__('View Result'))
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->visible(function (DigitalAssessment $record) {
                        $student = static::currentStudent();
                        return $student && $record->attempts()
                            ->where('student_id', $student->id)
                            ->complete()
                            ->exists();
                    })
                    ->url(function (DigitalAssessment $record) {
                        $student = static::currentStudent();
                        $bestAttempt = $record->attempts()
                            ->where('student_id', $student->id)
                            ->orderByDesc('percentage')
                            ->first();

                        return route('filament.student.pages.attempt-result', [
                            'attempt' => $bestAttempt->id,
                        ]);
                    })
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading(__('No assessments available'))
            ->emptyStateDescription(__('When your teacher publishes a digital assessment for your class, it will appear here.'));
    }

    protected static function getStartLabel(DigitalAssessment $record): string
    {
        $student = static::currentStudent();
        if (! $student) {
            return __('Start');
        }

        $inProgress = $record->attempts()
            ->where('student_id', $student->id)
            ->inProgress()
            ->first();

        if ($inProgress) {
            return __('Continue');
        }

        $completedCount = $record->attempts()
            ->where('student_id', $student->id)
            ->complete()
            ->count();

        if ($completedCount >= $record->max_attempts) {
            return __('Completed');
        }

        return __('Start');
    }

    protected static function getStartIcon(DigitalAssessment $record): string
    {
        $student = static::currentStudent();
        if (! $student) {
            return 'heroicon-o-play';
        }

        $inProgress = $record->attempts()
            ->where('student_id', $student->id)
            ->inProgress()
            ->exists();

        return $inProgress ? 'heroicon-o-arrow-right' : 'heroicon-o-play';
    }

    protected static function getStartColor(DigitalAssessment $record): string
    {
        $student = static::currentStudent();
        if (! $student) {
            return 'primary';
        }

        $inProgress = $record->attempts()
            ->where('student_id', $student->id)
            ->inProgress()
            ->exists();

        return $inProgress ? 'warning' : 'primary';
    }

    protected static function getStartUrl(DigitalAssessment $record): string
    {
        return route('filament.student.pages.assessment-detail', [
            'assessment' => $record->id,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentAssessments::route('/'),
        ];
    }
}
