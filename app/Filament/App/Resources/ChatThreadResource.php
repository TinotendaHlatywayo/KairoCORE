<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Concerns\ModulePermissionAccess;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth; // ADDED IMPORT
use Modules\Communication\Models\ChatMessage;
use Modules\Communication\Models\ChatThread;

class ChatThreadResource extends Resource
{
    use ModulePermissionAccess;

    public static function getNavigationGroup(): ?string
    {
        return __('Communication Center');
    }

    protected static ?string $model = ChatThread::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Communication Center';

    protected static ?string $modelLabel = 'Chat Room / Thread';

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
                Forms\Components\TextInput::make('name')
                    ->label(__('Group Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'one_to_one' => __('Direct Message'),
                        'group' => __('General Group Chat'),
                        'department' => __('Department Internal Chat'),
                        'class' => __('Classroom Stream Chat'),
                        'parent_teacher' => __('Parent-Teacher Private Chat'),
                    ])->required()->default('group'),
                Forms\Components\Select::make('participants')
                    ->multiple()
                    ->label(__('Add Members'))
                    ->options(User::all()->pluck('name', 'id'))
                    ->preload()
                    ->required()
                    ->relationship('users', 'name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->label(__('Discussion Name')),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('users_count')->counts('users')->label(__('Active Members')),
                Tables\Columns\TextColumn::make('messages_count')->counts('messages')->label(__('Messages Sent')),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->label(__('Last Activity')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // Added ViewAction
                Tables\Actions\EditAction::make(),

                Action::make('bulletin')
                    ->label(__('Post Alert'))
                    ->icon('heroicon-o-bell-alert')
                    ->color('warning')
                    ->form([
                        Forms\Components\Textarea::make('message')
                            ->label(__('System Notification Text'))
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        ChatMessage::create([
                            'school_id' => $record->school_id,
                            'thread_id' => $record->id,
                            'sender_id' => Auth::id(),
                            'message' => '🚨 SYSTEM BULLETIN: '.$data['message'],
                        ]);

                        Notification::make()
                            ->title(__('System Alert Broadcasted'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChatThreads::route('/'),
            'create' => CreateChatThread::route('/create'),
            'edit' => EditChatThread::route('/{record}/edit'),
            'view' => ViewChatThread::route('/{record}'), // ADDED VIEW ROUTE
        ];
    }
}

class ListChatThreads extends ListRecords
{
    protected static string $resource = ChatThreadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('Create Chat Thread')),
        ];
    }
}
class CreateChatThread extends CreateRecord
{
    protected static string $resource = ChatThreadResource::class;
}
class EditChatThread extends EditRecord
{
    protected static string $resource = ChatThreadResource::class;
}
class ViewChatThread extends ViewRecord
{
    protected static string $resource = ChatThreadResource::class;

    // Binds the wrapper page
    protected static string $view = 'filament.app.pages.view-chat-thread';
}
