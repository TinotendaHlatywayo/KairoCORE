@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: platform_name().' - School Management System')
@section('meta_description', marketing_content('meta_description') ?: __('Modern school management system for admissions, academics, fees, attendance, HR, communications and more.'))

@section('content')
<div class="bg-[#1F2E43] text-white text-center text-xs sm:text-sm py-2 px-4">
    <span>{{ marketing_content('announcement') }}</span>
</div>

<section class="relative overflow-hidden bg-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8 lg:pt-16">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold tracking-tight text-slate-900 leading-tight">{{ marketing_content('hero_title') }}</h1>
                <p class="mt-6 max-w-xl text-base sm:text-lg leading-8 text-slate-600">{{ marketing_content('hero_subtitle') }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ marketing_content('hero_primary_url') ?: route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#D94B39] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#c14332] transition-colors">{{ marketing_content('hero_primary_label') }}</a>
                    <a href="{{ marketing_content('hero_secondary_url') ?: route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">{{ marketing_content('hero_secondary_label') }}</a>
                </div>
                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600">
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Easy to set up</span>
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Secure &amp; reliable</span>
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Built for Zimbabwe</span>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-[16/10] w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                    <img src="{{ marketing_content('hero_image') }}" alt="Students working together in a classroom" class="h-full w-full object-cover" loading="eager">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-14 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ marketing_content('modules_title') }}</h2>
            <p class="mt-4 text-lg text-slate-600">{{ marketing_content('modules_subtitle') }}</p>
        </div>
        @php
            $modules = [
                ['name' => 'Admissions', 'desc' => 'Applications, enrolment and complete student records.'],
                ['name' => 'Academics', 'desc' => 'Classes, subjects, timetables and report cards.'],
                ['name' => 'Finance &amp; Fees', 'desc' => 'Fee structures, invoicing and multi-currency billing.'],
                ['name' => 'Attendance', 'desc' => 'Daily student and staff attendance with alerts.'],
                ['name' => 'HR &amp; Payroll', 'desc' => 'Staff records, leave management and payroll.'],
                ['name' => 'Communication', 'desc' => 'Announcements and email or WhatsApp alerts to parents.'],
                ['name' => 'Exams &amp; Results', 'desc' => 'Exam scheduling, grading schemes and result publishing.'],
                ['name' => 'Library', 'desc' => 'Book catalogue, issuance tracking, returns and fines.'],
                ['name' => 'Health &amp; Wellness', 'desc' => 'Student health records, clinic visits and medical alerts.'],
            ];
        @endphp
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $module)
                <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm transition hover:shadow-md">
                    <h3 class="text-base font-semibold text-slate-900">{{ $module['name'] }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $module['desc'] }}</p>
                </div>
            @endforeach
        </div>
        <div class="mt-10 text-center">
            <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#B93A2B] hover:text-[#D94B39]">See every feature &rarr;</a>
        </div>
    </div>
</section>

<section class="bg-white py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ marketing_content('benefits_title') }}</h2>
            <p class="mt-4 text-lg text-slate-600">{{ marketing_content('benefits_subtitle') }}</p>
        </div>
        @php
            $benefits = [
                [
                    'name' => 'One system for the whole school',
                    'desc' => 'Admissions, academics, finance, HR and communication share the same data, so nothing has to be captured twice.',
                    'icon' => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25A2.25 2.25 0 0 1 8.25 10.5H6A2.25 2.25 0 0 1 3.75 8.25V6ZM13.5 6A2.25 2.25 0 0 1 15.75 3.75h2.25A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25A2.25 2.25 0 0 1 13.5 8.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
                ],
                [
                    'name' => 'Save hours of admin every week',
                    'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'desc' => 'Automated fees, invoices, report cards and alerts replace repetitive paperwork so your staff can focus on learners.',
                ],
                [
                    'name' => 'Reach parents where they are',
                    'icon' => 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 16.5h3',
                    'desc' => 'Send announcements and timely updates by email or WhatsApp, and let parents follow attendance, fees and results.',
                ],
                [
                    'name' => 'Secure, cloud-based and current',
                    'icon' => 'M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z',
                    'desc' => 'Your data is backed up and always up to date, with role-based access so everyone sees only what they should.',
                ],
            ];
        @endphp
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($benefits as $benefit)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F3F5F8] text-[#1F2E43]">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="h-6 w-6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $benefit['icon'] }}" />
                        </svg>
                    </div>
                    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $benefit['name'] }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ $benefit['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">{{ marketing_content('steps_title') }}</h2>
            <p class="mt-4 text-lg text-slate-600">{{ marketing_content('steps_subtitle') }}</p>
        </div>
        <div class="mt-12 grid gap-8 md:grid-cols-3">
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#1F2E43] text-lg font-bold text-white">1</div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Register your school</h3>
                <p class="mt-2 text-sm text-slate-600">Create your school account in minutes and get your own secure workspace.</p>
            </div>
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#1F2E43] text-lg font-bold text-white">2</div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Set up your school</h3>
                <p class="mt-2 text-sm text-slate-600">Add classes, staff, fee structures and branding — with our team on hand to help.</p>
            </div>
            <div class="text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#1F2E43] text-lg font-bold text-white">3</div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">Go live</h3>
                <p class="mt-2 text-sm text-slate-600">Start managing admissions, academics, fees and communication from day one.</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">{{ marketing_content('cta_title') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ marketing_content('cta_subtitle') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ marketing_content('cta_primary_url') ?: route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#EF5F4D] px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#D94B39] transition-colors">{{ marketing_content('cta_primary_label') }}</a>
            <a href="{{ marketing_content('cta_secondary_url') ?: route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-lg border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors">{{ marketing_content('cta_secondary_label') }}</a>
        </div>
    </div>
</section>
@endsection
