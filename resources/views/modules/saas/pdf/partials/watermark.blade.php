@if(($cfg['watermark_enabled'] ?? true) && !empty($watermarkUri))
    <div class="watermark"><img src="{{ $watermarkUri }}" alt="" width="560"></div>
@endif