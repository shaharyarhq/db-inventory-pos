<?php

namespace App\Models\Purchase;

use App\Enums\TransactionType;
use App\Models\Accounting\SupplierLedger;
use App\Models\Purchase\Purchase;
use App\Models\Purchase\PurchaseReturnItem;
use App\Models\Traits\BelongsToOutlet;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\Printable;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

class PurchaseReturn extends Model
{
    use BelongsToOutlet, HasDocumentNumber, ResolvesDocumentNumber;
    // SoftDeletes,
    use Userstamps;
    use Printable;

    protected $fillable = [
        'return_number',
        'purchase_id',
        'description',
        'outlet_id',
        'attachments',
        'grand_total',
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public static string $documentNumberColumn = 'return_number';

    public static string $documentNumberPrefix = 'PR';

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function ledger()
    {
        return $this->morphOne(SupplierLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($return) {
            $total               = $return->items->sum('total');
            $return->grand_total = $total;
            $return->saveQuietly();

            SupplierLedger::updateOrCreate(
                [
                    'source_type' => self::class,
                    'source_id'   => $return->id,
                ],
                [
                    'date'       => $return->created_at,
                    'supplier_id'      => $return->purchase->supplier_id,
                    'amount'           => -$return->grand_total,
                    'transaction_type' => TransactionType::PURCHASE_RETURN,
                    'remarks'          => 'Purchase Return Saved',
                ]
            );
        });

        static::deleting(function ($return) {
            $return->ledger()->delete();
            $return->items->each->delete();
            // if ($return->ledger) {
            //     Notification::make('record_deletion_error')
            //         ->danger()
            //         ->title('Error While Deleting Record')
            //         ->body('Cannot delete item with linked ledger entries')
            //         ->send();

            //     throw new Exception();
            // }
        });
    }
}
