<x-email-layout
    :school="$school ?? null"
    :heading="__('New admission application received')"
    :brand-name="$schoolName"
>
    <div style="border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;background:#f8fafc;margin-bottom:24px;">
        <p style="margin:0 0 4px;font-size:15px;color:#0f172a;"><strong>{{ __('Application Reference:') }}</strong> {{ $application->application_number }}</p>
        <p style="margin:0;font-size:13px;color:#64748b;">{{ __('Submitted :date', ['date' => $application->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i')]) }}</p>
    </div>

    <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#475569;">
        <strong>{{ __('Applicant Name:') }}</strong> {{ $application->full_name }}<br>
        <strong>{{ __('Gender:') }}</strong> {{ ucfirst($application->gender) }}<br>
        <strong>{{ __('Date of Birth:') }}</strong> {{ $application->date_of_birth?->format('d M Y') ?? __('Not provided') }}<br>
        <strong>{{ __('Parent / Guardian:') }}</strong> {{ $application->parent_name }}<br>
        <strong>{{ __('Parent Email:') }}</strong> <a href="mailto:{{ $application->parent_email }}">{{ $application->parent_email }}</a><br>
        <strong>{{ __('Parent Phone:') }}</strong> {{ $application->parent_phone }}
        @if ($application->course)
            <br><strong>{{ __('Level / Form:') }}</strong> {{ $application->course->name }}
        @endif
    </p>

    <p style="margin:24px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
        {{ __('Please review this application in the Kairo CORE admissions dashboard to progress it to the next stage.') }}
    </p>
</x-email-layout>
