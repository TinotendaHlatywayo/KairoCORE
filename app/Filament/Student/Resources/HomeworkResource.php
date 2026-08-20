<?php

namespace App\Filament\Student\Resources;

use App\Filament\Student\Resources\HomeworkResource\Pages;
use App\Models\Scopes\TenantScope;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Modules\Lms\Models\Homework;
use Modules\Students\Models\Student;

class HomeworkResource extends Resource
{
    protected static ?string $model = Homework::class;

    /**
     * Per-request cache of currentStudent() results keyed by user id, so the
     * up-to-4-query resolution (plus its possible user_id write) runs once per
     * request instead of once per Livewire render/action.
     *
     * @var array<string, ?Student>
     */
    protected static array $currentStudentCache = [];

    protected static ?string $navigationGroup = 'Learning';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'My Homework';

    protected static ?string $slug = 'homeworks';

    public static function getNavigationLabel(): string
    {
        return __('My Homework');
    }

    /**
     * Students only ever see the homework assigned to the class streams they
     * are enrolled in. Their own submissions are eager-loaded so the table and
     * actions can answer "already submitted?" without N+1 queries.
     */
    public static function getEloquentQuery(): Builder
    {
        $student = static::currentStudent();

        $query = parent::getEloquentQuery();

        if (! $student) {
            return $query->whereRaw('1 = 0');
        }

        $sectionIds = $student->enrollments()->pluck('section_id')->filter()->unique();

        return $query
            ->whereIn('section_id', $sectionIds)
            ->with([
                'subject',
                'section.course',
                'submissions' => fn ($query) => $query->where('student_id', $student->id),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                Tables\Columns\TextColumn::make('subject.name')
                    ->label(__('Subject'))
                    ->placeholder(__('—'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('title')
                    ->label(__('Assignment'))
                    ->searchable()
                    ->description(fn (Homework $record) => $record->description)
                    ->wrap(),
                Tables\Columns\TextColumn::make('due_date')
                    ->label(__('Due Date'))
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('submission_status')
                    ->label(__('Status'))
                    ->badge()
                    ->color(fn (Homework $record) => match ($record->submission_status) {
                        'submitted' => 'success',
                        'overdue' => 'danger',
                        default => 'warning',
                    })
                    ->getStateUsing(fn (Homework $record) => $record->submission_status),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalHeading(fn (Homework $record) => $record->title)
                    ->form(fn (Homework $record): array => static::viewSchema($record)),
                Tables\Actions\Action::make('submit')
                    ->label(fn (Homework $record) => $record->submission_status === 'submitted' ? __('Submitted') : __('Submit Work'))
                    ->icon(fn (Homework $record) => $record->submission_status === 'submitted' ? 'heroicon-o-check-circle' : 'heroicon-o-cloud-arrow-up')
                    ->color(fn (Homework $record) => $record->submission_status === 'submitted' ? 'success' : 'primary')
                    ->disabled(fn (Homework $record) => $record->submission_status === 'submitted')
                    ->form([
                        Forms\Components\FileUpload::make('file_path')
                            ->label(__('Your Submission'))
                            ->helperText(__('Upload your completed work here (PDF, Word, images...).'))
                            ->disk('public')
                            ->directory('homework_submissions')
                            ->required()
                            ->preserveFilenames(),
                    ])
                    ->action(function (Homework $record, array $data) {
                        $student = static::currentStudent();

                        if (! $student) {
                            Notification::make()->title(__('Student record not found.'))->danger()->send();

                            return;
                        }

                        if ($record->submission_status === 'submitted') {
                            Notification::make()->title(__('Already submitted.'))->warning()->send();

                            return;
                        }

                        $record->submissions()->create([
                            'school_id' => $record->school_id,
                            'student_id' => $student->id,
                            'file_path' => $data['file_path'],
                            'submitted_at' => now(),
                        ]);

                        Notification::make()
                            ->title(__('Homework Submitted'))
                            ->body(__('Your teacher has been notified of your submission.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading(__('No homework yet'))
            ->emptyStateDescription(__('When your teacher issues homework to your class, it will appear here automatically.'));
    }

    /**
     * Read-only schema used by the View slide-over: assignment details,
     * attachments and any feedback the teacher has left.
     */
    protected static function viewSchema(Homework $record): array
    {
        $submission = $record->submissions->first();

        return [
            Forms\Components\Placeholder::make('subject')
                ->label(__('Subject'))
                ->content(fn () => $record->subject?->name ?? __('—')),
            Forms\Components\Placeholder::make('section')
                ->label(__('Class'))
                ->content(fn () => $record->section?->course?->name ?? __('—')),
            Forms\Components\Placeholder::make('due_date')
                ->label(__('Due Date'))
                ->content(fn () => $record->due_date?->format('d M Y')),
            Forms\Components\Placeholder::make('description')
                ->label(__('Instructions'))
                ->content(fn () => $record->description ?? __('No instructions provided.')),
            Forms\Components\Placeholder::make('attachments')
                ->label(__('Attachments'))
                ->content(fn () => $record->file_path
                    ? '<a href="'.asset('storage/'.$record->file_path).'" target="_blank" rel="noopener" class="text-primary-600 underline">'.__('Download attachment').'</a>'
                    : __('None')),
            Forms\Components\Placeholder::make('video')
                ->label(__('Lesson Video'))
                ->content(fn () => $record->youtube_url
                    ? '<a href="'.e($record->youtube_url).'" target="_blank" rel="noopener" class="text-primary-600 underline">'.__('Watch video').'</a>'
                    : __('None')),
            Forms\Components\Placeholder::make('submission')
                ->label(__('My Submission'))
                ->content(fn () => $submission
                    ? '<a href="'.asset('storage/'.$submission->file_path).'" target="_blank" rel="noopener" class="text-primary-600 underline">'.__('View my submission').'</a>'.' · '.($submission->submitted_at?->format('d M Y H:i') ?? '')
                    : __('Not submitted yet.')),
            Forms\Components\Placeholder::make('feedback')
                ->label(__('Teacher Feedback'))
                ->content(fn () => $submission?->teacher_feedback
                    ? $submission->teacher_feedback.($submission->grade_obtained !== null ? ' — '.__('Grade:').' '.$submission->grade_obtained : '')
                    : __('No feedback yet.')),
        ];
    }

    public static function currentStudent(): ?Student
    {
        if (! Auth::check()) {
            return null;
        }

        $user = Auth::user();

        // Cache per request so getEloquentQuery() + table render + submit
        // actions don't repeat up to 4 DB queries (and a possible write)
        // for the same user on every Livewire round-trip.
        $cacheKey = 'homework.current_student.'.$user->id;
        if (array_key_exists($cacheKey, static::$currentStudentCache)) {
            return static::$currentStudentCache[$cacheKey];
        }

        $student = static::resolveStudent($user);

        return static::$currentStudentCache[$cacheKey] = $student;
    }

    protected static function resolveStudent($user): ?Student
    {
        // 1. Direct link — student record already has this user_id.
        $student = Student::withoutGlobalScope(TenantScope::class)
            ->where('user_id', $user->id)
            ->first();

        if ($student) {
            return $student;
        }

        // 2. Identifier match — registration username matches a student number.
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

        // 3. Name match — user's name matches student's first + last name
        //    in the same school.  Split on the first space so that
        //    "Tendai Shumba" matches first_name=Tendai, last_name=Shumba.
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

        // 4. Email match — user email matches the student's parent_email.
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHomeworks::route('/'),
        ];
    }
}
