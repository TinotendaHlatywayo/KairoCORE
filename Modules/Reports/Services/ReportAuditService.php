<?php

namespace Modules\Reports\Services;

use Illuminate\Support\Facades\App;
use Modules\Reports\Models\GeneratedReport;

/**
 * Data-accuracy auditing for compiled reports.
 *
 * Every generated report records a content checksum over the exact rows that
 * were exported (plus the row count). The verifier re-runs the template's
 * query against the live database and compares the resulting checksum, so an
 * operator can prove that a report in the archive still matches current source
 * data — and see precisely when source data changed after compilation.
 */
class ReportAuditService
{
    public function __construct(
        protected LegacyAdapter $adapter,
        protected ReportQueryBuilder $builder,
    ) {}

    /**
     * Stable content fingerprint for a set of result rows. Includes the row
     * count so an identical checksum implies identical rows AND identical size.
     */
    public function checksum(iterable $rows): string
    {
        $payload = ['count' => 0, 'rows' => []];

        foreach ($rows as $row) {
            $payload['count']++;
            $payload['rows'][] = (array) $row;
        }

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * Re-run the template that produced $report and compare checksums.
     *
     * On success/failure the verification outcome is persisted on the report
     * (data_validated + validated_at) so the archive shows its accuracy state.
     */
    public function verify(GeneratedReport $report): bool
    {
        $template = $report->template;

        if (! $template) {
            return false;
        }

        $school = $template->school;
        if (! $school) {
            return false;
        }

        $previous = App::bound('current_tenant') ? App::get('current_tenant') : null;

        try {
            App::instance('current_tenant', $school);

            $config = $this->adapter->normalize($template);
            $rows = $this->builder->build($config, $report->filters_used ?? [])->get();

            $expected = $this->checksum($rows);
            $valid = hash_equals((string) $report->data_checksum, $expected);
        } catch (\Throwable $e) {
            report($e);
            $valid = false;
        } finally {
            if ($previous) {
                App::instance('current_tenant', $previous);
            } else {
                App::forgetInstance('current_tenant');
            }
        }

        $report->forceFill([
            'data_validated' => $valid,
            'validated_at' => now(),
        ])->save();

        return $valid;
    }
}
