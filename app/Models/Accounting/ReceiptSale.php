<?php

namespace App\Models\Accounting;

use App\Models\Sale\Sale;
use Illuminate\Database\Eloquent\Model;

class ReceiptSale extends Model
{
    protected $fillable = [
        'receipt_id',
        'sale_id',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class)->withoutGlobalScopes();
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class)->withoutGlobalScopes();
    }

    public static function booted()
    {
        static::created(function ($receiptSale) {
            $remarks = ' Sale : ' . $receiptSale->sale->sale_number;
            if ($receiptSale->receipt->remarks != '') {
                $remarks = $receiptSale->receipt->remarks . ' | ' . $remarks;
            }
            $receiptSale->receipt->update([
                'remarks' => $remarks,
            ]);
        });
    }
}
