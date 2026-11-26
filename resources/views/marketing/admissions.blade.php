@extends('marketing.layout')

@section('title', marketing_text('School Admissions & Enrolment Software').' | '.platform_name())
@section('meta_description', marketing_text('Run admissions end to end: online applications, document uploads, review, placement and enrolment that creates the student record on Kairo CORE.'))

@section('content')
<section class="relative overflow-hidden bg-white pb-10 pt-14 lg:pt-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/4 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_text('Admissions') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-[1.08]">{{ marketing_text('Admissions that end in an enrolled student, not a pile of forms') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ marketing_text('Families apply online on your school’s website. Your team reviews, places and admits applicants in the workspace. Admission creates the student record and feeds fees — one connected admissions and enrolment process on Kairo CORE.') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('demo.login') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_text('Try the live demo') }}</a>
                <a href="{{ route('marketing.help.enroll') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">{{ marketing_text('How enrollment works guide') }}</a>
            </div>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Online applications on your school website') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Every school gets a public website with an Apply / Apply Online page. Families complete the admission form themselves — no re-typing handwritten forms into a spreadsheet.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Documents attached to the application') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Birth certificates, previous report cards and other requested documents upload with the application and stay attached to the applicant’s file for review.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Review and placement in one queue') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Applications arrive in the workspace under Applications. Review them, decide placement and admit — each decision stays on the applicant’s history.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Admission creates the student record') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Admitting an applicant creates the student profile — the same record attendance, fees and results will use. No second data entry, no duplicate learners.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Direct path to fee invoicing') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('New students flow straight into the finance module, where fee structures and term invoices pick them up with the right class and amounts.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Intake you can measure') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Track applications, admits and enrolment numbers as the season progresses instead of reconstructing them from email and paper at term start.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ marketing_text('The admissions flow, step by step') }}</h2>
        <ol class="mt-8 space-y-6">
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">1</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('A family finds your school and applies') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('They open your school’s public website and click Apply / Apply Online, filling in student details, guardian contacts and previous school information.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">2</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Documents are uploaded with the form') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('Required documents attach to the application, so reviewers never chase attachments in an inbox.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">3</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Your team reviews in the workspace') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('Applications appear under Applications. Review the details and documents, decide placement and admit the applicant.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">4</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Enrolment completes the loop') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('The admitted learner becomes a student record, and fees can be invoiced — admissions, student data and finance stay in one system.') }}</p>
                </div>
            </li>
        </ol>
        <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm font-semibold text-slate-700">
            <a href="{{ route('marketing.students') }}" class="hover:text-[#D94B39]">{{ marketing_text('Student information system') }}</a>
            <a href="{{ route('marketing.school-fees') }}" class="hover:text-[#D94B39]">{{ marketing_text('Fees & finance') }}</a>
            <a href="{{ route('marketing.help.register') }}" class="hover:text-[#D94B39]">{{ marketing_text('Register your school guide') }}</a>
            <a href="{{ route('marketing.features', ['category' => 'core']) }}" class="hover:text-[#D94B39]">{{ marketing_text('Core features') }}</a>
        </div>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ marketing_text('Frequently asked questions') }}</h2>
        <div class="mt-8 space-y-8">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Do parents need an account to apply?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('The admission form is completed on your school’s public website. Your team receives the application in the workspace for review — the process is designed to be simple for families and structured for staff.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Can we handle walk-in or transfer admissions?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Yes — staff can also capture applicants directly, so walk-ins and transfers enter the same review and enrolment flow as online applications.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('What happens to applicants who are not admitted?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Their application stays on file with its status, so you keep an accurate intake record without those applicants becoming students.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ marketing_text('Run your next intake on Kairo CORE') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ marketing_text('Explore the live demo to see the admissions flow end to end, from the public application page to the enrolled student record.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('demo.login') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-[#D94B39]">{{ marketing_text('Try the live demo') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ marketing_text('Contact us') }}</a>
        </div>
    </div>
</section>
@endsection
