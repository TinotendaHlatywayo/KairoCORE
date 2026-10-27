@extends('marketing.layout')

@section('title', __('How to enroll a student on :platform', ['platform' => platform_name()]))
@section('meta_description', __('Learn how to enroll a student on :platform: submit the online admission application on the school website, upload documents, and the school reviews, admits, and invoices fees.', ['platform' => platform_name()]))

@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How to enroll a student on '.platform_name(),
    'description' => 'Submit an online admission application on the school website, then the school reviews, admits, and invoices the student.',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Open the school website', 'text' => 'Go to the school\'s public website (its '.platform_name().' subdomain) and click Apply or Apply Online.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Fill in the admission form', 'text' => 'Enter the student\'s personal details, guardian contacts, and previous school information.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Upload required documents', 'text' => 'Attach the birth certificate, previous report card, and any other documents the school requests.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Submit the application', 'text' => 'Submit the form online. The application appears in the school workspace under Applications.'],
        ['@type' => 'HowToStep', 'position' => 5, 'name' => 'School reviews and admits', 'text' => 'School staff review the application and admit it, which creates the official student record.'],
        ['@type' => 'HowToStep', 'position' => 6, 'name' => 'Pay fees', 'text' => 'Finance invoices the new student; guardians pay and receive a receipt.'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'Where do I enroll a student on '.platform_name().'?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'On the individual school\'s public website (its '.platform_name().' subdomain), via the Apply / Apply Online page — not on the central marketing homepage.']],
        ['@type' => 'Question', 'name' => 'What documents are needed to enroll?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Typically the student\'s birth certificate, previous report card, and guardian ID. The exact list is shown on each school\'s application form.']],
        ['@type' => 'Question', 'name' => 'How long does admission take?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Online submission takes a few minutes. School review times vary; contact the school directly for the decision timeline.']],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('marketing.home') }}" class="hover:text-slate-700">{{ __('Home') }}</a>
        <span class="mx-1">/</span>
        <a href="{{ route('marketing.help') }}" class="hover:text-slate-700">{{ __('Help') }}</a>
        <span class="mx-1">/</span>
        <span class="text-slate-700 font-semibold">{{ __('How to enroll a student') }}</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">{{ __('How to enroll a student on :platform', ['platform' => platform_name()]) }}</h1>
    <p class="mt-4 text-lg text-slate-600">{{ __(':platform powers each school\'s own website and admissions portal. Parents apply on the school\'s website; school staff then review and admit inside the workspace.', ['platform' => platform_name()]) }}</p>

    <h2 class="mt-10 text-2xl font-bold text-slate-900">{{ __('What it is') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('Enrolment is the path from applicant to student record: an online admission form on the school\'s website, documents attached to the application, a review queue in the workspace, and an admission step that creates the official student profile fees, attendance and results will use.') }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Why it matters') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('Manual enrolment means handwriting, re-typing and duplicate learners. When admission itself creates the record, your intake data stays accurate from day one — and finance, classes and report cards all inherit correct student details without a second entry.') }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Steps') }}</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>{{ __('Open the school\'s website') }}</strong> {{ __('— go to the school\'s :platform subdomain and click', ['platform' => platform_name()]) }} <strong>{{ __('Apply') }}</strong> {{ __('or') }} <strong>{{ __('Apply Online') }}</strong>.</li>
        <li><strong>{{ __('Fill in the admission form') }}</strong> {{ __('— student details, guardian contacts, and previous school information.') }}</li>
        <li><strong>{{ __('Upload required documents') }}</strong> {{ __('— birth certificate, previous report card, and anything else the school lists.') }}</li>
        <li><strong>{{ __('Submit the application') }}</strong> {{ __('— it appears in the school workspace under') }} <strong>{{ __('Applications') }}</strong>.</li>
        <li><strong>{{ __('School reviews and admits') }}</strong> {{ __('— staff verify details and admit, which creates the official student record.') }}</li>
        <li><strong>{{ __('Pay fees') }}</strong> {{ __('— Finance invoices the new student; guardians pay and receive a receipt.') }}</li>
    </ol>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Tips') }}</h2>
    <ul class="mt-4 space-y-2 text-slate-700 list-disc list-inside">
        <li>{{ __('List every required document on the application form up front — it removes the biggest cause of delayed admissions.') }}</li>
        <li>{{ __('Review the Applications queue daily during intake season so families get decisions while they are still deciding between schools.') }}</li>
        <li>{{ __('Admit applicants as soon as they are accepted: the student record is what unlocks class allocation and fee invoicing.') }}</li>
        <li>{{ __('Keep applicant statuses honest — unsuccessful applications stay on file as history without becoming students.') }}</li>
    </ul>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Frequently asked questions') }}</h2>
    <div class="mt-4 space-y-4">
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('Where do I enroll — on this marketing site?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('No. Enrollment happens on each school\'s own public website (its :platform subdomain) via the Apply page.', ['platform' => platform_name()]) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('What documents are needed?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('Typically a birth certificate, previous report card, and guardian ID. The exact list is shown on the school\'s form.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('How long does admission take?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('Submitting takes a few minutes. Review time depends on the school — contact them directly for a decision.') }}</p>
        </div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Related') }}</h2>
    <ul class="mt-3 space-y-2 text-slate-700">
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.admissions') }}">{{ __('Admissions & enrolment product page') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help.fees') }}">{{ __('How to collect school fees') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help') }}">{{ __('All help guides') }}</a></li>
    </ul>
</article>
@endsection
