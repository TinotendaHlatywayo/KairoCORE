<?php

namespace Modules\Reports\DataSources\Providers;

use Modules\Reports\DataSources\AbstractDatasetProvider;

class TimetableProvider extends AbstractDatasetProvider
{
    public function module(): string
    {
        return __('Timetables');
    }

    public function datasets(): array
    {
        return [
            $this->d('timetable.lesson', __('Timetable Lessons'), 'timetable_lessons', [
                $this->f('day_of_week', __('Day of Week'), 'integer'),
                $this->f('custom_label', __('Label')),
                $this->f('color', __('Color')),
                $this->f('is_locked', __('Locked'), 'boolean'),
                $this->f('course_name', __('Course'), 'string', 'timetable_lesson_course.name'),
                $this->f('section_name', __('Section'), 'string', 'timetable_lesson_section.name'),
                $this->f('subject_name', __('Subject'), 'string', 'timetable_lesson_subject.name'),
                $this->f('teacher_name', __('Teacher'), 'string', 'timetable_lesson_teacher.name'),
                $this->f('classroom_name', __('Room'), 'string', 'timetable_lesson_classroom.name'),
                $this->f('slot_name', __('Time Slot'), 'string', 'timetable_lesson_slot.name'),
                $this->f('slot_start', __('Starts'), 'string', 'timetable_lesson_slot.start_time'),
                $this->f('slot_end', __('Ends'), 'string', 'timetable_lesson_slot.end_time'),
                $this->f('day_name', __('Day'), 'string', 'CASE timetable_lesson.day_of_week WHEN 0 THEN \'Sunday\' WHEN 1 THEN \'Monday\' WHEN 2 THEN \'Tuesday\' WHEN 3 THEN \'Wednesday\' WHEN 4 THEN \'Thursday\' WHEN 5 THEN \'Friday\' ELSE \'Saturday\' END'),
            ], [
                'description' => __('Scheduled lessons with subject, teacher, room and slot context.'),
                'autoJoins' => [
                    ['alias' => 'timetable_lesson_course', 'table' => 'courses', 'type' => 'left', 'on' => [['timetable_lesson_course.id', 'timetable_lesson.course_id']]],
                    ['alias' => 'timetable_lesson_section', 'table' => 'sections', 'type' => 'left', 'on' => [['timetable_lesson_section.id', 'timetable_lesson.section_id']]],
                    ['alias' => 'timetable_lesson_subject', 'table' => 'subjects', 'type' => 'left', 'on' => [['timetable_lesson_subject.id', 'timetable_lesson.subject_id']]],
                    ['alias' => 'timetable_lesson_teacher', 'table' => 'employees', 'type' => 'left', 'on' => [['timetable_lesson_teacher.id', 'timetable_lesson.teacher_id']]],
                    ['alias' => 'timetable_lesson_classroom', 'table' => 'classrooms', 'type' => 'left', 'on' => [['timetable_lesson_classroom.id', 'timetable_lesson.classroom_id']]],
                    ['alias' => 'timetable_lesson_slot', 'table' => 'time_slots', 'type' => 'left', 'on' => [['timetable_lesson_slot.id', 'timetable_lesson.time_slot_id']]],
                ],
                'connections' => [
                    $this->connect('academics.subject', 'timetable_lesson.subject_id', 'academics_subject.id'),
                    $this->connect('hr.employee', 'timetable_lesson.teacher_id', 'hr_employee.id'),
                ],
                'filters' => [
                    ['key' => 'day_of_week', 'label' => __('Day of Week'), 'type' => 'select', 'options' => [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday']],
                    ['key' => 'is_locked', 'label' => __('Locked'), 'type' => 'select', 'options' => [1 => 'Locked', 0 => 'Unlocked']],
                ],
            ]),

            $this->d('timetable.time_slot', __('Time Slots'), 'time_slots', [
                $this->f('name', __('Name')),
                $this->f('start_time', __('Starts'), 'time'),
                $this->f('end_time', __('Ends'), 'time'),
                $this->f('is_break', __('Is Break'), 'boolean'),
                $this->f('is_locked', __('Locked'), 'boolean'),
            ], [
                'description' => __('Defined lesson periods.'),
            ]),

            $this->d('timetable.classroom', __('Classrooms'), 'classrooms', [
                $this->f('name', __('Name')),
                $this->f('capacity', __('Capacity'), 'integer'),
            ], [
                'description' => __('Physical teaching rooms.'),
            ]),
        ];
    }
}
