<?php

// Master module catalog for the platform.
//
// This is the single source of truth for the TOP-LEVEL modules shown in:
//  1. The System Settings -> Manage Modules visibility toggles
//     (Filament page keys: modules_<key>, stored in system_settings as
//      group='modules', key=<key>, value '1'/'0')
//  2. The registration wizard module picker (RegistrationWizard::$availableModules)
//
// Keys must match the module names consumed by
// App\Services\ModuleVisibilityManager::isVisible(). Sub-pages of a module are
// intentionally NOT listed here.

return [
    'admissions' => [
        'name' => 'Admissions',
        'desc' => 'Manage applications and the admission pipeline.',
    ],
    'students' => [
        'name' => 'Student Information System (SIS)',
        'desc' => 'Student profiles, guardians, and academic history.',
    ],
    'academics' => [
        'name' => 'Academic Setup',
        'desc' => 'Courses, class streams, subjects, timetable and promotions.',
    ],
    'exams' => [
        'name' => 'Examinations & Grading',
        'desc' => 'Assessments, grading scales, marks and academic reports.',
    ],
    'attendance' => [
        'name' => 'Attendance Management',
        'desc' => 'Track student and staff attendance.',
    ],
    'hr' => [
        'name' => 'HR, Payroll & Leave',
        'desc' => 'Employees, payroll, leave and disciplinary.',
    ],
    'boarding' => [
        'name' => 'Boarding & Hostels',
        'desc' => 'Hostels, rooms, beds and boarding allocations.',
    ],
    'clinic' => [
        'name' => 'Clinic & Medical',
        'desc' => 'Clinic visits and student medical records.',
    ],
    'library' => [
        'name' => 'Library Management',
        'desc' => 'Book catalog, e-books and borrow/return logs.',
    ],
    'inventory' => [
        'name' => 'Inventory System',
        'desc' => 'Assets, stock, suppliers and procurement.',
    ],
    'finance' => [
        'name' => 'Fee Management & Finance',
        'desc' => 'Fee structures, invoicing, expenses and revenue.',
    ],
    'communication' => [
        'name' => 'Communication & Announcements',
        'desc' => 'Announcements, events and helpdesk.',
    ],
    'website' => [
        'name' => 'Website Builder (CMS)',
        'desc' => 'Host a public-facing website for your school.',
    ],
    'lms' => [
        'name' => 'LMS & Online Lessons',
        'desc' => 'Online lessons and homework.',
    ],
    'knowledge' => [
        'name' => 'Knowledge Base',
        'desc' => 'Knowledge assets and galleries.',
    ],
    'reports' => [
        'name' => 'Reports & Analytics',
        'desc' => 'Reporting dashboards and analytics.',
    ],
    'administration' => [
        'name' => 'Administration',
        'desc' => 'User accounts, roles, departments and audit logs.',
    ],
    'saas' => [
        'name' => 'Subscriptions & Billing',
        'desc' => 'Subscription plans and platform billing.',
    ],
    'digital_assessment' => [
        'name' => 'Digital Assessment & Gamification',
        'desc' => 'Online assessments, question banks, adaptive testing and gamification.',
    ],
];
