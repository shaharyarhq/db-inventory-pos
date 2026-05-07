<?php

namespace Database\Seeders;

use App\Models\Accounting\Expense;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expense::whereNull('date')->chunkById(500, function ($expenses) {
            foreach ($expenses as $expense) {
                $expense->date = $expense->created_at;
                $expense->save();
            }
        });
    }
}
