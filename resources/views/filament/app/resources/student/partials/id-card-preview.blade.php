@php
    $orientation = $template->orientation ?? 'landscape';
    $isPortrait = $orientation === 'portrait';
    $templateName = $template->name ?? __('Default template');
@endphp

<div class="std-id-preview">
    <div class="std-id-preview-head">
        <b>{{ __('Live ID Card Preview') }}</b>
        <span>{{ $templateName }}</span>
    </div>

    <div class="std-id-preview-stage {{ $isPortrait ? 'is-portrait' : '' }}">
        <x-id-card-render :student="$student" :template="$template" :school="$school ?? current_tenant()" />
    </div>

    <p class="std-id-preview-note">
        {{ __('This preview uses the active ID card template. The card is scaled to fit; the printed card keeps its original dimensions.') }}
    </p>
</div>
