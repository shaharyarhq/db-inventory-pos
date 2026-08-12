<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Models\Accounting\SupplierLedger;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Exports\Traits\ResolvesParentRecord;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class SupplierLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ResolvesParentRecord;

    protected float $runningBalance = 0;

    public function __construct(
        protected ?Builder $filteredTableQuery,
        protected ?int $recordId = null,
        protected ?int $outletId = null,
    ) {}

    public function collection()
    {
        return SupplierLedger::with([
            'supplier',
            'source',
        ])
            ->where('supplier_id', $this->recordId)
            ->when($this->outletId, function ($q) {
                return $q->where('outlet_id', $this->outletId);
            })
            ->orderBy('id')
            ->get()
            ->sortBy('date');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Supplier',
            'Debit',
            'Credit',
            'Balance',
            'Transaction Type',
            'Source',
            'Remarks',
            'Outlet',
            'Created',
            'Created By',
            'Updated',
            'Updated By',
        ];
    }

    public function map($ledger): array
    {
        $debit = $ledger->amount < 0 ? abs($ledger->amount) : null;
        $credit  = $ledger->amount > 0 ? $ledger->amount : null;

        $this->runningBalance += $ledger->amount;

        $parent = $this->resolveParentRecord($ledger->source);

        return [
            $ledger->date,
            $ledger->supplier?->name,
            $debit ?: 0,
            $credit ?: 0,
            $this->runningBalance,
            $ledger->transaction_type->label(),
            $ledger->source && method_exists($ledger->source, 'resolveDocumentNumber')
                ? $ledger->source->resolveDocumentNumber()
                : '-',
            $ledger->remarks,
            $ledger->outlet?->name,
            Carbon::parse($ledger->created_at)->format(app_date_time_format()),
            $parent?->creator?->name ?? '-',
            Carbon::parse($ledger->updated_at)->format(app_date_time_format()),
            $parent?->editor?->name ?? '-',
        ];
    }
}
