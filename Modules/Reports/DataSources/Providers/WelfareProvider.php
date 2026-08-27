<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class WelfareProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('Library, Hostels & Welfare');
    }

    public function datasets(): array
    {
        return [
            $this->d('library.book', __('Library Books'), 'library_books', [
                $this->f('title', __('Title')),
                $this->f('subtitle', __('Subtitle')),
                $this->f('publisher', __('Publisher')),
                $this->f('publication_year', __('Publication Year'), 'integer'),
                $this->f('isbn', __('ISBN')),
                $this->f('language', __('Language')),
                $this->f('subject', __('Subject')),
                $this->f('grade_level', __('Grade Level')),
                $this->f('media_type', __('Media Type')),
                $this->f('category_name', __('Category'), 'string', 'library_book_cat.name'),
            ], [
                'description' => __('Library catalogue with category context.'),
                'autoJoins' => [
                    ['alias' => 'library_book_cat', 'table' => 'library_categories', 'type' => 'left', 'on' => [['library_book_cat.id', 'library_book.library_category_id']]],
                ],
                'connections' => [
                    $this->connect('library.issue', 'library_book.id', 'library_issue_book.id'),
                ],
            ]),

            $this->d('library.issue', __('Book Issues / Circulation'), 'library_issues', [
                $this->f('issued_at', __('Issued At'), 'datetime'),
                $this->f('due_at', __('Due At'), 'datetime'),
                $this->f('returned_at', __('Returned At'), 'datetime'),
                $this->f('status', __('Status')),
                $this->f('fine_amount', __('Fine Amount'), 'currency'),
                $this->f('renewals_count', __('Renewals'), 'integer'),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(library_issue_st.first_name, ' ', library_issue_st.last_name)"),
                $this->f('book_title', __('Book Title'), 'string', 'library_issue_book.title'),
                $this->f('class_name', __('Class'), 'string', "CONCAT(library_issue_course.name, ' ', library_issue_section.name)"),
                $this->f('overdue', __('Overdue'), 'boolean', 'CASE WHEN library_issue.status = \'borrowed\' AND library_issue.due_at < CURRENT_TIMESTAMP THEN 1 ELSE 0 END'),
            ], [
                'description' => __('Circulation ledger with student, book and class context.'),
                'autoJoins' => [
                    ['alias' => 'library_issue_copy', 'table' => 'library_book_copies', 'type' => 'left', 'on' => [['library_issue_copy.id', 'library_issue.library_book_copy_id']]],
                    ['alias' => 'library_issue_book', 'table' => 'library_books', 'type' => 'left', 'on' => [['library_issue_book.id', 'library_issue_copy.library_book_id']]],
                    ['alias' => 'library_issue_st', 'table' => 'students', 'type' => 'left', 'on' => [['library_issue_st.id', 'library_issue.student_id']]],
                    ['alias' => 'library_issue_enr', 'table' => 'enrollments', 'type' => 'left', 'on' => [['library_issue_enr.student_id', 'library_issue_st.id']], 'latest' => true],
                    ['alias' => 'library_issue_course', 'table' => 'courses', 'type' => 'left', 'on' => [['library_issue_course.id', 'library_issue_enr.course_id']]],
                    ['alias' => 'library_issue_section', 'table' => 'sections', 'type' => 'left', 'on' => [['library_issue_section.id', 'library_issue_enr.section_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'library_issue.student_id', 'students_register.id'),
                    $this->connect('library.book', 'library_issue_copy.library_book_id', 'library_book.id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['borrowed', 'returned', 'overdue', 'lost']],
                ],
            ]),

            $this->d('hostel.allocation', __('Hostel Allocations'), 'hostel_allocations', [
                $this->f('status', __('Allocation Status')),
                $this->f('allocated_at', __('Allocated At'), 'datetime'),
                $this->f('expected_checkout_at', __('Expected Checkout'), 'datetime'),
                $this->f('checked_out_at', __('Checked Out At'), 'datetime'),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(hostel_allocation_st.first_name, ' ', hostel_allocation_st.last_name)"),
                $this->f('admission_number', __('Admission Number'), 'string', 'hostel_allocation_st.admission_number'),
                $this->f('hostel_name', __('Hostel'), 'string', 'hostel_allocation_hostel.name'),
                $this->f('building_name', __('Building'), 'string', 'hostel_allocation_building.name'),
                $this->f('wing_name', __('Wing'), 'string', 'hostel_allocation_wing.name'),
                $this->f('floor_name', __('Floor'), 'string', 'hostel_allocation_floor.name'),
                $this->f('room_number', __('Room'), 'string', 'hostel_allocation_room.room_number'),
                $this->f('bed_number', __('Bed'), 'string', 'hostel_allocation_bed.bed_number'),
            ], [
                'description' => __('Hostel bed allocations with full location chain.'),
                'autoJoins' => [
                    ['alias' => 'hostel_allocation_st', 'table' => 'students', 'type' => 'left', 'on' => [['hostel_allocation_st.id', 'hostel_allocation.student_id']]],
                    ['alias' => 'hostel_allocation_bed', 'table' => 'hostel_beds', 'type' => 'left', 'on' => [['hostel_allocation_bed.id', 'hostel_allocation.bed_id']]],
                    ['alias' => 'hostel_allocation_room', 'table' => 'hostel_rooms', 'type' => 'left', 'on' => [['hostel_allocation_room.id', 'hostel_allocation_bed.room_id']]],
                    ['alias' => 'hostel_allocation_floor', 'table' => 'hostel_floors', 'type' => 'left', 'on' => [['hostel_allocation_floor.id', 'hostel_allocation_room.floor_id']]],
                    ['alias' => 'hostel_allocation_wing', 'table' => 'hostel_wings', 'type' => 'left', 'on' => [['hostel_allocation_wing.id', 'hostel_allocation_room.wing_id']]],
                    ['alias' => 'hostel_allocation_building', 'table' => 'hostel_buildings', 'type' => 'left', 'on' => [['hostel_allocation_building.id', 'hostel_allocation_floor.building_id']]],
                    ['alias' => 'hostel_allocation_hostel', 'table' => 'hostels', 'type' => 'left', 'on' => [['hostel_allocation_hostel.id', 'hostel_allocation_building.hostel_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'hostel_allocation.student_id', 'students_register.id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['active', 'checked_out', 'pending']],
                ],
            ]),

            $this->d('hostel.hostel', __('Hostels'), 'hostels', [
                $this->f('name', __('Hostel Name')),
                $this->f('type', __('Type')),
                $this->f('capacity', __('Capacity'), 'integer'),
                $this->f('status', __('Status')),
            ], [
                'description' => __('Hostel properties and capacities.'),
                'connections' => [
                    $this->connect('hostel.allocation', 'hostel_hostel.id', 'hostel_allocation_hostel.id'),
                ],
            ]),

            $this->d('clinic.visit', __('Clinic Visits'), 'clinic_visits', [
                $this->f('visit_time', __('Visit Time'), 'datetime'),
                $this->f('symptoms', __('Symptoms')),
                $this->f('diagnosis', __('Diagnosis')),
                $this->f('treatment_given', __('Treatment')),
                $this->f('temperature_celsius', __('Temperature (°C)'), 'decimal'),
                $this->f('status', __('Status')),
                $this->f('referral_destination', __('Referral')),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(clinic_visit_st.first_name, ' ', clinic_visit_st.last_name)"),
                $this->f('class_name', __('Class'), 'string', "CONCAT(clinic_visit_course.name, ' ', clinic_visit_section.name)"),
            ], [
                'description' => __('Sick bay visits with student context.'),
                'autoJoins' => [
                    ['alias' => 'clinic_visit_st', 'table' => 'students', 'type' => 'left', 'on' => [['clinic_visit_st.id', 'clinic_visit.student_id']]],
                    ['alias' => 'clinic_visit_enr', 'table' => 'enrollments', 'type' => 'left', 'on' => [['clinic_visit_enr.student_id', 'clinic_visit_st.id']], 'latest' => true],
                    ['alias' => 'clinic_visit_course', 'table' => 'courses', 'type' => 'left', 'on' => [['clinic_visit_course.id', 'clinic_visit_enr.course_id']]],
                    ['alias' => 'clinic_visit_section', 'table' => 'sections', 'type' => 'left', 'on' => [['clinic_visit_section.id', 'clinic_visit_enr.section_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'clinic_visit.student_id', 'students_register.id'),
                ],
            ]),

            $this->d('clinic.medical_record', __('Student Medical Records'), 'student_medical_records', [
                $this->f('blood_group', __('Blood Group')),
                $this->f('allergies', __('Allergies')),
                $this->f('chronic_conditions', __('Chronic Conditions')),
                $this->f('immunization_history', __('Immunization')),
                $this->f('regular_medications', __('Regular Medications')),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(clinic_medical_st.first_name, ' ', clinic_medical_st.last_name)"),
            ], [
                'description' => __('Health profiles per student.'),
                'autoJoins' => [
                    ['alias' => 'clinic_medical_st', 'table' => 'students', 'type' => 'left', 'on' => [['clinic_medical_st.id', 'clinic_medical_record.student_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'clinic_medical_record.student_id', 'students_register.id'),
                ],
            ]),

            $this->d('knowledge.asset', __('Knowledge Repository'), 'knowledge_assets', [
                $this->f('title', __('Title')),
                $this->f('subtitle', __('Subtitle')),
                $this->f('subtype', __('Subtype')),
                $this->f('abstract_description', __('Abstract')),
                $this->f('visibility', __('Visibility')),
                $this->f('publisher', __('Publisher')),
                $this->f('publication_year', __('Publication Year'), 'integer'),
                $this->f('language', __('Language')),
                $this->f('media_type', __('Media Type')),
                $this->f('created_at', __('Added At'), 'datetime'),
            ], [
                'description' => __('Curated knowledge repository assets.'),
                'connections' => [
                    $this->connect('library.book', 'knowledge_asset.id', 'library_book.id'),
                ],
            ]),

            $this->d('communication.announcement', __('Announcements'), 'communication_announcements', [
                $this->f('title', __('Title')),
                $this->f('content', __('Content')),
                $this->f('published_at', __('Published At'), 'datetime'),
                $this->f('status', __('Status')),
                $this->f('priority', __('Priority')),
                $this->f('visibility', __('Visibility')),
                $this->f('requires_acknowledgement', __('Requires Ack'), 'boolean'),
            ], [
                'description' => __('School announcements.'),
            ]),

            $this->d('communication.event', __('Events'), 'communication_events', [
                $this->f('title', __('Title')),
                $this->f('category', __('Category')),
                $this->f('start_time', __('Start'), 'datetime'),
                $this->f('end_time', __('End'), 'datetime'),
                $this->f('location', __('Location')),
                $this->f('color', __('Color')),
            ], [
                'description' => __('Calendar events and activities.'),
            ]),

            $this->d('communication.ticket', __('Helpdesk Tickets'), 'communication_helpdesk_tickets', [
                $this->f('ticket_number', __('Ticket Number')),
                $this->f('category', __('Category')),
                $this->f('subject', __('Subject')),
                $this->f('priority', __('Priority')),
                $this->f('status', __('Status')),
                $this->f('resolved_at', __('Resolved At'), 'datetime'),
                $this->f('created_at', __('Opened At'), 'datetime'),
            ], [
                'description' => __('Helpdesk support tickets.'),
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['open', 'pending', 'resolved', 'closed']],
                ],
            ]),

            $this->d('library.overdue_summary', __('Overdue Library Books'), 'SELECT
                    CONCAT(st.first_name, \' \', st.last_name) AS student_name,
                    st.admission_number AS admission_number,
                    b.title AS book_title,
                    li.issued_at AS issued_at,
                    li.due_at AS due_at,
                    DATEDIFF(CURRENT_DATE, li.due_at) AS days_overdue,
                    li.fine_amount AS fine_amount
                FROM library_issues li
                JOIN library_book_copies lbc ON lbc.id = li.library_book_copy_id
                JOIN library_books b ON b.id = lbc.library_book_id
                JOIN students st ON st.id = li.student_id
                WHERE li.school_id = {school_id}
                  AND li.status = \'borrowed\'
                  AND li.due_at < CURRENT_TIMESTAMP', [
                $this->f('student_name', __('Student Name')),
                $this->f('admission_number', __('Admission Number')),
                $this->f('book_title', __('Book Title')),
                $this->f('issued_at', __('Issued At'), 'datetime'),
                $this->f('due_at', __('Due At'), 'datetime'),
                $this->f('days_overdue', __('Days Overdue'), 'integer'),
                $this->money('fine_amount', __('Fine Amount'), 'library_overdue_summary.fine_amount'),
            ], [
                'description' => __('Books currently past their due date, with days overdue and accrued fines.'),
                'default_order' => 'days_overdue|desc',
            ]),

            $this->d('clinic.visit_summary', __('Clinic Visits (per month)'), 'SELECT
                    DATE_FORMAT(visit_time, \'%Y-%m\') AS month,
                    COUNT(*) AS visit_count,
                    COUNT(DISTINCT student_id) AS unique_students,
                    COUNT(CASE WHEN referral_destination IS NOT NULL AND referral_destination <> \'\' THEN 1 END) AS referral_count,
                    AVG(temperature_celsius) AS avg_temperature
                FROM clinic_visits
                WHERE school_id = {school_id}
                GROUP BY DATE_FORMAT(visit_time, \'%Y-%m\')', [
                $this->f('month', __('Month')),
                $this->f('visit_count', __('Total Visits'), 'integer'),
                $this->f('unique_students', __('Unique Students'), 'integer'),
                $this->f('referral_count', __('Referrals'), 'integer'),
                $this->f('avg_temperature', __('Avg Temperature (°C)'), 'decimal'),
            ], [
                'description' => __('Monthly sick-bay workload for staffing and health-outreach planning.'),
                'default_order' => 'month|asc',
            ]),
        ];
    }
}
