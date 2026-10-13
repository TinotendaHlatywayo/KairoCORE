@extends('marketing.layout')

@section('title', platform_name().' - Contact')
@section('meta_description', __('Contact us via Email, WhatsApp, Facebook, Instagram, or TikTok.'))

@section('content')
@php
    $contactEmail = \Modules\SaaS\Models\PlatformSetting::get('contact', 'email')
        ?: \Modules\SaaS\Models\PlatformSetting::get('notifications', 'super_admin_email', 'hlatwayne@gmail.com');
    $contactWhatsapp = \Modules\SaaS\Models\PlatformSetting::get('contact', 'whatsapp')
        ?: \Modules\SaaS\Models\PlatformSetting::get('notifications', 'whatsapp_number', '263786366855');
    $socialFacebook = \Modules\SaaS\Models\PlatformSetting::get('contact', 'facebook')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'facebook_url');
    $socialInstagram = \Modules\SaaS\Models\PlatformSetting::get('contact', 'instagram')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'instagram_url');
    $socialTiktok = \Modules\SaaS\Models\PlatformSetting::get('contact', 'tiktok')
        ?: \Modules\SaaS\Models\PlatformSetting::get('social', 'tiktok_url');
@endphp
<section class="bg-gradient-to-b from-slate-50 to-white py-16 sm:py-24">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-5xl">Get in Touch</h1>
        <p class="mt-6 text-xl text-slate-600 leading-8">Have questions about KairoCORE or want to schedule a personalized demo? Reach out to us through any of the channels below.</p>

        @if(session('success_message'))
            <div class="mt-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 text-sm">
                {{ session('success_message') }}
            </div>
        @endif

        <div class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Contact Form -->
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-200">
                <h3 class="text-xl font-bold text-slate-900 mb-6">Send us a Message</h3>
                <form action="{{ route('marketing.contact.submit') }}" method="POST" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Your Name</label>
                        <input type="text" name="name" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" placeholder="John Doe">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Your Email</label>
                        <input type="email" name="email" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" placeholder="john@example.com">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-slate-900 focus:ring-slate-900" placeholder="How can we help your school?"></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 px-4 bg-slate-900 text-white font-semibold rounded-lg shadow hover:bg-slate-800 transition">Send Email</button>
                </form>
            </div>

            <!-- Direct Channels & Socials -->
            <div class="space-y-6">
                @if($contactEmail)
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-start gap-4">
                    <div class="p-3 bg-slate-100 rounded-xl text-slate-900">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Email Support</h4>
                        <a href="mailto:{{ $contactEmail }}" class="text-sm text-slate-600 hover:underline">{{ $contactEmail }}</a>
                    </div>
                </div>
                @endif

                @if($contactWhatsapp)
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 flex items-start gap-4">
                    <div class="p-3 bg-green-50 rounded-xl text-green-600">
                        <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">WhatsApp</h4>
                        <a href="https://wa.me/{{ ltrim($contactWhatsapp, '+') }}" target="_blank" rel="noopener" class="text-sm text-slate-600 hover:underline">+{{ ltrim($contactWhatsapp, '+') }}</a>
                    </div>
                </div>
                @endif

                @if($socialFacebook || $socialInstagram || $socialTiktok)
                <div class="grid grid-cols-3 gap-4 pt-2">
                    @if($socialFacebook)
                    <a href="{{ $socialFacebook }}" target="_blank" rel="noopener" class="p-4 bg-white rounded-xl shadow-sm border border-slate-200 text-center hover:bg-slate-50 transition">
                        <span class="block text-xs font-semibold text-slate-900">Facebook</span>
                    </a>
                    @endif
                    @if($socialInstagram)
                    <a href="{{ $socialInstagram }}" target="_blank" rel="noopener" class="p-4 bg-white rounded-xl shadow-sm border border-slate-200 text-center hover:bg-slate-50 transition">
                        <span class="block text-xs font-semibold text-slate-900">Instagram</span>
                    </a>
                    @endif
                    @if($socialTiktok)
                    <a href="{{ $socialTiktok }}" target="_blank" rel="noopener" class="p-4 bg-white rounded-xl shadow-sm border border-slate-200 text-center hover:bg-slate-50 transition">
                        <span class="block text-xs font-semibold text-slate-900">TikTok</span>
                    </a>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection
