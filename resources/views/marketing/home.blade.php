@extends('marketing.layout')

@section('title', __('SchoolCore ERP - Multi-Tenant School Management Platform'))

@section('content')
@if(session()->has('success_message'))
    <div class="sc-container" style="padding-top: 1.25rem;">
        <div class="sc-alert" role="alert">
            <div>
                <strong>{{ __('Application Submitted!') }}</strong>
                <span>{{ session('success_message') }}</span>
            </div>
            <button type="button" class="sc-alert-close" onclick="this.closest('.sc-alert').remove()" aria-label="{{ __('Close') }}">&times;</button>
        </div>
    </div>
@endif

<section class="sc-hero">
    <span class="sc-hero-orb sc-hero-orb-1"></span>
    <span class="sc-hero-orb sc-hero-orb-2"></span>
    <span class="sc-hero-orb sc-hero-orb-3"></span>
    <div class="sc-container sc-hero-content">
        <span class="sc-hero-eyebrow">{{ __('Enterprise Multi-Tenant Ed-Tech SaaS') }}</span>
        <h1>{{ __('The Operating System for Modern Schools') }}</h1>
        <p>{{ __('Empower your institution with decoupled sub-domain isolation, real-time grading, automated fee ledgers, admissions workflows, and professional institutional websites.') }}</p>
        <div class="sc-hero-actions">
            <a href="{{ route('register') }}" class="sc-btn sc-btn-light sc-btn-lg">{{ __('Register Your School') }}</a>
            <a href="/platform" class="sc-btn sc-btn-light sc-btn-lg">{{ __('Platform Administration') }}</a>
        </div>
    </div>
</section>

<!-- Stats Band -->
<section style="background: #0b1033; color: #fff; padding: 2.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.08);">
    <div class="sc-container">
        <div class="sc-grid text-center" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #06b6d4; margin: 0 0 0.25rem;">100%</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">{{ __('Isolated Tenant Data') }}</p>
            </div>
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #7c6cf0; margin: 0 0 0.25rem;">18+</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">{{ __('Integrated ERP Modules') }}</p>
            </div>
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #10b981; margin: 0 0 0.25rem;">24/7</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">{{ __('Secure Cloud Availability') }}</p>
            </div>
            <div>
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #f59e0b; margin: 0 0 0.25rem;">5 Min</h2>
                <p style="margin: 0; color: #94a3b8; font-size: 0.9rem;">{{ __('Instant School Provisioning') }}</p>
            </div>
        </div>
    </div>
</section>

<section class="sc-section">
    <div class="sc-container">
        <div style="text-align: center; max-width: 700px; margin: 0 auto 3.5rem;">
            <span class="sc-hero-eyebrow" style="background: rgba(91,79,233,0.1); color: var(--sc-indigo); border-color: rgba(91,79,233,0.2);">{{ __('Ecosystem Architecture') }}</span>
            <h2 style="font-size: 2.2rem; font-weight: 800; letter-spacing: -0.02em; margin: 0.75rem 0 1rem;">{{ __('Designed for Founders, Administrators, and Educators') }}</h2>
            <p style="color: var(--sc-slate); font-size: 1.05rem;">{{ __('Every subsystem is crafted for maximum performance, strict regulatory compliance, and delightful user experiences.') }}</p>
        </div>

        <div class="sc-grid">
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">🏫</div>
                        <h3>{{ __('Sub-domain Multi-Tenancy') }}</h3>
                        <p>{{ __('Provision dedicated sub-domains (e.g., yourschool.lvh.me) instantly with automated tenant routing and isolated database scopes.') }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">📊</div>
                        <h3>{{ __('Academics & SIS') }}</h3>
                        <p>{{ __('Manage student information, continuous assessment plans, report card compilation, and attendance tracking effortlessly.') }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">💳</div>
                        <h3>{{ __('Finance & Fees') }}</h3>
                        <p>{{ __('Streamline tuition billing, multi-currency ledgers, automated invoices, payment gateway webhooks, and digital receipts.') }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">🌐</div>
                        <h3>{{ __('Modular CMS Builder') }}</h3>
                        <p>{{ __('Deploy professional institutional websites with swappable templates, drag-and-drop sections, and custom design tokens.') }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">👥</div>
                        <h3>{{ __('HR & Payroll') }}</h3>
                        <p>{{ __('Comprehensive staff management, contract tracking, department permissions, leave management, and automated payslip runs.') }}</p>
                    </div>
                </div>
            </div>
            <div class="sc-col-md-4">
                <div class="sc-card">
                    <div class="sc-card-body">
                        <div class="sc-card-icon">📱</div>
                        <h3>{{ __('Unified Communication') }}</h3>
                        <p>{{ __('Dispatch SMS alerts, parent newsletters, emergency notices, and secure in-workspace chat channels with ease.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="sc-section" style="padding-top: 0;">
    <div class="sc-container">
        <div class="sc-cta">
            <div>
                <h3>{{ __('Ready to modernize your institution?') }}</h3>
                <p>{{ __('Register your school in under two minutes and experience the future of school management.') }}</p>
            </div>
            <div class="sc-cta-actions">
                <a href="{{ route('register') }}" class="sc-btn sc-btn-light sc-btn-lg">{{ __('Register School') }}</a>
            </div>
        </div>
    </div>
</section>
@endsection
