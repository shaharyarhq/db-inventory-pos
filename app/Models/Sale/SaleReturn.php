<?php

namespace App\Models\Sale;

use App\BelongsToOutlet;
use App\Enums\TransactionType;
use App\Models\Accounting\CustomerLedger;
use App\Models\Traits\HasDocumentNumber;
use App\Models\Traits\Printable;
use App\Models\Traits\ResolvesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

class SaleReturn extends Model
{
    use BelongsToOutlet, HasDocumentNumber, ResolvesDocumentNumber;
    // SoftDeletes,
    use Userstamps;
    use Printable;

    public static string $documentNumberColumn = 'return_number';

    public static string $documentNumberPrefix = 'SR';

    protected $fillable = [
        'return_number',
        'sale_id',
        'description',
        'total',
        'discount_amount',
        'discount_type',
        'discount_value',
        'delivery_charges',
        'tax_charges',
        'grand_total',
        'outlet_id',
    ];

    protected $casts = [
        'attachments'       => 'array',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReturnItem::class);
    }

    public function ledger()
    {
        return $this->morphOne(CustomerLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($return) {

            // $total = $sale->items()->sum('total');

            // dd($total);

            // $deliveryCharges = $sale->delivery_charges ?? 0;
            // $taxCharges      = $sale->tax_charges ?? 0;

            // $discountAmount = 0;

            // if ($sale->discount_type === DiscountType::PERCENT) {

            //     $discountAmount = ($total * $sale->discount_value) / 100;
            // } elseif ($sale->discount_type === DiscountType::FIXED) {

            //     $discountAmount = $sale->discount_value;
            // }

            // $grandTotal = $total - $discountAmount;

            // $finalGrandTotal = $grandTotal + $deliveryCharges + $taxCharges;

            // if (
            //     $sale->total !== $total ||
            //     $sale->grand_total !== $finalGrandTotal
            // ) {
            //     $sale->updateQuietly([
            //         'total'       => $total,
            //         'grand_total' => $finalGrandTotal,
            //         'discount_amount' => $discountAmount
            //     ]);
            // }

            // if ($total > 0) {
            CustomerLedger::updateOrCreate(
                [
                    'source_type' => self::class,
                    'source_id'   => $return->id,
                ],
                [
                    'date'       => $return->created_at,
                    'customer_id'      => $return->sale->customer_id,
                    'amount'           => -$return->grand_total,
                    'transaction_type' => TransactionType::SALE_RETURN,
                    'remarks'          => 'Sale Return Saved',
                ]
            );
            // } else {
            // $sale->ledger()->delete();
            // }
        });

        static::deleting(function ($return) {
            // if ($return->sale->saleReturns()->exists()) {
            //     Notification::make()
            //         ->title('Cannot Delete')
            //         ->body('This record cannot be deleted because the purchase has been returned.')
            //         ->danger()
            //         ->send();

            //     throw new Halt("This record cannot be deleted because the purchase has been returned.");
            // }
            $return->ledger()->delete();
            $return->items->each->delete();
        });
    }
}
