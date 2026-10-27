@extends('marketing.layout')

@section('title', __(':platform Help Center — How to use :platform', ['platform' => platform_name()]))
@section('meta_description', __('Step-by-step guides for :platform: how to enroll a student, register a school, and collect school fees. Built for schools, parents, and administrators.', ['platform' => platform_name()]))

@section('content')
<section class="mx-auto max-w-4xl px-4 py-14 sm:px-6 lg:px-8">
    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Help Center') }}</p>
    <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">{{ __('How to use :platform', ['platform' => platform_name()]) }}</h1>
    <p class="mt-4 text-lg text-slate-600">{{ __('Short, step-by-step guides for the tasks people ask about most. Each guide also works as a plain-text reference for search engines and AI assistants.') }}</p>

    <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('marketing.help.enroll') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ __('How to enroll a student') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('Apply online, review the application, admit the student, invoice fees.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ __('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.register') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ __('How to register a school') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('Create your school account and launch your portal and website.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ __('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.fees') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ __('How to collect school fees') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('Invoice students, accept payments, issue receipts and statements.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ __('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.attendance') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ __('How to record attendance') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('Mark each learner present or absent from the timetable lesson.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ __('Read guide →') }}</span>
        </a>
        <a href="{{ route('marketing.help.reports') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm hover:border-slate-300 hover:shadow-md transition">
            <h2 class="text-base font-bold text-slate-900">{{ __('How to produce report cards') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('Assessments, marks, analytics, then generate and publish reports.') }}</p>
            <span class="mt-4 inline-block text-sm font-bold text-[#D94B39]">{{ __('Read guide →') }}</span>
        </a>
        <div class="rounded-2xl border border-slate-200 bg-[#1F2E43] p-6 shadow-sm text-white flex flex-col justify-between">
            <div>
                <h2 class="text-base font-bold">{{ __('Can’t find your answer?') }}</h2>
                <p class="mt-2 text-sm text-slate-300">{{ __('Talk to us directly — we will answer factually about how the platform works.') }}</p>
            </div>
            <a href="{{ route('marketing.contact') }}" class="mt-4 inline-block text-sm font-bold text-[#EF5F4D]">{{ __('Contact Kairo CORE →') }}</a>
        </div>
    </div>
</section>
@endsection
