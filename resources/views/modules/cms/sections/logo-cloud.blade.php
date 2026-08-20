<div style="display: flex; flex-direction: column; gap: 1.5rem;">
    <span class="sc-eyebrow" style="justify-content: {{ $v['align'] === 'center' ? 'center' : 'flex-start' }};">{{ $block['title'] ?? __('Accreditations & Partners') }}</span>
    <div class="sc-logos">
        @foreach($block['logos'] ?? [] as $logo)
            <span class="sc-logo-chip">
                @if(!empty($logo['logo_url']))
                    <img src="{{ $logo['logo_url'] }}" alt="{{ $logo['name'] ?? '' }}" height="24" style="max-height: 1.5rem; width: auto;">
                @else
                    {{ $logo['name'] ?? '' }}
                @endif
            </span>
        @endforeach
    </div>
</div>
