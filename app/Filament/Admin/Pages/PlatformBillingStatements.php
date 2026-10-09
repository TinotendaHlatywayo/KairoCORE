<?php

namespace App\Filament\Admin\Pages;

use App\Models\School;
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
 * Produces a combined billing statement for one tenant over a period:
 * invoices raised, receipts issued and payments received.
 */
class PlatformBillingStatements extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $navigationLabel = 'Tenant Statements';

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.admin.pages.platform-billing-statements';

    public ?array $data = [];

    public array $statement = [];

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user && $user->school_id === null;
    }

    public function mount(): void
    {
        $preselected = (int) request()->query('school_id');

        $this->form->fill([
            'school_id' => $preselected > 0 ? $preselected : null,
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->toDateString(),
        ]);

        if ($preselected > 0) {
            $this->generate();
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('school_id')
                    ->label(__('Institution'))
                    ->required()
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generate())
                    ->options(fn () => School::query()->orderBy('name')->limit(50)->pluck('name', 'id')->all())
                    ->getSearchResultsUsing(fn (string $search): array => School::query()
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('subdomain', 'like', '%'.$search.'%')
                        ->orderBy('name')
                        ->limit(50)
                        ->pluck('name', 'id')
                        ->all())
                    ->getOptionLabelUsing(fn ($value): ?string => School::query()->whereKey($value)->value('name')),
                DatePicker::make('start_date')
                    ->label(__('From'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generate()),
                DatePicker::make('end_date')
                    ->label(__('To'))
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn () => $this->generate()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function generate(): void
    {
        $state = $this->form->getState();

        $schoolId = $state['school_id'] ?? null;
        $start = $state['start_date'] ?? null;
        $end = $state['end_date'] ?? null;

        if (! $schoolId || ! $start || ! $end) {
            return;
        }

        $this->statement = app(PlatformFinanceService::class)->tenantStatement((int) $schoolId, $start, $end);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_pdf')
                ->label(__('Download Statement PDF'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->disabled(fn (): bool => $this->statement === [])
                ->action(function () {
                    $statement = $this->statement;
                    $pdf = Pdf::loadView('modules.saas.pdf.tenant-billing-statement', ['statement' => $statement]);
                    $name = 'billing-statement-'.($statement['school']?->id ?? 'tenant')
                        .'-'.$statement['start']->format('Ymd').'-'.$statement['end']->format('Ymd').'.pdf';

                    return response()->streamDownload(fn () => print ($pdf->output()), $name);
                }),
            Action::make('download_history_pdf')
                ->label(__('Download Payment History PDF'))
                ->icon('heroicon-o-clock')
                ->color('gray')
                ->disabled(fn (): bool => $this->selectedSchoolId() === null)
                ->action(function () {
                    $schoolId = $this->selectedSchoolId();

                    if (! $schoolId) {
                        return null;
                    }

                    $history = app(PlatformFinanceService::class)->paymentHistory($schoolId);

                    $pdf = Pdf::loadView('modules.saas.pdf.payment-history', ['history' => $history]);

                    return response()->streamDownload(
                        fn () => print ($pdf->output()),
                        'payment-history-'.$schoolId.'.pdf',
                    );
                }),
        ];
    }

    protected function selectedSchoolId(): ?int
    {
        $schoolId = $this->data['school_id'] ?? null;

        return $schoolId ? (int) $schoolId : null;
    }
}
