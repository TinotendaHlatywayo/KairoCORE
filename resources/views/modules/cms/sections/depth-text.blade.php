@php
    use Modules\CMS\Services\CmsTemplateService;

    $text = (string) ($block['text'] ?? __('Excellence'));
    $text = function_exists('mb_strtoupper') ? mb_strtoupper($text) : strtoupper($text);

    $layers = max(2, min(8, (int) ($block['layers'] ?? 4)));
    $step = max(1, min(8, (float) ($block['step'] ?? 2.5)));
    $pointerTracking = (bool) ($block['pointer_tracking'] ?? true);
    $autoOrbit = (bool) ($block['auto_orbit'] ?? true);
    $size = max(24, min(200, (int) ($block['title_size'] ?? 72)));
    $vw = round($size / 12, 3);
    $depthColor = CmsTemplateService::safeHex($block['color'] ?? '', '') ?: 'var(--sc-primary)';
@endphp
<section class="sc-depth-section" aria-label="{{ e($text) }}">
    <div class="sc-container" style="text-align: center; padding-block: clamp(3rem, 7vw, 5rem);">
        <div class="sc-depth" data-sc-depth
             data-sc-depth-text="{{ $text }}"
             data-sc-layers="{{ $layers }}"
             data-sc-step="{{ $step }}"
             data-sc-depth-color="{{ $depthColor }}"
             data-sc-depth-orbit="{{ $autoOrbit ? '1' : '0' }}"
             data-sc-depth-pointer="{{ $pointerTracking ? '1' : '0' }}"
             style="font-size: clamp(2.5rem, {{ $vw }}vw, {{ $size }}px);">
            <span class="sc-depth-face">{{ e($text) }}</span>
        </div>
    </div>
</section>
