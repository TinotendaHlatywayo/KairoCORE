<!DOCTYPE html>
<html lang="en">
<body style="margin:0;background:#f8fafc;color:#0f172a;font-family:Arial,sans-serif;">
    <div style="max-width:640px;margin:32px auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:28px;">
        <h1 style="margin:0 0 8px;font-size:22px;">{{ __('New admission application received') }}</h1>
        <p style="margin:0 0 24px;color:#475569;">{{ $schoolName }}</p>

        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;background:#f8fafc;margin-bottom:24px;">
            <p style="margin:0 0 4px;"><strong>{{ __('Application Reference:') }}</strong> {{ $application->application_number }}</p>
            <p style="margin:0;color:#64748b;">Submitted {{ $application->created_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</p>
        </div>

        <p><strong>{{ __('Applicant Name:') }}</strong> {{ $application->full_name }}</p>
        <p><strong>{{ __('Gender:') }}</strong> {{ ucfirst($application->gender) }}</p>
        <p><strong>{{ __('Date of Birth:') }}</strong> {{ $application->date_of_birth?->format('d M Y') ?? 'Not provided' }}</p>
        <p><strong>{{ __('Parent / Guardian:') }}</strong> {{ $application->parent_name }}</p>
        <p><strong>{{ __('Parent Email:') }}</strong> <a href="mailto:{{ $application->parent_email }}">{{ $application->parent_email }}</a></p>
        <p><strong>{{ __('Parent Phone:') }}</strong> {{ $application->parent_phone }}</p>
        @if ($application->course)
            <p><strong>{{ __('Level / Form:') }}</strong> {{ $application->course->name }}</p>
        @endif

        <p style="margin-top:24px;font-size:13px;color:#64748b;">
            {{ __('Please review this application in the Kairo CORE admissions dashboard to progress it to the next stage.') }}
        </p>
    </div>
</body>
</html>
