<?php

namespace App\Filament\App\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ApplicationSuccess extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static string $view = 'filament.app.pages.application-success';

    protected static ?string $title = 'Application Submitted';

    public function getTitle(): string
    {
        return __(static::$title ?? '');
    }

    protected static bool $shouldRegisterNavigation = false;

    public $applicationNumber;

    public $studentName;

    public function mount($applicationNumber = null, $studentName = null)
    {
        $this->applicationNumber = $applicationNumber;
        $this->studentName = $studentName;

        // Send a success notification
        Notification::make()
            ->title(__('Application Submitted Successfully! 🎉'))
            ->body("Application #{$this->applicationNumber} has been received.")
            ->success()
            ->duration(10000)
            ->send();
    }

    public function getApplicationStatus(): string
    {
        return 'Pending Review';
    }

    public function getEstimatedResponseTime(): string
    {
        return '3-5 business days';
    }
}
