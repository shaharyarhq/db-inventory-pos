<?php

namespace App\Exports;

use Carbon\Carbon;
use App\Enums\TransactionType;
use App\Models\Accounting\CustomerLedger;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\WithMapping;
use App\Exports\Traits\ResolvesParentRecord;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CustomerLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
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
        return CustomerLedger::with([
            'customer',
            'source',
        ])
            ->where('customer_id', $this->recordId)
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
            'Customer',
            'Debit',
            'Credit',
            'Balance',
            'Transaction Type',
            'Source',
            'Aging (Days)',
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
        $debit  = $ledger->amount > 0 ? $ledger->amount : null;
        $credit = $ledger->amount < 0 ? abs($ledger->amount) : null;

        $agingDays = $ledger->amount > 0
            && $ledger->transaction_type !== TransactionType::OPENING_BALANCE
            ? (int) Carbon::parse($ledger->created_at)->diffInDays(now())
            : null;

        $this->runningBalance += $ledger->amount;

        $parent = $this->resolveParentRecord($ledger->source);

        return [
            $ledger->date,
            $ledger->customer?->name,
            $debit ?: 0,
            $credit ?: 0,
            $this->runningBalance,
            $ledger->transaction_type->label(),
            $ledger->source && method_exists($ledger->source, 'resolveDocumentNumber')
                ? $ledger->source->resolveDocumentNumber()
                : '-',
            $agingDays,
            $ledger->remarks,
            $ledger->outlet?->name,
            Carbon::parse($ledger->created_at)->format(app_date_time_format()),
            $parent?->creator?->name ?? '-',
            Carbon::parse($ledger->updated_at)->format(app_date_time_format()),
            $parent?->editor?->name ?? '-',
        ];
    }
}
