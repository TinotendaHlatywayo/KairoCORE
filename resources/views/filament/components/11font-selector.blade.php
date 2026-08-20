@php
    $fonts = $fonts ?? [];
    
    // Sort fonts by category for organized display
    $categories = ['Block', 'Serif', 'Script', 'Decorative'];
    $sortedFonts = [];
    foreach ($categories as $category) {
        foreach ($fonts as $key => $font) {
            if (($font['category'] ?? '') === $category) {
                $sortedFonts[$key] = $font;
            }
        }
    }
@endphp

<!-- Dynamic Font Selector and Live Preview Container [1] -->
<div x-data="{
    selectedFont: @js($getState()),
    fontMap: @js($fonts),
    
    init() {
        // Entangle Alpine state with Filament's form state path [1]
        this.$watch('selectedFont', value => {
            this.$wire.set('{{ $getStatePath() }}', value);
        });

        // Sync with background updates
        this.$wire.$watch('{{ $getStatePath() }}', value => {
            if (value && value !== this.selectedFont) {
                this.selectedFont = value;
                this.updatePreview(value);
            }
        });

        // Load the initial font on load
        if (this.selectedFont) {
            this.updatePreview(this.selectedFont);
        }
    },
    
    getFontCss(fontKey) {
        const font = this.fontMap[fontKey] || this.fontMap['inter'] || { css: '&quot;Inter&quot;, sans-serif' };
        return font.css;
    },
    
    updatePreview(fontKey) {
        const font = this.fontMap[fontKey];
        if (!font) return;
        
        // Dynamically load Google Font stylesheet [1.2]
        const linkId = 'preview-font-' + fontKey;
        if (!document.getElementById(linkId)) {
            const link = document.createElement('link');
            link.id = linkId;
            link.rel = 'stylesheet';
            link.href = 'https://fonts.googleapis.com/css2?family=' + font.import + '&display=swap';
            document.head.appendChild(link);
        }
    }
}" class="space-y-4">

    <!-- Dropdown Selector -->
    <div>
        <label for="font-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ __('System Typography Font') }}
        </label>
        <select id="font-select"
                x-model="selectedFont"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500 transition duration-150 ease-in-out"
                :style="'font-family: ' + getFontCss(selectedFont) + ' !important; font-size: 14px;'">
            @php $currentCategory = ''; @endphp
            @foreach($sortedFonts as $key => $font)
                @php
                    $categoryName = $font['category'] ?? 'Other';
                    $displayName = ucwords(str_replace('_', ' ', $key));
                @endphp
                
                @if($categoryName !== $currentCategory)
                    @if(!$loop->first)
                        </optgroup>
                    @endif
                    <optgroup label="{{ $categoryName }} Styles">
                    @php $currentCategory = $categoryName; @endphp
                @endif
                
                <option value="{{ $key }}" 
                        style="font-family: {{ $font['css'] }} !important;"
                        {{ $key === $getState() ? 'selected' : '' }}>
                    {{ $displayName }}
                </option>
            @endforeach
            @if(!empty($currentCategory))
                </optgroup>
            @endif
        </select>
    </div>

    <!-- Live Preview Container -->
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5 transition-all duration-300">
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            {{ __('Typography Visual Preview') }}
        </h4>
        <div id="typography-preview" 
             class="text-gray-800 dark:text-gray-200 transition-all duration-300"
             :style="'font-family: ' + getFontCss(selectedFont) + '; font-size: 1.25rem; line-height: 1.8;'">
            <p class="font-bold text-xl">{{ __('The quick brown fox jumps over the lazy dog.') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">0123456789 (A B C D E F G H I J K L M N O P Q R S T U V W X Y Z)</p>
        </div>
    </div>
</div>