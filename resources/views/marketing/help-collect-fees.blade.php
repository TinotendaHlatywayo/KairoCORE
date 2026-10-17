@extends('marketing.layout')

@section('title', 'How to collect school fees on '.platform_name())
@section('meta_description', 'Collect school fees on '.platform_name().': create fee structures, invoice students, accept EcoCash, Innbucks and ZIPIT payments, and issue receipts and statements.')

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
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'Which payment methods can schools accept?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Schools can record cash, bank transfer, EcoCash, Innbucks and ZIPIT payments, and connect online payment gateways (Paynow, PaynowRTGS, NPS).']],
        ['@type' => 'Question', 'name' => 'Can guardians see what they owe?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes — guardians on the parent portal can view fee history, balances and invoices, and see receipts once payments are recorded.']],
        ['@type' => 'Question', 'name' => 'Are receipts official records?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Receipts and statements are generated from recorded payments with school letterhead details, and are stored in the finance records for audit and history.']],
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

    <h2 class="mt-10 text-2xl font-bold text-slate-900">What it is</h2>
    <p class="mt-3 leading-7 text-slate-600">Fees on {{ platform_name() }} run from structure → invoice → payment → receipt, all inside the Finance workspace. You define what fees exist, invoice students against them, record how each payment arrived, and issue official receipts and statements.</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Why it matters</h2>
    <p class="mt-3 leading-7 text-slate-600">Fee collection is where most schools lose time and trail: spreadsheets, receipt books and bank statements that never quite reconcile. Running the whole flow in one system means every payment is against a real invoice, every receipt is traceable, and guardians can see their own balance without phoning the bursar.</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Steps</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>Create the fee structure</strong> — define fee items per term, class, and category.</li>
        <li><strong>Invoice students</strong> — generate invoices individually or in bulk.</li>
        <li><strong>Accept payment</strong> — record cash, bank, EcoCash, Innbucks, ZIPIT, or connected gateway payments.</li>
        <li><strong>Issue receipts and statements</strong> — print or email official receipts and statements.</li>
    </ol>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Tips</h2>
    <ul class="mt-4 space-y-2 text-slate-700 list-disc list-inside">
        <li>Set up the full fee structure <em>before</em> invoicing the term — bulk invoicing then picks up the right amounts per class automatically.</li>
        <li>Record payments on the day they arrive; a daily cash-up habit keeps balances trustworthy for guardians checking the portal.</li>
        <li>Use statements at the start of each term so guardians see opening balances before new invoices land.</li>
        <li>New students admitted mid-term flow straight from admission into finance — no separate spreadsheet import.</li>
    </ul>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Frequently asked questions</h2>
    <div class="mt-4 space-y-4">
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Which payment methods can we accept?</h3>
            <p class="mt-1 text-slate-600">Cash, bank transfer, EcoCash, Innbucks and ZIPIT out of the box, plus connected online gateways (Paynow, PaynowRTGS, NPS) if you enable them.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Can guardians see what they owe?</h3>
            <p class="mt-1 text-slate-600">Yes — the parent portal shows fee history, balances and invoices, and receipts appear once payments are recorded.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Are receipts official records?</h3>
            <p class="mt-1 text-slate-600">Receipts and statements are generated from recorded payments with your school’s details, and stay in the finance history for audit.</p>
        </div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Related</h2>
    <ul class="mt-3 space-y-2 text-slate-700">
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.school-fees') }}">Fees & finance product page</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help.enroll') }}">How to enroll a student</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help') }}">All help guides</a></li>
    </ul>
</article>
@endsection
