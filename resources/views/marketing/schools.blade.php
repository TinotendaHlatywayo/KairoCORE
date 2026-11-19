@extends('marketing.layout')

@section('title', marketing_text('School websites') . ' - ' . platform_name())
@section('meta_description', marketing_text('Browse public websites of schools using Kairo CORE.'))
@section('seo_image', asset('images/og-schools.png'))

@section('content')
@php
    use Illuminate\Support\Facades\Schema;
    use Modules\CMS\Models\CmsWebsite;
    $websites = collect();
    if (Schema::hasTable('cms_websites')) {
        try {
            $websites = CmsWebsite::with('school')
                ->where('is_template_site', false)
                ->get()
                ->filter(fn ($website) => $website->school && filled($website->school->subdomain))
                ->sortBy(fn ($website) => $website->school->name);
        } catch (\Throwable $e) {
            $websites = collect();
        }
    }
    $host = parse_url(config('app.url'), PHP_URL_HOST);
@endphp
<section class="w" style="padding:40px 0">
    <h1>{{ marketing_text('School websites') }}</h1>
    <p>{{ marketing_text('Public websites of schools powered by Kairo CORE.') }}</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px;margin-top:24px">
        @foreach ($websites as $website)
            @php $school = $website->school; @endphp
            <a href="https://{{ $school->subdomain }}.{{ $host }}/" style="display:block;padding:16px;border:1px solid #e2e8f0;border-radius:12px;text-decoration:none">
                <strong>{{ $school->name }}</strong>
                <div style="color:#64748b;margin-top:4px">{{ $school->motto ?: marketing_text('Official school website') }}</div>
            </a>
        @endforeach
    </div>
</section>
@endsection
