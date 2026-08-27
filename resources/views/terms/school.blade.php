<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('School Terms & Conditions') }} - {{ $school->name ?? 'School Portal' }}</title>
    <style>
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
            line-height: 1.7;
            margin: 0;
            padding: 2rem 1rem;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            padding: 3rem;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gray-100);
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
        }
        h1 {
            font-size: 2rem;
            color: var(--gray-900);
            margin: 0;
        }
        .school-name {
            font-size: 1rem;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        .meta {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.25rem;
        }
        .btn-pdf {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.875rem;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-pdf:hover {
            background: var(--primary-dark);
        }
        .content {
            font-size: 1rem;
        }
        .content h3 {
            font-size: 1.25rem;
            color: var(--gray-900);
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .content p, .content ul, .content ol {
            margin-bottom: 1.25rem;
        }
        .content ul, .content ol {
            padding-left: 1.5rem;
        }
        .content li {
            margin-bottom: 0.5rem;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="javascript:history.back()" class="back-link">&larr; {{ __('Back to Registration') }}</a>
        
        <div class="header">
            <div>
                <div class="school-name">{{ $school->name ?? __('School Portal') }}</div>
                <h1>{{ __('School Terms & Conditions') }}</h1>
                <div class="meta">{{ __('Governing student, parent, and staff platform usage') }}</div>
            </div>
            <a href="{{ route('school.terms.pdf') }}" class="btn-pdf">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                {{ __('Download PDF') }}
            </a>
        </div>

        <div class="content">
            {!! $termsContent !!}
        </div>
    </div>
</body>
</html>