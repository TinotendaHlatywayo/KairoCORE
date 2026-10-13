@extends('marketing.layout')

@section('title', platform_name().' - About')
@section('meta_description', __('KairoCORE is a modern, all-in-one school management system built for African schools — admissions, academics, finance, HR and communication in one platform.'))

@section('content')
<section class="bg-gradient-to-b from-slate-50 to-white py-16 sm:py-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-[#B93A2B]">About us</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">School software built for the way schools actually run</h1>
        <p class="mt-6 text-xl text-slate-600 leading-8">KairoCORE is a modern, all-in-one school management system. It brings admissions, academics, finance, HR, communication, health, inventory and daily operations into one secure, cloud-based platform — for schools of every size.</p>
    </div>
</section>

<section class="bg-white py-16">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Our mission</h2>
                <p class="mt-2 text-slate-600">To give every school the tools to run smoothly — reducing administrative burden, improving transparency, and freeing teachers and administrators to focus on learners.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">Our vision</h2>
                <p class="mt-2 text-slate-600">A future where every school runs on reliable, affordable, modern technology — with real-time insight into academics, finances and day-to-day operations.</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-900">How we build</h2>
                <p class="mt-2 text-slate-600">With schools, not just for them. Every module is shaped by real school workflows, tested with administrators, and kept simple enough to use every day.</p>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-center text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Why schools choose KairoCORE</h2>
        <ul class="mt-10 grid gap-4 sm:grid-cols-2">
            @php
                $points = [
                    'One connected platform for every department',
                    'Built for African schools — multi-currency fees and WhatsApp alerts',
                    'Simple enough for busy admin teams to adopt quickly',
                    'Role-based access and regular backups keep your data safe',
                    'Reports and dashboards that turn daily data into decisions',
                    'A team that understands school operations, on hand to help',
                ];
            @endphp
            @foreach ($points as $point)
                <li class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 flex-shrink-0 text-[#EF5F4D]" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span class="text-sm text-slate-700">{{ $point }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</section>

<section class="bg-[#1F2E43] py-16">
    <div class="mx-auto max-w-4xl px-4 text-center sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold tracking-tight text-white sm:text-4xl">Want to see how KairoCORE fits your school?</h2>
        <p class="mt-4 text-lg text-slate-200">Book a personal demo and we will walk you through it, module by module.</p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#EF5F4D] px-6 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[#D94B39] transition-colors">Book a personal demo</a>
            <a href="{{ route('marketing.contact') }}" class="inline-flex items-center gap-2 rounded-lg border border-white/30 px-6 py-3 text-sm font-semibold text-white hover:bg-white/10 transition-colors">Contact us</a>
        </div>
    </div>
</section>
@endsection
