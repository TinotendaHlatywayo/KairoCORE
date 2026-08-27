@extends('marketing.layout')

@section('title', __('Contact - Kairo CORE'))

@section('content')
<section class="sc-section">
    <div class="sc-container">
        <div class="sc-grid" style="justify-content: center;">
            <div class="sc-col-lg-6">
                <h1 class="sc-page-title">{{ __('Contact Support') }}</h1>
                <p class="sc-page-lead">{{ __('Have questions regarding platform plans, pricing tiers, or deployment options? Reach out below.') }}</p>

                <div class="sc-card">
                    <div class="sc-card-body">
                        <form class="sc-form" action="{{ route('marketing.contact.submit') }}" method="POST">
                            @csrf
                            <div class="sc-field">
                                <label class="sc-form-label" for="contact-name">{{ __('Your Name') }}</label>
                                <input id="contact-name" type="text" name="name" class="sc-input" required>
                            </div>
                            <div class="sc-field">
                                <label class="sc-form-label" for="contact-email">{{ __('Email Address') }}</label>
                                <input id="contact-email" type="email" name="email" class="sc-input" required>
                            </div>
                            <div class="sc-field">
                                <label class="sc-form-label" for="contact-message">{{ __('Message') }}</label>
                                <textarea id="contact-message" name="message" class="sc-textarea" rows="4" required></textarea>
                            </div>
                            <div>
                                <button type="submit" class="sc-btn sc-btn-primary sc-btn-block">{{ __('Send Message') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
