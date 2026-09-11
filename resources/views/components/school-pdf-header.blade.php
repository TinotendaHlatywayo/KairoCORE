@props([
    'school' => null,
    'title' => '',
    'subtitle' => '',
    'primaryColor' => '#5b4fe9',
])

@php
    $logoPath = $school?->logo_path;
    $logoData = null;

    if ($logoPath) {
        $candidates = [
            storage_path('app/public/'.$logoPath),
            public_path('storage/'.$logoPath),
            public_path($logoPath),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                $logoData = 'data:image/png;base64,'.base64_encode(file_get_contents($candidate));
                break;
            }
        }
    }

    $contactLines = collect([
        $school?->email_address,
        $school?->phone_number ?: $school?->phone,
        $school?->website_url,
    ])->filter(fn ($value) => filled($value))->implode('  |  ');
@endphp

<table style="width:100%; padding-bottom:10px; margin-bottom:16px; border-bottom:2px solid {{ $primaryColor }};">
    <tr>
        <td style="width:88px; vertical-align:middle;">
            @if ($logoData)
                <img src="{{ $logoData }}" alt="school-logo" style="max-height:70px; max-width:70px;">
            @endif
        </td>
        <td style="vertical-align:middle; padding-left:12px;">
            <div style="font-size:18px; font-weight:bold; color:#0f172a; text-transform:uppercase; letter-spacing:0.5px;">
                {{ $school?->name ?? 'KAIRO DEMO ACADEMY' }}
            </div>
            @if (filled($school?->motto))
                <div style="font-size:9px; color:#64748b; font-style:italic; margin-top:2px;">{{ $school->motto }}</div>
            @endif
            @if (filled($contactLines))
                <div style="font-size:9px; color:#475569; margin-top:4px;">{{ $contactLines }}</div>
            @endif
            @if (filled($school?->physical_address))
                <div style="font-size:9px; color:#475569; margin-top:1px;">
                    Physical address: {{ $school->physical_address }}
                </div>
            @endif
        </td>
        <td style="text-align:right; vertical-align:bottom;">
            @if (filled($title))
                <div style="font-size:15px; font-weight:bold; color:{{ $primaryColor }}; text-transform:uppercase;">{{ $title }}</div>
            @endif
            @if (filled($subtitle))
                <div style="font-size:9px; color:#64748b; margin-top:3px;">{{ $subtitle }}</div>
            @endif
        </td>
    </tr>
</table>