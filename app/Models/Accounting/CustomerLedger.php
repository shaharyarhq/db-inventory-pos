<?php

namespace App\Models\Accounting;

use App\BelongsToOutlet;
use App\Models\Master\Customer;
use App\Models\Scopes\OutletScope;
use App\Models\Traits\HasTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomerLedger extends Model
{
    use BelongsToOutlet, HasTransactionType;

    protected $fillable = [
        'customer_id',
        'amount',
        'source_id',
        'source_type',
        'date',
        'transaction_type',
        'remarks',
        'outlet_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getBalanceForCustomerId(int $customerId): float
    {
        return self::withoutGlobalScope(OutletScope::class)->where('customer_id', $customerId)
            ->sum('amount');
    }

    public static function getCustomerBalanceQuery(int $customerId)
    {
        return self::withoutGlobalScope(OutletScope::class)->where('customer_id', $customerId);
    }
}
