@php
    $fonts = $fonts ?? [];
    $selectedFont = $selectedFont ?? 'inter';
    $selectedData = $fonts[$selectedFont] ?? $fonts['inter'] ?? ['css' => '"Inter", sans-serif', 'import' => 'Inter:wght@400;700'];
@endphp

<!--
<div x-data="fontPreview()" x-init="init()">
    <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-5 transition-all duration-300">
        <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            {{ __('Typography Visual Preview') }}
        </h4>
        <div id="typography-preview" 
             class="text-gray-800 dark:text-gray-200 transition-all duration-300"
             x-init="$watch('selectedFont', value => updatePreview(value))"
             :style="'font-family: ' + getFontCss(selectedFont) + '; font-size: 1.25rem; line-height: 1.8;'">
            <p class="font-bold text-xl">{{ __('The quick brown fox jumps over the lazy dog.') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">0123456789 (A B C D E F G H I J K L M N O P Q R S T U V W X Y Z)</p>
        </div>
    </div>
</div>
-->

@push('scripts')
<script>
    function fontPreview() {
        return {
            selectedFont: @json($selectedFont),
            fontMap: @json($fonts),
            init() {
                console.log('🔍 Preview initialized with font:', this.selectedFont);
                this.updatePreview(this.selectedFont);
                
                // Watch for changes on the Filament Select
                const select = document.querySelector('select[name="data.branding_font_family"]');
                if (select) {
                    select.addEventListener('change', (e) => {
                        const newFont = e.target.value;
                        console.log('📝 Select changed to:', newFont);
                        this.selectedFont = newFont;
                        this.updatePreview(newFont);
                    });
                }
                
                // Also listen for Livewire updates
                document.addEventListener('livewire:updated', () => {
                    const select = document.querySelector('select[name="data.branding_font_family"]');
                    if (select && select.value !== this.selectedFont) {
                        const newFont = select.value;
                        console.log('🔄 Livewire updated, new font:', newFont);
                        this.selectedFont = newFont;
                        this.updatePreview(newFont);
                    }
                });
            },
            getFontCss(fontKey) {
                const font = this.fontMap[fontKey] || this.fontMap['inter'] || { css: '"Inter", sans-serif' };
                return font.css;
            },
            updatePreview(fontKey) {
                const font = this.fontMap[fontKey];
                if (!font) {
                    console.warn('⚠️ Font not found:', fontKey);
                    return;
                }
                
                // Load Google Font
                const linkId = 'preview-font-' + fontKey;
                if (!document.getElementById(linkId)) {
                    const link = document.createElement('link');
                    link.id = linkId;
                    link.rel = 'stylesheet';
                    link.href = 'https://fonts.googleapis.com/css2?family=' + font.import + '&display=swap';
                    document.head.appendChild(link);
                    console.log('✅ Loaded preview font:', font.import);
                }
            }
        }
    }
</script>
@endpush