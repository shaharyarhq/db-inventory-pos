<?php

namespace App\Filament\Admin\Resources\Expenses\Pages;

use App\Exports\ExpenseLedgerExport;
use App\Filament\Admin\Resources\Expenses\ExpenseResource;
use App\Models\Outlet\Outlet;
use App\Support\Actions\LedgerExportAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
            LedgerExportAction::configure(ExpenseLedgerExport::class)
                ->fileName(function (?Model $record, ?Outlet $outlet) {
                    $outlet = Filament::getTenant();
                    return "expense_ledger_export";
                })
                ->isOutletRequired(false)
                ->hasOutletSelectionSchema(false)
                ->make(),
        ];
    }
}
