<?php

namespace App\Models\Accounting;

use App\Enums\TransactionType;
use App\Models\Accounting\AccountLedger;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class Account extends Model
{
    // use SoftDeletes;
    use Userstamps;

    protected $fillable = [
        'name',
        'account_number',
        'description',
        'opening_balance',
    ];

    public function ledgers()
    {
        return $this->hasMany(AccountLedger::class);
    }

    public function scopeWithBalances($query)
    {
        return $query->withSum('ledgers as current_balance', 'amount');
    }

    public static function booted()
    {
        static::saved(function ($account) {
            AccountLedger::updateOrCreate(
                [
                    'source_type' => Account::class,
                    'source_id' => $account->id,
                ],
                [
                    'date'       => $account->created_at,
                    'account_id' => $account->id,
                    'amount' => $account->opening_balance,
                    'transaction_type' => TransactionType::OPENING_BALANCE,
                    'remarks' => "Account Created",
                ]
            );
        });
    }
}
