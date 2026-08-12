<?php

namespace App\Models\Accounting;

use App\Enums\TransactionType;
use App\Models\Accounting\AccountLedger;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class Deposit extends Model
{
    use HasDocumentNumber, ResolvesDocumentNumber;
    use Userstamps;

    protected $fillable = [
        'deposit_number',
        'account_id',
        'attachments',
        'amount',
        'remarks',
        'attachments',
        'date',
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public static string $documentNumberColumn = 'deposit_number';

    public static string $documentNumberPrefix = 'DEP';

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function accountLedger()
    {
        return $this->morphOne(AccountLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($deposit) {
            AccountLedger::updateOrCreate([
                'source_id'   => $deposit->id,
                'source_type' => Deposit::class,
            ], [
                'date'             => $deposit->date,
                'account_id'       => $deposit->account_id,
                'amount'           => $deposit->amount,
                'transaction_type' => TransactionType::DEPOSIT->value,
                'remarks'          => 'Deposit created',
                'outlet_id'        => null,
            ]);
        });

        static::deleting(function ($deposit) {
            $deposit->accountLedger()->delete();
        });
    }
}
