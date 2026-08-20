{{-- Shared print header for finance documents.
     Expects: $financeTheme, $h, $t, $profile, $logoPath, $logoSize, $title, $refs (array of [label, value] pairs). --}}
@php($structure = $financeTheme['structure'] ?? 'classic')
@php($sideLogo = $h['show_logo'] && $logoPath && in_array($h['logo_position'] ?? null, ['left', 'right'], true))
<div class="doc-header style-{{ $structure }}">
    @if($sideLogo)
        @if($h['logo_position'] === 'left')
            <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
                <tr>
                    <td style="width:{{ $logoSize }}px; vertical-align:middle; padding-right:10px;">
                        <img src="{{ $logoPath }}" style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
                    </td>
                    <td style="vertical-align:middle;">
                        <div class="doc-identity" style="text-align:left; margin-bottom:0;">
                            @include('modules.finance.partials.document-identity', ['h' => $h, 'profile' => $profile])
                        </div>
                    </td>
                </tr>
            </table>
        @else
            <table style="width:100%; border-collapse:collapse; margin-bottom:8px;">
                <tr>
                    <td style="vertical-align:middle;">
                        <div class="doc-identity" style="text-align:right; margin-bottom:0; padding-right:10px;">
                            @include('modules.finance.partials.document-identity', ['h' => $h, 'profile' => $profile])
                        </div>
                    </td>
                    <td style="width:{{ $logoSize }}px; vertical-align:middle;">
                        <img src="{{ $logoPath }}" style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
                    </td>
                </tr>
            </table>
        @endif
    @else
        @if($h['show_logo'] && $logoPath)
            <div style="text-align:center; margin-bottom:8px;">
                <img src="{{ $logoPath }}" style="width: {{ $logoSize }}px; height: {{ $logoSize }}px;">
            </div>
        @endif
        <div class="doc-identity">
            @include('modules.finance.partials.document-identity', ['h' => $h, 'profile' => $profile])
        </div>
    @endif

    <div class="doc-title">{{ $title }}</div>
    @if(! empty($t['extra_text']))
        <div class="doc-title-extra">{!! nl2br(e($t['extra_text'])) !!}</div>
    @endif
    @if(! empty($refs))
        <div class="doc-refs">
            @foreach($refs as $ref)
                <strong>{{ $ref[0] }}</strong> {{ $ref[1] }}<br/>
            @endforeach
        </div>
    @endif
</div>