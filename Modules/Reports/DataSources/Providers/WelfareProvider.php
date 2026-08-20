<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class WelfareProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return 'Library, Hostels & Welfare';
    }

    public function datasets(): array
    {
        return [
            $this->d('library.book', 'Library Books', 'library_books', [
                $this->f('title', 'Title'),
                $this->f('subtitle', 'Subtitle'),
                $this->f('publisher', 'Publisher'),
                $this->f('publication_year', 'Publication Year', 'integer'),
                $this->f('isbn', 'ISBN'),
                $this->f('language', 'Language'),
                $this->f('subject', 'Subject'),
                $this->f('grade_level', 'Grade Level'),
                $this->f('media_type', 'Media Type'),
                $this->f('category_name', 'Category', 'string', 'library_book_cat.name'),
            ], [
                'description' => __('Library catalogue with category context.'),
                'autoJoins' => [
                    ['alias' => 'library_book_cat', 'table' => 'library_categories', 'type' => 'left', 'on' => [['library_book_cat.id', 'library_book.library_category_id']]],
                ],
                'connections' => [
                    $this->connect('library.issue', 'library_book.id', 'library_issue_book.id'),
                ],
            ]),

            $this->d('library.issue', 'Book Issues / Circulation', 'library_issues', [
                $this->f('issued_at', 'Issued At', 'datetime'),
                $this->f('due_at', 'Due At', 'datetime'),
                $this->f('returned_at', 'Returned At', 'datetime'),
                $this->f('status', 'Status'),
                $this->f('fine_amount', 'Fine Amount', 'currency'),
                $this->f('renewals_count', 'Renewals', 'integer'),
                $this->f('student_name', 'Student Name', 'string', "CONCAT(library_issue_st.first_name, ' ', library_issue_st.last_name)"),
                $this->f('book_title', 'Book Title', 'string', 'library_issue_book.title'),
                $this->f('class_name', 'Class', 'string', "CONCAT(library_issue_course.name, ' ', library_issue_section.name)"),
                $this->f('overdue', 'Overdue', 'boolean', 'CASE WHEN library_issue.status = \'borrowed\' AND library_issue.due_at < CURRENT_TIMESTAMP THEN 1 ELSE 0 END'),
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

            $this->d('hostel.allocation', 'Hostel Allocations', 'hostel_allocations', [
                $this->f('status', 'Allocation Status'),
                $this->f('allocated_at', 'Allocated At', 'datetime'),
                $this->f('expected_checkout_at', 'Expected Checkout', 'datetime'),
                $this->f('checked_out_at', 'Checked Out At', 'datetime'),
                $this->f('student_name', 'Student Name', 'string', "CONCAT(hostel_allocation_st.first_name, ' ', hostel_allocation_st.last_name)"),
                $this->f('admission_number', 'Admission Number', 'string', 'hostel_allocation_st.admission_number'),
                $this->f('hostel_name', 'Hostel', 'string', 'hostel_allocation_hostel.name'),
                $this->f('building_name', 'Building', 'string', 'hostel_allocation_building.name'),
                $this->f('wing_name', 'Wing', 'string', 'hostel_allocation_wing.name'),
                $this->f('floor_name', 'Floor', 'string', 'hostel_allocation_floor.name'),
                $this->f('room_number', 'Room', 'string', 'hostel_allocation_room.room_number'),
                $this->f('bed_number', 'Bed', 'string', 'hostel_allocation_bed.bed_number'),
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

            $this->d('hostel.hostel', 'Hostels', 'hostels', [
                $this->f('name', 'Hostel Name'),
                $this->f('type', 'Type'),
                $this->f('capacity', 'Capacity', 'integer'),
                $this->f('status', 'Status'),
            ], [
                'description' => __('Hostel properties and capacities.'),
                'connections' => [
                    $this->connect('hostel.allocation', 'hostel_hostel.id', 'hostel_allocation_hostel.id'),
                ],
            ]),

            $this->d('clinic.visit', 'Clinic Visits', 'clinic_visits', [
                $this->f('visit_time', 'Visit Time', 'datetime'),
                $this->f('symptoms', 'Symptoms'),
                $this->f('diagnosis', 'Diagnosis'),
                $this->f('treatment_given', 'Treatment'),
                $this->f('temperature_celsius', 'Temperature (°C)', 'decimal'),
                $this->f('status', 'Status'),
                $this->f('referral_destination', 'Referral'),
                $this->f('student_name', 'Student Name', 'string', "CONCAT(clinic_visit_st.first_name, ' ', clinic_visit_st.last_name)"),
                $this->f('class_name', 'Class', 'string', "CONCAT(clinic_visit_course.name, ' ', clinic_visit_section.name)"),
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

            $this->d('clinic.medical_record', 'Student Medical Records', 'student_medical_records', [
                $this->f('blood_group', 'Blood Group'),
                $this->f('allergies', 'Allergies'),
                $this->f('chronic_conditions', 'Chronic Conditions'),
                $this->f('immunization_history', 'Immunization'),
                $this->f('regular_medications', 'Regular Medications'),
                $this->f('student_name', 'Student Name', 'string', "CONCAT(clinic_medical_st.first_name, ' ', clinic_medical_st.last_name)"),
            ], [
                'description' => __('Health profiles per student.'),
                'autoJoins' => [
                    ['alias' => 'clinic_medical_st', 'table' => 'students', 'type' => 'left', 'on' => [['clinic_medical_st.id', 'clinic_medical_record.student_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'clinic_medical_record.student_id', 'students_register.id'),
                ],
            ]),

            $this->d('knowledge.asset', 'Knowledge Repository', 'knowledge_assets', [
                $this->f('title', 'Title'),
                $this->f('subtitle', 'Subtitle'),
                $this->f('subtype', 'Subtype'),
                $this->f('abstract_description', 'Abstract'),
                $this->f('visibility', 'Visibility'),
                $this->f('publisher', 'Publisher'),
                $this->f('publication_year', 'Publication Year', 'integer'),
                $this->f('language', 'Language'),
                $this->f('media_type', 'Media Type'),
                $this->f('created_at', 'Added At', 'datetime'),
            ], [
                'description' => __('Curated knowledge repository assets.'),
                'connections' => [
                    $this->connect('library.book', 'knowledge_asset.id', 'library_book.id'),
                ],
            ]),

            $this->d('communication.announcement', 'Announcements', 'communication_announcements', [
                $this->f('title', 'Title'),
                $this->f('content', 'Content'),
                $this->f('published_at', 'Published At', 'datetime'),
                $this->f('status', 'Status'),
                $this->f('priority', 'Priority'),
                $this->f('visibility', 'Visibility'),
                $this->f('requires_acknowledgement', 'Requires Ack', 'boolean'),
            ], [
                'description' => __('School announcements.'),
            ]),

            $this->d('communication.event', 'Events', 'communication_events', [
                $this->f('title', 'Title'),
                $this->f('category', 'Category'),
                $this->f('start_time', 'Start', 'datetime'),
                $this->f('end_time', 'End', 'datetime'),
                $this->f('location', 'Location'),
                $this->f('color', 'Color'),
            ], [
                'description' => __('Calendar events and activities.'),
            ]),

            $this->d('communication.ticket', 'Helpdesk Tickets', 'communication_helpdesk_tickets', [
                $this->f('ticket_number', 'Ticket Number'),
                $this->f('category', 'Category'),
                $this->f('subject', 'Subject'),
                $this->f('priority', 'Priority'),
                $this->f('status', 'Status'),
                $this->f('resolved_at', 'Resolved At', 'datetime'),
                $this->f('created_at', 'Opened At', 'datetime'),
            ], [
                'description' => __('Helpdesk support tickets.'),
                'filters' => [
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['open', 'pending', 'resolved', 'closed']],
                ],
            ]),

            $this->d('library.overdue_summary', 'Overdue Library Books', 'SELECT
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
                $this->f('student_name', 'Student Name'),
                $this->f('admission_number', 'Admission Number'),
                $this->f('book_title', 'Book Title'),
                $this->f('issued_at', 'Issued At', 'datetime'),
                $this->f('due_at', 'Due At', 'datetime'),
                $this->f('days_overdue', 'Days Overdue', 'integer'),
                $this->money('fine_amount', 'Fine Amount', 'library_overdue_summary.fine_amount'),
            ], [
                'description' => __('Books currently past their due date, with days overdue and accrued fines.'),
                'default_order' => 'days_overdue|desc',
            ]),

            $this->d('clinic.visit_summary', 'Clinic Visits (per month)', 'SELECT
                    DATE_FORMAT(visit_time, \'%Y-%m\') AS month,
                    COUNT(*) AS visit_count,
                    COUNT(DISTINCT student_id) AS unique_students,
                    COUNT(CASE WHEN referral_destination IS NOT NULL AND referral_destination <> \'\' THEN 1 END) AS referral_count,
                    AVG(temperature_celsius) AS avg_temperature
                FROM clinic_visits
                WHERE school_id = {school_id}
                GROUP BY DATE_FORMAT(visit_time, \'%Y-%m\')', [
                $this->f('month', 'Month'),
                $this->f('visit_count', 'Total Visits', 'integer'),
                $this->f('unique_students', 'Unique Students', 'integer'),
                $this->f('referral_count', 'Referrals', 'integer'),
                $this->f('avg_temperature', 'Avg Temperature (°C)', 'decimal'),
            ], [
                'description' => __('Monthly sick-bay workload for staffing and health-outreach planning.'),
                'default_order' => 'month|asc',
            ]),
        ];
    }
}
