<?php

namespace Modules\Finance\Services;

use Illuminate\Database\Eloquent\Model;
use Modules\Finance\Models\FinanceAuditLog;

class FinanceAuditService
{
    /**
     * Append an audit trail entry for a financial history change.
     *
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(
        int $studentId,
        string $action,
        ?Model $entity = null,
        ?array $before = null,
        ?array $after = null,
        ?string $memo = null,
    ): FinanceAuditLog {
        $schoolId = app()->bound('current_tenant') ? app('current_tenant')->id : null;

        return FinanceAuditLog::create([
            'school_id' => $schoolId,
            'user_id' => auth()->id(),
            'student_id' => $studentId,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->getKey(),
            'before' => $before ?: null,
            'after' => $after ?: null,
            'ip_address' => request()?->ip(),
            'memo' => $memo,
        ]);
    }

    public static function forStudent(int $studentId, ?int $limit = 200)
    {
        $query = FinanceAuditLog::query()
            ->with('user')
            ->where('student_id', $studentId)
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }
}