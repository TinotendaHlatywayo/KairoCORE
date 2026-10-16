@extends('marketing.layout')

@section('title', marketing_content('meta_title') ?: __('Security & Data Protection - Kairo CORE'))
@section('meta_description', marketing_content('meta_description') ?: __('How Kairo CORE protects school data: tenant isolation, role-based access, encrypted sessions, backups and least-privilege administration.'))

@section('content')
<section class="relative overflow-hidden bg-white pb-10 pt-14 lg:pt-20">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute -top-32 left-1/3 h-96 w-96 rounded-full bg-[#D94B39]/7 blur-3xl"></div>
    </div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#D94B39]">{{ __('Security & Data Protection') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl leading-[1.08]">{{ __('Your school’s data, protected by design') }}</h1>
            <p class="mt-5 text-lg leading-8 text-slate-600">{{ __('Kairo CORE holds the records your school cannot afford to lose — students, guardians, fees and results. This page explains, in plain terms, how the platform keeps that data isolated, access-controlled and recoverable.') }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a href="{{ route('marketing.contact') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#D94B39] px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-[#D94B39]/25 hover:bg-[#c14332]">{{ __('Ask us about security') }}</a>
                <a href="{{ route('platform.terms') }}" class="inline-flex items-center gap-2 rounded-xl border-2 border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">{{ __('Read our terms') }}</a>
            </div>
        </div>

        <div class="mt-14 grid gap-6 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Every school has its own isolated workspace') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Kairo CORE is multi-tenant: each school’s data lives in a separate workspace, and every request is bound to the school you are logged into. One school’s students, fees or documents are never served to another school’s users.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Role-based access for every staff member') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Staff only see and do what their role allows. A form teacher’s view differs from a bursar’s; sensitive areas such as payroll, backups and platform administration require elevated permissions that you control.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Protected sessions and forms') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Login sessions use secure, HTTP-only cookies, with session encryption available, and every state-changing form is protected against cross-site request forgery. Account activation links are single-use and time-limited.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Strong password storage') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Passwords are never stored in readable form; the platform stores salted bcrypt hashes, and password resets go through expiring links rather than reversible copies.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Private documents stay private') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Student documents, invoices, receipts and reports are served only through authenticated, permission-checked endpoints — never as public files, and never listed in sitemaps or search indexes.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Backups you can rely on') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('The platform includes backup management with restore points kept outside day-to-day operation, so a mistake or incident does not have to mean permanent data loss.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Out of reach of search engines by default') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Authenticated areas — workspaces, student portals and administration — are excluded from the public site index and from sitemaps. Private school data is protected by access control, not merely by obscurity.') }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-900">{{ __('Operated with care') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Dependencies are kept current, administrative access is restricted, and configuration defaults favour secure behaviour — such as production environments running with debug output disabled.') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('What we will never claim') }}</h2>
        <p class="mt-4 text-sm leading-7 text-slate-600">{{ __('We do not publish certifications, compliance badges or “bank-level” slogans we cannot substantiate. If your school has specific data-protection requirements — auditor questions, PoPIA or other regulatory reviews, or procurement policies — talk to us and we will answer factually about how the platform works.') }}</p>
        <div class="mt-8 flex flex-wrap gap-x-8 gap-y-3 text-sm font-semibold text-slate-700">
            <a href="{{ route('platform.terms') }}" class="hover:text-[#D94B39]">{{ __('Terms of Service') }}</a>
            <a href="{{ route('marketing.about') }}" class="hover:text-[#D94B39]">{{ __('About Kairo CORE') }}</a>
            <a href="{{ route('marketing.contact') }}" class="hover:text-[#D94B39]">{{ __('Contact') }}</a>
        </div>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ __('Have a security question?') }}</h2>
        <p class="mt-4 text-lg text-slate-200">{{ __('Ask us directly — we prefer an accurate answer over a marketing one.') }}</p>
        <div class="mt-8">
            <a href="{{ route('marketing.contact') }}" class="btn-magnetic inline-flex items-center gap-2 rounded-xl bg-[#EF5F4D] px-6 py-3.5 text-sm font-bold text-white shadow-lg hover:bg-[#D94B39]">{{ __('Contact Kairo CORE') }}</a>
        </div>
    </div>
</section>
@endsection
