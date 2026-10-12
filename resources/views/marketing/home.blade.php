@extends('marketing.layout')

@section('title', platform_name().' - School Management System')
@section('meta_description', __('Modern school management system for admissions, academics, fees, attendance, HR, communications and more.'))

@section('content')
<div class="bg-[#1F2E43] text-white text-center text-xs sm:text-sm py-2 px-4">
    <span>Cloud based - Multi-currency - Built for Zimbabwean schools</span>
</div>
<section class="relative overflow-hidden bg-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8 lg:pt-16">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold tracking-tight text-slate-900 leading-tight">Empower your school with a complete management platform</h1>
                <p class="mt-6 max-w-xl text-base sm:text-lg leading-8 text-slate-600">Admissions, academics, fees & billing, attendance, HR & payroll, communications, library, inventory & procurement, health & wellness - all in one responsive system.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-[#D94B39] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#c14332] transition-colors">Book a personal demo</a>
                    <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Explore Features</a>
                </div>
                <div class="mt-6 flex flex-wrap gap-x-6 gap-y-2 text-sm text-slate-600">
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Easy to use</span>
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Secure & reliable</span>
                    <span class="flex items-center gap-1"><span class="h-1.5 w-1.5 rounded-full bg-green-600"></span>Built for Zimbabwe</span>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-[16/10] w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1600&q=80&auto=format&fit=crop" alt="Students in classroom" class="h-full w-full object-cover" loading="lazy">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="bg-slate-50 py-10">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
            <div class="text-center"><p class="text-3xl font-bold text-slate-900">Admissions</p><p class="mt-1 text-sm text-slate-600">Enrollment &amp; Records</p></div>
            <div class="text-center"><p class="text-3xl font-bold text-slate-900">Academics</p><p class="mt-1 text-sm text-slate-600">Classes &amp; Reports</p></div>
            <div class="text-center"><p class="text-3xl font-bold text-slate-900">Finance</p><p class="mt-1 text-sm text-slate-600">Fees &amp; Billing</p></div>
            <div class="text-center"><p class="text-3xl font-bold text-slate-900">HR &amp; Payroll</p><p class="mt-1 text-sm text-slate-600">Staff Management</p></div>
        </div>
    </div>
</section>
@endsection
