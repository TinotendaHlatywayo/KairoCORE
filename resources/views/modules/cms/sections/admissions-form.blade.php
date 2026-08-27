<div x-data="{ showModal: {{ session('application_ref') ? 'true' : 'false' }} }">

    @php
        $initialStep = 1;
        if (isset($errors) && $errors->any()) {
            $initialStep = $errors->hasAny(['first_name','last_name','gender','date_of_birth','course_id','applying_year','applying_term']) ? 1
                : ($errors->hasAny(['parent_name','parent_email','parent_phone']) ? 2 : 3);
        }

        // Old input can be polluted with arrays (e.g. malformed/malicious
        // submissions to the public form). Never echo it raw into e().
        $oldInput = function (string $key, string $default = ''): string {
            $value = old($key, $default);

            return is_array($value) ? $default : (string) $value;
        };

        $schoolId = $school->id ?? app('current_tenant')?->id;

        $levels = \Modules\Academics\Models\Course::withoutTenantScope()
            ->where('school_id', $schoolId)->pluck('name', 'id');

        $years = \Modules\Academics\Models\AcademicYear::withoutTenantScope()
            ->where('school_id', $schoolId)->orderBy('id', 'desc')->pluck('name', 'name');

        $courseEntryMap = [];
        foreach ($levels as $id => $name) {
            $courseEntryMap[$id] = \Modules\Admissions\Models\Application::isEntryLevel($name);
        }

        $transferLetterRequired = (bool) \Modules\Admin\Models\SystemSetting::get('admission', 'transfer_letter_required', false);

        $successTitle = \Modules\Admin\Models\SystemSetting::get('admission', 'success_title', 'Application Submitted!');
        $successMessage = \Modules\Admin\Models\SystemSetting::get('admission', 'success_message', 'Your online application has been submitted successfully! Save your tracking reference below to monitor your application status.');
    @endphp

    <div class="sc-card" style="max-width: 48rem; margin-inline: auto; padding: clamp(1.5rem, 5vw, 3rem); @if(!empty($v['bg_color']))background-color: {{ $v['bg_color'] }};@endif @if(!empty($v['bg_image']))background-image: url('{{ $v['bg_image'] }}'); background-size: cover; background-position: center;@endif">

        <div style="text-align: center; margin-bottom: 2rem;">
            <span class="sc-eyebrow" style="justify-content: center;">{{ __('Enrollment Open') }}</span>
            @php
                $admTitle = !empty($block['title']) ? $block['title'] : __('Apply to :school', ['school' => $school->name ?? __('Our School')]);
                $admSubtitle = !empty($block['subtitle']) ? $block['subtitle'] : __('Please register student details below for the :school admissions review.', ['school' => $school->name ?? __('Our School')]);
            @endphp
            <h2 class="sc-section-title" style="font-size: clamp(1.6rem, 1.2rem + 1.6vw, 2.25rem); {{ $v['titleStyle'] ?? '' }}">{{ $admTitle }}</h2>
            <p class="sc-muted" style="margin-top: 0.6rem;">{{ $admSubtitle }}</p>
        </div>

        <!-- System Alerts -->
        @if(session('error'))
            <div class="sc-alert sc-alert-error" style="margin-bottom: 1.25rem;">
                {{ session('error') }}
            </div>
        @endif

        @if(isset($errors) && $errors->any())
            <div class="sc-alert sc-alert-error" style="margin-bottom: 1.25rem;">
                <p style="font-weight: 800; margin-bottom: 0.35rem;">{{ __('Please fix the following errors before submitting:') }}</p>
                <ul style="list-style: disc; padding-left: 1.25rem; font-weight: 600;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('cms-apply-submit') }}" method="POST" enctype="multipart/form-data"
              x-data="admissionWizard(@js($initialStep), @js($courseEntryMap), @js($transferLetterRequired))">
            @csrf
            <input type="hidden" name="school_id" value="{{ $school->id ?? app('current_tenant')?->id }}">

            {{-- Honeypot trap: invisible to humans, filled by spam bots. Uses a
                 session-random field name so browser autofill (Chrome) and bots
                 that target a fixed field name cannot pre-fill it. --}}
            @php($hpName = honeypot_field_name())
            <div style="position: absolute; left: -9999px; top: -9999px; display: none;" aria-hidden="true">
                <label for="{{ $hpName }}">{{ __('Leave this field empty') }}</label>
                <input type="text" id="{{ $hpName }}" name="{{ $hpName }}" tabindex="-1" autocomplete="off" class="hp-fill-guard" readonly onfocus="this.removeAttribute('readonly')">
            </div>

            <!-- Step indicator -->
            <div class="sc-wizard-steps">
                <div class="sc-wizard-step" :class="step >= 1 ? 'is-active' : ''">
                    <span class="sc-wizard-dot">1</span>
                    <span>{{ __('Student Profile') }}</span>
                </div>
                <div class="sc-wizard-line" :class="step > 1 ? 'is-done' : ''"></div>
                <div class="sc-wizard-step" :class="step >= 2 ? 'is-active' : ''">
                    <span class="sc-wizard-dot">2</span>
                    <span>{{ __('Parent / Guardian') }}</span>
                </div>
                <div class="sc-wizard-line" :class="step > 2 ? 'is-done' : ''"></div>
                <div class="sc-wizard-step" :class="step >= 3 ? 'is-active' : ''">
                    <span class="sc-wizard-dot">3</span>
                    <span>{{ __('Documents') }}</span>
                </div>
            </div>

            <div x-show="stepError" x-transition x-cloak class="sc-alert sc-alert-error" style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 1.25rem;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span x-text="stepError"></span>
            </div>

            <!-- STEP 1: Student Profile & Level -->
            <div x-show="step === 1" x-transition data-step="1">
                <h3 class="sc-step-heading">{{ __('') }}</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem;">
                    <div>
                        <label class="sc-label">{{ __('First Name') }} <span class="sc-required">*</span></label>
                        <input type="text" name="first_name" value="{{ $oldInput('first_name') }}" required maxlength="40" data-type="name"
                               placeholder="e.g. John"
                               class="sc-input">
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Last Name') }} <span class="sc-required">*</span></label>
                        <input type="text" name="last_name" value="{{ $oldInput('last_name') }}" required maxlength="40" data-type="name"
                               placeholder="e.g. Doe"
                               class="sc-input">
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Gender') }} <span class="sc-required">*</span></label>
                        <select name="gender" required class="sc-select">
                            <option value="">{{ __('Select gender') }}</option>
                            <option value="male" @selected(old('gender') === 'male')>{{ __('Male') }}</option>
                            <option value="female" @selected(old('gender') === 'female')>{{ __('Female') }}</option>
                            <option value="other" @selected(old('gender') === 'other')>{{ __('Other') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Date of Birth') }} <span class="sc-required">*</span></label>
                        <input type="date" name="date_of_birth" value="{{ $oldInput('date_of_birth') }}" required max="{{ date('Y-m-d') }}"
                               class="sc-input">
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Year Applying For') }} <span class="sc-required">*</span></label>
                        <select name="applying_year" required class="sc-select">
                            <option value="">{{ __('Select academic year') }}</option>
                            @foreach($years as $value => $label)
                                <option value="{{ $value }}" @selected(old('applying_year') == $value)>{{ $label }}</option>
                            @endforeach
                            @if($years->isEmpty())
                                <option value="{{ date('Y') }}" @selected(old('applying_year') == date('Y'))>{{ date('Y') }}</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label class="sc-label">Term (Optional)</label>
                        <select name="applying_term" class="sc-select">
                            <option value="">Select term (optional)</option>
                            <option value="Term 1" @selected(old('applying_term') === 'Term 1')>{{ __('Term 1') }}</option>
                            <option value="Term 2" @selected(old('applying_term') === 'Term 2')>{{ __('Term 2') }}</option>
                            <option value="Term 3" @selected(old('applying_term') === 'Term 3')>{{ __('Term 3') }}</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label class="sc-label">{{ __('Level Applying For') }} <span class="sc-required">*</span></label>
                        <select name="course_id" required class="sc-select"
                                @change="levelChanged($event.target.value)">
                            <option value="">{{ __('Select the level / form') }}</option>
                            @foreach($levels as $value => $label)
                                <option value="{{ $value }}" @selected(old('course_id') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Parent / Guardian -->
            <div x-show="step === 2" x-transition x-cloak data-step="2">
                <h3 class="sc-step-heading">{{ __('2. Parent / Guardian Contact Details') }}</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem;">
                    <div style="grid-column: 1 / -1;">
                        <label class="sc-label">{{ __('Full Parent/Guardian Name') }} <span class="sc-required">*</span></label>
                        <input type="text" name="parent_name" value="{{ $oldInput('parent_name') }}" required maxlength="70" data-type="name"
                               placeholder="e.g. Jane Doe"
                               class="sc-input">
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Primary Email Address') }} <span class="sc-required">*</span></label>
                        <input type="email" name="parent_email" x-model="parentEmail" @input="validateRealTimeEmail()" required maxlength="255"
                               placeholder="e.g. name@domain.com"
                               class="sc-input"
                               :class="emailFormatError ? 'is-error' : ''">
                        <p x-show="emailFormatError" x-cloak class="sc-form-hint is-error">{{ __('Please enter a valid email address.') }}</p>
                    </div>
                    <div>
                        <label class="sc-label">{{ __('Primary Phone Number') }} <span class="sc-required">*</span></label>
                        <input type="tel" name="parent_phone" x-model="parentPhone" @input="validateRealTimePhone()" required maxlength="30"
                               placeholder="e.g. 0786366855 or +263771234567"
                               class="sc-input"
                               :class="phoneFormatError ? 'is-error' : ''">
                        <p x-show="phoneFormatError" x-cloak class="sc-form-hint is-error">{{ __('Local: 10 digits starting with 0. Intl: Starts with + and 10-12 digits.') }}</p>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Supporting Documents -->
            <div x-show="step === 3" x-transition x-cloak data-step="3">
                <h3 class="sc-step-heading">{{ __('3. Supporting Documents') }}</h3>
                @php($documentGuidelines = \Modules\Admin\Models\SystemSetting::get(
                    'admission',
                    'document_guidelines',
                    'Upload PDF files only (max 5MB each). Birth certificate is required; certificates or result slips are highly recommended for secondary level applications.'
                ))
                <p class="sc-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">{{ $documentGuidelines }}</p>

                <div id="document-rows" style="display: flex; flex-direction: column; gap: 1rem;"></div>

                <button type="button" @click="addRow()" class="sc-btn sc-btn-ghost" style="margin-top: 1rem;">
                    <span aria-hidden="true">＋</span> {{ __('Add another document') }}
                </button>

                <!-- Verified transfer letter -->
                <div x-show="!isEntryLevel" x-transition style="margin-top: 1.5rem; border: 1px solid color-mix(in srgb, var(--sc-accent) 40%, transparent); background: color-mix(in srgb, var(--sc-accent) 9%, transparent); border-radius: var(--sc-radius); padding: 1.25rem;">
                    <h4 class="sc-step-heading">{{ __('Verified Transfer Letter') }}</h4>
                    <p class="sc-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">{{ __('Because the applicant is joining outside an entry level, a verified transfer letter from the previous school is requested.') }}</p>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem;">
                        <div>
                            <label class="sc-label">{{ __('Document Title') }}</label>
                            <input type="text" name="transfer_letter_title" value="Verified Transfer Letter" readonly
                                   class="sc-input" style="background: color-mix(in srgb, var(--sc-text) 6%, transparent); color: color-mix(in srgb, var(--sc-text) 60%, transparent); cursor: not-allowed;">
                        </div>
                        <div>
                            <label class="sc-label">{{ __('Upload Transfer Letter') }} <span x-show="transferRequired && !isEntryLevel" class="sc-required">*</span></label>
                            <input type="file" name="transfer_letter" accept=".pdf,application/pdf,.jpg,.jpeg,.png,image/jpeg,image/png" data-type="file"
                                   :required="transferRequired && !isEntryLevel"
                                   class="sc-input">
                            <p class="sc-form-hint">{{ __('PDF files only. Max 5MB per file.') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div style="display: flex; justify-content: space-between; gap: 1rem; margin-top: 1.5rem;">
                <button type="button" x-show="step > 1" @click="prev()"
                        class="sc-btn sc-btn-surface" style="width: auto;">
                    &#8592; {{ __('Back') }}
                </button>
                <template x-if="step < 3">
                    <button type="button" @click="next()" class="sc-btn sc-btn-primary" style="width: auto; margin-left: auto;">
                        {{ __('Next') }} &#8594;
                    </button>
                </template>
                <template x-if="step === 3">
                    <button type="submit" @click.prevent="submitForm($event)" class="sc-btn sc-btn-primary" style="width: auto; margin-left: auto; padding-inline: 1.75rem;">
                        {{ __('Submit Online Application') }}
                    </button>
                </template>
            </div>
        </form>
    </div>

    <script>
        function admissionWizard(initialStep, courseEntryMap, transferRequired) {
            return {
                step: parseInt(initialStep) || 1,
                docIndex: 0,
                isEntryLevel: true,
                transferRequired: transferRequired ?? true,
                stepError: null,

                parentEmail: '{{ $oldInput('parent_email') }}',
                emailFormatError: false,

                parentPhone: '{{ $oldInput('parent_phone') }}',
                phoneFormatError: false,

                // REGEX rules
                // Names: allow letters (incl. accents), spaces, hyphens, apostrophes and dots
                nameRegex: /^[\p{L}\s\-'.]+$/u,
                emailRegex: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
                // Phone Rule: allow digits, +, spaces, hyphens, parentheses and dots (7-20 chars)
                phoneRegex: /^[+0-9\s\-().]{7,20}$/,

                next() {
                    if (this.validateStep(this.step)) {
                        if (this.step < 3) {
                            this.step++;
                            this.stepError = null;
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    }
                },
                prev() {
                    if (this.step > 1) {
                        this.step--;
                        this.stepError = null;
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                },
                levelChanged(id) {
                    this.isEntryLevel = courseEntryMap[id] !== undefined ? courseEntryMap[id] : true;
                },

                validateRealTimeEmail() {
                    if(this.parentEmail.trim() === '') {
                        this.emailFormatError = false;
                        return;
                    }
                    this.emailFormatError = !this.emailRegex.test(this.parentEmail);
                },

                validateRealTimePhone() {
                    if(this.parentPhone.trim() === '') {
                        this.phoneFormatError = false;
                        return;
                    }
                    this.phoneFormatError = !this.phoneRegex.test(this.parentPhone);
                },

                validateStep(n) {
                    const pane = document.querySelector('[data-step="' + n + '"]');
                    if (!pane) return true;

                    let valid = true;
                    let firstInvalid = null;
                    this.stepError = null;

                    // 1. Check required fields are filled
                    const requiredFields = pane.querySelectorAll('input[required], select[required]');
                    requiredFields.forEach(field => {
                        if (!field.value || field.value.trim() === '') {
                            this.markInvalid(field);
                            valid = false;
                            if (!firstInvalid) firstInvalid = field;
                            this.stepError = 'Please complete all required fields (*) before proceeding.';
                        } else {
                            this.markValid(field);
                        }
                    });

                    // 2. Validate Names (No numbers allowed)
                    // Document titles are free text (they may contain years
                    // and digits, e.g. "2024 Report"), so they are exempt.
                    const nameFields = pane.querySelectorAll('input[data-type="name"]');
                    nameFields.forEach(field => {
                        if (field.classList.contains('document-title')) return;
                        if (field.value.trim() !== '' && !this.nameRegex.test(field.value)) {
                            this.markInvalid(field);
                            valid = false;
                            if (!firstInvalid) firstInvalid = field;
                            this.stepError = 'Names can only contain letters, spaces, hyphens, and apostrophes.';
                        }
                    });

                    // 3. Validate Email and Phone (Step 2)
                    if (n === 2) {
                        this.validateRealTimeEmail();
                        if (this.emailFormatError) {
                            valid = false;
                            if (!firstInvalid) firstInvalid = pane.querySelector('input[type="email"]');
                            this.stepError = 'Please provide a valid email address (e.g. name@domain.com).';
                        }

                        this.validateRealTimePhone();
                        if (this.phoneFormatError) {
                            valid = false;
                            if (!firstInvalid) firstInvalid = pane.querySelector('input[type="tel"]');
                            this.stepError = 'Invalid phone number format.';
                        }
                    }

                    // 4. Transfer letter required for non-entry levels.
                    if (n === 3) {
                        const transferInput = pane.querySelector('input[name="transfer_letter"]');
                        if (transferInput && transferInput.required && (!transferInput.files || transferInput.files.length === 0)) {
                            this.markInvalid(transferInput);
                            valid = false;
                            if (!firstInvalid) firstInvalid = transferInput;
                            this.stepError = 'A verified transfer letter is required for this level. Please upload a PDF, JPG or PNG in the "Verified Transfer Letter" section.';
                        }
                    }

                    // 5. Validate Files (Only PDF)
                    if (n === 3) {
                        const fileInputs = pane.querySelectorAll('input[type="file"]');
                        const allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
                        const maxSize = 10 * 1024 * 1024; // 10MB

                        fileInputs.forEach(field => {
                            if (field.files && field.files.length > 0) {
                                const file = field.files[0];
                                const extension = file.name.split('.').pop().toLowerCase();

                                if (!allowedExtensions.includes(extension)) {
                                    this.markInvalid(field);
                                    valid = false;
                                    if (!firstInvalid) firstInvalid = field;
                                    this.stepError = 'Security Error: Only PDF, JPG, JPEG and PNG files are allowed.';
                                } else if (file.size > maxSize) {
                                    this.markInvalid(field);
                                    valid = false;
                                    if (!firstInvalid) firstInvalid = field;
                                    this.stepError = 'File size error: Uploads must be smaller than 10MB.';
                                } else {
                                    this.markValid(field);
                                }
                            }
                        });

                        // Supporting documents are optional. When supplied,
                        // they are still checked above before submission.
                    }

                    if (!valid && firstInvalid) {
                        firstInvalid.focus();
                    }

                    return valid;
                },

                submitForm(event) {
                    if(this.validateStep(3)) {
                        event.target.closest('form').submit();
                    }
                },

                markInvalid(field) {
                    field.classList.add('is-error');
                },

                markValid(field) {
                    field.classList.remove('is-error');
                },

                addRow() {
                    this.docIndex++;
                    const container = document.querySelector('#document-rows');

                    const row = document.createElement('div');
                    row.className = 'document-row sc-upload-row';
                    row.style.cssText = 'display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem; padding: 1.25rem; border-radius: var(--sc-radius);';
                    row.innerHTML = `
                        <div>
                            <label class="sc-label">{{ __('Document Title / Name (required)') }}</label>
                            <input type="text" name="documents[${this.docIndex}][title]" maxlength="100"
                                   class="document-title sc-input" placeholder="{{ __('e.g., Birth Certificate') }}">
                        </div>
                        <div>
                            <label class="sc-label">{{ __('Upload File') }} <span class="sc-required">*</span></label>
                            <input type="file" name="documents[${this.docIndex}][file]" accept=".pdf,application/pdf,.jpg,.jpeg,.png,image/jpeg,image/png"
                                   class="sc-input">
                            <p class="sc-form-hint">{{ __('PDF, JPG or PNG. Max 10MB per file.') }}</p>
                        </div>
                        <div style="grid-column: 1 / -1; display: flex; justify-content: flex-end;">
                            <button type="button" class="remove-row sc-file-btn" style="background: none; border: none; cursor: pointer; color: #e11d48;">
                                <svg xmlns="http://www.w3.org/2000/svg" style="display: inline-block; margin-right: 0.25rem;" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                {{ __('Remove this document') }}
                            </button>
                        </div>
                    `;

                    row.querySelector('.remove-row').addEventListener('click', function() {
                        row.remove();
                    });

                    container.appendChild(row);
                }
            };
        }
    </script>

    {{-- Success popup modal --}}
    <div x-cloak x-show="showModal" class="sc-modal" role="dialog" aria-modal="true" aria-label="{{ $successTitle }}"
         x-transition.opacity>
        <div class="sc-modal-backdrop" @click="showModal = false"></div>
        <div class="sc-modal-card" x-transition x-transition.scale.90>
            <button type="button" @click="showModal = false"
                    style="position: absolute; top: 0.75rem; right: 0.75rem; background: none; border: none; cursor: pointer; color: var(--sc-muted, #6b7280); font-size: 1.25rem; line-height: 1; padding: 0.25rem;"
                    aria-label="{{ __('Close') }}">&#10005;</button>
            <div class="sc-modal-check">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="sc-section-title" style="font-size: 1.5rem; color: var(--sc-text);">{{ $successTitle }}</h3>
            <p class="sc-muted" style="font-size: 0.9rem; margin-top: 0.6rem;">
                {{ $successMessage }}
            </p>
            <div class="sc-ref-box">
                <span style="display: block; font-size: 0.68rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.6; margin-bottom: 0.35rem;">{{ __('Application Tracking Ref') }}</span>
                <span style="display: block; font-size: 1.15rem; font-weight: 700; font-family: ui-monospace, monospace; letter-spacing: 0.08em; word-break: break-all; color: var(--sc-text);">{{ session('application_ref') }}</span>
                <button type="button" onclick="navigator.clipboard?.writeText(this.previousElementSibling.textContent.trim()); this.textContent = '✓ Copied'; setTimeout(() => this.textContent = 'Copy Ref', 1800);"
                        class="sc-btn sc-btn-primary" style="margin-top: 0.85rem; width: auto;">
                    {{ __('Copy Ref') }}
                </button>
            </div>
            <button type="button" @click="showModal = false"
                    class="sc-btn sc-btn-surface" style="width: 100%; margin-top: 1.25rem;">
                {{ __('Done') }}
            </button>
        </div>
    </div>
</div>
