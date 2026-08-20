<?php

namespace App\Livewire;

use App\Models\School;
use App\Models\User;
use App\Services\SchoolRegistrationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Admin\Models\SystemSetting;

class RegistrationWizard extends Component
{
    // Step tracking
    public int $currentStep = 1;

    public int $totalSteps = 3;

    // Step 1: Institution & Admin Info (NO username or password during initial registration)
    public string $schoolName = '';

    public string $country = '';

    public string $physicalAddress = '';

    public string $language = 'english';

    public string $institutionType = 'secondary';

    public string $otherInstitutionType = '';

    public string $phone = '';

    public string $adminName = '';

    public string $adminEmail = '';

    // Step 2: Subdomain & Sample Data
    public string $subdomain = '';

    public bool $hasDummyData = false;

    public bool $isSubdomainAvailable = true;

    public string $subdomainInvalidChars = '';

    // Step 3: Module Selection
    public array $selectedModules = [];

    // Modules that can never be switched off at registration. Their visibility
    // inside the workspace is controlled later via user permissions.
    public array $lockedModules = ['administration', 'saas'];

    // All top-level modules, sourced from the shared module catalog so the
    // registration picker matches the System Settings -> Manage Modules page.
    public array $availableModules = [];

    public function mount()
    {
        $this->availableModules = config('modules', []);

        // All modules are selected by default; users can uncheck the ones they
        // do not need (locked modules stay checked).
        $this->selectedModules = array_keys($this->availableModules);
    }

    public function selectAllModules(): void
    {
        $this->selectedModules = array_keys($this->availableModules);
    }

    public function clearModules(): void
    {
        $this->selectedModules = $this->lockedModules;
    }

    /**
     * The full, searchable list of countries available for selection.
     */
    public function getCountriesProperty(): array
    {
        return config('countries', []);
    }

    /**
     * Listen to changes in subdomain and run live validation.
     */
    public function updatedSubdomain($value)
    {
        // Format subdomain as slug
        $this->subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $value));

        // Track any characters that were stripped so the user can be told
        // exactly which characters are not allowed.
        $bad = preg_replace('/[a-zA-Z0-9-]/', '', $value ?? '');
        $this->subdomainInvalidChars = $bad === '' ? '' : collect(mb_str_split($bad))->unique()->implode('');

        if (empty($this->subdomain)) {
            $this->isSubdomainAvailable = false;
        } else {
            // Check if subdomain is already taken
            $this->isSubdomainAvailable = ! School::where('subdomain', $this->subdomain)->exists();
        }

        $this->validateField('subdomain');
    }

    // ─── Real-time (on-blur) per-field validation ───────────────────────────
    // Each hook runs the moment the user leaves the field, so problems are
    // flagged while filling the form instead of only on final submission.

    public function updatedSchoolName(): void
    {
        $this->validateField('schoolName');
    }

    public function updatedCountry(): void
    {
        $this->validateField('country');
    }

    public function updatedPhysicalAddress(): void
    {
        $this->validateField('physicalAddress');
    }

    public function updatedLanguage(): void
    {
        $this->validateField('language');
    }

    public function updatedInstitutionType(): void
    {
        $this->validateField('institutionType');
        $this->validateField('otherInstitutionType');
    }

    public function updatedOtherInstitutionType(): void
    {
        $this->validateField('otherInstitutionType');
    }

    public function updatedPhone(): void
    {
        $this->validateField('phone');
    }

    public function updatedAdminName(): void
    {
        $this->validateField('adminName');
    }

    public function updatedAdminEmail(): void
    {
        $this->validateField('adminEmail');
    }

    public function nextStep()
    {
        $this->validateStep();
        $this->currentStep++;
    }

    public function prevStep()
    {
        $this->currentStep--;
    }

    private function validateField(string $field): void
    {
        if (! array_key_exists($field, $this->fieldRules())) {
            return;
        }

        $this->validateOnly($field, [$field => $this->fieldRules()[$field]], $this->fieldMessages());
    }

    /**
     * Per-field validation rules. Kept in one place so the final submit check
     * and the real-time (on-blur) checks can never drift apart.
     */
    private function fieldRules(): array
    {
        return [
            'schoolName' => ['required', 'string', 'min:3', 'max:255', 'not_regex:/[<>]/'],
            'country' => ['required', 'string', Rule::in(config('countries', []))],
            'physicalAddress' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/'],
            'language' => ['required', 'string', 'max:50'],
            'institutionType' => ['required', 'string', 'max:100'],
            'otherInstitutionType' => ['required_if:institutionType,other', 'nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'phone' => ['required', 'regex:/^\+?[0-9()\- ]{7,20}$/', 'not_regex:/[<>]/'],
            'adminName' => ['required', 'string', 'min:3', 'max:255', 'not_regex:/[<>]/'],
            'adminEmail' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email,NULL,id,deleted_at,NULL'],
            'subdomain' => ['required', 'alpha_dash', 'min:3', 'max:50'],
        ];
    }

    private function fieldMessages(): array
    {
        return [
            'schoolName.required' => 'Please enter the institution name.',
            'schoolName.min' => 'The institution name must be at least 3 characters.',
            'schoolName.not_regex' => 'The school name may not contain HTML or script characters.',
            'country.required' => 'Please select your country.',
            'country.in' => 'Please select a valid country from the list.',
            'physicalAddress.required' => 'Please enter the physical address.',
            'physicalAddress.min' => 'The address must be at least 5 characters.',
            'physicalAddress.not_regex' => 'The address may not contain HTML or script characters.',
            'language.required' => 'Please choose the system language.',
            'institutionType.required' => 'Please choose the type of institution.',
            'otherInstitutionType.required_if' => 'Please specify the institution type.',
            'otherInstitutionType.not_regex' => 'The specified type may not contain HTML or script characters.',
            'phone.required' => 'Please enter a phone number.',
            'phone.regex' => 'Enter a valid phone number, e.g. +263 77 123 4567 (digits, spaces, plus, minus and parentheses only).',
            'adminName.required' => 'Please enter the administrator name.',
            'adminName.min' => 'The administrator name must be at least 3 characters.',
            'adminName.not_regex' => 'The administrator name may not contain HTML or script characters.',
            'adminEmail.required' => 'Please enter the administrator email.',
            'adminEmail.email' => 'Enter a valid email address, e.g. name@example.com.',
            'adminEmail.unique' => 'This email is already registered.',
            'subdomain.required' => 'Please choose a subdomain.',
            'subdomain.alpha_dash' => 'Only letters, numbers, dashes and underscores are allowed.',
            'subdomain.min' => 'The subdomain must be at least 3 characters.',
            'subdomain.max' => 'The subdomain may not be longer than 50 characters.',
        ];
    }

    private function validateStep()
    {
        if ($this->currentStep === 1) {
            $rules = [];
            foreach (['schoolName', 'country', 'physicalAddress', 'language', 'institutionType', 'otherInstitutionType', 'phone', 'adminName', 'adminEmail'] as $field) {
                $rules[$field] = $this->fieldRules()[$field];
            }

            $this->validate($rules, $this->fieldMessages());
        }

        if ($this->currentStep === 2) {
            $this->validate(['subdomain' => $this->fieldRules()['subdomain']], $this->fieldMessages());

            if (! $this->isSubdomainAvailable) {
                $this->addError('subdomain', 'This subdomain is already taken.');
                throw ValidationException::withMessages([
                    'subdomain' => 'This subdomain is already taken.',
                ]);
            }
        }
    }

    public function submit()
    {
        $this->validateStep();

        // If this subdomain was previously registered and soft-deleted, its
        // tombstone still occupies the unique subdomain index. Purge it (and
        // its users) so the school can be re-registered from scratch.
        School::onlyTrashed()
            ->where('subdomain', $this->subdomain)
            ->get()
            ->each->forceDelete();

        // 1. Create the school record as PENDING APPROVAL. It only becomes
        //    active once a platform administrator approves it (which sends the
        //    activation email) and the contact then completes activation.
        $school = School::create([
            'name' => $this->schoolName,
            'country' => $this->country,
            'physical_address' => $this->physicalAddress,
            'language' => $this->language,
            'institution_type' => $this->institutionType,
            'other_institution_type' => $this->otherInstitutionType,
            'phone' => $this->phone,
            'subdomain' => $this->subdomain,
            'status' => 'pending',
            'has_dummy_data' => $this->hasDummyData,
            'settings' => [
                'enabled_modules' => $this->selectedModules,
            ],
        ]);

        // 1b. Persist the module visibility toggles so the selected modules
        //     actually drive what is visible inside the new school's workspace
        //     (same System Settings -> Manage Modules keys). Unselected modules
        //     are written as '0' (hidden). Locked modules are always enabled.
        foreach (array_keys(config('modules', [])) as $moduleKey) {
            $enabled = in_array($moduleKey, $this->selectedModules, true)
                || in_array($moduleKey, $this->lockedModules, true);

            SystemSetting::withoutTenantScope()->updateOrCreate(
                [
                    'school_id' => $school->id,
                    'group' => 'modules',
                    'key' => $moduleKey,
                ],
                [
                    'value' => $enabled ? '1' : '0',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // 2. Create the initial administrative account with PENDING status
        //    No username or password is collected at this stage; the contact
        //    sets both during account activation. The activation email is NOT
        //    sent here — it is sent when a platform administrator approves the
        //    institution from the admin panel.
        $adminUser = User::create([
            'school_id' => $school->id,
            'name' => $this->adminName,
            'email' => mb_strtolower(trim($this->adminEmail)),
            'username' => null,
            'password' => Hash::make(Str::random(64)), // Temporary placeholder until account activation
            'requested_role' => 'administrator',
            'account_status' => User::STATUS_PENDING,
        ]);

        // 3. Notify the platform super administrators (in-app + email) so they
        //    are aware a new school is awaiting approval. Approving the school
        //    from the admin panel triggers the activation email.
        try {
            app(SchoolRegistrationService::class)->notifySuperAdmin($school, $adminUser);
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('registration.success', ['school_name' => $this->schoolName]);
    }

    public function render()
    {
        return view('livewire.registration-wizard');
    }
}
