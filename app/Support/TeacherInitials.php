<?php

namespace App\Support;

class TeacherInitials
{
    /**
     * Produce a compact uppercase initial pair for a teacher's full name,
     * e.g. "Tariro Mutasa" -> "TM". Used in the merged stream timetable
     * cells ("Grade 1 B English (TM)") and printed schedules.
     */
    public static function for(?string $name): string
    {
        if (blank($name)) {
            return '';
        }

        $words = preg_split('/\s+/', trim($name)) ?: [];

        $initials = '';
        foreach ($words as $word) {
            $cleaned = trim($word, " \t\n\r\0\x0B.,'\"()-");
            if ($cleaned === '') {
                continue;
            }

            $initials .= strtoupper(mb_substr($cleaned, 0, 1));
        }

        return $initials;
    }
}