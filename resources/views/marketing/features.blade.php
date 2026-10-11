@extends('marketing.layout')

@section('title', platform_name().' - Features')
@section('meta_description', __('Explore all features of the school management system - admissions, academics, finance, HR, communications, attendance, library and more.'))

@section('content')
<section class="bg-white py-12">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl">All Features</h1>
            <p class="mt-4 text-lg text-slate-600">Everything you need to run your school efficiently</p>
        </div>
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @php
                $features = [
                    [
                        'name' => 'Admissions',
                        'desc' => 'Manage student enrollment, applications and admission workflow.',
                        'subs' => ['Online Applications', 'Student Registration', 'Admission Approval'],
                        'img' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Academics',
                        'desc' => 'Classes, timetables, subjects, grading and report cards.',
                        'subs' => ['Class Management', 'Timetable Scheduling', 'Report Cards'],
                        'img' => 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Students',
                        'desc' => 'Comprehensive student profiles and records.',
                        'subs' => ['Student Profiles', 'Attendance Tracking', 'Student Directory'],
                        'img' => 'https://images.unsplash.com/photo-1497486751825-1233686d5d80?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Finance & Fees',
                        'desc' => 'Fees, billing, invoicing and financial management.',
                        'subs' => ['Fee Structure', 'Invoicing', 'Financial History'],
                        'img' => 'https://images.unsplash.com/photo-1553729459-efe14ef6055d?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'HR & Payroll',
                        'desc' => 'Staff management, leave, attendance and payroll.',
                        'subs' => ['Staff Records', 'Leave Management', 'Payroll Processing'],
                        'img' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Attendance',
                        'desc' => 'Track student and staff attendance with ease.',
                        'subs' => ['Daily Attendance', 'Attendance Reports', 'SMS Alerts'],
                        'img' => 'https://images.unsplash.com/photo-1511632765486-a01980e01a18?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Communications',
                        'desc' => 'Notices, announcements, messages and alerts.',
                        'subs' => ['Announcements', 'SMS/Email Alerts', 'Parent Communication'],
                        'img' => 'https://images.unsplash.com/photo-1479920252409-6e3d8e8d4866?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Library',
                        'desc' => 'Manage library resources and circulation.',
                        'subs' => ['Book Catalog', 'Issuance Tracking', 'Returns & Fines'],
                        'img' => 'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Transport',
                        'desc' => 'Routes, vehicles, drivers and student transport.',
                        'subs' => ['Route Management', 'Vehicle Tracking', 'Transport Fees'],
                        'img' => 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Inventory',
                        'desc' => 'Supplies, stock, purchases and assets.',
                        'subs' => ['Stock Management', 'Purchase Orders', 'Asset Tracking'],
                        'img' => 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Exams & Results',
                        'desc' => 'Examinations, grading schemes and result publication.',
                        'subs' => ['Exam Scheduling', 'Grade Setup', 'Result Publication'],
                        'img' => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'LMS',
                        'desc' => 'Learning management with assignments and resources.',
                        'subs' => ['Assignments', 'Course Materials', 'Student Submissions'],
                        'img' => 'https://images.unsplash.com/photo-1513258496099-48168024aec0?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Boarding & Hostel',
                        'desc' => 'Hostel management, rooms and allocations.',
                        'subs' => ['Room Allocation', 'Hostel Attendance', 'Boarding Fees'],
                        'img' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Reports & Analytics',
                        'desc' => 'Comprehensive reports and data insights.',
                        'subs' => ['Financial Reports', 'Academic Reports', 'Custom Reports'],
                        'img' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1200&q=80&auto=format&fit=crop',
                    ],
                    [
                        'name' => 'Website CMS',
                        'desc' => 'Manage school website content easily.',
                        'subs' => ['Page Management', 'Media Library', 'Content Editor'],
                        'img' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200&q=80&auto=format&fit=crop',
                    ],
                ];
            @endphp
            @foreach($features as $f)
                <div class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                    <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100">
                        <img src="{{ $f['img'] }}" alt="{{ $f['name'] }}" class="h-full w-full object-cover">
                    </div>
                    <div class="flex flex-1 flex-col p-5">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $f['name'] }}</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $f['desc'] }}</p>
                        <ul class="mt-4 space-y-2">
                            @foreach($f['subs'] as $sub)
                                <li class="flex items-center gap-2 text-sm text-slate-600">
                                    <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/></svg>
                                    <span>{{ $sub }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-6">
                            <a href="{{ url('/platform') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Explore {{ $f['name'] }}</a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
