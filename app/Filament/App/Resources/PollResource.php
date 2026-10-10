<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Filament\App\Resources\PollResource\Pages\CreatePoll;
use App\Filament\App\Resources\PollResource\Pages\EditPoll;
use App\Filament\App\Resources\PollResource\Pages\ListPolls;
use App\Filament\App\Resources\PollResource\Pages\ViewPoll;
use App\Models\User;
use App\Security\RoleCatalogue;
use App\Services\ModuleVisibilityManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Models\Poll;
use Modules\Communication\Models\PollVote;

class PollResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = Poll::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Poll & Survey';

    public static function getModelLabel(): string
    {
        return __(static::$modelLabel);
    }

    // Reached via the Communication Center contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Survey Definition'))
                    ->schema([
                        Forms\Components\TextInput::make('question')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description'),
                        Forms\Components\Select::make('type')
                            ->options([
                                'poll' => __('Quick Multi-choice Poll'),
                                'survey' => __('Open Feedback Survey'),
                                'election' => __('Formal Student/Staff Election'),
                            ])
                            ->required()
                            ->live()
                            ->helperText(fn ($state) => $state === 'survey'
                                ? __('Participants type their own answers — predefined choices are optional.')
                                : __('Participants pick one of the predefined choices below.')),
                        Forms\Components\Toggle::make('is_anonymous')
                            ->default(false)
                            ->helperText(__('When ON, nobody (including the creator) can see who responded — only the tallies.')),
                        Forms\Components\Toggle::make('show_results')
                            ->default(false)
                            ->helperText(__('When ON, targeted participants can see the live results after responding. When OFF (recommended), results are visible only to the creator and school administrators.')),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Audience Boundaries'))
                        ->extraAttributes(['class' => 'overflow-visible!'])
                        ->schema([
                            Forms\Components\Select::make('target_roles')
                                ->label(__('Target Roles'))
                                ->multiple()
                                ->options(fn (): array => RoleCatalogue::audienceRoleOptions())
                                ->preload(),
                            Forms\Components\Select::make('target_user_ids')
                                ->label(__('Target Specific Individuals'))
                                ->multiple()
                                ->options(fn () => User::where('school_id', auth()->user()?->school_id)->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\DateTimePicker::make('expires_at')
                                ->label(__('Expires At'))
                                ->required()
                                ->default(now()->addDays(7))
                                ->seconds(false),
                        ]),
                ])->columnSpan(1),

                // INLINE OPTIONS BUILDER (optional for open feedback surveys)
                Forms\Components\Section::make(__('Choice Parameters'))
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->relationship('options')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('option_value')
                                    ->label(__('Choice Option Label'))
                                    ->required(),
                            ])
                            ->minItems(function (Get $get): int {
                                return $get('type') === 'survey' ? 0 : 2;
                            })
                            ->helperText(function (Get $get): string {
                                return $get('type') === 'survey'
                                    ? __('Optional for open feedback surveys — participants type their own answer instead.')
                                    : __('Add at least two choices. Participants pick one.');
                            })
                            ->columns(1),
                    ])->columnSpanFull(),
            ])->columns(3);
    }

    /**
     * The poll creator and the school administrator may always read the results
     * (including, for non-anonymous polls, who voted). Everyone else sees the
     * question only or casts a vote from the grid.
     */
    public static function canViewResults($record): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if ($user->school_id === null) {
            return true;
        }

        if ((int) $user->id === (int) ($record->created_by ?? 0)) {
            return true;
        }

        return ModuleVisibilityManager::isSchoolAdmin();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make(__('Poll & Survey'))
                    ->schema([
                        TextEntry::make('question')
                            ->weight('bold')
                            ->size('lg'),
                        TextEntry::make('type')
                            ->badge(),
                        TextEntry::make('expires_at')
                            ->label(__('Expires At'))
                            ->dateTime(),
                    ]),

                InfolistSection::make(__('Live Voting Results'))
                    ->visible(fn ($record) => self::canViewResults($record))
                    ->schema([
                        TextEntry::make('results_bars')
                            ->label(__('Current Standing'))
                            ->state(fn ($record): string => view('filament.app.resources.poll-results', ['poll' => $record])->render())
                            ->html()
                            ->extraAttributes(['class' => 'w-full']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\IconColumn::make('is_anonymous')->boolean()->label(__('Anonymous')),
                Tables\Columns\TextColumn::make('votes_count')->counts('votes')->label(__('Participation')),
                Tables\Columns\TextColumn::make('expires_at')->label(__('Expires'))
                    ->formatStateUsing(fn ($state) => $state?->format('d M Y H:i')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(fn ($record) => self::canViewResults($record) ? __('View Results') : __('View'))
                    ->visible(fn ($record) => self::canViewResults($record)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Poll & Survey'))
                    ->label(__('Delete')),

                // INTERACTIVE ACTION: CAST VOTE DIRECTLY FROM GRID
                Action::make('vote')
                    ->label(__('Vote'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn ($record) => $record->expires_at?->isFuture()
                        && ! PollVote::where('poll_id', $record->id)->where('user_id', Auth::id())->exists())
                    ->form(function ($record) {
                        if ($record->type === 'survey') {
                            return [
                                Forms\Components\Textarea::make('written_response')
                                    ->label(__('Your Response'))
                                    ->required()
                                    ->rows(3),
                            ];
                        }

                        return [
                            Forms\Components\Select::make('option_id')
                                ->label(__('Select Option'))
                                ->options($record->options->pluck('option_value', 'id'))
                                ->required(),
                        ];
                    })
                    ->action(function ($record, array $data) {
                        PollVote::create([
                            'school_id' => $record->school_id,
                            'poll_id' => $record->id,
                            'option_id' => $data['option_id'] ?? null,
                            'written_response' => $data['written_response'] ?? null,
                            'user_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title(__('Response Recorded'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    /**
     * Resolve the users a poll/survey is aimed at and fan an in-app
     * notification out to exactly those users (same audience rules as
     * announcements: real role keys + specific individuals).
     *
     * @return Collection<int, User>
     */
    public static function broadcastToAudience(Poll $record)
    {
        $usersQuery = User::query()->where('school_id', $record->school_id);

        $roles = $record->target_roles ?? [];
        $targetUserIds = $record->target_user_ids ?? [];

        if (! empty($roles) || ! empty($targetUserIds)) {
            $usersQuery->where(function ($q) use ($roles, $targetUserIds) {
                if (! empty($roles)) {
                    $q->where(function ($roleQ) use ($roles) {
                        $roleQ->whereIn('requested_role', $roles)
                            ->orWhereHas('customRole', fn ($r) => $r->whereIn('role_key', $roles));
                    });
                }

                if (! empty($targetUserIds)) {
                    $q->orWhereIn('id', $targetUserIds);
                }
            });
        }

        $targetedUsers = $usersQuery->get();

        foreach ($targetedUsers as $user) {
            Notification::make()
                ->title(__($record->type === 'survey' ? 'New Survey' : 'New Poll'))
                ->body($record->question)
                ->info()
                ->sendToDatabase($user);
        }

        return $targetedUsers;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPolls::route('/'),
            'create' => CreatePoll::route('/create'),
            'edit' => EditPoll::route('/{record}/edit'),
            'view' => ViewPoll::route('/{record}'),
        ];
    }
}
