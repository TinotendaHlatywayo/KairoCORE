@php
    $ids = $students->pluck('id')->map(fn ($id) => (string) $id)->implode(',');
    $createUrl = \App\Filament\App\Resources\CardTemplateResource::getUrl('create');
    $useDefaultUrl = ! empty($downloadPng)
        ? route('students.download-pngs', ['ids' => $ids, 'use_default' => '1'])
        : route('students.print-cards', ['ids' => $ids, 'layout' => $layout ?? 'pvc', 'use_default' => '1']);
    $backUrl = url()->previous();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('No Active ID Card Template') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
            padding: 24px;
            color: #0f172a;
        }
        .card {
            width: 100%;
            max-width: 560px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 20px 40px -12px rgba(15, 23, 42, 0.2);
            overflow: hidden;
        }
        .card-header {
            background: #312e81;
            color: #ffffff;
            padding: 28px 28px 22px;
            text-align: center;
        }
        .card-header .badge {
            display: inline-block;
            background: rgba(255,255,255,0.16);
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.04em;
            padding: 4px 14px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .card-header h1 { font-size: 20px; font-weight: 700; }
        .card-header p { margin-top: 8px; font-size: 14px; color: #c7d2fe; }
        .card-body { padding: 26px 28px 30px; }
        .card-body .lead { font-size: 15px; line-height: 1.6; color: #334155; margin-bottom: 22px; }
        .options { display: flex; flex-direction: column; gap: 14px; }
        .option {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }
        .option:hover {
            border-color: #6366f1;
            box-shadow: 0 8px 20px -8px rgba(99, 102, 241, 0.4);
            background: #f8faff;
        }
        .option .icon {
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .option.primary .icon { background: #eef2ff; color: #4338ca; }
        .option.default .icon { background: #fefce8; color: #a16207; }
        .option h2 { font-size: 15px; font-weight: 700; margin-bottom: 3px; }
        .option p { font-size: 13px; color: #64748b; }
        .foot { margin-top: 20px; text-align: center; font-size: 13px; color: #94a3b8; }
        .foot a { color: #4338ca; text-decoration: none; font-weight: 600; }
        .foot a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <span class="badge">{{ __('ID Card Printing') }}</span>
            <h1>{{ __('No Active ID Card Template') }}</h1>
            <p>{{ $school->name }}</p>
        </div>
        <div class="card-body">
            <p class="lead">
                {{ __('You are about to print ') }}
                <strong>{{ $students->count() === 1 ? __('1 student card') : __(':count student cards', ['count' => $students->count()]) }}</strong>.
                {{ __('No active ID card template is set for this school yet. Please choose how you would like to proceed.') }}
            </p>
            <div class="options">
                <a class="option primary" href="{{ $useDefaultUrl }}">
                    <div class="icon">&#128424;</div>
                    <div>
                        <h2>{{ __('Use Default Template') }}</h2>
                        <p>{{ __('Print now with the built-in Premium Royal Gold (landscape) design. You can customize later.') }}</p>
                    </div>
                </a>
                <a class="option default" href="{{ $createUrl }}">
                    <div class="icon">&#9998;</div>
                    <div>
                        <h2>{{ __('Create a Template') }}</h2>
                        <p>{{ __('Open the ID Card Designer to build and activate a custom template, then print afterwards.') }}</p>
                    </div>
                </a>
            </div>
            <div class="foot">
                <a href="{{ $backUrl }}">&#8592; {{ __('Back to the previous page') }}</a>
            </div>
        </div>
    </div>
</body>
</html>
