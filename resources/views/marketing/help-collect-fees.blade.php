@extends('marketing.layout')

@section('title', 'How to collect school fees on '.platform_name())
@section('meta_description', 'Collect school fees on '.platform_name().': create fee structures, invoice students, accept payments, and issue receipts and statements.')

@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How to collect school fees on '.platform_name(),
    'description' => 'Set up fee structures, invoice students, record payments, and issue receipts and statements.',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Create the fee structure', 'text' => 'Define fee items per term, class, and student category in Finance.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Invoice students', 'text' => 'Generate invoices for students or whole classes.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Accept payment', 'text' => 'Record cash, bank, mobile money, or online gateway payments against each invoice.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Issue receipts and statements', 'text' => 'Print or email official receipts and per-student financial history statements.'],
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
        <span class="text-slate-700 font-semibold">How to collect fees</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">How to collect school fees on {{ platform_name() }}</h1>
    <p class="mt-4 text-lg text-slate-600">Fees run from structure → invoice → payment → receipt, all inside the Finance workspace.</p>
    <h2 class="mt-10 text-2xl font-bold text-slate-900">Steps</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>Create the fee structure</strong> — define fee items per term, class, and category.</li>
        <li><strong>Invoice students</strong> — generate invoices individually or in bulk.</li>
        <li><strong>Accept payment</strong> — record cash, bank, mobile money, or online payments.</li>
        <li><strong>Issue receipts and statements</strong> — print or email official receipts and statements.</li>
    </ol>
</article>
@endsection
