@extends('marketing.layout')

@section('title', 'How to register a school on '.platform_name())
@section('meta_description', 'Register your school on '.platform_name().': create your account online, verify it, and launch your portal with website, admissions, academics, and fees.')

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
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('marketing.home') }}" class="hover:text-slate-700">Home</a>
        <span class="mx-1">/</span>
        <a href="{{ route('marketing.help') }}" class="hover:text-slate-700">Help</a>
        <span class="mx-1">/</span>
        <span class="text-slate-700 font-semibold">How to register a school</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">How to register a school on {{ platform_name() }}</h1>
    <p class="mt-4 text-lg text-slate-600">Registration creates your school account, your staff workspace, and your public website in one flow.</p>
    <h2 class="mt-10 text-2xl font-bold text-slate-900">Steps</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>Open registration</strong> — click <a class="font-bold text-[#D94B39]" href="{{ route('register') }}">Register School</a> and complete the multi-step form.</li>
        <li><strong>Activate the account</strong> — use the time-sensitive link emailed to the administrator.</li>
        <li><strong>Sign in to the workspace</strong> — log in on your school subdomain and finish the setup wizard.</li>
        <li><strong>Publish the website</strong> — customize pages, then share your Apply Online link with parents.</li>
    </ol>
</article>
@endsection
