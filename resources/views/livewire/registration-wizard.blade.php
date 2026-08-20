<div id="sc-reg-card" class="card shadow-sm border-0 p-4" :style="'transform: perspective(1600px) rotateX(' + (-my * 0.6).toFixed(3) + 'deg) rotateY(' + (mx * 0.6).toFixed(3) + 'deg);'">
    <!-- Progress Indicator -->
    <div class="d-flex justify-content-between mb-4">
        <span class="badge {{ $currentStep >= 1 ? 'bg-primary' : 'bg-secondary' }}">{{ __('1. Institution & Admin') }}</span>
        <span class="badge {{ $currentStep >= 2 ? 'bg-primary' : 'bg-secondary' }}">{{ __('2. Web Address') }}</span>
        <span class="badge {{ $currentStep >= 3 ? 'bg-primary' : 'bg-secondary' }}">{{ __('3. Custom Modules') }}</span>
    </div>

    <!-- Step 1 Form -->
    @if($currentStep === 1)
        <div>
            <h4 class="fw-bold mb-3">{{ __('Create Institutional & Admin Account') }}</h4>
            
            <div class="row g-3">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Registered Institution Name') }}</label>
                    <input type="text" wire:model.blur="schoolName" class="form-control @error('schoolName') is-invalid @enderror" placeholder="e.g., Greenwood Academy">
                    @error('schoolName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3" x-data="{
                    open: false,
                    search: '',
                    liveCountry: @entangle('country').live,
                    all: @js($this->countries),
                    init() { this.search = this.liveCountry; },
                    filtered() {
                        const q = (this.search || '').toLowerCase();
                        return q ? this.all.filter(c => c.toLowerCase().includes(q)) : this.all;
                    },
                    pick(c) { this.search = c; this.liveCountry = c; this.open = false; }
                }">
                    <label class="form-label fw-semibold text-secondary">{{ __('Country') }}</label>
                    <div class="position-relative">
                        <input
                            type="text"
                            x-model="search"
                            @focus="open = true"
                            @click="open = true"
                            @keydown.escape="open = false"
                            autocomplete="off"
                            placeholder="{{ __('Search or select your country...') }}"
                            class="form-control @error('country') is-invalid @enderror"
                        >
                        @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror

                        <div x-show="open" @click.outside="open = false" x-transition
                             class="position-absolute w-100 mt-1 bg-white border rounded shadow-lg sc-country-list">
                            <template x-for="c in filtered()" :key="c">
                                <button type="button" @click.prevent="pick(c)"
                                        class="d-flex align-items-center w-100 text-start px-3 py-2 border-0 bg-transparent"
                                        :class="c === liveCountry ? 'text-primary fw-bold' : ''"
                                        style="font-size: 0.9rem;"
                                        x-text="c"></button>
                            </template>
                            <div x-show="filtered().length === 0" class="px-3 py-2 text-muted small">{{ __('No matching country found.') }}</div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Physical Address') }}</label>
                    <textarea wire:model.blur="physicalAddress" rows="2" class="form-control @error('physicalAddress') is-invalid @enderror" placeholder="Street address, city, region"></textarea>
                    @error('physicalAddress') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('System Language') }}</label>
                    <select wire:model.blur="language" class="form-select @error('language') is-invalid @enderror">
                        <option value="english">{{ __('English') }}</option>
                        <option value="spanish">{{ __('Spanish') }}</option>
                        <option value="french">{{ __('French') }}</option>
                        <option value="portuguese">{{ __('Portuguese') }}</option>
                        <option value="shona">{{ __('Shona') }}</option>
                        <option value="swahili">{{ __('Swahili') }}</option>
                    </select>
                    @error('language') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Primary Phone Number') }}</label>
                    <input type="text" wire:model.blur="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="+263771122334">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Type of Institution') }}</label>
                    <select wire:model.live="institutionType" class="form-select @error('institutionType') is-invalid @enderror">
                        <option value="primary">{{ __('Primary') }}</option>
                        <option value="secondary">{{ __('Secondary') }}</option>
                        <option value="tertiary">{{ __('Tertiary') }}</option>
                        <option value="both">{{ __('Both Primary and Secondary') }}</option>
                        <option value="other">{{ __('Other') }}</option>
                    </select>
                    @error('institutionType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                @if($institutionType === 'other')
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold text-secondary">{{ __('Specify Other Institution Type') }}</label>
                        <input type="text" wire:model.blur="otherInstitutionType" class="form-control @error('otherInstitutionType') is-invalid @enderror" placeholder="Specify type">
                        @error('otherInstitutionType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                @endif

                <div class="col-12"><hr class="my-2"></div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Administrator Full Name') }}</label>
                    <input type="text" wire:model.blur="adminName" class="form-control @error('adminName') is-invalid @enderror" placeholder="Prof. Alex Mercer">
                    @error('adminName') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label fw-semibold text-secondary">{{ __('Administrator Real Email Address') }}</label>
                    <input type="email" wire:model.blur="adminEmail" class="form-control @error('adminEmail') is-invalid @enderror" placeholder="admin@greenwood.edu">
                    @error('adminEmail') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text small">{{ __('Note: Email address cannot be changed later for security. You will set your username and password upon approval.') }}</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Step 2 Form -->
    @if($currentStep === 2)
        <div>
            <h4 class="fw-bold mb-1">{{ __('Choose Web Subdomain') }}</h4>
            <p class="text-muted small mb-4">{{ __('Your school portal will run at this specific web address.') }}</p>

            <div class="mb-3">
                <label class="form-label fw-semibold text-secondary">{{ __('Subdomain') }}</label>
                <div class="input-group">
                    <input type="text" wire:model.live.debounce.300ms="subdomain" class="form-control @error('subdomain') is-invalid @enderror" placeholder="greenwood">
                    <span class="input-group-text bg-light text-secondary">{{ __('.lvh.me') }}</span>
                </div>
                @error('subdomain') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                @if(!empty($subdomainInvalidChars))
                    <div class="text-warning small mt-1">
                        {{ __('Removed invalid character') }}{{ mb_strlen($subdomainInvalidChars) > 1 ? 's' : '' }}: <strong>{{ $subdomainInvalidChars }}</strong>.
                        {{ __('Only letters, numbers, dashes and underscores are allowed.') }}
                    </div>
                @endif

                @if(!empty($subdomain) && !$errors->has('subdomain'))
                    @if($isSubdomainAvailable)
                        <div class="text-success small mt-1">{{ __('✔ This address is available:') }} <strong>{{ $subdomain }}.lvh.me</strong></div>
                    @else
                        <div class="text-danger small mt-1">{{ __('✖ This subdomain is already taken.') }}</div>
                    @endif
                @endif
            </div>

            <hr class="my-4">

            <div class="form-check form-switch bg-light p-3 rounded">
                <div class="ps-2">
                    <input class="form-check-input" type="checkbox" wire:model="hasDummyData" id="dummyDataToggle">
                    <label class="form-check-label fw-semibold text-dark" for="dummyDataToggle">{{ __('Pre-load Demonstration Data') }}</label>
                    <p class="text-muted small mb-0">{{ __('Highly recommended. Seeds classes, test students, and mock assets for immediate testing.') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Step 3 Form -->
    @if($currentStep === 3)
        @php
            $moduleIcons = [
                'admissions' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/></svg>',
                'students' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"/></svg>',
                'academics' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5"/></svg>',
                'exams' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16.862 4.487l1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/><path d="M12 6.75h.01"/></svg>',
                'attendance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/><path d="M9.75 12h.008v.008H9.75V12Zm0 2.25h.008v.008H9.75v-.008Zm0 2.25h.008v.008H9.75V15Zm-2.25-4.5h.008v.008H7.5v-.008Zm0 2.25h.008v.008H7.5v-.008Zm0 2.25h.008v.008H7.5V15Zm9-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm2.25-4.5h.008v.008H18v-.008Zm0 2.25h.008v.008H18v-.008Z"/></svg>',
                'hr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path fill-rule="evenodd" d="M7.5 5.25a3 3 0 0 1 3-3h3a3 3 0 0 1 3 3v.205c.933.085 1.857.197 2.774.334 1.454.219 2.476 1.483 2.476 2.916v2.415c0 1.477-.978 2.758-2.381 3.003a48.743 48.743 0 0 1-9.738 0 3.012 3.012 0 0 1-2.381-3.003V8.705c0-1.433 1.022-2.697 2.476-2.916A48.561 48.561 0 0 1 7.5 5.455V5.25Zm7.5 0v.09a49.27 49.27 0 0 0-6 0v-.09a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5Z" clip-rule="evenodd"/></svg>',
                'boarding' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 21v-8a1.5 1.5 0 0 0-1.5-1.5H12A1.5 1.5 0 0 0 10.5 13v8m-6.75 0V9.45a3 3 0 0 1 .87-2.12l4.5-4.5A3 3 0 0 1 11.121 2h1.758a3 3 0 0 1 2.122.879l4.5 4.5a3 3 0 0 1 .879 2.121V21m-13.5 0h13.5"/></svg>',
                'clinic' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>',
                'library' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/></svg>',
                'inventory' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/></svg>',
                'finance' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
                'communication' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a2.25 2.25 0 0 1 0 3.46"/></svg>',
                'website' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/></svg>',
                'lms' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/><path d="M15.91 11.672a.375.375 0 0 1 0 .656l-5.603 3.113a.375.375 0 0 1-.557-.328V8.887c0-.286.307-.466.557-.327l5.603 3.112Z"/></svg>',
                'knowledge' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 18v-5.25m0 0a6.01 6.01 0 0 0 1.5-.189m-1.5.189a6.01 6.01 0 0 1-1.5-.189m3.75 7.478a12.06 12.06 0 0 1-4.5 0m3.75 2.383a14.406 14.406 0 0 1-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 1 0-7.517 0c.85.493 1.509 1.333 1.509 2.316V18"/></svg>',
                'reports' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z"/></svg>',
                'administration' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path fill-rule="evenodd" d="M11.078 2.25c-.917 0-1.699.663-1.85 1.567L9.05 4.889c-.02.12-.115.26-.297.348a7.493 7.493 0 0 0-.986.57c-.166.115-.334.126-.45.083L6.3 5.508a1.875 1.875 0 0 0-2.282.982l-.722 1.449a1.875 1.875 0 0 0 .432 2.137l.764.765c.127.127.136.298.066.428a7.5 7.5 0 0 0 0 1.462c.07.13.06.3-.066.428l-.764.765a1.875 1.875 0 0 0-.432 2.137l.722 1.449c.406.813 1.367 1.181 2.282.982l1.018-.226c.116-.043.284-.032.45.083.311.218.642.403.986.57.182.088.277.227.297.348l.178 1.072c.151.904.933 1.567 1.85 1.567h1.844c.916 0 1.699-.663 1.85-1.567l.178-1.072c.02-.12.115-.26.297-.348.344-.167.675-.352.986-.57.166-.115.334-.126.45-.083l1.018.226c.915.199 1.876-.169 2.282-.982l.722-1.449a1.875 1.875 0 0 0-.432-2.137l-.764-.765c-.127-.127-.136-.298-.066-.428a7.5 7.5 0 0 0 0-1.462c-.07-.13-.06-.3.066-.428l.764-.765a1.875 1.875 0 0 0 .432-2.137l-.722-1.449a1.875 1.875 0 0 0-2.282-.982l-1.018.226c-.116.043-.284.032-.45-.083a7.493 7.493 0 0 0-.986-.57c-.182-.088-.277-.227-.297-.348l-.178-1.072A1.875 1.875 0 0 0 12.922 2.25h-1.844Z" clip-rule="evenodd"/><path fill-rule="evenodd" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" clip-rule="evenodd"/></svg>',
                'saas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>',
            ];
        @endphp
        <div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div>
                    <h4 class="fw-bold mb-1">{{ __('Configure Installed Modules') }}</h4>
                    <p class="text-muted small mb-0">{{ __('Select the applications to activate. Unselected modules will remain completely hidden.') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" wire:click="selectAllModules" class="btn btn-sm" style="background: var(--sc-brand-gradient); color:#fff; border:none; border-radius:0.6rem; font-weight:700;">{{ __('Select All') }}</button>
                    <button type="button" wire:click="clearModules" class="btn btn-sm" style="border:1.5px solid #cbd5e1; color:#334155; border-radius:0.6rem; font-weight:700; background:#fff;">{{ __('Deselect All') }}</button>
                </div>
            </div>
            <p class="text-muted small mb-3"><span class="text-danger">*</span> {{ __('Administration and Subscriptions &amp; Billing are always enabled and cannot be turned off.') }}</p>

            <div class="row g-3">
                @foreach($availableModules as $key => $mod)
                    @php $locked = in_array($key, $lockedModules, true); @endphp
                    <div class="col-12 col-md-6 col-lg-4 d-flex">
                        <label class="sc-mod-card {{ in_array($key, $selectedModules) ? 'selected' : '' }} {{ $locked ? 'locked' : '' }}" for="mod_{{ $key }}">
                            <div class="sc-mod-icon">{!! $moduleIcons[$key] ?? '' !!}</div>
                            <div class="sc-mod-body">
                                <span class="sc-mod-name">
                                    {{ $mod['name'] }}
                                    @if($locked)
                                        <span class="sc-lock-badge"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="10" height="10" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>{{ __('Always On') }}</span>
                                    @endif
                                </span>
                                <span class="sc-mod-desc">{{ $mod['desc'] }}</span>
                            </div>
                            <input class="sc-mod-check form-check-input" type="checkbox" value="{{ $key }}" wire:model.live="selectedModules" id="mod_{{ $key }}" @if($locked) checked disabled @endif>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Navigation Buttons -->
    <div class="d-flex justify-content-between mt-4 border-top pt-3">
        @if($currentStep > 1)
            <button type="button" wire:click="prevStep" class="btn btn-outline-secondary">{{ __('Back') }}</button>
        @else
            <div></div>
        @endif

        @if($currentStep < $totalSteps)
            <button type="button" wire:click="nextStep" class="btn btn-primary px-4" style="background-color: #1e3a8a; border-color: #1e3a8a;">{{ __('Continue') }}</button>
        @else
            <button type="button" wire:click="submit" class="btn btn-success px-4" {{ !$isSubdomainAvailable ? 'disabled' : '' }}>{{ __('Submit Application') }}</button>
        @endif
    </div>
</div>
