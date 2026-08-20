<?php

namespace App\Filament\App\Resources\ApplicationResource\Pages;

use App\Filament\App\Resources\ApplicationResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getRedirectUrl(): string
    {
        // Stay on the create page or go to index
        return route('filament.app.resources.applications.index');
    }

    protected function afterCreate(): void
    {
        // Send a notification with a custom view
        Notification::make()
            ->title(__('Application Submitted Successfully! 🎉'))
            ->body("Application Tracking Ref: {$this->record->application_number}")
            ->success()
            ->persistent()
            ->actions([
                Action::make('view')
                    ->label(__('View Application'))
                    ->url(route('filament.app.resources.applications.edit', $this->record)),
                Action::make('close')
                    ->label(__('Close'))
                    ->close(),
            ])
            ->send();
    }
}
