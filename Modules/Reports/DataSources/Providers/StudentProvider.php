<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class StudentProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('Students');
    }

    public function datasets(): array
    {
        $stu = 'students_register';

        return [
            $this->d('students.register', __('Students (Register)'), 'students', [
                $this->f('admission_number', __('Admission Number')),
                $this->f('student_id_number', __('Student ID Number')),
                $this->f('national_id', __('National ID / Passport')),
                $this->f('first_name', __('First Name')),
                $this->f('last_name', __('Last Name')),
                $this->f('full_name', __('Full Name'), 'string', "CONCAT({$stu}.first_name, ' ', {$stu}.last_name)"),
                $this->f('gender', __('Gender')),
                $this->f('date_of_birth', __('Date of Birth'), 'date'),
                $this->f('admission_date', __('Admission Date'), 'date'),
                $this->f('status', __('Status')),
                $this->f('boarding_status', __('Boarding / Day Scholar')),
                $this->f('house', __('House')),
                $this->f('blood_group', __('Blood Group')),
                $this->f('class_name', __('Class Stream'), 'string', "CONCAT({$stu}_course.name, ' ', {$stu}_section.name)"),
                $this->f('course_name', __('Course'), 'string', "{$stu}_course.name"),
                $this->f('section_name', __('Section'), 'string', "{$stu}_section.name"),
                $this->f('roll_number', __('Roll Number'), 'string', "{$stu}_enr.roll_number"),
                $this->f('medical_notes', __('Medical Notes')),
                $this->f('emergency_contact_name', __('Emergency Contact')),
                $this->f('emergency_contact_phone', __('Emergency Contact Phone')),
                $this->f('guardian_name', __('Guardian Name'), 'string', "{$stu}_grd.name"),
                $this->f('guardian_phone', __('Guardian Phone'), 'string', "{$stu}_grd.phone"),
                $this->f('guardian_email', __('Guardian Email'), 'string', "{$stu}_grd.email"),
                $this->f('guardian_relationship', __('Guardian Relationship'), 'string', "{$stu}_grd.relationship"),
            ], [
                'description' => __('Current student register enriched with latest enrollment (class stream), guardian and emergency contacts.'),
                'autoJoins' => [
                    [
                        'alias' => "{$stu}_enr", 'table' => 'enrollments', 'type' => 'left',
                        'on' => [["{$stu}_enr.student_id", "{$stu}.id"]],
                        'latest' => true,
                    ],
                    ['alias' => "{$stu}_course", 'table' => 'courses', 'type' => 'left', 'on' => [["{$stu}_course.id", "{$stu}_enr.course_id"]]],
                    ['alias' => "{$stu}_section", 'table' => 'sections', 'type' => 'left', 'on' => [["{$stu}_section.id", "{$stu}_enr.section_id"]]],
                    ['alias' => "{$stu}_sg", 'table' => 'student_guardian', 'type' => 'left', 'on' => [["{$stu}_sg.student_id", "{$stu}.id"]]],
                    ['alias' => "{$stu}_grd", 'table' => 'guardians', 'type' => 'left', 'on' => [["{$stu}_grd.id", "{$stu}_sg.guardian_id"]]],
                ],
                'connections' => [
                    $this->connect('finance.invoice', "{$stu}.id", 'finance_invoice.student_id'),
                    $this->connect('finance.balance', "{$stu}.id", 'finance_balance.student_id'),
                    $this->connect('attendance.summary', "{$stu}.id", 'attendance_summary.student_id'),
                    $this->connect('attendance.daily', "{$stu}.id", 'attendance_daily.student_id'),
                    $this->connect('academics.enrollment', "{$stu}.id", 'academics_enrollment.student_id'),
                    $this->connect('academics.mark_record', "{$stu}.id", 'academics_mark_record.student_id'),
                    $this->connect('academics.performance', "{$stu}.id", 'academics_performance.student_id'),
                    $this->connect('hostel.allocation', "{$stu}.id", 'hostel_allocation.student_id'),
                    $this->connect('library.issue', "{$stu}.id", 'library_issue.student_id'),
                    $this->connect('clinic.visit', "{$stu}.id", 'clinic_visit.student_id'),
                    $this->connect('clinic.medical_record', "{$stu}.id", 'clinic_medical_record.student_id'),
                    $this->connect('finance.payment_plan', "{$stu}.id", 'finance_payment_plan.student_id'),
                ],
                'filters' => [
                    ['key' => 'gender', 'label' => __('Gender'), 'type' => 'select', 'options' => ['Male', 'Female', 'Other']],
                    ['key' => 'status', 'label' => __('Status'), 'type' => 'select', 'options' => ['active', 'inactive', 'graduated', 'transferred', 'suspended']],
                    ['key' => 'boarding_status', 'label' => __('Boarding Status'), 'type' => 'select', 'options' => ['boarding', 'day']],
                ],
            ]),

            $this->d('attendance.summary', __('Attendance Summary (per student)'), 'SELECT
                    student_id AS student_id,
                    COUNT(*) AS total_days,
                    SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) AS present_days,
                    SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) AS absent_days,
                    SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) AS late_days
                FROM student_attendances
                WHERE school_id = {school_id}
                GROUP BY student_id', [
                $this->f('total_days', __('Total School Days'), 'integer'),
                $this->f('present_days', __('Days Present'), 'integer'),
                $this->f('absent_days', __('Days Absent'), 'integer'),
                $this->f('late_days', __('Days Late'), 'integer'),
                $this->pct('attendance_rate', __('Attendance Rate'), 'ROUND(100.0 * attendance_summary.present_days / NULLIF(attendance_summary.total_days, 0), 2)'),
            ], [
                'description' => __('Aggregated attendance per student — enables attendance % and absent-day filters across any other module.'),
                'connections' => [
                    $this->connect('students.register', 'attendance_summary.student_id', 'students_register.id'),
                    $this->connect('academics.performance', 'attendance_summary.student_id', 'academics_performance.student_id'),
                ],
            ]),

            $this->d('attendance.daily', __('Attendance (daily rows)'), 'student_attendances', [
                $this->f('date', __('Attendance Date'), 'date'),
                $this->f('status', __('Status'), 'string', null, null),
                $this->f('remarks', __('Remarks')),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(attendance_daily_st.first_name, ' ', attendance_daily_st.last_name)"),
                $this->f('class_name', __('Class Stream'), 'string', "CONCAT(attendance_daily_course.name, ' ', attendance_daily_section.name)"),
            ], [
                'description' => __('Raw daily attendance rows with student and class context.'),
                'autoJoins' => [
                    ['alias' => 'attendance_daily_st', 'table' => 'students', 'type' => 'left', 'on' => [['attendance_daily_st.id', 'attendance_daily.student_id']]],
                    ['alias' => 'attendance_daily_enr', 'table' => 'enrollments', 'type' => 'left', 'on' => [['attendance_daily_enr.student_id', 'attendance_daily_st.id']], 'latest' => true],
                    ['alias' => 'attendance_daily_course', 'table' => 'courses', 'type' => 'left', 'on' => [['attendance_daily_course.id', 'attendance_daily_enr.course_id']]],
                    ['alias' => 'attendance_daily_section', 'table' => 'sections', 'type' => 'left', 'on' => [['attendance_daily_section.id', 'attendance_daily_enr.section_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'attendance_daily.student_id', 'students_register.id'),
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Attendance Status'), 'type' => 'select', 'options' => ['present', 'absent', 'late', 'excused']],
                ],
            ]),

            $this->d('academics.enrollment', __('Enrollments (Students ↔ Courses)'), 'enrollments', [
                $this->f('roll_number', __('Roll Number')),
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(academics_enrollment_st.first_name, ' ', academics_enrollment_st.last_name)"),
                $this->f('admission_number', __('Admission Number'), 'string', 'academics_enrollment_st.admission_number'),
                $this->f('course_name', __('Course'), 'string', 'academics_enrollment_course.name'),
                $this->f('section_name', __('Section'), 'string', 'academics_enrollment_section.name'),
                $this->f('academic_year', __('Academic Year'), 'string', 'academics_enrollment_ay.name'),
            ], [
                'description' => __('Student enrollment records joined with course, section and academic year.'),
                'autoJoins' => [
                    ['alias' => 'academics_enrollment_st', 'table' => 'students', 'type' => 'left', 'on' => [['academics_enrollment_st.id', 'academics_enrollment.student_id']]],
                    ['alias' => 'academics_enrollment_course', 'table' => 'courses', 'type' => 'left', 'on' => [['academics_enrollment_course.id', 'academics_enrollment.course_id']]],
                    ['alias' => 'academics_enrollment_section', 'table' => 'sections', 'type' => 'left', 'on' => [['academics_enrollment_section.id', 'academics_enrollment.section_id']]],
                    ['alias' => 'academics_enrollment_ay', 'table' => 'academic_years', 'type' => 'left', 'on' => [['academics_enrollment_ay.id', 'academics_enrollment.academic_year_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'academics_enrollment.student_id', 'students_register.id'),
                ],
            ]),

            $this->d('academics.subject', __('Subjects'), 'subjects', [
                $this->f('name', __('Subject Name')),
                $this->f('code', __('Subject Code')),
                $this->f('type', __('Subject Type')),
                $this->f('credit_weight', __('Credit Weight'), 'integer'),
            ], [
                'description' => __('Subjects offered by the school.'),
                'connections' => [
                    $this->connect('academics.mark_record', 'academics_subject.id', 'academics_mark_record.subject_id'),
                ],
            ]),

            $this->d('academics.mark_record', __('Student Marks (per subject / paper)'), 'mark_records', [
                $this->f('student_name', __('Student Name'), 'string', "CONCAT(academics_mark_record_st.first_name, ' ', academics_mark_record_st.last_name)"),
                $this->f('admission_number', __('Admission Number'), 'string', 'academics_mark_record_st.admission_number'),
                $this->f('subject_name', __('Subject'), 'string', 'academics_mark_record_sub.name'),
                $this->f('paper_name', __('Paper'), 'string', 'academics_mark_record_paper.name'),
                $this->f('course_name', __('Course'), 'string', 'academics_mark_record_course.name'),
                $this->f('section_name', __('Section'), 'string', 'academics_mark_record_section.name'),
                $this->f('bot_mark', __('BOT Mark'), 'decimal'),
                $this->f('mot_mark', __('MOT Mark'), 'decimal'),
                $this->f('eot_mark', __('EOT Mark'), 'decimal'),
                $this->f('c1_mark', __('C1 Mark'), 'decimal'),
                $this->f('c2_mark', __('C2 Mark'), 'decimal'),
                $this->f('c3_mark', __('C3 Mark'), 'decimal'),
                $this->f('total_score', __('Total Score'), 'decimal',
                    'COALESCE(academics_mark_record.bot_mark,0) + COALESCE(academics_mark_record.mot_mark,0) + COALESCE(academics_mark_record.eot_mark,0) + COALESCE(academics_mark_record.c1_mark,0) + COALESCE(academics_mark_record.c2_mark,0) + COALESCE(academics_mark_record.c3_mark,0)'),
                $this->f('average_score', __('Average Mark'), 'decimal',
                    'ROUND((COALESCE(academics_mark_record.bot_mark,0) + COALESCE(academics_mark_record.mot_mark,0) + COALESCE(academics_mark_record.eot_mark,0)) / 3.0, 2)'),
            ], [
                'description' => __('Mark records per student per subject paper with class context.'),
                'autoJoins' => [
                    ['alias' => 'academics_mark_record_enr', 'table' => 'enrollments', 'type' => 'left', 'on' => [['academics_mark_record_enr.id', 'academics_mark_record.enrollment_id']]],
                    ['alias' => 'academics_mark_record_st', 'table' => 'students', 'type' => 'left', 'on' => [['academics_mark_record_st.id', 'academics_mark_record_enr.student_id']]],
                    ['alias' => 'academics_mark_record_course', 'table' => 'courses', 'type' => 'left', 'on' => [['academics_mark_record_course.id', 'academics_mark_record_enr.course_id']]],
                    ['alias' => 'academics_mark_record_section', 'table' => 'sections', 'type' => 'left', 'on' => [['academics_mark_record_section.id', 'academics_mark_record_enr.section_id']]],
                    ['alias' => 'academics_mark_record_sub', 'table' => 'subjects', 'type' => 'left', 'on' => [['academics_mark_record_sub.id', 'academics_mark_record.subject_id']]],
                    ['alias' => 'academics_mark_record_paper', 'table' => 'subject_papers', 'type' => 'left', 'on' => [['academics_mark_record_paper.id', 'academics_mark_record.subject_paper_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'academics_mark_record_enr.student_id', 'students_register.id'),
                    $this->connect('academics.subject', 'academics_mark_record.subject_id', 'academics_subject.id'),
                ],
            ]),

            $this->d('academics.performance', __('Academic Performance Summary (per student)'), 'SELECT
                    enr.student_id AS student_id,
                    COUNT(DISTINCT mr.subject_id) AS subjects_taken,
                    ROUND(AVG((COALESCE(mr.bot_mark,0) + COALESCE(mr.mot_mark,0) + COALESCE(mr.eot_mark,0)) / 3.0), 2) AS average_mark,
                    ROUND(AVG((COALESCE(mr.bot_mark,0) + COALESCE(mr.mot_mark,0) + COALESCE(mr.eot_mark,0)) / 3.0) / 20.0 * 100.0, 2) AS score_percent
                FROM mark_records mr
                JOIN enrollments enr ON enr.id = mr.enrollment_id
                WHERE mr.school_id = {school_id}
                GROUP BY enr.student_id', [
                $this->f('subjects_taken', __('Subjects Taken'), 'integer'),
                $this->f('average_mark', __('Average Mark (out of 20)'), 'decimal'),
                $this->pct('score_percent', __('Overall Score %'), 'academics_performance.score_percent'),
            ], [
                'description' => __('Per-student academic summary computed from mark records.'),
                'connections' => [
                    $this->connect('students.register', 'academics_performance.student_id', 'students_register.id'),
                    $this->connect('attendance.summary', 'academics_performance.student_id', 'attendance_summary.student_id'),
                ],
            ]),

            $this->d('attendance.class_summary', __('Attendance Summary (per class stream)'), 'SELECT
                    CONCAT(c.name, \' \', s.name) AS class_name,
                    COUNT(DISTINCT a.student_id) AS total_students,
                    COUNT(*) AS total_records,
                    SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) AS present_days,
                    SUM(CASE WHEN a.status = \'absent\' THEN 1 ELSE 0 END) AS absent_days,
                    SUM(CASE WHEN a.status = \'late\' THEN 1 ELSE 0 END) AS late_days,
                    SUM(CASE WHEN a.status = \'excused\' THEN 1 ELSE 0 END) AS excused_days,
                    ROUND(100.0 * SUM(CASE WHEN a.status = \'present\' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS attendance_rate
                FROM student_attendances a
                JOIN enrollments e ON e.student_id = a.student_id
                    AND e.id = (SELECT MAX(e2.id) FROM enrollments e2 WHERE e2.student_id = a.student_id)
                JOIN courses c ON c.id = e.course_id
                JOIN sections s ON s.id = e.section_id
                WHERE a.school_id = {school_id}
                GROUP BY c.name, s.name', [
                $this->f('class_name', __('Class Stream')),
                $this->f('total_students', __('Students Covered'), 'integer'),
                $this->f('total_records', __('Attendance Records'), 'integer'),
                $this->f('present_days', __('Days Present'), 'integer'),
                $this->f('absent_days', __('Days Absent'), 'integer'),
                $this->f('late_days', __('Days Late'), 'integer'),
                $this->f('excused_days', __('Days Excused'), 'integer'),
                $this->pct('attendance_rate', __('Attendance Rate'), 'attendance_class_summary.attendance_rate'),
            ], [
                'description' => __('Attendance aggregates per class stream — compare class-level attendance health.'),
                'default_order' => 'attendance_rate|asc',
            ]),

            $this->d('attendance.monthly_trend', __('Attendance Trend (per month)'), 'SELECT
                    DATE_FORMAT(date, \'%Y-%m\') AS month,
                    COUNT(*) AS total_records,
                    SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) AS present_days,
                    SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) AS absent_days,
                    SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) AS late_days,
                    SUM(CASE WHEN status = \'excused\' THEN 1 ELSE 0 END) AS excused_days,
                    ROUND(100.0 * SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0), 2) AS attendance_rate
                FROM student_attendances
                WHERE school_id = {school_id}
                GROUP BY DATE_FORMAT(date, \'%Y-%m\')', [
                $this->f('month', __('Month')),
                $this->f('total_records', __('Attendance Records'), 'integer'),
                $this->f('present_days', __('Days Present'), 'integer'),
                $this->f('absent_days', __('Days Absent'), 'integer'),
                $this->f('late_days', __('Days Late'), 'integer'),
                $this->f('excused_days', __('Days Excused'), 'integer'),
                $this->pct('attendance_rate', __('Attendance Rate'), 'attendance_monthly_trend.attendance_rate'),
            ], [
                'description' => __('Monthly attendance trend for spotting seasonal dips.'),
                'default_order' => 'month|asc',
            ]),
        ];
    }
}
