<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use RuntimeException;

final class DemoSessionGuard
{
    private static bool $active = false;

    private static ?int $schoolId = null;

    private static bool $writesAllowed = false;

    private static bool $checkingWrite = false;

    /** @var array<string, bool> */
    private static array $schoolIdTables = [];

    public static function begin(int $schoolId): void
    {
        self::$active = true;
        self::$schoolId = $schoolId;
    }

    public static function end(): void
    {
        self::$active = false;
        self::$schoolId = null;
    }

    public static function active(): bool
    {
        return self::$active;
    }

    public static function allowingWrites(callable $callback): mixed
    {
        $previous = self::$writesAllowed;
        self::$writesAllowed = true;

        try {
            return $callback();
        } finally {
            self::$writesAllowed = $previous;
        }
    }

    public static function writesAllowed(): bool
    {
        return self::$writesAllowed;
    }

    public static function schoolId(): ?int
    {
        return self::$schoolId;
    }

    /** @param array<int, mixed> $bindings */
    public static function assertWriteQueryAllowed(string $sql, array $bindings): void
    {
        if (! self::$active || self::$writesAllowed || self::$checkingWrite) {
            return;
        }

        if (! preg_match('/^\s*(insert\s+into|update|delete\s+from)\s+`?([a-z0-9_]+)`?/i', $sql, $match)) {
            return;
        }

        $operation = strtolower(strtok($match[1], ' '));
        $table = $match[2];

        self::$checkingWrite = true;

        try {
            if (! self::tableHasSchoolId($table)) {
                return;
            }

            foreach (self::schoolIdBindingIndexes($sql) as $index) {
                $value = $bindings[$index] ?? null;

                if (is_numeric($value) && (int) $value !== self::$schoolId) {
                    throw new RuntimeException(
                        'Demo sandbox sessions cannot write school ['.(int) $value.'] through '.$table.'.'
                    );
                }
            }

            if ($operation === 'insert') {
                return;
            }

            $whereSql = self::whereSql($sql);

            if (self::schoolIdBindingIndexes($whereSql) === [] && self::idBindingIndexes($sql) === []) {
                throw new RuntimeException('Demo sandbox sessions cannot run unscoped writes on '.$table.'.');
            }

            $ids = [];

            foreach (self::idBindingIndexes($sql) as $index) {
                $value = $bindings[$index] ?? null;

                if (is_numeric($value)) {
                    $ids[] = (int) $value;
                }
            }

            foreach (array_unique($ids) as $id) {
                $schoolId = DB::table($table)->where('id', $id)->value('school_id');

                if ($schoolId !== null && (int) $schoolId !== self::$schoolId) {
                    throw new RuntimeException(
                        'Demo sandbox sessions cannot modify school ['.(int) $schoolId.'] records through '.$table.'.'
                    );
                }
            }
        } finally {
            self::$checkingWrite = false;
        }
    }

    public static function assertWriteAllowed(string $action): void
    {
        if (! self::$active || self::$writesAllowed) {
            return;
        }

        throw new RuntimeException('Demo sandbox sessions cannot '.$action.'.');
    }

    public static function assertJobAllowed(?int $jobSchoolId): void
    {
        if (! self::$active || self::$writesAllowed || $jobSchoolId === self::$schoolId) {
            return;
        }

        if ($jobSchoolId === null) {
            throw new RuntimeException('Demo sandbox sessions cannot dispatch global jobs.');
        }

        throw new RuntimeException('Demo sandbox sessions cannot dispatch jobs for school ['.$jobSchoolId.'].');
    }

    public static function schoolIdFromJob(mixed $job): ?int
    {
        if (! is_object($job)) {
            return null;
        }

        foreach ((new ReflectionClass($job))->getProperties() as $property) {
            if (! $property->isInitialized($job)) {
                continue;
            }

            $property->setAccessible(true);
            $value = $property->getValue($job);
            $name = $property->getName();

            if (in_array($name, ['schoolId', 'school_id', 'tenantId', 'tenant_id'], true) && is_numeric($value)) {
                return (int) $value;
            }

            if (! in_array($name, ['school', 'schedule', 'template'], true) || ! is_object($value)) {
                continue;
            }

            if (is_numeric($value->school_id ?? null)) {
                return (int) $value->school_id;
            }

            if ($name === 'school' && is_numeric($value->id ?? null)) {
                return (int) $value->id;
            }
        }

        return null;
    }

    private static function tableHasSchoolId(string $table): bool
    {
        if (! array_key_exists($table, self::$schoolIdTables)) {
            self::$schoolIdTables[$table] = Schema::hasColumn($table, 'school_id');
        }

        return self::$schoolIdTables[$table];
    }

    private static function whereSql(string $sql): string
    {
        $position = stripos($sql, ' where ');

        return $position === false ? '' : substr($sql, $position);
    }

    /** @return array<int, int> */
    private static function schoolIdBindingIndexes(string $sql): array
    {
        return self::bindingIndexesForPattern($sql, '/`?school_id`?\s*=\s*\?/i');
    }

    /** @return array<int, int> */
    private static function idBindingIndexes(string $sql): array
    {
        $whereSql = self::whereSql($sql);
        $offset = strlen($sql) - strlen($whereSql);

        if ($whereSql === '') {
            return [];
        }

        $indexes = self::bindingIndexesForPattern($sql, '/(?<![\w])`?id`?\s*=\s*\?/i', $offset, $whereSql);

        if (preg_match_all('/(?<![\w])`?id`?\s+in\s*\(([^)]+)\)/i', $whereSql, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $captureOffset = $match[1];
                $capture = $match[0];

                if (preg_match_all('/\?/', $capture, $questions, PREG_OFFSET_CAPTURE)) {
                    foreach ($questions[0] as $question) {
                        $indexes[] = self::placeholderIndexAt($sql, $offset + $captureOffset + $question[1]);
                    }
                }
            }
        }

        sort($indexes);

        return array_values(array_unique($indexes));
    }

    /**
     * @return array<int, int>
     */
    private static function bindingIndexesForPattern(
        string $sql,
        string $pattern,
        int $offset = 0,
        ?string $subject = null
    ): array {
        if (! preg_match_all($pattern, $subject ?? $sql, $matches, PREG_OFFSET_CAPTURE)) {
            return [];
        }

        $indexes = [];

        foreach ($matches[0] as $match) {
            $indexes[] = self::placeholderIndexAt($sql, $offset + $match[1] + strlen($match[0]) - 1);
        }

        sort($indexes);

        return array_values(array_unique($indexes));
    }

    private static function placeholderIndexAt(string $sql, int $offset): int
    {
        return substr_count(substr($sql, 0, $offset), '?');
    }
}
