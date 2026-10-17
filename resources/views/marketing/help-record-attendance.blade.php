@extends('marketing.layout')

@section('title', 'How to record student attendance on '.platform_name())
@section('meta_description', 'Record student attendance on '.platform_name().': open the timetable lesson, mark each learner present or absent with remarks, and track class attendance over time.')

@push('seo_jsonld')
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'HowTo',
    'name' => 'How to record student attendance on '.platform_name(),
    'description' => 'Mark student attendance from a timetable lesson: present or absent per learner, with remarks, then review class attendance over time.',
    'step' => [
        ['@type' => 'HowToStep', 'position' => 1, 'name' => 'Open the timetable', 'text' => 'Go to the timetable for the class or stream and find the lesson you are taking.'],
        ['@type' => 'HowToStep', 'position' => 2, 'name' => 'Open Record Attendance', 'text' => 'From the lesson, open the attendance page for that date.'],
        ['@type' => 'HowToStep', 'position' => 3, 'name' => 'Mark each student', 'text' => 'Set each learner present or absent, adding a remark where one is needed.'],
        ['@type' => 'HowToStep', 'position' => 4, 'name' => 'Save the register', 'text' => 'Save so the day’s record is stored against the lesson and counts towards class attendance history.'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        ['@type' => 'Question', 'name' => 'Who records student attendance?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'The teacher taking the lesson — typically the form teacher for daily registers — from the attendance page opened off the timetable lesson.']],
        ['@type' => 'Question', 'name' => 'Can I add a reason for an absence?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Yes — each student’s entry accepts a remark, so notes like “doctor’s appointment” stay attached to the record.']],
        ['@type' => 'Question', 'name' => 'Does attendance show up in reports?', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Saved registers build class and grade attendance history that staff can review over time, instead of entries living in a paper register only.']],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
<article class="mx-auto max-w-3xl px-4 py-14 sm:px-6 lg:px-8">
    <nav class="text-sm text-slate-500" aria-label="Breadcrumb">
        <a href="{{ route('marketing.home') }}" class="hover:text-slate-700">Home</a>
        <span class="mx-1">/</span>
        <a href="{{ route('marketing.help') }}" class="hover:text-slate-700">Help</a>
        <span class="mx-1">/</span>
        <span class="text-slate-700 font-semibold">How to record attendance</span>
    </nav>
    <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900">How to record student attendance on {{ platform_name() }}</h1>

    <h2 class="mt-10 text-2xl font-bold text-slate-900">What it is</h2>
    <p class="mt-3 leading-7 text-slate-600">Attendance on {{ platform_name() }} is recorded against the timetable: each lesson you open lets you mark every learner present or absent for that date, with a remark where one is needed. Saved registers become each class’s attendance history.</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Why it matters</h2>
    <p class="mt-3 leading-7 text-slate-600">A register is the school’s daily truth about who is present — safeguarding, parent communication and performance conversations all start there. When the register lives next to the timetable instead of in a book, patterns are visible (repeated Monday absences, a learner dropping off) without anyone transcribing weeks of ticks.</p>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Steps</h2>
    <ol class="mt-4 space-y-4 text-slate-700 list-decimal list-inside">
        <li><strong>Open the timetable</strong> — find the lesson for the class or stream you are taking.</li>
        <li><strong>Open Record Attendance</strong> — launch the attendance page for that lesson and date.</li>
        <li><strong>Mark each student</strong> — set present or absent per learner, adding remarks where needed.</li>
        <li><strong>Save the register</strong> — the record is stored against the lesson and builds the class’s attendance history.</li>
    </ol>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Tips</h2>
    <ul class="mt-4 space-y-2 text-slate-700 list-disc list-inside">
        <li>Take the register in the first minutes of the lesson — attendance marked later is attendance remembered poorly.</li>
        <li>Use remarks for anything a parent will ask about later; “unwell” in the moment beats reconstructing a week later.</li>
        <li>Review a class’s attendance history before parent meetings — trends are easier to discuss than single days.</li>
        <li>Handover note for your setup: the form teacher designated during setup is responsible for the class’s daily pastoral care and attendance.</li>
    </ul>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Frequently asked questions</h2>
    <div class="mt-4 space-y-4">
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Who records student attendance?</h3>
            <p class="mt-1 text-slate-600">The teacher taking the lesson — typically the form teacher for daily registers — from the page opened off the timetable.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Can I add a reason for an absence?</h3>
            <p class="mt-1 text-slate-600">Yes — each entry accepts a remark, so the reason stays attached to that day’s record.</p>
        </div>
        <div class="rounded-xl border border-slate-200 p-5">
            <h3 class="font-bold text-slate-900">Does attendance feed reports?</h3>
            <p class="mt-1 text-slate-600">Registers build class and grade attendance history that staff can review over time — no paper transcription required.</p>
        </div>
    </div>

    <h2 class="mt-8 text-2xl font-bold text-slate-900">Related</h2>
    <ul class="mt-3 space-y-2 text-slate-700">
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.academics') }}">Academics, exams & report cards product page</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help.reports') }}">How to produce report cards</a></li>
        <li><a class="font-bold text-[#D94B39]" href="{{ route('marketing.help') }}">All help guides</a></li>
    </ul>
</article>
@endsection
