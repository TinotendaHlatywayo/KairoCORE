<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
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
                Forms\Components\Section::make('Survey Definition')
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
                            ])->required(),
                        Forms\Components\Toggle::make('is_anonymous')
                            ->default(false),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Audience Boundaries')
                        ->schema([
                            Forms\Components\Select::make('target_roles')
                                ->multiple()
                                ->options([
                                    'admin' => __('Administrators'),
                                    'teacher' => __('Teachers'),
                                    'student' => __('Students'),
                                    'parent' => __('Parents'),
                                ])->preload(),
                            Forms\Components\DatePicker::make('expires_at')->required(),
                        ]),
                ])->columnSpan(1),

                // INLINE OPTIONS BUILDER
                Forms\Components\Section::make('Choice Parameters')
                    ->schema([
                        Forms\Components\Repeater::make('options')
                            ->relationship('options')
                            ->schema([
                                Forms\Components\TextInput::make('option_value')
                                    ->label(__('Choice Option Label'))
                                    ->required(),
                            ])
                            ->minItems(2)
                            ->columns(1),
                    ])->columnSpanFull(),
            ])->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistSection::make('Live Voting Results Summary')
                    ->schema([
                        TextEntry::make('question')
                            ->weight('bold')
                            ->size('lg'),

                        // DESIGN: Dynamic Percentage Standings Calculator
                        TextEntry::make('options_summary')
                            ->label(__('Current Standing (Percentage & Count)'))
                            ->formatStateUsing(function ($record) {
                                $totalVotes = $record->votes()->count();
                                if ($totalVotes === 0) {
                                    return 'No votes have been recorded for this poll.';
                                }

                                return $record->options->map(function ($opt) use ($totalVotes) {
                                    $optVotes = $opt->votes()->count();
                                    $pct = round(($optVotes / $totalVotes) * 100, 1);

                                    return "• {$opt->option_value}: {$optVotes} votes ({$pct}%)";
                                })->implode("\n");
                            })
                            ->listWithLineBreaks(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\IconColumn::make('is_anonymous')->boolean(),
                Tables\Columns\TextColumn::make('votes_count')->counts('votes')->label(__('Participation')),
                Tables\Columns\TextColumn::make('expires_at')->date()->label(__('Closing Date')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // INTERACTIVE ACTION: CAST VOTE DIRECTLY FROM GRID
                Action::make('vote')
                    ->label(__('Vote'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('success')
                    ->visible(fn ($record) => $record->expires_at->isFuture() && ! PollVote::where('poll_id', $record->id)->where('user_id', Auth::id())->exists())
                    ->form(function ($record) {
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
                            'option_id' => $data['option_id'],
                            'user_id' => Auth::id(),
                        ]);

                        Notification::make()
                            ->title(__('Vote Recorded'))
                            ->success()
                            ->send();
                    }),
            ]);
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

class ListPolls extends ListRecords
{
    protected static string $resource = PollResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('New Poll/Survey')),
        ];
    }
}
class CreatePoll extends CreateRecord
{
    protected static string $resource = PollResource::class;
}
class EditPoll extends EditRecord
{
    protected static string $resource = PollResource::class;
}
class ViewPoll extends ViewRecord
{
    protected static string $resource = PollResource::class;
}
