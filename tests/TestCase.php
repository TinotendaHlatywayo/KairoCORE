<?php

namespace Tests;

use App\Models\School;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithDatabase;

    /**
     * Bind a school as the active tenant for the current test. Mirrors what
     * ResolveTenant does on real tenant-subdomain requests, so tenant-scoped
     * models (SystemSetting etc.) behave exactly as they would in production.
     */
    protected function actingAsTenant(School $school): static
    {
        app()->instance('current_tenant', $school);

        return $this;
    }

    /**
     * Remove any bound tenant (simulates the central platform host).
     */
    protected function withoutTenant(): static
    {
        if (app()->bound('current_tenant')) {
            app()->forgetInstance('current_tenant');
        }

        return $this;
    }
}
