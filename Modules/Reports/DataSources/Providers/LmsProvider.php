<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class LmsProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('LMS');
    }

    public function datasets(): array
    {
        return [
            $this->d('lms.homework', __('Homework Assignments'), 'homeworks', [
                $this->f('title', __('Title')),
                $this->f('description', __('Description')),
                $this->f('due_date', __('Due Date'), 'datetime'),
                $this->f('created_at', __('Assigned At'), 'datetime'),
                $this->f('section_name', __('Section'), 'string', 'lms_homework_section.name'),
                $this->f('subject_name', __('Subject'), 'string', 'lms_homework_subject.name'),
            ], [
                'description' => __('Homework tasks assigned to sections.'),
                'autoJoins' => [
                    ['alias' => 'lms_homework_section', 'table' => 'sections', 'type' => 'left', 'on' => [['lms_homework_section.id', 'lms_homework.section_id']]],
                    ['alias' => 'lms_homework_subject', 'table' => 'subjects', 'type' => 'left', 'on' => [['lms_homework_subject.id', 'lms_homework.subject_id']]],
                ],
                'connections' => [
                    $this->connect('academics.subject', 'lms_homework.subject_id', 'academics_subject.id'),
                ],
            ]),

            $this->d('lms.submission', __('Homework Submissions'), 'homework_submissions', [
                $this->f('grade_obtained', __('Grade Obtained')),
                $this->f('teacher_feedback', __('Teacher Feedback')),
                $this->f('submitted_at', __('Submitted At'), 'datetime'),
                $this->f('student_name', __('Student'), 'string', "CONCAT(lms_submission_st.first_name, ' ', lms_submission_st.last_name)"),
                $this->f('homework_title', __('Homework'), 'string', 'lms_submission_hw.title'),
                $this->f('on_time', __('On Time'), 'boolean', 'CASE WHEN lms_submission.submitted_at <= lms_submission_hw.due_date THEN 1 ELSE 0 END'),
            ], [
                'description' => __('Student homework submissions with timeliness.'),
                'autoJoins' => [
                    ['alias' => 'lms_submission_st', 'table' => 'students', 'type' => 'left', 'on' => [['lms_submission_st.id', 'lms_submission.student_id']]],
                    ['alias' => 'lms_submission_hw', 'table' => 'homeworks', 'type' => 'left', 'on' => [['lms_submission_hw.id', 'lms_submission.homework_id']]],
                ],
                'connections' => [
                    $this->connect('students.register', 'lms_submission.student_id', 'students_register.id'),
                    $this->connect('lms.homework', 'lms_submission.homework_id', 'lms_homework.id'),
                ],
            ]),
        ];
    }
}
