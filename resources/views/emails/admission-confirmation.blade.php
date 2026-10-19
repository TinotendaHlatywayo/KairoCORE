<x-email-layout
    :school="$school ?? null"
    :title="$emailSubject"
    :heading="__('Official Admission Confirmation')"
    :brand-name="$schoolName"
    :footer-note="__('Powered by Kairo CORE')"
>
    <p style="margin:0 0 16px 0;font-size:15px;line-height:1.65;color:#475569;white-space:pre-wrap;">{!! nl2br(e($emailBody)) !!}</p>

    <div style="background-color:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:20px;margin:20px 0;">
        <table style="width:100%;border-collapse:collapse;" role="presentation">
            <tr>
                <td style="padding:8px 0;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:600;color:#64748b;width:45%;">{{ __('Student ID Number') }}</td>
                <td style="padding:8px 0;font-size:16px;font-weight:700;color:#059669;font-family:'Courier New',monospace;">{{ $student->student_id_number }}</td>
            </tr>
            <tr>
                <td style="padding:8px 0;font-size:12px;text-transform:uppercase;letter-spacing:.04em;font-weight:600;color:#64748b;">{{ __('Admission Number') }}</td>
                <td style="padding:8px 0;font-size:16px;font-weight:700;color:#059669;font-family:'Courier New',monospace;">{{ $student->admission_number }}</td>
            </tr>
        </table>
    </div>

    <p style="margin:16px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
        {{ __('Please keep this information for your records. For any queries regarding this admission, contact the admissions office.') }}
    </p>

    @if (!empty($activationUrl))
        <div style="text-align:center;padding:26px 0 8px 0;">
            <a href="{{ $activationUrl }}"
               style="display:inline-block;background-color:#059669;color:#ffffff;text-decoration:none;padding:13px 36px;border-radius:10px;font-size:15px;font-weight:700;">
                {{ __('Activate My Account') }}
            </a>
            <p style="margin:14px 0 0 0;font-size:12px;line-height:1.6;color:#94a3b8;">
                {{ __('Use the button above to set your password and activate your student portal account. The link is valid for :hours hours.', ['hours' => config('auth.activation_token_ttl_hours', 48)]) }}
            </p>
        </div>
    @endif
</x-email-layout>
