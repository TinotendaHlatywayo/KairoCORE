@extends('tenant.layout')

@section('title', 'Welcome')

@section('content')
<div class="hero-banner py-5">
    <div class="container py-5 text-center">
        <h1 class="display-5 fw-bold text-success mb-3">Welcome to {{ $school->name }}</h1>
        <p class="lead text-secondary mb-4" style="max-width: 650px; margin: 0 auto;">{{ __('Nurturing academic excellence, character development, and lifetime leadership in our community.') }}</p>
        <a href="{{ route('login') }}" class="btn btn-success btn-lg px-4 shadow-sm">{{ __('Access Secure Portal') }}</a>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm p-4 text-center">
                <h4 class="fw-bold mb-3">{{ __('Admissions Open') }}</h4>
                <p class="text-muted">{{ __('Applications for the upcoming academic cycle are now being accepted. Click to register on our online registration system, or log in to track your current student records.') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection