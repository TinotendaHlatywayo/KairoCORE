@extends('tenant.layout')

@section('title', 'About Us')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="fw-bold text-success mb-4">{{ __('About Our Institution') }}</h1>
            <p class="fs-5 text-secondary">At {{ $school->name }}, we are dedicated to setting high academic standards across our terms and curriculums.</p>
            <p class="text-muted">{{ __('Our educational system leverages modern interactive interfaces, digital report generation, and robust learning tools to empower both teachers and students. We believe in providing a safe, transparent, and structured study environment.') }}</p>
        </div>
    </div>
</div>
@endsection