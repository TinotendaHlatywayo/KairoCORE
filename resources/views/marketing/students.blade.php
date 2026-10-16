@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: __('Student Information System for Schools - Kairo CORE'))
@section('meta_description', marketing_content('meta_description') ?: __('Central student records: profiles, guardians, enrolment history, attendance, fees and results in one student information system for schools.'))

@section('content')
<section class="relative overflow-hidden bg-white pb-10 pt-14 lg:pt-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/3 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Students') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-[1.08]">{{ __('One complete record for every student') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('Kairo CORE is a student information system built around a single, trustworthy profile per learner — guardians, attendance, fees, health notes and results — that follows the student from first admission to graduation.') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ __('Book a personal demo') }}</a>
                <a href="{{ route('marketing.help.enroll') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">{{ __('How to enroll a student') }}</a>
            </div>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Complete student profiles') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Personal details, guardian contacts, medical notes, transport and previous school information live on one profile your whole team can search and trust.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Guardians and parent records') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Every student links to guardians with contact details, so communications, fee statements and emergency calls always reach the right people.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Enrolment history that follows the child') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Admission, grade moves and stream changes are recorded as history — not overwritten — so you can always answer “which class was this learner in, and when?”') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Attendance, fees and results in context') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Open a student to see the full picture: daily attendance, fee balances and payments, exam results and report cards — no tab-switching between systems.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Searchable student directory') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Find any learner by name, class or admission number in seconds — the directory every department relies on instead of shared spreadsheets.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Student ID cards and documents') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Generate and print student ID cards from the same record, and keep uploaded documents attached to the student file — private to authorised staff.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Built for the people who maintain student records') }}</h2>
        <div class="mt-8 grid gap-8 sm:grid-cols-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Registrars & admin officers') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Capture a new learner once. Admission creates the record; every later touch — attendance, fees, results — attaches to it.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Form teachers & staff') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Mark registers, view your class list and see the notes that matter before an activity or trip — from phone or desktop.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Leadership') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Trust one source of truth for enrolment numbers and student data, with role-based access so people see only what their job requires.') }}</p>
            </div>
        </div>
        <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm font-semibold text-slate-700">
            <a href="{{ route('marketing.admissions') }}" class="hover:text-[#D94B39]">{{ __('Admissions & enrolment') }}</a>
            <a href="{{ route('marketing.school-fees') }}" class="hover:text-[#D94B39]">{{ __('Fees & finance') }}</a>
            <a href="{{ route('marketing.academics') }}" class="hover:text-[#D94B39]">{{ __('Academics & reporting') }}</a>
            <a href="{{ route('marketing.help') }}" class="hover:text-[#D94B39]">{{ __('Help Center') }}</a>
        </div>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Frequently asked questions') }}</h2>
        <div class="mt-8 space-y-8">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Can we import our existing student list?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Kairo CORE supports bulk student data actions from spreadsheet templates during setup. Talk to us about your current records during onboarding and we will map the safest import path.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Who can see student information?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Access is role-based and every school’s data is isolated in its own workspace. A teacher sees their class; finance sees fee data; sensitive documents require the right permission. Details on our ') }}<a href="{{ route('marketing.security') }}" class="font-semibold text-[#B93A2B] hover:underline">{{ __('security page') }}</a>{{ __('.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Does a student record carry across grades?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Yes. The same student record follows the learner through grade moves and streams; enrolment history preserves each change instead of creating duplicates.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('Put every learner on one reliable record') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ __('See the student information system in a demo tailored to how your school keeps records today.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-[#D94B39]">{{ __('Book a personal demo') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ __('Contact us') }}</a>
        </div>
    </div>
</section>
@endsection
