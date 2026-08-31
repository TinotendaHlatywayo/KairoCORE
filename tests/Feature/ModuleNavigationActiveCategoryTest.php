<?php

namespace Tests\Feature;

use App\Navigation\ModuleNavigationService;
use Tests\TestCase;

/**
 * Verifies the category-aware sidebar highlighting logic: a category hub's
 * sidebar item must stay active while any page within the same category group
 * is open (e.g. open Staff Loans / Payroll Periods / Salary Grades → highlight
 * "Payroll & Compensation"; open Fee Structures / Invoices / Payment Proofs /
 * Fee Waivers → highlight "Student Billing & Revenue").
 */
class ModuleNavigationActiveCategoryTest extends TestCase
{
    private function service(): ModuleNavigationService
    {
        return app(ModuleNavigationService::class);
    }

    public function test_finance_pages_resolve_to_student_billing_category(): void
    {
        $service = $this->service();
        $finance = $service->moduleBySlug('finance');

        $this->assertNotNull($finance);

        foreach (['fee-structures', 'fee-categories', 'invoices', 'fee-payment-submissions', 'fee-waivers'] as $slug) {
            $path = 'workspace/'.$slug;
            $this->assertSame(
                'Student Billing & Revenue',
                $service->activeTabGroup($finance, $path),
                "{$slug} should belong to Student Billing & Revenue"
            );
            $this->assertTrue(
                $service->currentTabInGroup($finance, 'Student Billing & Revenue', $path),
                "{$slug} should keep Student Billing & Revenue highlighted"
            );
        }
    }

    public function test_hr_pages_resolve_to_payroll_category(): void
    {
        $service = $this->service();
        $hr = $service->moduleBySlug('hr');

        $this->assertNotNull($hr);

        foreach (['payroll-periods', 'salary-grades', 'staff-loans'] as $slug) {
            $path = 'workspace/'.$slug;
            $this->assertSame(
                'Payroll & Compensation',
                $service->activeTabGroup($hr, $path),
                "{$slug} should belong to Payroll & Compensation"
            );
            $this->assertTrue(
                $service->currentTabInGroup($hr, 'Payroll & Compensation', $path),
                "{$slug} should keep Payroll & Compensation highlighted"
            );
        }
    }

    public function test_different_categories_do_not_cross_highlight(): void
    {
        $service = $this->service();
        $hr = $service->moduleBySlug('hr');
        $finance = $service->moduleBySlug('finance');

        // Opening Payroll Periods must NOT highlight Student Billing (finance).
        $this->assertFalse(
            $service->currentTabInGroup($finance, 'Student Billing & Revenue', 'workspace/payroll-periods')
        );
        // Opening Invoices must NOT highlight Payroll & Compensation (hr).
        $this->assertFalse(
            $service->currentTabInGroup($hr, 'Payroll & Compensation', 'workspace/invoices')
        );
    }
}
