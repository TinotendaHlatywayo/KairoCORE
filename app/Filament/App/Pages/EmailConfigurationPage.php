<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ManagesEmailConfiguration;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Modules\Admin\Services\PermissionRegistry;

/**
 * Central "Email Configuration" page under System Administration.
 *
 * Holds one section per email category (Admissions, Finance, Academic,
 * Communication). Every section reads and writes the same
 * `email_configurations` rows that the Admissions Settings page uses, so the
 * two entry points never diverge.
 */
class EmailConfigurationPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ManagesEmailConfiguration;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'System Administration';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Email Configuration';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'email-configuration';

    protected static string $view = 'filament.app.pages.email-configuration';

    public ?array $data = [];

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return PermissionRegistry::checkPermission('administration.manage_email_config')
            || PermissionRegistry::checkPermission('administration.manage_settings');
    }

    public function mount(): void
    {
        $data = [];
        $this->fillEmailConfigurationState($data);
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->emailConfigurationSections())
            ->statePath('data');
    }

    public function save(): void
    {
        $this->saveEmailConfiguration($this->form->getState());

        Notification::make()
            ->title(__('Email configuration saved'))
            ->success()
            ->send();
    }
}
