<?php

namespace App\Exports;

use App\Models\Sale\Sale;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(
        protected ?int $recordId = null,
        protected ?int $outletId = null,
        protected ?Builder $filteredTableQuery = null,
    ) {}

    public function collection()
    {
        return $this->filteredTableQuery->with(['customer', 'items', 'creator'])->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'Sale Number',
            'Customer',
            'Description',
            'Total',
            'Discount Type',
            'Discount Value',
            'Discount Amount',
            'Delivery Charges',
            'Tax Charges',
            'Grand Total',
            'Outlet',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->sale_number,
            $sale->customer?->name ?? '-',
            $sale->description,
            $sale->total,
            $sale->discount_type?->label() ?? '-',
            $sale->discount_value,
            $sale->discount_amount,
            $sale->delivery_charges,
            $sale->tax_charges,
            $sale->grand_total,
            $sale->outlet->name,
            Carbon::parse($sale->created_at)->format(app_date_time_format()),
            $sale->creator->name,
            Carbon::parse($sale->updated_at)->format(app_date_time_format()),
            $sale->editor->name,
        ];
    }
}
