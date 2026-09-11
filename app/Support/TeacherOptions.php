<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Reusable fuzzy teacher lookup used by every "assign a teacher" selector.
 *
 * Teachers are matched by name AND a fuzzy subsequence so that typing a
 * fragment such as "jhn" still surfaces "John Mutasa" (chars in order,
 * not necessarily contiguous). Dropdown labels always show the full
 * "Firstname Surname" so staff see exactly who they are picking.
 */
class TeacherOptions
{
    public static function schoolId(): int
    {
        return (int) (current_tenant()?->id
            ?? auth()->user()?->school_id
            ?? config('tenancy.single_tenant_id'));
    }

    public static function query()
    {
        $schoolId = static::schoolId();

        return User::query()
            ->where('school_id', $schoolId)
            ->where(function ($q) use ($schoolId) {
                // 1. Platform Spatie role ("teacher").
                $q->whereHas('roles', fn ($r) => $r->where('name', 'like', 'teacher'));

                // 2. Per-school custom role named after teaching ("Teaching Staff").
                $q->orWhereHas('customRole', fn ($r) => $r
                    ->where('school_id', $schoolId)
                    ->where('name', 'like', 'teach%'));

                // 3. Self-registration request marked as a teacher.
                $q->orWhere('requested_role', 'like', '%teach%');

                // 4. HR employee directory "teaching_staff" records (the canonical
                //    source: every teaching employee has a linked user account).
                $q->orWhereIn('id', function ($sub) use ($schoolId) {
                    $sub->select('user_id')
                        ->from('employees')
                        ->where('school_id', $schoolId)
                        ->where('role', 'teaching_staff')
                        ->whereNotNull('user_id');
                });
            });
    }

    public static function options(): array
    {
        $label = static::label(...);

        return static::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (User $u) => [$u->id => $label($u)])
            ->all();
    }

    public static function label(User $user): string
    {
        if (trim((string) $user->name) !== '') {
            return $user->name;
        }

        return $user->username ?: 'Teacher #'.$user->id;
    }

    /**
     * Return id => "Full Name" for teachers whose name fuzzy-matches $search.
     * Exact substring matches are ranked ahead of loose subsequence matches.
     */
    public static function search(string $search): array
    {
        $needle = mb_strtolower(trim($search));
        if ($needle === '') {
            return static::options();
        }

        $results = collect();

        static::query()
            ->get(['id', 'name', 'username'])
            ->each(function (User $u) use ($needle, $results) {
                $name = mb_strtolower(static::label($u));

                if (str_contains($name, $needle)) {
                    $results->push(['id' => $u->id, 'name' => static::label($u), 'rank' => 0]);
                } elseif (static::fuzzyMatches($name, $needle)) {
                    $results->push(['id' => $u->id, 'name' => static::label($u), 'rank' => 1]);
                }
            });

        return $results
            ->sortBy(fn ($r) => [$r['rank'], $r['name']])
            ->mapWithKeys(fn ($r) => [$r['id'] => $r['name']])
            ->all();
    }

    public static function labelFor($value): string
    {
        $user = User::where('school_id', static::schoolId())->find((int) $value);

        return $user ? static::label($user) : (string) $value;
    }

    /**
     * Case-insensitive subsequence match: every character of $needle must
     * appear in $haystack in order ("jhn" => "john nyambiya").
     */
    public static function fuzzyMatches(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        $haystack = mb_strtolower($haystack);
        $needle = mb_strtolower($needle);

        $i = 0;
        $len = mb_strlen($haystack);

        foreach (mb_str_split($needle) as $char) {
            $found = false;
            for (; $i < $len; $i++) {
                if (mb_substr($haystack, $i, 1) === $char) {
                    $found = true;
                    $i++;
                    break;
                }
            }
            if (! $found) {
                return false;
            }
        }

        return true;
    }
}