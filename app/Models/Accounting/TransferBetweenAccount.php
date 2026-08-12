<?php

namespace App\Models\Accounting;

use App\Enums\TransactionType;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class TransferBetweenAccount extends Model
{
    use HasDocumentNumber, ResolvesDocumentNumber;
    use Userstamps;

    protected $fillable = [
        'transfer_number',
        'from_account_id',
        'to_account_id',
        'amount',
        'remarks',
        'attachments',
        'date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public static string $documentNumberColumn = 'transfer_number';

    public static string $documentNumberPrefix = 'DEP';


    public function fromAccount()
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    /**
     * The account receiving the money.
     */
    public function toAccount()
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }


    public function accountLedger()
    {
        return $this->morphOne(AccountLedger::class, 'source');
    }


    public static function booted()
    {
        static::saved(function ($transfer) {
            AccountLedger::updateOrCreate([
                'source_id'   => $transfer->id,
                'source_type' => TransferBetweenAccount::class,
                'index' => 0,
            ], [
                'date'             => $transfer->date,
                'account_id'       => $transfer->from_account_id,
                'amount'           => -$transfer->amount,
                'transaction_type' => TransactionType::TRANSFER_BETWEEN_ACCOUNTS->value,
                'remarks'          => "Transferred to Account '{$transfer->fromAccount->name}'",
                'outlet_id'        => null,
            ]);

            AccountLedger::updateOrCreate([
                'source_id'   => $transfer->id,
                'source_type' => TransferBetweenAccount::class,
                'index' => 1,
            ], [
                'date'             => $transfer->date,
                'account_id'       => $transfer->to_account_id,
                'amount'           => $transfer->amount,
                'transaction_type' => TransactionType::TRANSFER_BETWEEN_ACCOUNTS->value,
                'remarks'          => "Transferred From Account '{$transfer->toAccount->name}'",
                'outlet_id'        => null,
            ]);
        });

        static::deleting(function ($transfer) {
            $transfer->accountLedger()->delete();
        });
    }
}
