<?php

namespace App\Models\Purchase;

use App\BelongsToOutlet;
use App\Enums\TransactionType;
use App\Models\Accounting\Payment;
use App\Models\Accounting\SupplierLedger;
use App\Models\Master\Supplier;
use App\Models\Purchase\PurchaseItem;
use App\Models\Purchase\PurchaseReturn;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\Printable;
use App\Models\Traits\ResolvesDocumentNumber;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

class Purchase extends Model
{
    use BelongsToOutlet, HasDocumentNumber, ResolvesDocumentNumber;
    // SoftDeletes,
    use Userstamps;
    use Printable;

    protected $fillable = [
        'purchase_number',
        'attachments',
        'supplier_id',
        'description',
        'outlet_id',
        'total',
        'grand_total',
        'tax_charges',
        'delivery_charges',
        'discount_amount',
        'discount_value',
        'discount_type',
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public static string $documentNumberColumn = 'purchase_number';

    public static string $documentNumberPrefix = 'PO';

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function ledger()
    {
        return $this->morphOne(SupplierLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($purchase) {
            SupplierLedger::updateOrCreate(
                [
                    'source_type' => self::class,
                    'source_id'   => $purchase->id,
                ],
                [
                    'date'       => $purchase->created_at,
                    'supplier_id'      => $purchase->supplier_id,
                    'amount'           => $purchase->grand_total,
                    'transaction_type' => TransactionType::PURCHASE,
                    'remarks'          => 'Purchase Saved',
                ]
            );
        });

        static::deleting(function ($purchase) {
            if ($purchase->purchaseReturns()->exists()) {
                Notification::make('record_deletion_error')
                    ->danger()
                    ->title('Error While Deleting Record')
                    ->body('Cannot delete item with linked ledger entries')
                    ->send();

                throw new Halt();
            }
            $purchase->ledger()->delete();
            $purchase->items->each->delete();
            // if ($purchase->ledger) {
            //     Notification::make('record_deletion_error')
            //         ->danger()
            //         ->title('Error While Deleting Record')
            //         ->body('Cannot delete item with linked ledger entries')
            //         ->send();

            //     throw new Exception();
            // }

            // $ledgerExists = $purchase->ledger()->exists();

            // if (should_prevent_record_deletion_if_record_exists($purchase) && $ledgerExists) {
            //     throw new \RuntimeException('Cannot delete purchase with linked ledger entries.');
            // }

            // if ($ledgerExists) {
            //     $purchase->ledger()->delete();
            // }

        });
    }
}
