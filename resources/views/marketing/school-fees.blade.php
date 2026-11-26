@extends('marketing.layout')

@section('title', marketing_text('School Fees & Finance Management Software').' | '.platform_name())
@section('meta_description', marketing_text('Manage school fees end to end: fee structures, bulk invoicing, multi-currency payments, receipts, statements and financial reports on Kairo CORE.'))

@section('content')
<section class="relative overflow-hidden bg-white pb-10 pt-14 lg:pt-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 right-1/4 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ marketing_text('Fees & Finance') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-[1.08]">{{ marketing_text('School fees management, from structure to statement') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ marketing_text('Kairo CORE brings fee structures, invoicing, payments, receipts and reporting into one finance module, so bursars always know what is owed, what has been paid and what is overdue.') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('demo.login') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ marketing_text('Try the live demo') }}</a>
                <a href="{{ route('marketing.help.fees') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">{{ marketing_text('How to collect fees guide') }}</a>
            </div>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Fee structures per class and term') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Define fee items — tuition, boarding, levies, activities — per grade, stream and term. Reuse the same structure across the school instead of rebuilding fee schedules in spreadsheets each term.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Bulk invoicing in minutes') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Generate invoices for a whole class, grade or the entire school at once. Every invoice carries the correct fee items, discounts and balances for that student.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Multi-currency billing') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Bill and reconcile in the currencies your school actually uses, including US dollars and ZiG, with payments recorded against the right invoice every time.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Payments, receipts and reconciliation') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Record cash, bank transfer, mobile money and gateway payments against invoices, issue official receipts, and see daily receipts on the finance dashboard.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Balances, arrears and statements') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('See outstanding balances per student, class and school. Print a full fee statement or financial history for any student when a parent asks.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ marketing_text('Expenses and financial reports') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Track purchases and expenses alongside income, and produce the reports your governing body expects — collection rates, revenue by fee item and expenditure summaries.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ marketing_text('How fee collection works on Kairo CORE') }}</h2>
        <ol class="mt-8 space-y-6">
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">1</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Build the fee structure once') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('Set up fee items and amounts per class and term. The structure drives every invoice that follows, so a change in policy is a change in one place.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">2</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Invoice students in bulk') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('Select the class or grade, generate invoices, and each student carries their own balance forward automatically from previous terms.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">3</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Accept and record payments') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('Record payments as they arrive — counter cash, bank deposits, mobile money or online gateway — against the right invoice, and print receipts on the spot.') }}</p>
                </div>
            </li>
            <li class="flex gap-4">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#1F2E43] text-sm font-bold text-white">4</span>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Report with confidence') }}</h3>
                    <p class="mt-1 text-sm leading-6 text-slate-600">{{ marketing_text('At any point — mid-term or term end — produce collection reports, outstanding-arrears lists and per-student statements without reconciling a single spreadsheet.') }}</p>
                </div>
            </li>
        </ol>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ marketing_text('Frequently asked questions') }}</h2>
        <div class="mt-8 space-y-8">
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Which currencies can we bill in?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Kairo CORE supports multi-currency billing. Schools in Zimbabwe commonly bill in US dollars and ZiG; the same invoice and payment records stay consistent in whichever currencies your school configures.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Can parents see their balances?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Students and guardians get fee checkout access where enabled, and schools can share statements produced from the finance module. Your team always controls what is shared and with whom.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('What about discounts, siblings and part payments?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Invoices support discounts and part payments: every payment reduces the balance, and the remaining arrears stay visible until settled. Ask us during your demo how your school’s fee policy maps onto the system.') }}</p>
            </div>
            <div>
                <h3 class="text-base font-bold text-slate-900">{{ marketing_text('Is our financial data private?') }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ marketing_text('Yes. Every school runs in its own isolated workspace, access is role-based, and financial pages are never exposed to search engines or public links. See our ') }}<a href="{{ route('marketing.security') }}" class="font-semibold text-[#B93A2B] hover:underline">{{ marketing_text('security overview') }}</a>.</p>
            </div>
        </div>
        <div class="mt-10 flex flex-wrap gap-x-8 gap-y-3 text-sm font-semibold text-slate-700">
            <a href="{{ route('marketing.features', ['category' => 'finance']) }}" class="hover:text-[#D94B39]">{{ marketing_text('Explore finance features') }}</a>
            <a href="{{ route('marketing.students') }}" class="hover:text-[#D94B39]">{{ marketing_text('Student records') }}</a>
            <a href="{{ route('marketing.admissions') }}" class="hover:text-[#D94B39]">{{ marketing_text('Admissions & enrolment') }}</a>
            <a href="{{ route('marketing.help') }}" class="hover:text-[#D94B39]">{{ marketing_text('Help Center') }}</a>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ marketing_text('See your fee cycle on Kairo CORE') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ marketing_text('Explore the live demo to see fee structures, invoicing and reporting with sample school numbers.') }}</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('demo.login') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-[#D94B39]">{{ marketing_text('Try the live demo') }}</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-white/30 px-6 py-3.5 text-sm font-bold text-white hover:bg-white/10 transition-colors">{{ marketing_text('Contact us') }}</a>
        </div>
    </div>
</section>
@endsection
