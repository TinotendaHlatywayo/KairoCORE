<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Concerns\ManagesEmailConfiguration;
use App\Models\User;
use App\Services\ModuleVisibilityManager;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\PermissionRegistry;

class AdmissionSettingsPage extends Page implements HasForms
{
    use InteractsWithForms;
    use ManagesEmailConfiguration;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Admissions';

    public static function getNavigationGroup(): ?string
    {
        return __(static::$navigationGroup);
    }

    protected static ?string $navigationLabel = 'Admission Settings';

    protected static ?string $slug = 'admission-settings';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.app.pages.admission-settings';

    public ?array $data = [];

    // Reached via the module contextual tabs, not the sidebar.
    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Admission Settings');
    }

    public static function canAccess(): bool
    {
        if (! ModuleVisibilityManager::isPageVisible('admissions', 'settings')) {
            return false;
        }

        if (class_exists('\Modules\Admin\Services\PermissionRegistry')) {
            return PermissionRegistry::checkPermission('academic_ops.manage_admissions');
        }

        return true;
    }

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        $data = [
            'admission_email' => SystemSetting::get('admission', 'contact_email', ''),
            'admission_phone' => SystemSetting::get('admission', 'contact_phone', ''),
            'email_subject' => SystemSetting::get('admission', 'email_subject', 'Admission Confirmation — {school_name}'),
            'email_body' => SystemSetting::get('admission', 'email_body') ?: $this->defaultEmailBody(),
            'notify_email_enabled' => SystemSetting::get('admission', 'notify_email_enabled', true),
            'document_guidelines' => SystemSetting::get('admission', 'document_guidelines', $this->defaultDocumentGuidelines()),
            'transfer_letter_required' => SystemSetting::get('admission', 'transfer_letter_required', true),
            'success_title' => SystemSetting::get('admission', 'success_title', 'Application Submitted!'),
            'success_message' => SystemSetting::get('admission', 'success_message', 'Your online application has been submitted successfully! Save your tracking reference below to monitor your application status.'),
        ];

        $this->fillEmailConfigurationState($data);
        $this->form->fill($data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('Admission Contact Details'))
                    ->description(__('These details are shown on the public school contact page and identify who handles admissions enquiries.'))
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('admission_email')
                            ->label(__('Admission Email Address'))
                            ->email()
                            ->required()
                            ->placeholder(__('admissions@yourschool.edu'))
                            ->helperText(__('Publicly displayed for admission enquiries. Emails sent on successful admission are sent to the applicant\'s registered email.')),
                        TextInput::make('admission_phone')
                            ->label(__('Admission Phone Number'))
                            ->tel()
                            ->required()
                            ->placeholder(__('+263 77 123 456'))
                            ->helperText(__('Publicly displayed on the school contact page for admission enquiries.')),
                    ])->columns(2),

                Section::make(__('New Application Email Alerts'))
                    ->description(__('When a parent submits an online application, notify the admission email address above so your team can act quickly.'))
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('notify_email_enabled')
                            ->label(__('Email the Admission Contact Address on New Applications'))
                            ->default(true)
                            ->helperText(__('When enabled, the admissions email address above receives an email for every new online application.')),
                    ])->columns(1),

                Section::make(__('Application Success Popup'))
                    ->description(__('This popup is shown to the applicant immediately after a successful online application submission, together with their tracking reference.'))
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('success_title')
                            ->label(__('Popup Title'))
                            ->required()
                            ->default(__('Application Submitted!'))
                            ->placeholder(__('Application Submitted!')),
                        Textarea::make('success_message')
                            ->label(__('Popup Message'))
                            ->rows(4)
                            ->required()
                            ->helperText(__('Shown below the popup title. The applicant\'s tracking reference is always displayed underneath.'))
                            ->placeholder(__('Your online application has been submitted successfully! Save your tracking reference below to monitor your application status.')),
                    ])->columns(1),

                Section::make(__('Supporting Documents Guidelines'))
                    ->description(__('This message is shown on the public online application form under the "Supporting Documents" section. Use it to tell applicants what size, format and documents your school requires.'))
                    ->columnSpan(1)
                    ->schema([
                        Textarea::make('document_guidelines')
                            ->label(__('Supporting Documents Description'))
                            ->rows(5)
                            ->helperText(__('Shown on the public application form below the "Supporting Documents" heading.'))
                            ->placeholder(__('Upload scanned copies (PDF, JPG or PNG, max 5MB each). Birth certificate is required; certificates or result slips are highly recommended for secondary level applications.')),
                    ])->columns(1),

                Section::make(__('Transfer Letter'))
                    ->description(__('Applicants joining outside entry levels (e.g. Grade 1, Form 1, Form 5) are asked for a verified transfer letter from their previous school.'))
                    ->columnSpan(1)
                    ->schema([
                        Toggle::make('transfer_letter_required')
                            ->label(__('Require Transfer Letter for Mid-Level Applicants'))
                            ->default(true)
                            ->helperText(__('When enabled, applicants joining outside entry levels must upload a verified transfer letter. When disabled, it stays optional.')),
                    ])->columns(1),

                Section::make(__('Admission Confirmation Email'))
                    ->description(__('This email is sent to the parent/guardian and the student when a student is enrolled. It includes the student\'s ID number, admission number, and an activation link for the student to set their password.'))
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('email_subject')
                            ->label(__('Email Subject'))
                            ->required()
                            ->default(__('Admission Confirmation'))
                            ->placeholder(__('Admission Confirmation')),
                        Textarea::make('email_body')
                            ->label(__('Email Body'))
                            ->rows(10)
                            ->required()
                            ->helperText(__('Available placeholders: {student_name}, {student_id}, {admission_number}, {school_name}, {academic_year}, {activation_url}, {hours}'))
                            ->placeholder(__("Dear Parent/Guardian,\n\nWe are pleased to confirm that {student_name} has been offered admission to {school_name}.\n\nStudent ID Number: {student_id}\nStudent/Admission Number: {admission_number}\n\nWelcome to our school community!")),
                    ])->columns(1),

                $this->emailConfigurationSection(EmailCategory::Admissions)
                    ->columnSpanFull(),
            ])
            ->columns(['default' => 1, 'lg' => 2])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        /** @var User|null $user */
        $user = Auth::user();
        $schoolId = session('current_tenant')?->id ?? ($user ? $user->school_id : null);

        if (! $schoolId) {
            return;
        }

        $mapping = [
            'admission_email' => ['admission', 'contact_email'],
            'admission_phone' => ['admission', 'contact_phone'],
            'email_subject' => ['admission', 'email_subject'],
            'email_body' => ['admission', 'email_body'],
            'notify_email_enabled' => ['admission', 'notify_email_enabled'],
            'document_guidelines' => ['admission', 'document_guidelines'],
            'transfer_letter_required' => ['admission', 'transfer_letter_required'],
            'success_title' => ['admission', 'success_title'],
            'success_message' => ['admission', 'success_message'],
        ];

        foreach ($mapping as $field => [$group, $key]) {
            SystemSetting::updateOrCreate(
                ['school_id' => $schoolId, 'group' => $group, 'key' => $key],
                ['value' => $state[$field] ?? ''],
            );
        }

        // Persist the admissions email configuration (shared with the central
        // "Email Configuration" page under System Administration). The stored
        // system setting is kept in sync so both entry points always agree.
        $this->saveEmailConfiguration($state, [EmailCategory::Admissions]);

        // Mirror the enabled flag into the legacy setting key so existing
        // consumers (e.g. admissions service) keep working without divergence.
        $emailCfg = data_get($state, 'emailcfg.'.EmailCategory::Admissions->value) ?? [];
        if (array_key_exists('is_enabled', $emailCfg)) {
            SystemSetting::updateOrCreate(
                ['school_id' => $schoolId, 'group' => 'admission', 'key' => 'email_enabled'],
                ['value' => $emailCfg['is_enabled'] ? '1' : '0'],
            );
        }

        Notification::make()
            ->title(__('Admission settings saved'))
            ->success()
            ->send();
    }

    protected function defaultDocumentGuidelines(): string
    {
        return 'Upload scanned copies (PDF, JPG or PNG, max 10MB each). Birth certificate is required; certificates or result slips are highly recommended for secondary level applications.';
    }

    protected function defaultEmailBody(): string
    {
        return "Dear Parent/Guardian,\n\nWe are pleased to confirm that {student_name} has been offered admission to {school_name} for the {academic_year} academic year in {level}.\n\nStudent ID Number: {student_id}\nAdmission/Student Number: {admission_number}\n\nWelcome to our school community!\n\nKind Regards,\nAdmissions Office";
    }
}
