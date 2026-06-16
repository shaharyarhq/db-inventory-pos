<?php

namespace App\Models\Master;

use App\Enums\CustomerType;
use App\Enums\TransactionType;
use App\Models\Accounting\CustomerLedger;
use App\Models\Accounting\Receipt;
use App\Models\Master\Area;
use App\Models\Master\City;
use App\Models\Master\CustomerProductRate;
use App\Models\Sale\Sale;
use App\Models\Scopes\OutletScope;
use App\Models\Traits\HasStatus;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mattiverse\Userstamps\Traits\Userstamps;

class Customer extends Model
{
    use HasStatus;
    use Userstamps;

    protected $fillable = [
        'name',
        'city_id',
        'area_id',
        'photo',
        'address',
        'contact',
        'customer_type',
        'opening_balance',
        'attachments',
        'referred_by',
    ];

    protected $casts = [
        'attachments' => 'array',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by');
    }

    public function ledgers()
    {
        return $this->hasMany(CustomerLedger::class);
    }

    public function ledger()
    {
        return $this->morphOne(CustomerLedger::class, 'source');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function productRates(): HasMany
    {
        return $this->hasMany(CustomerProductRate::class);
    }

    public function businessToDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $this->loadMissing('sales.items', 'sales.saleReturns.items');
                return $this->sales->sum(fn($sale) => $sale->revenue);
            }
        );
    }

    public function lastSaleDate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->sales()->max('created_at')
        );
    }

    public function lastReceiptDate(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->receipts()->max('created_at')
        );
    }

    public function fullName(): Attribute
    {
        $name = $this->name;
        $area = $this->area?->name;
        $city = $this->city?->name;

        return Attribute::make(
            get: fn() => collect([$name, $area, $city])->filter()->implode(' - ')
        );
    }

    public function scopeWithCustomerBalances($query)
    {
        return $query->withSum([
            'ledgers as current_balance' => function ($q) {
                $q->withoutGlobalScope(OutletScope::class);
            },
        ], 'amount');
    }

    public static function options()
    {
        return Customer::get()->pluck('name', 'id');
    }

    public function getCustomerBalanceAsOf($asOf = null): float
    {
        $asOf = $asOf ? Carbon::parse($asOf) : now();

        return CustomerLedger::getCustomerBalanceQuery($this->id)
            ->where('created_at', '<', $asOf)
            ->sum('amount');
    }

    public static function booted()
    {
        static::saved(function ($customer) {
            CustomerLedger::withoutGlobalScope(OutletScope::class)->updateOrCreate(
                [
                    'customer_id'      => $customer->id,
                    'source_type'      => self::class,
                    'source_id'        => $customer->id,
                    'transaction_type' => TransactionType::OPENING_BALANCE->value,
                ],
                [
                    'amount'    => $customer->opening_balance ?? 0,
                    'remarks'   => 'Opening balance synced',
                    'outlet_id' => null,
                ]
            );

            // if ($customer->opening_balance == 0) {
            //     $openingBalanceLedger = $customer->ledger;

            //     if (
            //         $openingBalanceLedger
            //         && !$customer->receipts()->exists()
            //         && !$customer->sales()->exists()
            //         && $openingBalanceLedger->amount == 0
            //     ) {
            //         $openingBalanceLedger->delete();
            //     }
            // }
        });

        static::deleting(function ($customer) {
            if ($customer->customer_type === CustomerType::WALK_IN->value) {
                Notification::make('record_deletion_error')
                    ->danger()
                    ->title('Error While Deleting Record')
                    ->body('Walk-in customer cannot be deleted')
                    ->send();

                throw new \Exception('Walk-in customer cannot be deleted');
            }

            // if (! $customer->receipts()->exists() && ! $customer->sales()->exists()) {
            //     if ($customer->ledgers->count() === 1 && $customer->ledger->amount === 0) {
            //         $customer->ledger->delete();
            //     }
            // }
        });
    }
}
