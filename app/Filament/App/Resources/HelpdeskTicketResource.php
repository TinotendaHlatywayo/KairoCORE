<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Modules\Communication\Models\HelpdeskTicket;
use Modules\Communication\Models\HelpdeskTicketReply;

class HelpdeskTicketResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = HelpdeskTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Helpdesk Ticket';

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
                Forms\Components\Section::make(__('Ticket Scope'))
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label(__('Submitter / Author'))
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('category')
                            ->options([
                                'it' => __('IT & Software troubleshooting'),
                                'payroll' => __('Payroll & Salaries'),
                                'maintenance' => __('Facilities Maintenance'),
                                'transport' => __('Transport Operations'),
                                'finance' => __('Fees & Payments'),
                                'admissions' => __('Admissions Desk'),
                            ])->required(),
                        Forms\Components\TextInput::make('subject')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->columnSpanFull(),
                    ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make(__('Internal Assignment'))
                        ->schema([
                            Forms\Components\Select::make('priority')
                                ->options([
                                    'low' => __('Low Priority'),
                                    'medium' => __('Medium Priority'),
                                    'high' => __('High Priority'),
                                ])->required()->default('medium'),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'open' => __('Open'),
                                    'assigned' => __('Assigned'),
                                    'in_progress' => __('In Progress'),
                                    'waiting_for_user' => __('Waiting for User'),
                                    'resolved' => __('Resolved'),
                                    'closed' => __('Closed'),
                                ])->required()->default('open'),
                            Forms\Components\Select::make('assigned_to_id')
                                ->label(__('Assigned Agent'))
                                ->options(User::all()->pluck('name', 'id'))
                                ->searchable(),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_number')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('user.name')->label(__('Submitter')),
                Tables\Columns\TextColumn::make('category'),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'secondary' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'open',
                        'primary' => 'assigned',
                        'warning' => ['in_progress', 'waiting_for_user'],
                        'success' => ['resolved', 'closed'],
                    ]),
                Tables\Columns\TextColumn::make('assignedTo.name')->label(__('Assigned Agent')),
                Tables\Columns\TextColumn::make('created_at')->date()->label(__('Date Opened')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                // ACTION BUTTON: QUICK ASSIGN TICKET
                Action::make('assign')
                    ->label(__('Assign'))
                    ->icon('heroicon-o-user-plus')
                    ->color('warning')
                    ->visible(fn ($record) => in_array($record->status, ['open', 'assigned']))
                    ->form([
                        Forms\Components\Select::make('assigned_to_id')
                            ->label(__('Select Support Agent'))
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'assigned_to_id' => $data['assigned_to_id'],
                            'status' => 'assigned',
                        ]);

                        Notification::make()
                            ->title(__('Ticket Assigned Successfully'))
                            ->success()
                            ->send();
                    }),

                // ACTION BUTTON: CONVERSATIONAL REPLY
                Action::make('reply')
                    ->label(__('Reply'))
                    ->icon('heroicon-o-chat-bubble-left')
                    ->color('primary')
                    ->visible(fn ($record) => ! in_array($record->status, ['closed', 'resolved']))
                    ->form([
                        Forms\Components\RichEditor::make('message')
                            ->label(__('Reply Message'))
                            ->required(),
                        Forms\Components\Toggle::make('is_internal')
                            ->label(__('Internal Note (Hidden from parents/students)'))
                            ->default(false),
                    ])
                    ->action(function ($record, array $data) {
                        HelpdeskTicketReply::create([
                            'school_id' => $record->school_id,
                            'ticket_id' => $record->id,
                            'user_id' => Auth::id(),
                            'message' => $data['message'],
                            'is_internal' => $data['is_internal'],
                        ]);

                        $record->update([
                            'status' => $data['is_internal'] ? $record->status : 'waiting_for_user',
                        ]);

                        Notification::make()
                            ->title(__('Reply Sent'))
                            ->success()
                            ->send();
                    }),

                // ACTION BUTTON: CLOSE TICKET
                Action::make('close')
                    ->label(__('Close'))
                    ->icon('heroicon-o-lock-closed')
                    ->color('success')
                    ->visible(fn ($record) => ! in_array($record->status, ['closed', 'resolved']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('resolution')
                            ->label(__('Resolution Summary'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        HelpdeskTicketReply::create([
                            'school_id' => $record->school_id,
                            'ticket_id' => $record->id,
                            'user_id' => Auth::id(),
                            'message' => '🚨 TICKET CLOSED - RESOLUTION: '.$data['resolution'],
                            'is_internal' => false,
                        ]);

                        $record->update([
                            'status' => 'closed',
                            'resolved_at' => now(),
                        ]);

                        Notification::make()
                            ->title(__('Ticket Closed & Resolved'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHelpdeskTickets::route('/'),
        ];
    }
}

class ListHelpdeskTickets extends ListRecords
{
    protected static string $resource = HelpdeskTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Open Ticket')),
        ];
    }
}
