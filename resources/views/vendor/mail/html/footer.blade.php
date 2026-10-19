@php
    $brand = email_branding();
    $details = collect([
        $brand['company_address'],
        $brand['company_phone'] !== null ? __('Phone').': '.$brand['company_phone'] : null,
    ])->filter()->implode(' &middot; ');
@endphp
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
@if ($details !== '')
<p style="margin: 8px 0 0 0;">{!! $details !!}</p>
@endif
@if (!empty($brand['company_email']))
<p style="margin: 4px 0 0 0;"><a href="mailto:{{ $brand['company_email'] }}">{{ $brand['company_email'] }}</a></p>
@endif
</td>
</tr>
</table>
</td>
</tr>
