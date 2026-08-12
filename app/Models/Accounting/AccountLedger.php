<?php

namespace App\Models\Accounting;

use App\BelongsToOutlet;
use App\Models\Accounting\Account;
use App\Models\Scopes\OutletScope;
use App\Models\Traits\HasTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountLedger extends Model
{
    use BelongsToOutlet, HasTransactionType;

    protected $fillable = [
        'account_id',
        'amount',
        'source_id',
        'source_type',
        'date',
        'transaction_type',
        'remarks',
        'outlet_id',
        'date'
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getBalanceForAccountId(int $accountId): float
    {
        return AccountLedger::withoutGlobalScope(OutletScope::class)
            ->where('account_id', $accountId)
            ->sum('amount');
    }
}
