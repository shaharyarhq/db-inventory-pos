<?php

namespace App\Models\Sale;

use App\Enums\DiscountType;
use App\Enums\TransactionType;
use App\Models\Inventory\InventoryLedger;
use App\Models\Master\Product;
use App\Models\Master\Unit;
use App\Models\Sale\Sale;
use App\Models\Traits\HasTransactionType;
use App\Models\Traits\ResolvesDocumentNumber;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    use ResolvesDocumentNumber, HasTransactionType;

    protected $fillable = [
        'sale_id',
        'product_id',
        'unit_id',
        'qty',
        'cost',
        'rate',
        'discount_type',
        'discount_value',
        'total'
    ];

    protected $casts = [
        'discount_type' => DiscountType::class,
    ];

    public static $parentRelation = 'sale';

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function ledger()
    {
        return $this->morphOne(InventoryLedger::class, 'source');
    }

    public static function booted()
    {
        static::saving(function ($item) {
            // $qty   = $item->qty;
            // $rate  = $item->rate;

            // dd($item->total, $qty, $rate);

            // $total = $qty * $rate;

            if (!$item->discount_type) {
                $item->discount_type = DiscountType::FIXED;
                $item->discount_value = 0;
            }

            // if ($item->discount_type === DiscountType::PERCENT) {
            //     $total -= ($total * $item->discount_value / 100);
            // }

            // if ($item->discount_type === DiscountType::FIXED) {
            //     $total -= $item->discount_value;
            // }

            // $item->cost = $item->product->getAvgRateOfUnitAsOf($item->created_at, $item->unit_id);

            $item->cost = $item->product->getAvgRateOfUnitAsOf($item->created_at);

            // dd($item->product->getAvgRateOfUnitAsOf($item->created_at, $item->unit_id));

            // $item->total =  $total;
        });

        static::saved(function ($item) {
            $avgRate = $item->cost;

            if ($avgRate <= 0) {
                Notification::make()
                    ->title('Cost is zero or negative')
                    ->body('Product: ' . $item->product->full_name . ' has zero or negative cost (avg rate).')
                    ->danger()
                    ->send();

                throw new Halt();
            }

            $product = $item->product;

            $baseQty = $item->product->toBaseQty($item->qty, $item->unit_id);

            $data =  [
                'product_id'     => $item->product_id,
                'unit_id'        => $product->unit_id, // base unit
                'qty'            => -$baseQty,
                'rate'           => $avgRate,
                'value'          => - ($avgRate * $baseQty),
                'transaction_type' => TransactionType::SALE,
                'remarks'        => 'Sale Saved',
            ];


            if (!filament()->getPanel()) {
                $data = array_merge($data, [
                    'outlet_id' => $item->sale->outlet_id,
                ]);
            }

            InventoryLedger::updateOrCreate(
                [
                    'source_type' => self::class,
                    'source_id'   => $item->id,
                ],
                $data
            );
        });

        static::deleting(function ($item) {
            $item->ledger()->delete();
            // if ($item->ledger || $item->supplierLedger) {
            //     Notification::make('record_deletion_error')
            //         ->danger()
            //         ->title('Error While Deleting Record')
            //         ->body('Cannot delete item with linked ledger entries')
            //         ->send();

            //     throw new Halt;
            // }
        });
    }
}
