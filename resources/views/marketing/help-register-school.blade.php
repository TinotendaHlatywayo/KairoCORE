@extends('marketing.layout')

@section('title', __('How to register a school on :platform', ['platform' => platform_name()]))
@section('meta_description', __('Register your school on :platform: create your account online, verify it, and launch your portal with website, admissions, academics, and fees.', ['platform' => platform_name()]))

@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How to register a school on '.platform_name(),
    'description' => 'Create a school account, verify it, and launch the school portal and public website.',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Open registration', 'text' => 'Click Register School and complete the multi-step registration form with school and administrator details.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Activate the account', 'text' => 'Use the time-sensitive activation link sent to the administrator email address.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Sign in to the workspace', 'text' => 'Log in on the school subdomain workspace and complete the academic setup wizard.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Publish the website', 'text' => 'Customize and publish the public website, then share the Apply Online link with parents.'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'What do I need to register a school?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'The school’s basic details and an administrator’s email address. You complete these in the multi-step Register School form.']],
        ['@type' => 'Question', 'name' => 'How long does registration take?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Filling in the form takes a few minutes. After email activation you sign in and run the setup wizard to configure your academic year and school structure.']],
        ['@type' => 'Question', 'name' => 'Do parents need an account to apply to my school?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No — parents apply through your school’s public website using the Apply / Apply Online page, without creating an account first.']],
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
        <span class="text-slate-700 font-semibold">{{ __('How to register a school') }}</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">{{ __('How to register a school on :platform', ['platform' => platform_name()]) }}</h1>

    <h2 class="mt-10 text-2xl font-bold text-slate-900">{{ __('What it is') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('Registration creates your school account, your staff workspace, and your public website in one flow. From the Register School form to the setup wizard, you move from “a name” to a working portal your staff can log into and parents can apply through.') }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Why it matters') }}</h2>
    <p class="mt-3 leading-7 text-slate-600">{{ __('Getting onboarding right sets up everything that follows: your academic year and terms drive enrolment, attendance, fees and exams. Registering properly — with a working administrator email — means activation, setup and your first intake all stay in one uninterrupted path.') }}</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Steps') }}</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>{{ __('Open registration') }}</strong> {{ __('— click') }} <a class="font-bold text-[#D94B39]" href="{{ route('register') }}">{{ __('Register School') }}</a> {{ __('and complete the multi-step form.') }}</li>
        <li><strong>{{ __('Activate the account') }}</strong> {{ __('— use the time-sensitive link emailed to the administrator.') }}</li>
        <li><strong>{{ __('Sign in to the workspace') }}</strong> {{ __('— log in on your school subdomain and finish the setup wizard.') }}</li>
        <li><strong>{{ __('Publish the website') }}</strong> {{ __('— customize pages, then share your Apply Online link with parents.') }}</li>
    </ol>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Tips') }}</h2>
    <ul class="mt-4 space-y-2 text-slate-700 list-disc list-inside">
        <li>{{ __('Register with an email you check daily — the activation link is time-sensitive and single-use.') }}</li>
        <li>{{ __('Complete the setup wizard in one sitting: registering the active academic year first makes every later module attach to it automatically.') }}</li>
        <li>{{ __('Before sharing your Apply link publicly, publish your website and click through the admission form yourself once.') }}</li>
        <li>{{ __('Invite key staff (bursar, academics lead) early so the first term isn’t run from one administrator account.') }}</li>
    </ul>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Frequently asked questions') }}</h2>
    <div class="mt-4 space-y-4">
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('What do I need to get started?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('The school’s basic details and an administrator’s email address — that’s it to fill in the form.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('How long does it take?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('The form takes minutes; after activation you run the setup wizard to configure your academic year and structure.') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">{{ __('Do parents need an account to apply?') }}</h3>
            <p class="mt-1 text-slate-600">{{ __('No — parents use the Apply / Apply Online page on your public website without registering themselves.') }}</p>
        </div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">{{ __('Related') }}</h2>
    <ul class="mt-3 space-y-2 text-slate-700">
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help.enroll') }}">{{ __('How to enroll a student') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.admissions') }}">{{ __('Admissions & enrolment product page') }}</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help') }}">{{ __('All help guides') }}</a></li>
    </ul>
</article>
@endsection
