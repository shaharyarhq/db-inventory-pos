<?php

namespace App\Models\Accounting;

use App\BelongsToOutlet;
use App\Models\Accounting\Expense;
use App\Models\Traits\HasTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ExpenseLedger extends Model
{
    use BelongsToOutlet, HasTransactionType;
    
    protected $fillable = [
        'expense_id',
        'amount',
        'source_id',
        'source_type',
        'transaction_type',
        'remarks',
        'date',
        'outlet_id'
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
