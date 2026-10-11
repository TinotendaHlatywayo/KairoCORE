@extends('marketing.layout')

@section('title', __('About').' - '.platform_name())
@section('meta_description', __(':company is designed for forward-thinking schools, colleges and training institutions needing a single, centralized database for their entire operational workflow.', ['company' => platform_name()]))
@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode(platform_breadcrumb_list([['name' => __('Home'), 'url' => route('marketing.home')], ['name' => __('About'), 'url' => route('marketing.about')]]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<section class="sc-section">
    <div class="sc-container">
        <div class="sc-grid sc-col-center" style="justify-content: center;">
            <div class="sc-col-lg-8">
                <h1 class="sc-page-title">{{ __('About Kairo CORE') }}</h1>
                <p class="sc-page-lead">{{ __('Kairo CORE is designed for forward-thinking schools, colleges, and training institutions requiring a single, centralized database for their entire operational workflows.') }}</p>

                <div class="sc-card">
                    <div class="sc-card-body">
                        <p style="margin: 0; color: var(--sc-muted);">{{ __('By decoupling administrative databases into modular subdomains, our SaaS architecture helps founders deploy high-performance applications to school networks safely, protecting against server configuration overheads and data leakage.') }}</p>
                    </div>
                </div>

                <hr class="sc-hr">

                <h2 style="font-size: 1.4rem; font-weight: 800; letter-spacing: -0.01em; color: var(--sc-ink); margin: 0 0 1.25rem;">{{ __('Platform Core Commitments') }}</h2>
                <div class="sc-grid">
                    <div class="sc-col-md-4">
                        <div class="sc-card">
                            <div class="sc-card-body">
                                <div class="sc-card-icon">🔒</div>
                                <p>{{ __('Strict sub-domain data isolation at the model level.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="sc-col-md-4">
                        <div class="sc-card">
                            <div class="sc-card-body">
                                <div class="sc-card-icon">💳</div>
                                <p>{{ __('Hassle-free payment integrations and collection ledgers.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="sc-col-md-4">
                        <div class="sc-card">
                            <div class="sc-card-body">
                                <div class="sc-card-icon">📱</div>
                                <p>{{ __('Dynamic multi-device interfaces scaled to any browser viewport.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
