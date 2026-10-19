<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\HasPageHelp;
use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Mail\AnnouncementPublishedMail;
use App\Models\User;
use App\Security\RoleCatalogue;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Modules\Communication\Models\EventCalendar;

class EventCalendarResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = EventCalendar::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Event Calendar';

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
                Forms\Components\Section::make(__('Event Scope'))
                    ->extraAttributes(['class' => 'overflow-visible!'])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description'),
                        Forms\Components\Select::make('category')
                            ->options([
                                'academic' => __('Academic Event'),
                                'meetings' => __('Board/Staff Meeting'),
                                'sports' => __('Sports Event'),
                                'examinations' => __('Examination Period'),
                                'holiday' => __('Public Holiday'),
                            ])->required(),
                        Forms\Components\TextInput::make('location'),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Date Boundaries'))
                        ->extraAttributes(['class' => 'overflow-visible!'])
                        ->schema([
                            Forms\Components\DateTimePicker::make('start_time')->required(),
                            Forms\Components\DateTimePicker::make('end_time')->required(),
                            Forms\Components\ColorPicker::make('color')
                                ->default('#1e3a8a')
                                ->required(),
                            Forms\Components\Select::make('target_roles')
                                ->label(__('Target Roles'))
                                ->multiple()
                                ->options(function (): array {
                                    $options = RoleCatalogue::audienceRoleOptions();
                                    $options['all'] = __('All Roles');

                                    return $options;
                                })
                                ->preload(),
                            Forms\Components\Select::make('target_user_ids')
                                ->label(__('Target Specific Individuals'))
                                ->multiple()
                                ->options(fn () => User::where('school_id', auth()->user()?->school_id)->pluck('name', 'id'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('channel')
                                ->label(__('Delivery Channel'))
                                ->options([
                                    'system_only' => __('System only'),
                                    'email_only' => __('Email only'),
                                    'both' => __('System and Email'),
                                ])
                                ->default('system_only'),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\TextColumn::make('start_time')->dateTime(),
                Tables\Columns\TextColumn::make('end_time')->dateTime(),
                Tables\Columns\TextColumn::make('location'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEventCalendars::route('/'),
            'create' => CreateEventCalendar::route('/create'),
            'edit' => EditEventCalendar::route('/{record}/edit'),
        ];
    }

    public static function broadcastToAudience(EventCalendar $record)
    {
        $usersQuery = User::query()->where('school_id', $record->school_id);

        $visibility = $record->target_roles ?? [];
        $targetUserIds = $record->target_user_ids ?? [];

        $sendToAll = in_array('all', $visibility, true);

        if ($sendToAll) {
            if (! empty($targetUserIds)) {
                $usersQuery->whereIn('id', $targetUserIds);
            }
        } else {
            $usersQuery->where(function ($q) use ($visibility, $targetUserIds) {
                $roleOr = false;
                if (! empty($visibility)) {
                    $q->where(function ($roleQ) use ($visibility) {
                        $roleQ->whereIn('requested_role', $visibility)
                            ->orWhereHas('customRole', fn ($r) => $r->whereIn('role_key', $visibility));
                    });
                    $roleOr = true;
                }
                if (! empty($targetUserIds)) {
                    if ($roleOr) {
                        $q->orWhereIn('id', $targetUserIds);
                    } else {
                        $q->whereIn('id', $targetUserIds);
                    }
                }
            });
        }

        $notifiedUsers = $usersQuery->get();

        $channel = $record->channel ?? 'system_only';
        $notifyInApp = in_array($channel, ['system_only', 'both'], true);
        $notifyByEmail = in_array($channel, ['email_only', 'both'], true);

        foreach ($notifiedUsers as $user) {
            if ($notifyInApp) {
                Notification::make()
                    ->title(__('New Event'))
                    ->body(__('Event: ').$record->title)
                    ->success()
                    ->sendToDatabase($user);
            }
        }

        if ($notifyByEmail && ! empty($notifiedUsers->whereNotNull('email')->count())) {
            try {
                foreach ($notifiedUsers->whereNotNull('email') as $user) {
                    Mail::to($user->email)->send(new AnnouncementPublishedMail(
                        title: $record->title,
                        content: str_replace('&nbsp;', ' ', (string) preg_replace('/<p[^>]*>|<\/p>/i', '', (string) $record->description)),
                        schoolName: $record->school?->name ?? '',
                        attachmentPaths: [],
                        school: $record->school,
                    ));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $notifiedUsers;
    }
}

class ListEventCalendars extends ListRecords
{
    use HasPageHelp;

    protected static string $resource = EventCalendarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->getHelpAction(),
            Actions\CreateAction::make()->label(__('New Event')),
        ];
    }
}
class CreateEventCalendar extends CreateRecord
{
    protected static string $resource = EventCalendarResource::class;

    protected function afterCreate(): void
    {
        try {
            EventCalendarResource::broadcastToAudience($this->record);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
class EditEventCalendar extends EditRecord
{
    protected static string $resource = EventCalendarResource::class;

    protected function afterSave(): void
    {
        if ($this->record->wasChanged('target_roles') || $this->record->wasChanged('target_user_ids') || $this->record->wasChanged('channel')) {
            try {
                EventCalendarResource::broadcastToAudience($this->record);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
