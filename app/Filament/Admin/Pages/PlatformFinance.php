<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Modules\SaaS\Services\PlatformFinanceService;

/**
 * KairoCORE's own financial statements: revenue vs operating expenses and the
 * resulting net profit for a chosen period, with PDF/CSV export.
 */
class PlatformFinance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Platform Finance';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.platform-finance';

    public ?array $data = [];

    public array $report = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->toDateString(),
            'year' => (int) now()->year,
        ]);

        $this->generateReport();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label(__('From'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generateReport()),
                DatePicker::make('end_date')
                    ->label(__('To'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generateReport()),
                Select::make('year')
                    ->label(__('Monthly breakdown year'))
                    ->options(fn () => collect($this->availableYears())
                        ->mapWithKeys(fn (int $year) => [$year => (string) $year])
                        ->all())
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generateReport()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    /**
     * @return list<int>
     */
    public function availableYears(): array
    {
        return app(PlatformFinanceService::class)->availableYears();
    }

    public function generateReport(): void
    {
        $state = $this->form->getState();

        $start = $state['start_date'] ?? null;
        $end = $state['end_date'] ?? null;

        if (! $start || ! $end) {
            return;
        }

        $service = app(PlatformFinanceService::class);

        $this->report = $service->summary($start, $end);

        $year = (int) ($state['year'] ?? now()->year);
        $breakdown = $service->yearBreakdown($year);

        $this->report['year'] = $breakdown['year'];
        $this->report['months'] = $breakdown['months'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label(__('Download PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $report = $this->currentReport();
                    $pdf = Pdf::loadView('modules.saas.pdf.platform-financial-statement', ['report' => $report]);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        $this->fileName('pdf'),
                    );
                }),
            Action::make('download_csv')
                ->label(__('Download CSV'))
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    $report = $this->currentReport();

                    return response()->streamDownload(function () use ($report) {
                        $handle = fopen('php://output', 'w');
                        $currency = $report['currency'];

                        fputcsv($handle, ['KairoCORE Financial Statement']);
                        fputcsv($handle, ['Period', $report['start']->toDateString(), $report['end']->toDateString()]);
                        fputcsv($handle, []);
                        fputcsv($handle, ['Metric', 'Amount ('.$currency.')']);
                        fputcsv($handle, ['Revenue received', $report['revenue']]);
                        fputcsv($handle, ['Invoiced', $report['invoiced_total']]);
                        fputcsv($handle, ['Outstanding', $report['outstanding_total']]);
                        fputcsv($handle, ['Operating expenses', $report['expenses']]);
                        fputcsv($handle, ['Net profit', $report['net_profit']]);
                        fputcsv($handle, []);
                        fputcsv($handle, ['Month', 'Revenue', 'Expenses', 'Net']);

                        foreach ($report['months'] as $month) {
                            fputcsv($handle, [$month['label'], $month['revenue'], $month['expenses'], $month['net']]);
                        }

                        fputcsv($handle, []);
                        fputcsv($handle, ['Expense category', 'Total', 'Count']);
                        foreach ($report['by_category'] as $row) {
                            fputcsv($handle, [$row['category'], $row['total'], $row['count']]);
                        }

                        fclose($handle);
                    }, $this->fileName('csv'));
                }),
        ];
    }

    protected function currentReport(): array
    {
        if ($this->report === []) {
            $this->generateReport();
        }

        return $this->report;
    }

    protected function fileName(string $extension): string
    {
        $start = $this->report['start'] ?? now()->startOfYear();
        $end = $this->report['end'] ?? now();

        return 'kairo-core-financial-statement-'.$start->format('Ymd').'-'.$end->format('Ymd').'.'.$extension;
    }
}
