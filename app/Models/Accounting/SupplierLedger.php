<?php

namespace App\Models\Accounting;

use App\BelongsToOutlet;
use App\Models\Master\Supplier;
use App\Models\Scopes\OutletScope;
use App\Models\Traits\HasTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplierLedger extends Model
{

    use BelongsToOutlet, HasTransactionType;

    protected $fillable = [
        'supplier_id',
        'amount',
        'source_id',
        'source_type',
        'date',
        'reference_id',
        'reference_type',
        'transaction_type',
        'remarks',
        'outlet_id',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function reference()
    {
        return $this->morphTo();
    }

    public static function getBalanceForSupplierId(int $supplierId): float
    {
        return SupplierLedger::withoutGlobalScope(OutletScope::class)
            ->where('supplier_id', $supplierId)
            ->sum('amount');
    }

    public static function getSupplierBalanceQuery(int $supplierId)
    {
        return SupplierLedger::withoutGlobalScope(OutletScope::class)
            ->where('supplier_id', $supplierId);
    }
}
