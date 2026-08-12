<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\Expense;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SyncExpenseLedgerDateWithAccountLedger extends Seeder
{
    public function run(): void
    {
        AccountLedger::where('source_type', Expense::class)
            ->with('source')
            ->chunkById(500, function ($ledgers) {
                foreach ($ledgers as $ledger) {
                    if (! $ledger->source) {
                        continue;
                    }
                    if (!filled($ledger->source->date)) dd($ledger, $ledger->source);
                    $ledger->date = $ledger->source->date;
                    $ledger->save();
                }
            });
    }
}
