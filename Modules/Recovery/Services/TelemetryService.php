<?php

namespace Modules\Recovery\Services;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TelemetryService
{
    public function gatherPlatformStats(): array
    {
        $totalSchools = DB::table('schools')->count();
        $activeSchools = DB::table('schools')->where('status', 'active')->count();
        $suspendedSchools = DB::table('schools')->where('status', 'suspended')->count();

        // Multi-tier fallback resolver for calculating SaaS platform MRR without column/table crashes
        $mrr = 0;
        try {
            if (Schema::hasTable('saas_transactions')) {
                $columns = Schema::getColumnListing('saas_transactions');
                $amountCol = in_array('amount', $columns)
                    ? 'amount'
                    : (in_array('total', $columns) ? 'total' : null);

                if ($amountCol) {
                    $mrr = DB::table('saas_transactions')
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->sum($amountCol);
                }
            } elseif (Schema::hasTable('invoices')) {
                // Fallback to student invoices table if platform subscriptions table is not yet migrated
                $columns = Schema::getColumnListing('invoices');
                $amountCol = in_array('net_total', $columns)
                    ? 'net_total'
                    : (in_array('total_amount', $columns)
                        ? 'total_amount'
                        : (in_array('amount', $columns) ? 'amount' : null));

                if ($amountCol) {
                    $mrr = DB::table('invoices')
                        ->where('created_at', '>=', now()->startOfMonth())
                        ->sum($amountCol);
                }
            }
        } catch (\Exception $e) {
            $mrr = 0; // Safe fallback prevents platform-admin 500 error screens
        }

        $totalUsers = DB::table('users')->count();
        $totalStorageBytes = 0;

        try {
            if (Schema::hasTable('platform_backups')) {
                $totalStorageBytes = DB::table('platform_backups')->sum('size_bytes');
            }
        } catch (\Exception $e) {
            $totalStorageBytes = 0;
        }

        return [
            'total_schools' => $totalSchools,
            'active_schools' => $activeSchools,
            'suspended_schools' => $suspendedSchools,
            'mrr' => $mrr,
            'arr' => $mrr * 12,
            'total_users' => $totalUsers,
            'storage_used_mb' => round($totalStorageBytes / (1024 * 1024), 2),
        ];
    }

    public function generateHealthReport(int $schoolId): array
    {
        $school = School::find($schoolId);
        if (! $school) {
            return ['score' => 0, 'status' => 'Critical', 'recommendations' => []];
        }

        $recommendations = [];
        $scorePoints = 100;

        // Factor 1: Active Academic Year setup
        $hasActiveYear = false;
        try {
            if (Schema::hasTable('academic_years')) {
                $hasActiveYear = DB::table('academic_years')
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->exists();
            }
        } catch (\Exception $e) {
            $hasActiveYear = false;
        }

        if (! $hasActiveYear) {
            $scorePoints -= 35;
            $recommendations[] = 'Active academic year has not been configured.';
        }

        // Factor 2: Student enrollment completeness
        $enrollmentCount = 0;
        try {
            if (Schema::hasTable('enrollments')) {
                $enrollmentCount = DB::table('enrollments')
                    ->where('school_id', $schoolId)
                    ->count();
            }
        } catch (\Exception $e) {
            $enrollmentCount = 0;
        }

        if ($enrollmentCount === 0) {
            $scorePoints -= 25;
            $recommendations[] = 'No active student enrollment profiles exist.';
        }

        // Factor 3: Financial standing
        $unpaidInvoices = 0;
        try {
            if (Schema::hasTable('invoices')) {
                $unpaidInvoices = DB::table('invoices')
                    ->where('school_id', $schoolId)
                    ->where('status', 'unpaid')
                    ->count();
            }
        } catch (\Exception $e) {
            $unpaidInvoices = 0;
        }

        if ($unpaidInvoices > 3) {
            $scorePoints -= 20;
            $recommendations[] = 'Multiple outstanding school accounts invoices require reconciliation.';
        }

        // Determine Rating Bounds
        $status = 'Excellent';
        if ($scorePoints < 50) {
            $status = 'Critical';
        } elseif ($scorePoints < 75) {
            $status = 'Needs Attention';
        } elseif ($scorePoints < 90) {
            $status = 'Good';
        }

        return [
            'score' => max($scorePoints, 0),
            'status' => $status,
            'recommendations' => $recommendations,
        ];
    }
}
