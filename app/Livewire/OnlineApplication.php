<?php

namespace App\Livewire;

use App\Mail\ApplicationReceived;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Modules\Academics\Models\Course;
use Modules\Admin\Enums\EmailCategory;
use Modules\Admin\Models\SystemSetting;
use Modules\Admin\Services\TenantEmailConfigurationService;
use Modules\Admissions\Models\Application;

class OnlineApplication extends Component
{
    public $school;

    public string $first_name = '';

    public string $last_name = '';

    public string $gender = '';

    public string $date_of_birth = '';

    public string $parent_name = '';

    public string $parent_email = '';

    public string $parent_phone = '';

    public $course_id;

    public bool $isSubmitted = false;

    public string $generatedTrackingNumber = '';

    public function mount()
    {
        $this->school = App::make('current_tenant');
    }

    public function submit(): void
    {
        $schoolId = $this->school?->id;

        $this->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date|before:today',
            'parent_name' => 'required|string|max:255',
            'parent_email' => 'required|email|max:255',
            'parent_phone' => 'required|string|max:50',
            'course_id' => ['required', Rule::exists('courses', 'id')->where('school_id', $schoolId)],
        ]);

        do {
            $this->generatedTrackingNumber = 'APP-'.date('Y').'-'.strtoupper(Str::random(6));
        } while (Application::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('application_number', $this->generatedTrackingNumber)
            ->exists());

        $application = Application::create([
            'school_id' => $schoolId,
            'application_number' => $this->generatedTrackingNumber,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'parent_name' => $this->parent_name,
            'parent_email' => $this->parent_email,
            'parent_phone' => $this->parent_phone,
            'course_id' => $this->course_id,
            'status' => 'pending',
        ]);

        $this->notifyAdmissionsTeam($application);
        $this->isSubmitted = true;
    }

    private function notifyAdmissionsTeam(Application $application): void
    {
        try {
            $staff = User::withoutTenantScope()->where('school_id', $application->school_id)->get();
            if ($staff->isNotEmpty()) {
                Notification::send($staff, new NewApplicationNotification($application));
            }

            $admissionsEmail = SystemSetting::get('admission', 'contact_email', '');
            if (filter_var($admissionsEmail, FILTER_VALIDATE_EMAIL)) {
                app(TenantEmailConfigurationService::class)->send(
                    new ApplicationReceived($application, $admissionsEmail, $this->school->name ?? 'Our School'),
                    EmailCategory::Admissions,
                    $this->school,
                );
            }
        } catch (\Throwable $exception) {
            // The application has already been saved; notification delivery
            // must not turn a valid public submission into a failure.
            report($exception);
        }
    }

    public function render()
    {
        $courses = Course::withoutTenantScope()
            ->where('school_id', $this->school?->id)
            ->pluck('name', 'id');

        return view('livewire.online-application', compact('courses'))->layout('components.layouts.app');
    }
}
