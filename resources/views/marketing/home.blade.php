@extends('marketing.layout')

@section('title', platform_name().' - School Management System')
@section('meta_description', __('Modern school management system for admissions, academics, fees, attendance, HR, communications and more.'))

@section('content')
<section class="relative overflow-hidden bg-white">
    <div class="mx-auto max-w-7xl px-4 pb-16 pt-10 sm:px-6 lg:px-8 lg:pt-16">
        <div class="grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl">Empower your school with a complete management platform</h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">Admissions, academics, fees & billing, attendance, HR & payroll, communications, library, transport, inventory and more - all in one responsive system.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">Register Your School</a>
                    <a href="{{ url('/platform') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Platform Login</a>
                    <a href="{{ route('marketing.features') }}" class="inline-flex items-center gap-2 rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Explore Features</a>
                </div>
            </div>
            <div class="relative">
                <div class="aspect-[16/10] w-full overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm">
                    <img src="https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1600&q=80&auto=format&fit=crop" alt="Students in classroom" class="h-full w-full object-cover">
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
