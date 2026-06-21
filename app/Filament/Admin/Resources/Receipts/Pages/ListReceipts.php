<?php

namespace App\Filament\Admin\Resources\Receipts\Pages;

use App\Exports\ReceiptExport;
use App\Filament\Admin\Resources\Receipts\ReceiptResource;
use App\Models\Outlet\Outlet;
use App\Support\Actions\LedgerExportAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListReceipts extends ListRecords
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            LedgerExportAction::configure(ReceiptExport::class)
                ->fileName(function (?Model $record, ?Outlet $outlet) {
                    return "receipt_export";
                })
                ->isOutletRequired(false)
                ->hasOutletSelectionSchema(false)
                ->make(),
        ];
    }
}
