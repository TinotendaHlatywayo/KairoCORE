<?php

namespace Tests\Feature;

use App\Filament\App\Resources\FinanceDocumentTemplateResource\Pages\EditFinanceDocumentTemplate;
use App\Models\School;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Modules\Finance\Http\Controllers\FinanceDocumentVerificationController;
use Modules\Finance\Models\FinanceDocumentTemplate;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\InvoiceItem;
use Modules\Finance\Services\BillingDocumentSettingsService;

class FinanceTemplateSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.database' => 'schoolcore']);

        $school = School::find(15);
        app()->instance('current_tenant', $school);
        URL::defaults(['tenant' => $school->subdomain]);
        $this->withSession(['locale' => 'en']);
    }

    public function test_finance_template_pages_render_live_preview(): void
    {
        $user = User::find(13);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        $r = $this->get('/workspace/finance-document-templates/create');
        $r->assertOk();
        $html = $r->getContent();

        $this->assertStringContainsString('width:334px;height:472px', $html);
        $this->assertStringContainsString('doc-header style-classic', $html);
        $this->assertStringContainsString('body class="style-classic"', $html);
        $this->assertStringContainsString('.doc-page { padding: 10mm 12mm; }', $html);
        $this->assertStringContainsString('qrserver.com', $html);
        $this->assertStringContainsString('position: fixed; bottom: 0', $html);
        $this->assertStringContainsString('A4 · live preview', $html);

        $tpl = FinanceDocumentTemplate::create([
            'school_id' => 15,
            'document_type' => 'invoice',
            'name' => 'Smoke Test Invoice',
            'design_theme' => 'elegant_editorial',
            'is_active' => true,
            'layout_config' => [
                'font_family' => "'Times New Roman', Times, serif",
                'footer' => ['qr_position' => 'center', 'qr_size' => 120],
            ],
        ]);

        $r2 = $this->get("/workspace/finance-document-templates/{$tpl->id}/edit");
        $r2->assertOk();
        $html2 = $r2->getContent();
        $this->assertStringContainsString('doc-header style-editorial', $html2);
        $this->assertStringContainsString('width: 120px; height: 120px;', $html2);

        $this->get('/workspace/finance-document-templates')->assertOk();

        $tpl->delete();
    }

    public function test_new_themes_and_template_logo_render_in_preview(): void
    {
        $user = User::find(13);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        $tpl = FinanceDocumentTemplate::create([
            'school_id' => 15,
            'document_type' => 'invoice',
            'name' => 'Smoke Swiss Invoice',
            'design_theme' => 'swiss_minimal',
            'is_active' => false,
            'layout_config' => [],
        ]);

        $r = $this->get("/workspace/finance-document-templates/{$tpl->id}/edit");
        $r->assertOk();
        $html = $r->getContent();
        $this->assertStringContainsString('body class="style-swiss"', $html);
        $this->assertStringContainsString('Template Logo', $html);
        $this->assertStringContainsString('fi-fo-file-upload', $html);

        $tpl->delete();
    }

    public function test_logo_upload_render_does_not_crash_livewire(): void
    {
        $user = User::find(13);
        $this->actingAs($user)
            ->withServerVariables(['HTTP_HOST' => 'tinwayacademy.lvh.me:8000']);

        Filament::setCurrentPanel(Filament::getPanel('app'));

        $file = UploadedFile::fake()->image('logo.png', 120, 120);

        $tpl = FinanceDocumentTemplate::create([
            'school_id' => 15,
            'document_type' => 'invoice',
            'name' => 'Smoke Logo Upload',
            'design_theme' => 'classic_line',
            'is_active' => false,
            'layout_config' => [],
        ]);

        try {
            Livewire::test(
                EditFinanceDocumentTemplate::class,
                ['record' => $tpl->getRouteKey()],
            )
                ->assertOk()
                ->upload('data.layout_config.header.logo', [$file])
                ->assertOk()
                ->assertSee('A4 · live preview')
                ->assertDontSee('Preview could not be rendered')
                ->assertSee('livewire/preview-file')
                ->call('save');

            $tpl->refresh();

            $storedLogo = $tpl->layout_config['header']['logo'] ?? null;
            $this->assertIsString($storedLogo);
            $this->assertNotEmpty($storedLogo);
            $this->assertFileExists(public_path('storage/'.$storedLogo));

            Livewire::test(
                EditFinanceDocumentTemplate::class,
                ['record' => $tpl->getRouteKey()],
            )
                ->assertOk()
                ->assertSee('A4 · live preview')
                ->assertDontSee('Preview could not be rendered')
                ->assertSee('storage/tenant/branding/templates');
        } finally {
            if (is_string($tpl->layout_config['header']['logo'] ?? null)) {
                @unlink(public_path('storage/'.$tpl->layout_config['header']['logo']));
            }
            $tpl->refresh()->delete();
        }
    }

    /**
     * Self-contained finance fixture: the dev database no longer guarantees
     * an invoice with a linked student, so every run creates its own.
     *
     * @return array{0: \Modules\Students\Models\Student, 1: Invoice}
     */
    private function makeFinanceFixture(float $paid = 40.00): array
    {
        $student = \Modules\Students\Models\Student::create([
            'school_id' => 15,
            'student_id_number' => 'TEST-FIN-'.uniqid(),
            'admission_number' => 'TEST-FADM-'.uniqid(),
            'first_name' => 'Finance',
            'last_name' => 'Fixture',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(14)->toDateString(),
            'admission_date' => now()->startOfYear()->toDateString(),
            'status' => 'active',
        ]);

        $invoice = Invoice::create([
            'school_id' => 15,
            'student_id' => $student->id,
            'invoice_number' => 'TEST-FINV-'.strtoupper(uniqid()),
            'currency' => 'USD',
            'subtotal_amount' => 100,
            'total_amount' => 100,
            'paid_amount' => $paid,
            'balance_amount' => max(0, 100 - $paid),
            'status' => $paid >= 100 ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid'),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'name' => 'Tuition Fees',
            'amount' => 100,
        ]);

        return [$student, $invoice->refresh()];
    }

    public function test_finance_verification_route_and_page_render(): void
    {
        $route = app('router')->getRoutes()->getByName('finance.verify');
        $this->assertNotNull($route, 'finance.verify route must be registered');

        [$student, $invoice] = $this->makeFinanceFixture();

        try {
            $controller = app(FinanceDocumentVerificationController::class);

            $html = $controller->verify(new Request(['type' => 'invoice']), $invoice->integrity_hash)
                ->render();
            $this->assertStringContainsString('VERIFIED GENUINE', $html);
            $this->assertStringContainsString($student->full_name, $html);
            $this->assertStringContainsString('Outstanding Balance', $html);
            $this->assertStringContainsString($invoice->integrity_hash, $html);
        } finally {
            InvoiceItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }

    public function test_active_template_config_appears_in_actual_document(): void
    {
        $user = User::find(13);
        $this->actingAs($user);
        $school = School::find(15);

        $storedPath = 'tenant/branding/templates/smoke_doc_logo.png';
        Storage::disk('public')->makeDirectory('tenant/branding/templates');
        $file = UploadedFile::fake()->image('smoke_doc_logo.png', 90, 90);
        Storage::disk('public')->put($storedPath, file_get_contents($file->getRealPath()));

        FinanceDocumentTemplate::where('school_id', 15)->where('document_type', 'invoice')->update(['is_active' => false]);
        $tpl = FinanceDocumentTemplate::create([
            'school_id' => 15,
            'document_type' => 'invoice',
            'name' => 'Smoke Active Config',
            'design_theme' => 'classic_line',
            'is_active' => true,
            'layout_config' => [
                'header' => [
                    'show_logo' => true, 'logo_size' => 80, 'logo_position' => 'left',
                    'logo' => $storedPath, 'school_name_color' => '#ff0000',
                ],
                'footer' => ['show_qr' => true, 'qr_size' => 60, 'qr_position' => 'center'],
            ],
        ]);

        // Term context for the PDF view (term.academicYear chain).
        $year = \Modules\Academics\Models\AcademicYear::firstOrCreate(
            ['school_id' => 15, 'is_active' => true],
            ['name' => now()->format('Y').' Academic Year', 'start_date' => now()->startOfYear(), 'end_date' => now()->endOfYear()]
        );
        $term = \Modules\Academics\Models\Term::where('school_id', 15)->where('academic_year_id', $year->id)->orderBy('id')->first()
            ?? \Modules\Academics\Models\Term::create([
                'school_id' => 15, 'academic_year_id' => $year->id, 'name' => 'Term 1',
                'start_date' => now()->startOfYear(), 'end_date' => now()->startOfYear()->addMonths(3),
            ]);

        [$student, $invoice] = $this->makeFinanceFixture();
        $invoice->update(['term_id' => $term->id]);

        try {
            $resolved = FinanceDocumentTemplate::resolveFor(15, 'invoice');
            $this->assertSame($tpl->id, $resolved->id);

            $html = view('modules.finance.invoice-pdf', [
                'invoice' => $invoice,
                'school' => $school,
                'student' => $student,
                'config' => BillingDocumentSettingsService::get(),
                'template' => $resolved,
            ])->render();

            $this->assertStringContainsString($storedPath, $html);
            $this->assertStringContainsString('ff0000', $html);
            $this->assertStringContainsString('60x60', $html);
            $this->assertStringContainsString('float: none; margin: 0 auto', $html);
            $this->assertStringContainsString('Scan to Verify', $html);
        } finally {
            FinanceDocumentTemplate::where('school_id', 15)->where('document_type', 'invoice')->update(['is_active' => false]);
            $tpl->delete();
            @unlink(public_path('storage/'.$storedPath));
            InvoiceItem::where('invoice_id', $invoice->id)->delete();
            $invoice->delete();
            $student->forceDelete();
        }
    }
}
