@extends('marketing.layout')

@section('title', platform_name().' - Contact')
@section('meta_description', __('Contact us.'))

@section('content')
<section class="bg-white py-12">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">Contact</h1>
        <p class="mt-6 text-lg text-slate-600 leading-8">Get in touch to learn more about how KairoCORE can help your school.</p>
    </div>
</section>
@push('seo_jsonld')
    <script type="application/ld+json">{!! json_encode(platform_breadcrumb_list([
        ['name' => __('Home'), 'url' => route('marketing.home')],
        ['name' => __('Contact'), 'url' => route('marketing.contact')],
    ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@endsection
