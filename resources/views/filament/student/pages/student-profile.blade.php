<x-filament-panels::page>
    <div class="space-y-6">

        @if(! $student)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-950/20">
                <x-heroicon-o-user-circle class="mx-auto h-10 w-10 text-amber-500"/>
                <h2 class="mt-3 text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Profile Not Linked') }}</h2>
                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('No student record is linked to this account yet.') }}</p>
            </div>
        @else
            {{-- Profile Photo + Student ID --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Profile Photo') }}</h3>

                @if($photoRejection)
                    <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500"/>
                            <div>
                                <p class="text-sm font-bold text-amber-800 dark:text-amber-200">{{ __('Your photo was removed') }}</p>
                                <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">
                                    {{ __('Reason') }}: {{ $photoRejection['reason'] ?: __('No reason provided') }}
                                    <span class="opacity-70">· {{ $photoRejection['rejected_at']?->format('d M Y') }}</span>
                                </p>
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">{{ __('Please upload a new passport-style photo below.') }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                    <div class="shrink-0">
                        @if($student->photo_path)
                            <img src="{{ resolve_public_asset_path($student->photo_path) }}"
                                 alt="{{ $student->full_name }}"
                                 class="h-32 w-24 rounded-lg object-cover shadow-sm ring-2 ring-slate-100 dark:ring-slate-800">
                        @else
                            @php
                                $fallbackPhoto = ($student->gender === 'female') ? 'images/no_profile_female.jpg' : 'images/no_profile_male.png';
                            @endphp
                            <img src="{{ asset($fallbackPhoto) }}"
                                 alt="{{ $student->full_name }}"
                                 class="h-32 w-24 rounded-lg object-cover shadow-sm ring-2 ring-slate-100 dark:ring-slate-800">
                        @endif
                    </div>

                    <div class="flex-1 space-y-4">
                        {{-- Student ID badge --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 dark:border-indigo-900 dark:bg-indigo-950/40">
                                <x-heroicon-o-identification class="h-4 w-4 text-indigo-600 dark:text-indigo-300"/>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-indigo-500 dark:text-indigo-300">{{ __('Student ID') }}</span>
                                <span class="font-mono text-sm font-bold text-indigo-800 dark:text-indigo-200">{{ $student->student_id_number }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-800 dark:bg-slate-950/40">
                                <x-heroicon-o-archive-box class="h-4 w-4 text-slate-500 dark:text-slate-400"/>
                                <span class="text-[10px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Admission') }}</span>
                                <span class="font-mono text-sm font-bold text-slate-700 dark:text-slate-200">{{ $student->admission_number }}</span>
                            </span>
                        </div>

                        @if($hasPhoto)
                            <p class="text-sm text-slate-600 dark:text-slate-400">{{ __('Your profile photo is on file.') }}</p>
                            <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">{{ __('You can upload a new one below to replace it.') }}</p>
                        @endif

                        <x-passport-photo-uploader
                            wire-method="savePhoto"
                            :current-photo="$student->photo_path ? resolve_public_asset_path($student->photo_path) : null"
                            :placeholder="$student->photo_path ? null : (($student->gender === 'female') ? asset('images/no_profile_female.jpg') : asset('images/no_profile_male.png'))"
                            :has-photo="$hasPhoto"
                        />
                    </div>
                </div>
            </div>

            {{-- Personal Details (read-only) --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Personal Details') }}</h3>
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Full Name') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->full_name }}</dd>
                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ __('Contact admin to update') }}</p>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Email') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $user->email ?? '—' }}</dd>
                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ __('Contact admin to update') }}</p>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Phone') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->phone ?? $user->phone ?? '—' }}</dd>
                        <p class="text-[10px] text-slate-400 dark:text-slate-600 mt-0.5">{{ __('Contact admin to update') }}</p>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Student Number') }}</dt>
                        <dd class="mt-0.5 font-mono text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->student_id_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Admission Number') }}</dt>
                        <dd class="mt-0.5 font-mono text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->admission_number }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Date of Birth') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Gender') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ ucfirst($student->gender ?? '—') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('National ID') }}</dt>
                        <dd class="mt-0.5 font-mono text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->national_id ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Class / Course') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $enrollment?->course?->name ?? $student->currentEnrollment?->course?->name ?? __('Not Enrolled') }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('House') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->house ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Boarding Status') }}</dt>
                        <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ ucfirst($student->boarding_status ?? '—') }}</dd>
                    </div>
                </dl>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Medical & Emergency') }}</h3>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Blood Group') }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->blood_group ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Medical Notes') }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->medical_notes ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Emergency Contact') }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->emergency_contact_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">{{ __('Emergency Phone') }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $student->emergency_contact_phone ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('Guardians / Parents') }}</h3>
                    @forelse($guardians as $guardian)
                        <div class="mb-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-950/40">
                            <p class="text-sm font-bold text-slate-800 dark:text-slate-200">{{ $guardian->name }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $guardian->relationship }} · {{ $guardian->email }} · {{ $guardian->phone }}</p>
                        </div>
                    @empty
                        <p class="py-6 text-center text-xs text-slate-400">{{ __('No guardians listed.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-4">{{ __('My Documents') }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse($documents as $document)
                        <a href="{{ $document->download_url }}" target="_blank" rel="noopener"
                           class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50/60 p-3 hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-indigo-700">
                            <x-heroicon-o-document-text class="h-5 w-5 shrink-0 text-indigo-500"/>
                            <div class="min-w-0">
                                <p class="truncate text-xs font-bold text-slate-800 dark:text-slate-200">{{ $document->original_name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $document->document_type_label }}</p>
                            </div>
                        </a>
                    @empty
                        <p class="col-span-full py-6 text-center text-xs text-slate-400">{{ __('No documents uploaded yet.') }}</p>
                    @endforelse
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>