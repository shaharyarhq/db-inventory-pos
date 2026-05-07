<?php

namespace App\Models\Accounting;

use App\BelongsToOutlet;
use App\Enums\TransactionType;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\HasTransactionType;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class Expense extends Model
{
    use BelongsToOutlet, HasDocumentNumber, HasTransactionType, ResolvesDocumentNumber;
    use Userstamps;

    protected $fillable = [
        'expense_number',
        'account_id',
        'expense_category_id',
        'attachments',
        'date',
        'payment_method_id',
        'amount',
        'description',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public static string $documentNumberColumn = 'expense_number';

    public static string $documentNumberPrefix = 'EXP';

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function accountLedger()
    {
        return $this->morphOne(AccountLedger::class, 'source');
    }

    public function expenseLedger()
    {
        return $this->morphOne(ExpenseLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($expense) {

            AccountLedger::updateOrCreate(
                [
                    'source_type' => Expense::class,
                    'source_id' => $expense->id,
                ],
                [
                    'account_id' => $expense->account_id,
                    'amount' => -$expense->amount,
                    'transaction_type' => TransactionType::EXPENSE,
                    'remarks' => "Expense Recorded: {$expense->expense_number} from account: '{$expense->account->name}' for category '{$expense->expenseCategory->name}'".($expense->paymentMethod ? " via payment method: '{$expense->paymentMethod->name}'" : ''),
                ]
            );

            ExpenseLedger::updateOrCreate(
                [
                    'source_type' => Expense::class,
                    'source_id' => $expense->id,
                ],
                [
                    'date' => $expense->date ?? $expense->created_at,
                    'expense_id' => $expense->id,
                    'amount' => $expense->amount,
                    'transaction_type' => TransactionType::EXPENSE,
                    'remarks' => "Expense Created: {$expense->expense_number} from account: '{$expense->account->name}' for category '{$expense->expenseCategory->name}'".($expense->paymentMethod ? " via payment method: '{$expense->paymentMethod->name}'" : ''),
                ]
            );
        });

        static::deleting(function ($expense) {
            $expense->accountLedger()->delete();
            $expense->expenseLedger()->delete();
        });
    }
}
