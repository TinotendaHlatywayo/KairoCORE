<?php

namespace App\Livewire;

use App\Jobs\ProcessSchoolRegistrationJob;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Modules\Admin\Models\SystemSetting;

class RegistrationWizard extends Component
{
    // Standard "I agree" checkbox — must be ticked before submitting
    public bool $termsAccepted = false;

    // Step tracking
    public int $currentStep = 1;

    public int $totalSteps = 3;

    // Step 1: Institution & Admin Info
    public string $schoolName = '';

    public string $country = '';

    public string $physicalAddress = '';

    public string $language = 'english';

    public string $institutionType = 'secondary';

    public string $otherInstitutionType = '';

    public string $phone = '';

    public string $adminName = '';

    public string $adminEmail = '';

    public string $institutionEmail = '';

    // Step 2: Subdomain & Sample Data
    public string $subdomain = '';

    public bool $hasDummyData = false;

    public bool $isSubdomainAvailable = true;

    public string $subdomainInvalidChars = '';

    // Step 3: Module Selection
    public array $selectedModules = [];

    // Modules that can never be switched off
    public array $lockedModules = ['administration', 'saas'];

    public array $availableModules = [];

    public function mount()
    {
        if (config('tenancy.mode') === 'single') {
            abort(404);
        }

        $this->availableModules = config('modules', []);
        $this->selectedModules = array_keys($this->availableModules);
    }

    // ── Module helpers ──────────────────────────────────────────────────────

    public function selectAllModules(): void
    {
        $this->selectedModules = array_keys($this->availableModules);
    }

    public function clearModules(): void
    {
        $this->selectedModules = $this->lockedModules;
    }

    public function getCountriesProperty(): array
    {
        return config('countries', []);
    }

    /**
     * Host-only base domain (e.g. "lvh.me" locally, "kairocore.com" in
     * production) so subdomain previews never render a scheme inside the URL.
     */
    public function getBaseDomainProperty(): string
    {
        $url = (string) config('app.url', 'https://lvh.me');
        $host = parse_url($url, PHP_URL_HOST)
            ?? preg_replace('#^[a-z][a-z0-9+.-]*://#i', '', $url);

        return trim($host ?: 'lvh.me', '/');
    }

    /** Full portal URL the visitor will get once approved. */
    public function getSubdomainPreviewUrlProperty(): string
    {
        if ($this->subdomain === '') {
            return 'https://' . $this->baseDomain;
        }

        return 'https://' . $this->subdomain . '.' . $this->baseDomain;
    }

    // ── Real-time (on-change / on-blur) per-field validation ───────────────

    public function updatedSubdomain($value)
    {
        $this->subdomain = strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $value));
        $bad = preg_replace('/[a-zA-Z0-9-]/', '', $value ?? '');
        $this->subdomainInvalidChars = $bad === '' ? '' : collect(mb_str_split($bad))->unique()->implode('');

        if (empty($this->subdomain)) {
            $this->isSubdomainAvailable = false;
        } else {
            $this->isSubdomainAvailable = ! School::where('subdomain', $this->subdomain)->exists();
        }

        $this->validateField('subdomain');
    }

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

    public function updatedInstitutionEmail(): void
    {
        $this->validateField('institutionEmail');
    }

    // ── Step navigation ─────────────────────────────────────────────────────

    public function nextStep()
    {
        $this->validateStep();
        $this->currentStep++;
    }

    public function prevStep()
    {
        $this->currentStep--;
    }

    // ── Validation engine ───────────────────────────────────────────────────

    private function validateField(string $field): void
    {
        if (! array_key_exists($field, $this->fieldRules())) {
            return;
        }

        $this->validateOnly($field, [$field => $this->fieldRules()[$field]], $this->fieldMessages());
    }

    private function fieldRules(): array
    {
        return [
            'schoolName' => [
                'required', 'string', 'min:3', 'max:150',
                'not_regex:/[<>{}[\]\\\\]/',
                'regex:/^[A-Za-z0-9\s\.\-\,\&\'\"]+$/',
            ],
            'country' => ['required', 'string', Rule::in(config('countries', []))],
            'physicalAddress' => ['required', 'string', 'min:5', 'max:500', 'not_regex:/[<>]/'],
            'language' => ['required', 'string', 'max:50'],
            'institutionType' => ['required', 'string', 'max:100'],
            'otherInstitutionType' => ['required_if:institutionType,other', 'nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
            'phone' => ['required', 'regex:/^\+?[0-9()\- ]{7,20}$/', 'not_regex:/[<>]/'],
            'adminName' => ['required', 'string', 'min:3', 'max:100', 'not_regex:/[<>{}]/', 'regex:/^[A-Za-z\s\.\-\']+$/'],
            'adminEmail' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email,NULL,id,deleted_at,NULL'],
            'institutionEmail' => ['required', 'email:rfc,dns', 'max:255', 'different:adminEmail'],
            'subdomain' => ['required', 'alpha_dash', 'min:3', 'max:50', Rule::notIn([
                'www', 'platform', 'workspace', 'student', 'api', 'mail', 'smtp', 'ftp', 'admin', 'app', 'root', 'ns1', 'ns2', 'cdn', 'static', 'assets', 'localhost', 'saas', 'paynow', 'webhook', 'cms', 'auth', 'sso', 'login', 'logout', 'register', 'activation', 'verify', 'password', 'email', 'profile', 'settings', 'dashboard', 'home', 'about', 'contact', 'terms', 'privacy', 'blog', 'news', 'support', 'help', 'docs', 'documentation', 'status', 'health', 'metrics', 'monitoring',
            ])],
            'termsAccepted' => ['accepted'],
        ];
    }

    private function fieldMessages(): array
    {
        return [
            'schoolName.required' => __('Please enter the institution name.'),
            'schoolName.min' => __('The institution name must be at least 3 characters.'),
            'schoolName.max' => __('The institution name must not exceed 150 characters.'),
            'schoolName.not_regex' => __('The institution name may not contain HTML, script tags, or special characters like <, >, {, }, [, ].'),
            'schoolName.regex' => __('The institution name may only contain letters, numbers, spaces, periods, hyphens, commas, ampersands, and quotes.'),
            'country.required' => __('Please select your country.'),
            'country.in' => __('Please select a valid country from the list.'),
            'physicalAddress.required' => __('Please enter the physical address.'),
            'physicalAddress.min' => __('The address must be at least 5 characters.'),
            'physicalAddress.not_regex' => __('The address may not contain HTML or script characters.'),
            'language.required' => __('Please choose the system language.'),
            'institutionType.required' => __('Please choose the type of institution.'),
            'otherInstitutionType.required_if' => __('Please specify the institution type.'),
            'otherInstitutionType.not_regex' => __('The specified type may not contain HTML or script characters.'),
            'phone.required' => __('Please enter a phone number.'),
            'phone.regex' => __('Enter a valid international phone number, e.g. +1 234 567 8901 or +263 77 123 4567.'),
            'adminName.required' => __('Please enter the administrator full name.'),
            'adminName.min' => __('The administrator name must be at least 3 characters.'),
            'adminName.max' => __('The administrator name must not exceed 100 characters.'),
            'adminName.not_regex' => __('The administrator name may not contain numbers, HTML, or script characters.'),
            'adminName.regex' => __('The administrator name may only contain letters, spaces, periods, hyphens and apostrophes.'),
            'adminEmail.required' => __('Please enter the administrator email address.'),
            'adminEmail.email' => __('Please enter a valid email address, e.g. name@example.com.'),
            'adminEmail.unique' => __('This email address is already registered.'),
            'institutionEmail.required' => __('Please enter the institution email address.'),
            'institutionEmail.email' => __('Please enter a valid institution email, e.g. info@schoolname.edu.'),
            'institutionEmail.different' => __('The institution email must be different from the administrator email.'),
            'subdomain.required' => __('Please choose a subdomain.'),
            'subdomain.alpha_dash' => __('Only lowercase letters, numbers, dashes and underscores are allowed.'),
            'subdomain.min' => __('The subdomain must be at least 3 characters.'),
            'subdomain.max' => __('The subdomain must not exceed 50 characters.'),
            'subdomain.not_in' => __('This subdomain is reserved and cannot be used.'),
            'termsAccepted.accepted' => __('You must read and agree to the Terms of Service and Terms of Use before registering.'),
        ];
    }

    private function validateStep(): void
    {
        if ($this->currentStep === 1) {
            $step1Fields = [
                'schoolName', 'country', 'physicalAddress', 'language',
                'institutionType', 'otherInstitutionType', 'phone',
                'adminName', 'adminEmail', 'institutionEmail',
            ];
            $rules = [];
            foreach ($step1Fields as $field) {
                $rules[$field] = $this->fieldRules()[$field];
            }
            $this->validate($rules, $this->fieldMessages());
        }

        if ($this->currentStep === 2) {
            $this->validate(
                ['subdomain' => $this->fieldRules()['subdomain']],
                $this->fieldMessages()
            );

            if (! $this->isSubdomainAvailable) {
                $this->addError('subdomain', __('This subdomain is already taken.'));
                throw ValidationException::withMessages([
                    'subdomain' => __('This subdomain is already taken.'),
                ]);
            }
        }
    }

    // ── Form submission ─────────────────────────────────────────────────────

    public function submit()
    {
        if (config('tenancy.mode') === 'single') {
            abort(404);
        }

        $this->validateStep();

        if (! $this->termsAccepted) {
            throw ValidationException::withMessages([
                'termsAccepted' => __('You must read and agree to the Terms of Service and Terms of Use before registering.'),
            ]);
        }

        // A live (non-deleted) school already owns this subdomain — surface a
        // friendly validation error instead of a raw SQL unique-constraint 500.
        if (School::where('subdomain', $this->subdomain)->exists()) {
            $this->isSubdomainAvailable = false;
            throw ValidationException::withMessages([
                'subdomain' => __('This subdomain is already taken.'),
            ]);
        }

        // Soft-deleted registrations with the same subdomain still occupy the
        // unique index, so purge them before creating the new school.
        School::onlyTrashed()
            ->where('subdomain', $this->subdomain)
            ->get()
            ->each->forceDelete();

        $localeMap = [
            'english' => 'en',
            'spanish' => 'es',
            'french' => 'fr',
            'portuguese' => 'pt',
            'shona' => 'sn',
            'swahili' => 'sw',
        ];
        $schoolLocale = $localeMap[strtolower($this->language)] ?? 'en';

        // Everything below is atomic and intentionally FAST: we only persist the
        // core pending-school row, its module settings and the pending admin user.
        // The heavy work (optional demo-data seeding + super-admin notification)
        // is pushed to a background queue so the applicant sees an immediate
        // "request sent — awaiting approval" screen instead of a long spinner.
        $hasDummyData = $this->hasDummyData;

        $school = DB::transaction(function () use ($schoolLocale) {
            $school = School::create([
                'name' => $this->schoolName,
                'country' => $this->country,
                'physical_address' => $this->physicalAddress,
                'language' => $this->language,
                'locale' => $schoolLocale,
                'institution_type' => $this->institutionType,
                'other_institution_type' => $this->otherInstitutionType,
                'phone' => $this->phone,
                'email_address' => mb_strtolower(trim($this->institutionEmail)),
                'subdomain' => $this->subdomain,
                'status' => 'pending',
                'has_dummy_data' => $this->hasDummyData,
                'settings' => [
                    'enabled_modules' => $this->selectedModules,
                ],
            ]);

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
                    ]
                );
            }

            User::create([
                'school_id' => $school->id,
                'name' => $this->adminName,
                'email' => mb_strtolower(trim($this->adminEmail)),
                'username' => null,
                'locale' => $schoolLocale,
                'password' => Hash::make(Str::random(64)),
                'requested_role' => 'administrator',
                'account_status' => User::STATUS_PENDING,
            ]);

            return $school;
        });

        // Background completion: demo-data seeding + super-admin notification.
        ProcessSchoolRegistrationJob::dispatch($school->id, $hasDummyData);

        return redirect()->route('registration.success', ['school_name' => $this->schoolName]);
    }

    public function render()
    {
        return view('livewire.registration-wizard');
    }
}
