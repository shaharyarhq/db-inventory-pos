<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Sale\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SalesBreakDownExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(
        protected ?int $recordId = null,
        protected ?int $outletId = null,
        protected ?Builder $filteredQuery = null,
    ) {}

    public function collection()
    {
        $sales = $this->filteredQuery
            ->with(['customer', 'creator', 'outlet', 'items.product', 'items.unit'])
            ->orderBy('id')
            ->get();

        return $sales->flatMap(function (Sale $sale) {
            return $sale->items->map(function ($item) use ($sale) {
                $item->setRelation('sale', $sale);
                return $item;
            });
        });
    }

    public function headings(): array
    {
        return [
            'Sale Number',
            'Customer',
            'Outlet',
            'Product',
            'Unit',
            'Qty',
            'Rate',
            'Cost',
            'Item Total',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];
    }

    public function map($item): array
    {
        $sale = $item->sale;

        return [
            $sale->sale_number,
            $sale->customer?->name ?? '-',
            $sale->outlet?->name ?? '-',
            $item->product?->full_name ?? '-',
            $item->unit?->name ?? '-',
            $item->qty,
            $item->rate,
            $item->cost,
            $item->total,
            Carbon::parse($sale->created_at)->format(app_date_time_format()),
            $sale->creator?->name ?? '-',
            Carbon::parse($sale->updated_at)->format(app_date_time_format()),
            $sale->updated_by?->name ?? '-',
        ];
    }
}
