@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: __('Academic Management, Exams & Report Cards - Kairo CORE'))
@section('meta_description', marketing_content('meta_description') ?: __('Manage classes, timetables, assessments, examinations, grading and report cards — academic management built for how schools run.'))

@section('content')
<section class="relative overflow-hidden bg-white pb-10 pt-14 lg:pt-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 right-1/3 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Academics & Exams') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-[1.08]">{{ __('From class timetable to report card, in one academic system') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('Set up classes, streams and subjects once. Schedule timetables, capture assessments against your own grading scheme, run examinations and publish report cards — academic management on Kairo CORE mirrors the school year you already work in.') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ __('Book a personal demo') }}</a>
                <a href="{{ route('marketing.features', ['category' => 'academics']) }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">{{ __('Explore academic features') }}</a>
            </div>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Classes, streams and subjects') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Model your school exactly as it runs: grades, streams, subjects and the teachers assigned to each — the foundation every timetable, mark and report builds on.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Timetables') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Build once-off timetables per class or stream and keep them printable and accessible, so clash-free periods are visible to staff and students.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Assessments under your grading scheme') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Capture continuous assessment and exam marks against your own scheme — marks, letters or percentages — instead of a foreign grading preset.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Examinations and result publication') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Schedule exams by form and subject, capture and resolve marks, then publish ranked results and analyse-of-results the moment marking is complete.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Report cards in seconds') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Generate term reports per class, ready to print or share with parents — the end-of-term rush becomes a batch job, not a weekend of typing.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Attendance feeding the academic picture') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Daily registers roll up into class and grade reports, so attendance patterns are visible alongside performance — not in a separate book.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('The academic year on Kairo CORE') }}</h2>
        <ol class="mt-8 space-y-6">
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">1</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('Configure the year') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('Set academic years and terms, then classes, streams and subjects with their teachers.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">2</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('Teach and assess') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('Run timetables, mark attendance and capture continuous assessment marks against your grading scheme.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">3</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('Run examinations') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('Schedule exams, capture or import marks, and publish results when they are ready.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">4</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('Produce report cards') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ __('Generate and issue term reports per class, then carry averages and history into the next term automatically.') }}</p>
                </div>
            </li>
        </ol>
        <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm font-semibold text-slate-700">
            <a href="{{ route('marketing.students') }}" class="hover:text-[#D94B39]">{{ __('Student records') }}</a>
            <a href="{{ route('marketing.help') }}" class="hover:text-[#D94B39]">{{ __('Help Center') }}</a>
            <a href="{{ route('marketing.admissions') }}" class="hover:text-[#D94B39]">{{ __('Admissions & enrolment') }}</a>
        </div>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('Frequently asked questions') }}</h2>
        <div class="mt-8 space-y-8">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Can we use our own grading scheme?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Yes. Kairo CORE grades against your scheme — marks, letters or percentages — so reports match your school’s policy rather than a foreign preset.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Are report cards printable?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Yes — reports generate per class and are ready to print or share with parents through the channels your school uses.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ __('Does this work for both primary and secondary schools?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('The academic module is structured around forms, streams and subjects, which maps to both primary and secondary school organisation. Bring your structure to a demo and we will show how it maps.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('See an academic term start to report card') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ __('Book a personal demo and we will walk through setup, assessment and report generation with your school’s academic structure.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-[#D94B39]">{{ __('Book a personal demo') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ __('Contact us') }}</a>
        </div>
    </div>
</section>
@endsection
