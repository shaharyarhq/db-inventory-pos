<?php

namespace App\Filament\Admin\Resources\Sales\Pages;

use App\Exports\SalesBreakDownExport;
use App\Exports\SalesExport;
use App\Filament\Admin\Resources\Sales\SaleResource;
use App\Models\Outlet\Outlet;
use App\Support\Actions\LedgerExportAction;
use App\Support\Actions\RefreshAction;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListSales extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = SaleResource::class;

    protected function getHeaderWidgets(): array
    {
        return SaleResource::getWidgets();
    }

    protected function getHeaderActions(): array
    {
        return [
            LedgerExportAction::configure(SalesExport::class)
                ->fileName(function (?Model $record, ?Outlet $outlet) {
                    return "sales_export";
                })
                ->isOutletRequired(false)
                ->hasOutletSelectionSchema(false)
                ->make(),
            LedgerExportAction::configure(SalesBreakDownExport::class)
                ->fileName(function (?Model $record, ?Outlet $outlet) {
                    return "sales_breakdown_export";
                })
                ->isOutletRequired(false)
                ->hasOutletSelectionSchema(false)
                ->make('Export Sales Breakdown'),
            RefreshAction::make(),
        ];
    }
}
