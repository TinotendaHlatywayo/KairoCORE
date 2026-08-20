@php
    $fonts = $fonts ?? [];
    $selectedFont = $selectedFont ?? 'inter';
    
    // Sort by category
    $categories = ['Block', 'Serif', 'Script', 'Decorative'];
    $sortedFonts = [];
    foreach ($categories as $category) {
        foreach ($fonts as $key => $font) {
            if (($font['category'] ?? '') === $category) {
                $sortedFonts[$key] = $font;
            }
        }
    }
    
    $selectedData = $fonts[$selectedFont] ?? $fonts['inter'] ?? ['css' => '"Inter", sans-serif'];
@endphp

<div class="space-y-1.5">
    <label for="font-select" class="inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
        {{ __('System Typography Font') }}
    </label>
    
    <select id="font-select"
            wire:model.live="data.branding_font_family"
            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500 transition duration-150 ease-in-out"
            style="font-family: {{ $selectedData['css'] }};">
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
                    style="font-family: {{ $font['css'] }};"
                    {{ $key === $selectedFont ? 'selected' : '' }}>
                {{ $displayName }}
            </option>
        @endforeach
        @if(!empty($currentCategory))
            </optgroup>
        @endif
    </select>
</div>