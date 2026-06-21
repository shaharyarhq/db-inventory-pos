<?php

namespace App\Filament\Admin\Resources\Sales\Widgets;


use App\Filament\Outlet\Resources\Sale\Sales\Pages\ListSales;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesOverviewWidget extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = true;

    protected ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return ListSales::class;
    }

    protected function getStats(): array
    {
        $pageTableQuery = $this->getPageTableQuery();

        $total = $pageTableQuery->sum('total');
        $grandTotal = $pageTableQuery->sum('grand_total');

        return [
            Stat::make('Sales Total', currency_format($total)),
            Stat::make('Sales Grand Total', currency_format($grandTotal)),
        ];
    }
}
