<?php

namespace App\Exports;

use App\Enums\TransactionType;
use App\Exports\Traits\ResolvesParentRecord;
use App\Models\Inventory\InventoryLedger;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InventoryLedgerExport implements FromCollection, WithHeadings, WithMapping, WithStrictNullComparison
{
    use ResolvesParentRecord;

    protected float $runningBalance = 0;
    protected float $runningValuation = 0;

    public function __construct(
        protected ?Builder $filteredTableQuery,
        protected ?int $recordId = null,
        protected ?int $outletId = null,
    ) {}

    protected function canViewFinancials(): bool
    {
        return filament()->auth()->user()->can('ViewFinancials:Product');
    }

    public function collection()
    {
        return InventoryLedger::withCasts(default_ledger_casts())
            ->with([
                'product',
                'unit',
                'source',
            ])
            ->where('product_id', $this->recordId)
            ->when($this->outletId, function ($q) {
                return $q->where('outlet_id', $this->outletId);
            })
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        $headings = [
            'Product',
            'Unit',
            'In',
            'Out',
            'Balance',
            'Rate',
        ];

        if ($this->canViewFinancials()) {
            $headings[] = 'Value';
            $headings[] = 'Valuation';
        }

        return array_merge($headings, [
            'Transaction Type',
            'Source',
            'Remarks',
            'Outlet',
            'Created',
            'Created By',
            'Updated',
            'Updated By',
        ]);
    }

    public function map($ledger): array
    {
        $in  = $ledger->qty > 0 ? $ledger->qty : null;
        $out = $ledger->qty < 0 ? abs($ledger->qty) : null;

        $this->runningBalance += $ledger->qty;
        $this->runningValuation += $ledger->value;

        $row = [
            $ledger->product?->name,
            $ledger->unit?->name,
            $in ?: 0,
            $out ?: 0,
            $this->runningBalance,
            $ledger->rate,
        ];

        if ($this->canViewFinancials()) {
            $row[] = $ledger->value;
            $row[] = $this->runningValuation;
        }

        $transactionType = $ledger->transaction_type === TransactionType::SALE ? "{$ledger->transaction_type->label()} - {$ledger->source->sale->customer->name}" : $ledger->transaction_type->label();

        $parent = $this->resolveParentRecord($ledger->source);

        return array_merge($row, [
            $transactionType,
            $ledger->source && method_exists($ledger->source, 'resolveDocumentNumber')
                ? $ledger->source->resolveDocumentNumber()
                : '-',
            $ledger->remarks,
            $ledger->outlet->name,
            Carbon::parse($ledger->created_at)->format(app_date_time_format()),
            $parent?->creator?->name ?? '-',
            Carbon::parse($ledger->updated_at)->format(app_date_time_format()),
            $parent?->editor?->name ?? '-',
        ]);
    }
}
