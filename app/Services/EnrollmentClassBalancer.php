<?php

namespace App\Services;

use Modules\Academics\Models\Section;
use Modules\Students\Models\Enrollment;

/**
 * Chooses the class (section) with the fewest actively enrolled students so
 * applicants are placed in the least populated class automatically.
 */
class EnrollmentClassBalancer
{
    /**
     * Return the id of the section with the fewest active enrollments for the
     * given school, academic year and course. Sections are ordered by name so
     * ties resolve deterministically to the first section alphabetically.
     */
    public static function leastPopulatedSection(int $schoolId, int $academicYearId, int $courseId): ?int
    {
        $sections = Section::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('course_id', $courseId)
            ->orderBy('name')
            ->get(['id']);

        if ($sections->isEmpty()) {
            return null;
        }

        $leastSectionId = null;
        $leastCount = PHP_INT_MAX;

        foreach ($sections as $section) {
            $count = Enrollment::withoutTenantScope()
                ->where('school_id', $schoolId)
                ->where('academic_year_id', $academicYearId)
                ->where('course_id', $courseId)
                ->where('section_id', $section->id)
                ->where('status', Enrollment::STATUS_ACTIVE)
                ->count();

            if ($count < $leastCount) {
                $leastCount = $count;
                $leastSectionId = $section->id;
            }
        }

        return $leastSectionId;
    }
}
