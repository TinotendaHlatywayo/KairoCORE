@extends('marketing.layout')

@section('title', platform_name().' - About')
@section('meta_description', __('About KairoCORE School Management System.'))

@section('content')
<section class="bg-gradient-to-b from-slate-50 to-white py-16 sm:py-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">About KairoCORE</h1>
        <p class="mt-6 text-xl text-slate-600 leading-8">KairoCORE is a modern, responsive, all-in-one school management system designed to streamline admissions, academics, finance, HR, communications, health, inventory, and daily operations for educational institutions of all sizes.</p>
        
        <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Our Mission</h3>
                <p class="text-slate-600">To empower schools with cutting-edge technology that reduces administrative burden, enhances transparency, and fosters exceptional educational outcomes.</p>
            </div>
            <div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Why Choose Us</h3>
                <p class="text-slate-600">Built specifically for African schools with robust multi-currency billing, WhatsApp alerts, offline-resilient sync, and intuitive user experiences.</p>
            </div>
        </div>
    </div>
</section>
@endsection
