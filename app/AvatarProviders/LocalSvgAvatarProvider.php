<?php

namespace App\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Illuminate\Database\Eloquent\Model;
use Modules\HR\Models\Employee;
use Modules\Students\Models\Student;

class LocalSvgAvatarProvider implements AvatarProvider
{
    /**
     * Generate a local, offline-ready Base64-encoded SVG avatar [1].
     *
     * The topbar profile button shows the user's staff or student number
     * (e.g. EMP-2026-0042 / STU-2026-0101) instead of name initials so it is
     * easy to identify an account at a glance.
     */
    public function get(Model $record): string
    {
        $label = $this->resolveIdentifier($record);

        if (empty($label)) {
            $label = $this->resolveInitials($record);
        }

        if (empty($label)) {
            $label = 'U';
        }

        // Scale the font down for longer identifiers so they always fit.
        $fontSize = strlen($label) <= 2 ? 36 : (strlen($label) <= 8 ? 26 : 18);

        // Standard high-contrast Indigo background with crisp white text
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="100" height="100">
            <rect width="100" height="100" fill="#4f46e5" rx="50"/>
            <text x="50" y="54" font-family="system-ui, monospace" font-size="'.$fontSize.'" font-weight="bold" fill="#ffffff" dominant-baseline="middle" text-anchor="middle">'.$label.'</text>
        </svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Resolve the employee number or student number for the given user.
     */
    protected function resolveIdentifier(Model $record): string
    {
        $schoolId = $record->getAttribute('school_id');
        $userId = $record->getKey();

        if ($userId === null) {
            return '';
        }

        $employeeNumber = Employee::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('user_id', $userId)
            ->value('employee_number');

        if ($employeeNumber) {
            return $employeeNumber;
        }

        $studentNumber = Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('user_id', $userId)
            ->value('student_id_number');

        return $studentNumber ?: '';
    }

    protected function resolveInitials(Model $record): string
    {
        $name = $record->getAttribute('name') ?? '';

        $words = preg_split('/\s+/', trim($name));

        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials;
    }
}
