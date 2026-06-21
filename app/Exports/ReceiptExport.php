<?php

namespace App\Exports;

use App\Models\Accounting\Receipt;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ReceiptExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    public function __construct(
        protected Builder $filteredTableQuery,
        protected ?int $recordId = null,
        protected ?int $outletId = null,
    ) {}


    public function collection()
    {
        return $this->filteredTableQuery->with(['customer', 'account', 'creator'])->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'Receipt Number',
            'Customer',
            'Account',
            'Amount',
            'Status',
            'Remarks',
            'Outlet',
            'Created At',
            'Created By',
            'Updated At',
            'Updated By',
        ];
    }

    public function map($receipt): array
    {
        return [
            $receipt->receipt_number,
            $receipt->customer?->name ?? '-',
            $receipt->account?->name ?? '-',
            $receipt->amount ?? 0,
            $receipt->status?->label() ?? '-',
            $receipt->remarks,
            $receipt->outlet?->name ?? '-',
            Carbon::parse($receipt->created_at)->format(app_date_time_format()),
            $receipt->creator?->name ?? '-',
            Carbon::parse($receipt->updated_at)->format(app_date_time_format()),
            $receipt->editor?->name ?? '-',
        ];
    }
}
