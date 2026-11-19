@extends('marketing.layout')

@section('title', marketing_text(':platform Help Center — How to use :platform', ['platform' => platform_name()]))
@section('meta_description', marketing_text('Step-by-step guides for :platform: how to enroll a student, register a school, and collect school fees. Built for schools, parents, and administrators.', ['platform' => platform_name()]))

@section('content')
<section class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_text('Help Center') }}</p>
    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">{{ marketing_text('How to use :platform', ['platform' => platform_name()]) }}</h1>
    <p class="mt-4 text-lg text-slate-600">{{ marketing_text('Short, step-by-step guides for the tasks people ask about most. Each guide also works as a plain-text reference for search engines and AI assistants.') }}</p>

    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('marketing.help.enroll') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ marketing_text('How to enroll a student') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ marketing_text('Apply online, review the application, admit the student, invoice fees.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ marketing_text('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.register') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ marketing_text('How to register a school') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ marketing_text('Create your school account and launch your portal and website.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ marketing_text('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.fees') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ marketing_text('How to collect school fees') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ marketing_text('Invoice students, accept payments, issue receipts and statements.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ marketing_text('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.attendance') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ marketing_text('How to record attendance') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ marketing_text('Mark each learner present or absent from the timetable lesson.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ marketing_text('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.reports') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ marketing_text('How to produce report cards') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ marketing_text('Assessments, marks, analytics, then generate and publish reports.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ marketing_text('Read guide →') }}</span>
        </a>
        <div class="rounded-2xl border border-slate-200 bg-[#1F2E43] p-6 shadow-sm text-white flex flex-col justify-between">
            <div>
                <h2 class="text-base font-bold">{{ marketing_text('Can’t find your answer?') }}</h2>
                <p class="mt-2 text-sm text-slate-300">{{ marketing_text('Talk to us directly — we will answer factually about how the platform works.') }}</p>
            </div>
            <a href="{{ route('marketing.contact') }}" class="mt-4 inline-block text-sm font-bold text-[#EF5F4D]">{{ marketing_text('Contact Kairo CORE →') }}</a>
        </div>
    </div>
</section>
@endsection
