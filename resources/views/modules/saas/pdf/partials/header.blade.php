{{-- Header: full logo top-left, document title + metadata top-right, coral rule underneath. --}}
<table width="100%">
    <tr>
        <td width="50%" style="vertical-align: top;">
            @include('modules.saas.pdf.partials.logo', ['logoUri' => $logoUri, 'cfg' => $cfg])
        </td>
        <td width="50%" style="vertical-align: top;">
            <h1 class="doc-title">{{ $documentTitle }}</h1>
            @foreach(($documentMeta ?? []) as $label => $value)
                <div class="meta-line"><span class="meta-lbl">{{ $label }}</span> {{ $value }}</div>
            @endforeach
        </td>
    </tr>
</table>

<div class="rule"></div>