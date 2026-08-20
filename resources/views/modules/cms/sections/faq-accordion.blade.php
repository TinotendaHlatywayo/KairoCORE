@php
    $title = $block['title'] ?? __('Frequently Asked Questions');
    $faqs = $block['faqs'] ?? [];
@endphp

<div style="display: flex; flex-direction: column; gap: 2rem;">
    <div class="sc-section-head is-center">
        <span class="sc-eyebrow">{{ __('Need Help?') }}</span>
        <h2 {!! 'style="' . $v['titleStyle'] . '"' !!} class="sc-section-title">{{ $title }}</h2>
    </div>

    <div class="sc-faq" x-data="{ open: null }">
        @foreach($faqs as $idx => $faq)
            <div class="sc-faq-item" :class="open === {{ $idx }} ? 'is-open' : ''">
                <button type="button"
                        class="sc-faq-q"
                        :aria-expanded="open === {{ $idx }} ? 'true' : 'false'"
                        :aria-controls="'sc-faq-panel-{{ $idx }}'"
                        @click="open = (open === {{ $idx }} ? null : {{ $idx }})">
                    <span>{{ $faq['q'] }}</span>
                    <span class="sc-faq-icon" aria-hidden="true">+</span>
                </button>
                <div id="sc-faq-panel-{{ $idx }}"
                     class="sc-faq-a"
                     role="region"
                     x-show="open === {{ $idx }}"
                     x-collapse
                     x-cloak>
                    {!! $rich($faq['a']) !!}
                </div>
            </div>
        @endforeach
    </div>
</div>
