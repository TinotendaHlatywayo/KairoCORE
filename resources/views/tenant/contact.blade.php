@extends('tenant.layout')

@section('title', 'Contact Us')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <h1 class="fw-bold text-success mb-3">{{ __('Contact Administration') }}</h1>
            <p class="text-muted mb-4">{{ __('For enrollment inquiries, fee queries, or student report details, please get in touch with our office front desk.') }}</p>
            
            <div class="card border-0 shadow-sm p-4">
                <form>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Your Name') }}</label>
                        <input type="text" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Email Address') }}</label>
                        <input type="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ __('Message') }}</label>
                        <textarea class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">{{ __('Send Inquiry') }}</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection