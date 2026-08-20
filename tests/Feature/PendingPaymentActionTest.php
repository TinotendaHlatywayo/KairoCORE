<?php

namespace Tests\Feature;

use App\Filament\Admin\Resources\PendingPaymentResource\Pages\ListPendingPayments;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\SaaS\Models\SaaSInvoice;
use Modules\SaaS\Models\SaaSManualSubmission;
use Modules\SaaS\Models\SaaSPlan;
use Modules\SaaS\Models\SaaSReceipt;
use Modules\SaaS\Models\SaaSSubscription;
use Modules\SaaS\Models\SaaSTransaction;
use Tests\TestCase;

class PendingPaymentActionTest extends TestCase
{
    use InteractsWithDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('database.default', 'mysql');
        Config::set('database.connections.mysql.database', 'schoolcore');
        Config::set('database.connections.mysql.host', '127.0.0.1');
        Config::set('database.connections.mysql.port', '3306');
        Config::set('database.connections.mysql.username', env('DB_USERNAME', 'root'));
        Config::set('database.connections.mysql.password', env('DB_PASSWORD', ''));
        DB::purge('mysql');
    }

    public function test_approve_action_credits_subscription_and_creates_transaction(): void
    {
        $fixtures = $this->fixtures();

        Livewire::test(ListPendingPayments::class)
            ->callTableAction('approve', $fixtures['submission'])
            ->assertOk();

        $this->assertSame('approved', $fixtures['submission']->fresh()->status);
        $this->assertDatabaseHas('saas_transactions', [
            'school_id' => $fixtures['school']->id,
            'saas_invoice_id' => $fixtures['invoice']->id,
            'transaction_reference' => $fixtures['submission']->reference_number,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('saas_receipts', [
            'school_id' => $fixtures['school']->id,
            'saas_invoice_id' => $fixtures['invoice']->id,
        ]);
        $this->assertSame('paid', $fixtures['invoice']->fresh()->status);
        $this->assertSame('active', $fixtures['subscription']->fresh()->status);

        $this->cleanup($fixtures);
    }

    public function test_reject_action_sets_status_and_reason(): void
    {
        $fixtures = $this->fixtures();

        Livewire::test(ListPendingPayments::class)
            ->callTableAction('reject', $fixtures['submission'], ['rejection_reason' => 'Bank statement does not match.'])
            ->assertOk();

        $fresh = $fixtures['submission']->fresh();
        $this->assertSame('rejected', $fresh->status);
        $this->assertSame('Bank statement does not match.', $fresh->rejection_reason);
        $this->assertSame($fixtures['admin']->id, $fresh->reviewed_by_id);
        $this->assertNotNull($fresh->reviewed_at);
        $this->assertDatabaseMissing('saas_transactions', [
            'school_id' => $fixtures['school']->id,
            'saas_invoice_id' => $fixtures['invoice']->id,
        ]);

        $this->cleanup($fixtures);
    }

    public function test_actions_are_hidden_after_submission_is_processed(): void
    {
        $fixtures = $this->fixtures();
        $fixtures['submission']->update(['status' => 'approved']);

        $component = Livewire::test(ListPendingPayments::class);
        $component->assertOk();
        $component->assertTableActionHidden('approve', $fixtures['submission']);
        $component->assertTableActionHidden('reject', $fixtures['submission']);

        $this->cleanup($fixtures);
    }

    /**
     * @return array{school: School, admin: User, subscription: SaaSSubscription, invoice: SaaSInvoice, submission: SaaSManualSubmission}
     */
    private function fixtures(): array
    {
        $school = School::firstOrFail();
        $admin = User::where('school_id', null)->firstOrFail();
        $this->actingAs($admin);

        URL::defaults(['panel' => 'admin']);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $subscription = SaaSSubscription::where('school_id', $school->id)->first()
            ?? SaaSSubscription::create([
                'school_id' => $school->id,
                'saas_plan_id' => SaaSPlan::firstOrFail()->id,
                'billing_period' => 'monthly',
                'status' => 'active',
                'next_payment_date' => now()->addDays(5)->toDateString(),
            ]);

        $invoice = SaaSInvoice::create([
            'school_id' => $school->id,
            'saas_subscription_id' => $subscription->id,
            'invoice_number' => 'INV-'.uniqid(),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'subtotal' => 50,
            'total' => 50,
            'currency' => 'USD',
            'status' => 'unpaid',
        ]);

        $submission = SaaSManualSubmission::create([
            'school_id' => $school->id,
            'saas_invoice_id' => $invoice->id,
            'reference_number' => 'REF-'.uniqid(),
            'amount' => 50,
            'currency' => 'USD',
            'payment_date' => now()->toDateString(),
            'bank_name' => 'Fixture Bank',
            'status' => 'pending',
        ]);

        return [
            'school' => $school,
            'admin' => $admin,
            'subscription' => $subscription,
            'invoice' => $invoice,
            'submission' => $submission,
        ];
    }

    /**
     * @param  array{school: School, admin: User, subscription: SaaSSubscription, invoice: SaaSInvoice, submission: SaaSManualSubmission}  $fixtures
     */
    private function cleanup(array $fixtures): void
    {
        SaaSReceipt::where('school_id', $fixtures['school']->id)
            ->where('saas_invoice_id', $fixtures['invoice']->id)
            ->forceDelete();
        SaaSTransaction::where('school_id', $fixtures['school']->id)
            ->where('saas_invoice_id', $fixtures['invoice']->id)
            ->withTrashed()
            ->forceDelete();
        $fixtures['submission']->forceDelete();
        $fixtures['invoice']->forceDelete();
        if (SaaSSubscription::where('school_id', $fixtures['school']->id)->withTrashed()->count() === 1) {
            $fixtures['subscription']->forceDelete();
        }
    }
}
