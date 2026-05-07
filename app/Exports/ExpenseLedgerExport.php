<?php

namespace App\Exports;

use App\Exports\Traits\ResolvesParentRecord;
use App\Models\Accounting\ExpenseLedger;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ExpenseLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
     use ResolvesParentRecord;
    protected float $runningBalance = 0;

    public function __construct(
        protected ?int $expenseId = null,
        protected ?int $outletId = null,
    ) {}

    public function collection()
    {
        return ExpenseLedger::with([
            'expense',
            'expense.account',
            'expense.paymentMethod',
            'expense.expenseCategory',
            'source',
            'outlet',
        ])
            // ->when($this->expenseId, fn($q) => $q->where('expense_id', $this->expenseId))
            // ->when($this->outletId, fn($q) => $q->where('outlet_id', $this->outletId))
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Expense',
            'Account',
            'Payment Method',
            'Expense Category',
            'Debit',
            'Credit',
            'Balance',
            'Transaction Type',
            'Source',
            'Remarks',
            'Outlet',
            'Date',
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

        $this->runningBalance += $ledger->amount;

        $parent = $this->resolveParentRecord($ledger->source);

        return [
            $ledger->expense?->expense_number,
            $ledger->expense?->account->name,
            $ledger->expense?->paymentMethod?->name,
            $ledger->expense?->expenseCategory->name,
            $debit ?: 0,
            $credit ?: 0,
            $this->runningBalance,
            $ledger->transaction_type->label(),
            $ledger->source && method_exists($ledger->source, 'resolveDocumentNumber')
                ? $ledger->source->resolveDocumentNumber()
                : '-',
            $ledger->remarks,
            $ledger->outlet?->name,
            Carbon::parse($ledger->date)->format(app_date_format()),
            Carbon::parse($ledger->created_at)->format(app_date_time_format()),
            $parent?->creator?->name ?? '-',
            Carbon::parse($ledger->updated_at)->format(app_date_time_format()),
            $parent?->editor?->name ?? '-',
        ];
    }
}
