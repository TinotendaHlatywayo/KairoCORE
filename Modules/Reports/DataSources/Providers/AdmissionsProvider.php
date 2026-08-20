<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class AdmissionsProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return 'Admissions';
    }

    public function datasets(): array
    {
        return [
            $this->d('admissions.application', 'Admission Applications', 'applications', [
                $this->f('application_number', 'Application Number'),
                $this->f('first_name', 'First Name'),
                $this->f('last_name', 'Last Name'),
                $this->f('gender', 'Gender'),
                $this->f('date_of_birth', 'Date of Birth', 'date'),
                $this->f('parent_name', 'Parent / Guardian'),
                $this->f('parent_email', 'Parent Email'),
                $this->f('parent_phone', 'Parent Phone'),
                $this->f('status', 'Status'),
                $this->f('created_at', 'Applied At', 'datetime'),
                $this->f('course_name', 'Course', 'string', 'application_course.name'),
                $this->f('full_name', 'Full Name', 'string', "CONCAT(admissions_application.first_name, ' ', admissions_application.last_name)"),
            ], [
                'description' => __('Prospective student applications and admission pipeline.'),
                'autoJoins' => [
                    ['alias' => 'application_course', 'table' => 'courses', 'type' => 'left', 'on' => [['application_course.id', 'admissions_application.course_id']]],
                ],
                'filters' => [
                    ['key' => 'status', 'label' => __('Application Status'), 'type' => 'select', 'options' => ['submitted', 'under_review', 'shortlisted', 'admitted', 'rejected', 'withdrawn']],
                    ['key' => 'gender', 'label' => __('Gender'), 'type' => 'select', 'options' => ['male', 'female', 'other']],
                ],
            ]),

            $this->d('admissions.funnel', 'Admissions Funnel (by status)', 'SELECT
                    status,
                    COUNT(*) AS application_count,
                    COUNT(CASE WHEN gender = \'male\' THEN 1 END) AS male_count,
                    COUNT(CASE WHEN gender = \'female\' THEN 1 END) AS female_count,
                    COUNT(CASE WHEN transfer_letter_verified = 1 THEN 1 END) AS transfer_verified_count
                FROM applications
                WHERE school_id = {school_id}
                GROUP BY status', [
                $this->f('status', 'Application Status'),
                $this->f('application_count', 'Applications', 'integer'),
                $this->f('male_count', 'Male', 'integer'),
                $this->f('female_count', 'Female', 'integer'),
                $this->f('transfer_verified_count', 'Transfer Letters Verified', 'integer'),
            ], [
                'description' => __('Application pipeline broken down by status and gender.'),
                'default_order' => 'application_count|desc',
            ]),

            $this->d('admissions.trend', 'Admission Applications (per month)', 'SELECT
                    DATE_FORMAT(created_at, \'%Y-%m\') AS month,
                    COUNT(*) AS application_count,
                    COUNT(CASE WHEN status = \'enrolled\' THEN 1 END) AS enrolled_count
                FROM applications
                WHERE school_id = {school_id}
                GROUP BY DATE_FORMAT(created_at, \'%Y-%m\')', [
                $this->f('month', 'Month'),
                $this->f('application_count', 'Applications', 'integer'),
                $this->f('enrolled_count', 'Enrolled', 'integer'),
            ], [
                'description' => __('Application volume and enrolments per month.'),
                'default_order' => 'month|asc',
            ]),
        ];
    }
}
