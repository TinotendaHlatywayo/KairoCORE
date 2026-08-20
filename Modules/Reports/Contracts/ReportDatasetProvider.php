<?php

namespace Modules\Reports\Contracts;

/**
 * A module reports its reporting datasets by implementing this contract.
 *
 * Adding a new module (or exposing new datasets from an existing one) is as
 * simple as writing a provider that returns dataset definitions — the
 * DatasetRegistry auto-discovers it and the whole report engine picks it up:
 * wizard source picker, join graph, field picker, filters, grouping, KPI widgets.
 */
interface ReportDatasetProvider
{
    /** Human-readable module name, e.g. "Finance & Fees". */
    public function module(): string;

    /**
     * Dataset definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function datasets(): array;
}
