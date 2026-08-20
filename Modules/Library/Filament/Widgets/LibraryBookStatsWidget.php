<?php

declare(strict_types=1);

namespace Modules\Library\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Library\Models\LibraryBookCopy;

class LibraryBookStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Pulled securely from the global multi-tenant scope
        $totalCopies = LibraryBookCopy::count();
        $availableCopies = LibraryBookCopy::where('status', 'available')->count();
        $damagedCopies = LibraryBookCopy::where('condition', 'damaged')->count();
        $lostCopies = LibraryBookCopy::where('status', 'lost')->count();

        return [
            Stat::make('Total Copies in Library', (string) $totalCopies)
                ->description(__('Aggregate of all registered copy serials'))
                ->color('info'),

            Stat::make('Copies Available for Borrowing', (string) $availableCopies)
                ->description(__('Active available copies'))
                ->color('success'),

            Stat::make('Damaged Books', (string) $damagedCopies)
                ->description(__('Copies flagged with damaged conditions'))
                ->color('warning'),

            Stat::make('Lost Books', (string) $lostCopies)
                ->description(__('Copies flagged with lost statuses'))
                ->color('danger'),
        ];
    }
}
