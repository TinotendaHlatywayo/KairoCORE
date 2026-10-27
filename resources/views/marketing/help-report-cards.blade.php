@extends('marketing.layout')

@section('title', __('How to produce report cards on :platform', ['platform' => platform_name()]))
@section('meta_description', __('Produce report cards on :platform: register assessments, enter marks, review performance analytics, then generate and publish term report cards.', ['platform' => platform_name()]))

@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How to produce report cards on '.platform_name(),
    'description' => 'Register assessments, capture marks, review analytics, and generate and publish term report cards.',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Register assessments', 'text' => 'Register each test or exam with its maximum mark and the weight it carries towards the final term grade.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Capture marks', 'text' => 'Enter marks in Marks Entry — per subject, per class.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Review performance analytics', 'text' => 'Check class performance before publication while marks can still be moderated.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Generate report cards', 'text' => 'Produce report cards per class, graded against your grading scale and descriptors.'],
        ['@type' => 'HowToStep', 'position' => 5, 'name' => 'Publish to the portal', 'text' => 'Publish results so students and guardians see them on the portal alongside the report card.'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'Can we use our own grading scheme?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes — grading scales define bands with marks and descriptors such as Distinction or Needs Improvement, and the active scale is used automatically when marks are graded.']],
        ['@type' => 'Question', 'name' => 'Do parents and students see the results?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Once you publish, marks appear in the student portal results view and on the report card, so families see the same published outcome as the school.']],
        ['@type' => 'Question', 'name' => 'When should we publish?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'After reviewing performance analytics — it is the last reliable point to catch an underperforming class while marks can still be moderated.']],
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
        <span class="text-slate-700 font-semibold">{{ __('How to produce report cards') }}</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">{{ __('How to produce report cards on :platform', ['platform' => platform_name()]) }}</h1>

    <h2 class="mt-10 text-2xl font-bold text-slate-900">{{ __('What it is') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('Report cards on :platform are the end of a connected academic chain: assessments registered with their marks and weights, marks captured per subject, performance reviewed, then grades generated against your own grading scale and published to the portal.', ['platform' => platform_name()]) }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Why it matters') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('End-of-term report writing is the classic school bottleneck — spreadsheets, copy-paste and formatting evenings. When marks already carry weights and grades resolve against a defined scale, the report becomes a batch generation instead of a manual assembly, and families get results in the portal at the same moment the school does.') }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Steps') }}</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>{{ __('Register assessments') }}</strong> {{ __('— each test or exam gets a maximum mark and a weight towards the term grade.') }}</li>
        <li><strong>{{ __('Capture marks') }}</strong> {{ __('— enter marks per subject, per class, in Marks Entry.') }}</li>
        <li><strong>{{ __('Review performance analytics') }}</strong> {{ __('— check class performance before anything is published.') }}</li>
        <li><strong>{{ __('Generate report cards') }}</strong> {{ __('— produce reports per class, graded on your grading scale with descriptors.') }}</li>
        <li><strong>{{ __('Publish to the portal') }}</strong> {{ __('— students and guardians see published results alongside the report card.') }}</li>
    </ol>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Tips') }}</h2>
    <ul class="mt-4 space-y-2 text-slate-700 list-disc list-inside">
        <li>{{ __('Register every assessment at the start of the term with correct weights — weights are what make final grades add up without recalculation.') }}</li>
        <li>{{ __('Set up grading scales with descriptors first (Distinction, Needs Improvement, and so on), so report comments read in words, not just numbers.') }}</li>
        <li>{{ __('Treat performance analytics as your moderation checkpoint: it is the last point to fix a marking error before publication.') }}</li>
        <li>{{ __('Publish once, per class batch — families receive the portal result and the report card as a single outcome.') }}</li>
    </ul>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Frequently asked questions') }}</h2>
    <div class="mt-4 space-y-4">
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('Can we use our own grading scheme?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('Yes — grading scales define mark bands with descriptors, and the active scale is applied automatically when marks are graded.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('Do parents and students see the results?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('Yes — once published, results appear in the student portal and on the report card, matching what the school issued.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('When should we publish?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('After reviewing analytics — the last reliable point to moderate marks while changes are still possible.') }}</p>
        </div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Related') }}</h2>
    <ul class="mt-3 space-y-2 text-slate-700">
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.academics') }}">{{ __('Academics, exams & report cards product page') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help.attendance') }}">{{ __('How to record attendance') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help') }}">{{ __('All help guides') }}</a></li>
    </ul>
</article>
@endsection
