<?php

namespace App\Models\Accounting;

use App\Enums\TransactionType;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountLedger;
use App\Models\Accounting\PaymentMethod;
use App\Models\Accounting\SupplierLedger;
use App\Models\Master\Supplier;
use App\Models\Purchase\Purchase;
use App\Models\Traits\BelongsToOutlet;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\HasTransactionType;
use App\Models\Traits\Printable;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class Payment extends Model
{
    use BelongsToOutlet, HasDocumentNumber, HasTransactionType, ResolvesDocumentNumber;
    use Userstamps;
    use Printable;

    protected $fillable = [
        'payment_number',
        'supplier_id',
        'account_id',
        'purchase_id',
        'payment_method_id',
        'amount',
        'remarks',
        'attachments',
        'outlet_id',
        'date'
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public static string $documentNumberColumn = 'payment_number';

    public static string $documentNumberPrefix = 'PAY';

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function accountLedger()
    {
        return $this->morphOne(AccountLedger::class, 'source');
    }

    public function supplierLedger()
    {
        return $this->morphOne(SupplierLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($payment) {
            $transactionType = $payment->amount < 0 ? TransactionType::PAYMENT_REFUND_OR_ADJUSTMENT->value : TransactionType::PAYMENT->value;

            AccountLedger::updateOrCreate(
                [
                    'source_type' => Payment::class,
                    'source_id'   => $payment->id,
                ],
                [
                    'date'             => $payment->date,
                    'account_id'       => $payment->account_id,
                    'amount'           => -$payment->amount,
                    'transaction_type' => $transactionType,
                    'remarks'          => $payment->remarks ?? "Payment created for supplier {$payment->supplier->name} from account {$payment->account->name}",
                ]
            );
            SupplierLedger::updateOrCreate(
                [
                    'source_type' => Payment::class,
                    'source_id'   => $payment->id,
                ],
                [
                    'date'             => $payment->date,
                    'supplier_id'      => $payment->supplier_id,
                    'amount'           => -$payment->amount,
                    'transaction_type' => $transactionType,
                    'remarks'          => $payment->remarks ?? "Payment created for supplier {$payment->supplier->name} from account {$payment->account->name}",
                ]
            );
        });

        static::deleting(function ($receipt) {
            $receipt->accountLedger()->delete();
            $receipt->supplierLedger()->delete();
        });
    }
}
