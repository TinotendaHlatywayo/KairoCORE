@extends('tenant.layout')

@section('title', 'Administrative Dashboard')

@section('content')
<div class="container py-5">
    <!-- Welcome Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">{{ __('ERP Workspace Dashboard') }}</h2>
            <p class="text-secondary mb-0">{{ __('Active Academic Year:') }} <strong>{{ date('Y') }}</strong> | Logged in as: <span class="badge bg-secondary">{{ auth()->user()->name }}</span></p>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-outline-danger fw-semibold">{{ __('Logout Session') }}</button>
        </form>
    </div>

    <!-- Quick Analytics Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <span class="text-secondary small fw-bold uppercase">{{ __('Total Students') }}</span>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ __('0') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <span class="text-secondary small fw-bold uppercase">{{ __('Active Classes') }}</span>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ __('0') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <span class="text-secondary small fw-bold uppercase">{{ __('Total Faculty') }}</span>
                <h3 class="fw-bold text-dark mt-2 mb-0">{{ __('1') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm p-4 h-100 bg-white">
                <span class="text-secondary small fw-bold uppercase">{{ __('Term Balance') }}</span>
                <h3 class="fw-bold text-success mt-2 mb-0">$0.00</h3>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="row g-4">
        <!-- Installed Modules Card -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold mb-3 text-dark">{{ __('Active System Modules') }}</h5>
                <p class="text-muted small">{{ __('Your active subscription includes access to the following administrative systems:') }}</p>
                
                <div class="row g-2 mt-2">
                    @php
                        $enabled = $school->settings['enabled_modules'] ?? ['students', 'academics', 'attendance'];
                    @endphp
                    @foreach($enabled as $mod)
                        <div class="col-sm-6">
                            <div class="border p-3 rounded d-flex align-items-center bg-light">
                                <span class="fw-bold text-success me-2">{{ __('✔') }}</span>
                                <span class="small fw-semibold text-dark">{{ strtoupper($mod) }} Module</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Quick Access Sidebar -->
        <div class="col-md-4">
            <div class="card border-0 shadow-sm p-4 bg-white h-100">
                <h5 class="fw-bold mb-3 text-dark">{{ __('Quick Operations') }}</h5>
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary text-start fw-semibold py-2">{{ __('Add New Student') }}</button>
                    <button class="btn btn-outline-primary text-start fw-semibold py-2">{{ __('Publish Class Stream') }}</button>
                    <button class="btn btn-outline-primary text-start fw-semibold py-2">{{ __('Configure Fee Structure') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection