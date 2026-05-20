<?php

namespace App\Models\Inventory;

use App\Enums\TransactionType;
use App\Models\Master\Product;
use App\Models\Traits\ResolvesDocumentNumber;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustmentItem extends Model
{
    use ResolvesDocumentNumber;

    protected $fillable = [
        'inventory_adjustment_id',
        'product_id',
        'qty',
    ];

    public static $parentRelation = 'adjustment';

    public function adjustment()
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function ledger()
    {
        return $this->morphOne(InventoryLedger::class, 'source');
    }

    public static function booted()
    {
        static::saved(function ($item) {

            $avgRate =  $item->product->getAvgRateAsOf($item->created_at) ?: (float) $item->product->cost_price;

            if ($avgRate <= 0) {
                Notification::make()
                    ->title('Average rate is zero or negative')
                    ->body('The average rate for the product: ' . $item->product->name . ' is zero or negative. Please ensure the product has cost price defined.')
                    ->danger()
                    ->send();
                throw new Halt();
            }

            $value = $item->qty * $avgRate;

            InventoryLedger::updateOrCreate(
                [
                    'source_type' => self::class,
                    'source_id'   => $item->id,
                ],
                [
                    'product_id'       => $item->product_id,
                    'unit_id'          => $item->product->unit_id,
                    'qty'              => $item->qty,
                    'rate'             => $avgRate,
                    'value'            => $value,
                    'transaction_type' => TransactionType::INVENTORY_ADJUSTMENT,
                    'remarks'          => 'Inventory Adjustment Saved',
                ]
            );
        });

        static::deleting(function ($item) {
            $item->ledger()->delete();
        });
    }
}
