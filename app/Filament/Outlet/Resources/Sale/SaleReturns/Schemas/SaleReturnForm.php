<?php

namespace App\Filament\Outlet\Resources\Sale\SaleReturns\Schemas;

use App\Enums\DiscountType;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountAmountInput;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountTypeSelect;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountValueInput;
use App\Models\Master\Product;
use App\Models\Sale\Sale;
use Closure;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class SaleReturnForm
{
    public static function configure(Schema $schema): Schema
    {
        // filament()
        //     ->getCurrentPanel()
        //     ->maxContentWidth(Width::Full);

        $products = Product::with('unit', 'subUnit', 'customerRates')->withOutletStock()->get(['id', 'name', 'selling_price', 'unit_id', 'sub_unit_id', 'sub_unit_conversion']);

        $productsKeyedArray = $products->keyBy('id')->toArray();

        return $schema
            ->components([
                // Hidden::make('products')
                //     ->afterStateHydrated(fn(Set $set) => $set('products', $productsKeyedArray))
                //     ->dehydrated(false),

                Section::make()
                    ->columnSpanFull()
                    ->schema([
                        Select::make('sale_id')
                            ->relationship('sale', 'sale_number')
                            ->columnSpanFull()
                            ->disabled()
                            ->saved()
                            ->required(),
                    ]),
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        DiscountTypeSelect::make()
                            ->disabled()
                            ->saved(true),
                        DiscountValueInput::make()
                            ->rules(function (Get $get, ?Model $record) {
                                return [
                                    'min:0',
                                    function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                        $discountType = $get('discount_type');
                                        $discountValue = $get('discount_value');
                                        $deliveryCharges = $get('delivery_charges') ?? 0;
                                        $taxCharges      = $get('tax_charges') ?? 0;

                                        if ($discountType === DiscountType::PERCENT) {
                                            if ($discountValue < 0 || $discountValue > 100) {
                                                $fail('Percent discount must be between 0 and 100.');
                                            }
                                        } else { // FIXED
                                            if ($discountValue < 0) {
                                                $fail('Fixed discount cannot be negative.');
                                            }
                                        }

                                        $items = $get('items') ?? [];
                                        $total = 0;

                                        foreach ($items as $item) {
                                            $total += $item['total'];
                                        }

                                        if ($discountType === DiscountType::PERCENT) {
                                            $discountAmount = ($total * $discountValue) / 100;
                                        } elseif ($discountType === DiscountType::FIXED) {
                                            $discountAmount = $discountValue;
                                        }

                                        $grandTotal = $deliveryCharges + $taxCharges + $total;

                                        if ($discountAmount > $grandTotal) {
                                            $fail("Discount amount cannot be greater than total amount Rs $grandTotal");
                                        }

                                        $saleId = $get('sale_id');
                                        $alreadyPaidDiscount = 0;

                                        if ($saleId) {

                                            $sale = Sale::with([
                                                'items',
                                                'saleReturns.items',
                                            ])->find($saleId);

                                            if ($sale) {

                                                $originalSaleDiscount = $sale->discount_amount; // use actual amount, not percent

                                                foreach ($sale->saleReturns as $return) {

                                                    // exclude current record while editing
                                                    if ($record && $return->id === $record->id) {
                                                        continue;
                                                    }

                                                    $alreadyPaidDiscount += $return->discount_amount;
                                                }

                                                $remainingDiscount = $originalSaleDiscount - $alreadyPaidDiscount;

                                                if ($discountAmount > $remainingDiscount) {
                                                    $fail("You can only apply Rs {$remainingDiscount} discount on this return.");
                                                }
                                            }
                                        }
                                    }
                                ];
                            }),
                        DiscountAmountInput::make(),
                        TextInput::make('total')
                            ->label('Total Amount')
                            ->currency()
                            ->disabled()
                            ->saved()
                            ->minValue(0)
                            ->rules('min:0')
                            ->required()
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                $items = $get('items') ?? [];
                                $total = 0;

                                foreach ($items as $item) {
                                    $qty = $item['qty'] ?? 0;
                                    $rate = $item['rate'] ?? 0;
                                    $total += $qty * $rate;
                                }

                                return $total;
                            }),
                        TextInput::make('delivery_charges')
                            ->currency()
                            ->afterStateUpdatedJs(self::calculateGrandTotal())
                            ->required(),
                        TextInput::make('tax_charges')
                            ->currency()
                            ->afterStateUpdatedJs(self::calculateGrandTotal())
                            ->required(),
                        TextInput::make('grand_total')
                            ->label('Grand Total')
                            ->currency()
                            ->disabled()
                            ->saved()
                            ->minValue(0)
                            ->rules(function (Set $set, Get $get) {
                                return [
                                    'min:0',
                                    function ($attribute, $value, Closure $fail) use ($get) {
                                        $grandTotal = $get('grand_total') ?? 0;
                                        $saleId = $get('sale_id');

                                        $sale = Sale::with([
                                            'items',
                                            'saleReturns.items',
                                        ])->find($saleId);

                                        if ($sale) {

                                            $originalSaleTotal = $sale->grand_total; // must be stored properly

                                            $alreadyReturnedTotal = 0;

                                            foreach ($sale->saleReturns as $return) {

                                                // exclude current record while editing
                                                if ($get('id') && $return->id == $get('id')) {
                                                    continue;
                                                }

                                                $alreadyReturnedTotal += $return->grand_total;
                                            }

                                            $remainingRefundable = $originalSaleTotal - $alreadyReturnedTotal;

                                            if ($value > $remainingRefundable) {
                                                $fail("Return total cannot exceed remaining refundable amount Rs {$remainingRefundable}");
                                            }
                                        }
                                    }
                                ];
                            })
                            ->required()
                            ->dehydrateStateUsing(function ($state, Get $get) {
                                $items = $get('items') ?? [];
                                $total = 0;
                                $deliveryCharges = (float) $get('delivery_charges');
                                $taxCharges      = (float) $get('tax_charges');
                                $discountType = $get('discount_type') ?? DiscountType::FIXED;
                                $discountValue = (float) $get('discount_value');

                                foreach ($items as $item) {
                                    $qty = $item['qty'] ?? 0;
                                    $rate = $item['rate'] ?? 0;
                                    $total += $qty * $rate;
                                }

                                if ($discountType === DiscountType::PERCENT) {
                                    $total -= ($total * $discountValue / 100);
                                } elseif ($discountType === DiscountType::FIXED) {
                                    $total -= $discountValue;
                                }

                                $grandTotal = $total + $deliveryCharges + $taxCharges;

                                return $grandTotal;
                            }),
                    ]),
                Repeater::make('items')
                    ->relationship('items')
                    ->statePath('items')
                    ->minItems(fn($operation) => $operation == 'edit' ? 0 : 1)
                    ->columnSpanFull()
                    ->columns(6)
                    ->afterStateUpdatedJs(self::calculateGrandTotal())
                    ->default(self::getDefaultRepeaterData())
                    ->table([
                        TableColumn::make('Product'),
                        TableColumn::make('Unit'),
                        TableColumn::make('Quantity'),
                        TableColumn::make('Rate'),
                        // TableColumn::make('Discount Type'),
                        // TableColumn::make('Discount Value'),
                        TableColumn::make('Total'),
                    ])
                    // ->reorderable()
                    ->addable(false)
                    ->compact()
                    ->schema([
                        Select::make('product_id')
                            ->relationship('product', 'name')
                            ->disabled()
                            ->dehydrated()
                            ->disableOptionWhen(function ($value, $state, $get) {
                                $selected = collect($get('../../items'))
                                    ->pluck('product_id')
                                    ->filter()
                                    ->toArray();

                                return in_array($value, $selected) && $state != $value;
                            })
                            // ->afterStateUpdatedJs(<<<'JS'
                            //     const productId = $get('product_id');
                            //     const customerId = $get('../../customer_id');
                            //     const products = $get('../../products') ?? {};

                            //     let rate = 0;
                            //     let unit_id = null;

                            //     console.log(productId)
                            //     console.log(customerId)
                            //     console.log(products)

                            //     if (products[productId]) {

                            //     console.log(products[productId])

                            //     const customerRateObj = (products[productId].customer_rates || []).find(
                            //         r => r.customer_id == customerId
                            //     );

                            //     if (customerRateObj) {
                            //         rate = parseFloat(customerRateObj.selling_price);
                            //     } else {
                            //         rate = parseFloat(products[productId].selling_price || 0);
                            //     }

                            //     unit_id = products[productId].unit_id ?? null;

                            //     console.log(unit_id, rate)


                            //     }

                            //     $set('rate', rate);
                            //     $set('unit_id', unit_id);

                            //     const qty = parseFloat($get('qty')) || 0;
                            //     $set('total', qty * rate);

                            //     const items = $get('../../items') ?? {};
                            //     const grandTotal = Object.values(items).reduce((sum, item) => {
                            //         return sum + (parseFloat(item.total) || 0);
                            //     }, 0);

                            //     $set('../../grand_total', grandTotal);
                            // JS)
                            ->required(),
                        Select::make('unit_id')
                            ->relationship('unit', 'name')
                            ->disableOptionWhen(function ($value, $state, $get) use ($productsKeyedArray) {
                                $productId = $get('product_id');
                                $product = $productsKeyedArray[$productId] ?? null;

                                $unitId = $product['unit_id'] ?? null;
                                $subUnitId = $product['sub_unit_id'] ?? null;

                                return !in_array($value, [$unitId, $subUnitId]);
                            })
                            ->rules(function (Get $get) use ($productsKeyedArray) {
                                $productId = $get('product_id');
                                $product   = $productsKeyedArray[$productId] ?? null;

                                if (! $product) {
                                    return [];
                                }

                                $allowedUnits = array_filter([
                                    $product['unit_id'] ?? null,
                                    $product['sub_unit_id'] ?? null,
                                ]);

                                if (empty($allowedUnits)) {
                                    return [];
                                }

                                return [
                                    'required',
                                    'in:' . implode(',', $allowedUnits),
                                ];
                            })
                            ->disabled()
                            ->saved()
                            // ->afterStateUpdatedJs(<<<'JS'
                            //     const productId = $get('product_id');
                            //     const selectedUnitId = $get('unit_id');
                            //     const customerId = $get('../../customer_id');
                            //     const products = $get('../../products') ?? {};

                            //     let rate = 0;
                            //     let baseRate = 0;

                            //     if (products[productId]) {
                            //         const product = products[productId];

                            //         const customerRateObj = (product.customer_rates || []).find(
                            //             r => r.customer_id == customerId
                            //         );

                            //         rate = parseFloat(
                            //             customerRateObj?.selling_price ??
                            //             product.selling_price ??
                            //             0
                            //         );

                            //         const productUnitId = product.unit_id;
                            //         const productSubUnitId = product.sub_unit_id;
                            //         const conversion = parseFloat(product.sub_unit_conversion ?? 0);

                            //         console.log(productUnitId);
                            //         console.log(productSubUnitId);
                            //         console.log(conversion);
                            //         console.log(selectedUnitId);

                            //         if (selectedUnitId == productUnitId) {
                            //             baseRate = rate;
                            //         } else if (selectedUnitId == productSubUnitId) {
                            //             baseRate = rate / conversion;
                            //         } else {
                            //             baseRate = rate;
                            //         }

                            //         console.log(baseRate ,rate, rate / conversion);

                            //     }

                            //     $set('rate', Number(baseRate.toFixed(2)));

                            //     const qty = parseFloat($get('qty')) || 0;
                            //     $set('total', qty * baseRate);

                            //     const items = $get('../../items') ?? {};
                            //     const grandTotal = Object.values(items).reduce((sum, item) => {
                            //         return sum + (parseFloat(item.total) || 0);
                            //     }, 0);

                            //     $set('../../grand_total', grandTotal);
                            // JS)
                            // ->helperText(function (Get $get) use ($productsKeyedArray) {
                            //     $productId = $get('product_id');
                            //     $selectedUnitId = $get('unit_id');

                            //     $product = $productsKeyedArray[$productId] ?? null;
                            //     if (!$product) {
                            //         return;
                            //     }

                            //     $unitId = $product['unit_id'] ?? null;
                            //     $subUnitId = $product['sub_unit_id'] ?? null;
                            //     $conversion = $product['sub_unit_conversion'] ?? 0;

                            //     $stock = $product['current_outlet_stock'] ?? 0;
                            //     $stockInSubUnit = $stock * $conversion;

                            //     if ($selectedUnitId === $unitId) {
                            //         return $stock;
                            //     } elseif ($selectedUnitId === $subUnitId) {
                            //         return $stockInSubUnit;
                            //     }

                            //     return '';
                            // })
                            ->required(),
                        TextInput::make('qty')
                            ->label('Quantity')
                            ->numeric()
                            ->required()
                            ->afterStateUpdatedJs(self::calculateTotals())
                            ->default(0)
                            ->minValue(fn($operation) => $operation === "edit" ? 0 : 1)
                            ->rules(function (Get $get, ?Model $record) use ($productsKeyedArray) {
                                return [
                                    'required',
                                    'numeric',
                                    fn($operation) => $operation === "edit" ? 'min:0' : 'min:1',
                                    function (string $attribute, $value, Closure $fail) use ($get, $productsKeyedArray, $record) {
                                        $productId      = $get('product_id');
                                        $selectedUnitId = $get('unit_id');
                                        $saleId         = $get('../../sale_id');

                                        if (! $productId || ! $selectedUnitId) {
                                            return;
                                        }

                                        $product = $productsKeyedArray[$productId] ?? null;

                                        if (! $product) {
                                            return;
                                        }

                                        /*
            |--------------------------------------------------------------------------
            | UNIT CONVERSION (convert entered qty → base unit)
            |--------------------------------------------------------------------------
            */

                                        $unitId     = $product['unit_id'] ?? null;
                                        $subUnitId  = $product['sub_unit_id'] ?? null;
                                        $conversion = (float) ($product['sub_unit_conversion'] ?? 0);

                                        $enteredQtyInBase = 0;

                                        if ($selectedUnitId == $unitId) {
                                            $enteredQtyInBase = (float) $value;
                                        } elseif ($selectedUnitId == $subUnitId) {
                                            if ($conversion <= 0) {
                                                $fail("Invalid sub unit conversion, please define conversion in product");
                                            }
                                            $enteredQtyInBase = (float) $value / $conversion;
                                        }

                                        /*
            |--------------------------------------------------------------------------
            | SALE RETURN LIMIT VALIDATION
            |--------------------------------------------------------------------------
            */

                                        if ($saleId) {

                                            $sale = Sale::with([
                                                'items',
                                                'saleReturns.items',
                                            ])->find($saleId);

                                            if ($sale) {

                                                $saleItem = $sale->items
                                                    ->firstWhere('product_id', $productId);

                                                if ($saleItem) {

                                                    $alreadyReturnedQty = $sale->saleReturns
                                                        ->flatMap->items
                                                        ->where('product_id', $productId)
                                                        ->sum('qty'); // assumed stored in base unit


                                                    $remainingQty = $saleItem->qty - $alreadyReturnedQty;

                                                    if ($record?->id) {
                                                        $remainingQty += ($record->qty ?? 0);
                                                    }

                                                    if ($enteredQtyInBase > $remainingQty) {
                                                        $fail("Return quantity cannot exceed remaining quantity of {$remainingQty}.");
                                                        return;
                                                    }
                                                }
                                            }
                                        }
                                    },
                                ];
                            })
                            ->step(1),
                        TextInput::make('rate')
                            ->numeric()
                            ->required()
                            ->currency()
                            ->afterStateUpdatedJs(self::calculateTotals())
                            ->disabled()
                            ->dehydrated()
                            ->minValue(1)
                            ->step(0.01),
                        // Select::make('discount_type')
                        //     ->options(options: [
                        //         DiscountType::FIXED->value => ucfirst(DiscountType::FIXED->value),
                        //         DiscountType::PERCENT->value => ucfirst(DiscountType::PERCENT->value),
                        //     ])
                        //     ->default(DiscountType::FIXED->value)
                        //     ->afterStateUpdatedJs(self::calculateTotals())
                        //     ->required(),
                        // TextInput::make('discount_value')
                        //     ->required()
                        //     ->default(0)
                        //     ->minValue(0)
                        //     ->maxValue(function (Set $set, Get $get) {
                        //         $discountType = $get('discount_type');

                        //         if ($discountType === DiscountType::PERCENT->value) {
                        //             return 100;
                        //         }

                        //         return null;
                        //     })
                        //     ->afterStateUpdatedJs(self::calculateTotals())
                        //     ->numeric(),
                        TextInput::make('total')
                            ->numeric()
                            ->disabled()
                            ->saved()
                            ->dehydrateStateUsing(function ($state, callable $get) {
                                $qty = (float) ($get('qty') ?? 0);
                                $rate = (float) ($get('rate') ?? 0);

                                // $discountType = $get('discount_type') ?? DiscountType::FIXED;
                                // $discountValue = (float) $get('discount_value');

                                // $total = $qty * $rate;

                                // if ($discountType === DiscountType::PERCENT) {
                                //     $total = ($total * $discountValue / 100);
                                // } elseif ($discountType === DiscountType::FIXED) {
                                //     $total -= $discountValue;
                                // }

                                return $qty * $rate;
                            })
                            ->currency(),
                    ]),
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Textarea::make('description')
                            ->nullable()
                            ->default(null)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function calculateTotals(): string
    {
        return <<<'JS'
        const qty  = parseFloat($get('qty')) || 0;
        const rate = parseFloat($get('rate')) || 0;

        const discountType  = $get('discount_type');
        const discountValue = parseFloat($get('discount_value')) || 0;

        let lineTotal = qty * rate;

        if (discountType === 'percent') {
            lineTotal -= (lineTotal * discountValue / 100);
        }

        if (discountType === 'fixed') {
            lineTotal -= discountValue;
        }

        lineTotal = Math.max(lineTotal, 0);

        $set('total', lineTotal);

        // calculate grand total of all items
        const items = $get('../../items') ?? {};
        let grandTotal = Object.values(items).reduce((sum, item) => {
            return sum + (parseFloat(item.total) || 0);
        }, 0);

        // apply invoice-level discount
        const saleDiscountType  = $get('../../discount_type');
        const saleDiscountValue = parseFloat($get('../../discount_value')) || 0;
        const deliveryCharges = parseFloat($get('../../delivery_charges')) || 0;
        const taxCharges = parseFloat($get('../../tax_charges')) || 0;

        let saleGrandTotal = grandTotal;

        if (saleDiscountType === 'percent') {
            saleGrandTotal -= (saleGrandTotal * saleDiscountValue / 100);
        }

        if (saleDiscountType === 'fixed') {
            saleGrandTotal -= saleDiscountValue;
        }

        saleGrandTotal += deliveryCharges;
        saleGrandTotal += taxCharges;

        saleGrandTotal = Math.max(saleGrandTotal, 0);

        // set totals
        $set('../../total', grandTotal);
        $set('../../grand_total', saleGrandTotal);
    JS;
    }

    public static function calculateGrandTotal(): string
    {
        return <<<'JS'
            const saleDiscountType  = $get('discount_type');
            const saleDiscountValue = parseFloat($get('discount_value')) || 0;
            const deliveryCharges = parseFloat($get('delivery_charges')) || 0;
            const taxCharges = parseFloat($get('tax_charges')) || 0;

            const items = $get('items') ?? {};

            let grandTotal = Object.values(items).reduce((sum, item) => {
                return sum + (parseFloat(item.total) || 0);
            }, 0);

            let saleGrandTotal = grandTotal;

            if (saleDiscountType === 'percent') {
                saleGrandTotal -= (saleGrandTotal * saleDiscountValue / 100);
            }

            if (saleDiscountType === 'fixed') {
                saleGrandTotal -= saleDiscountValue;
            }

            saleGrandTotal += deliveryCharges;
            saleGrandTotal += taxCharges;

            saleGrandTotal = Math.max(saleGrandTotal, 0);

            $set('total', grandTotal);
            $set('grand_total', saleGrandTotal);
        JS;
    }

    public static function getDefaultRepeaterData()
    {
        $saleId = request()->query('sale_id');

        $sale = Sale::with([
            'items',
            'saleReturns.items',
        ])->find($saleId);

        if (! $sale) {
            return [];
        }

        return $sale->items
            ->map(function ($item) use ($sale) {

                $alreadyReturnedQty = $sale->saleReturns
                    ->flatMap->items
                    ->where('product_id', $item->product_id)
                    ->sum('qty');

                $remainingQty = $item->qty - $alreadyReturnedQty;

                // Do NOT show fully returned items
                if ($remainingQty <= 0) {
                    return null;
                }

                $productid = $item->product_id;
                $unitId = $item->unit_id;
                $rate = $item->rate;
                $discountType = $item->discount_type;
                $discountValue = $item->discount_value;

                $total = (float) ($remainingQty * $rate);

                if ($item->discount_type === DiscountType::PERCENT->value) {
                    $total -= ($total * $item->discount_value / 100);
                }

                if ($item->discount_type === DiscountType::FIXED->value) {
                    $total -= $item->discount_value;
                }

                return [
                    'product_id' => $productid,
                    'unit_id' => $unitId,
                    // 'qty'        => (float) $remainingQty, // !! important
                    'qty'        => (float) 0, // !! important
                    'rate'       => (float) $rate,
                    'discount_type' => $discountType,
                    'discount_value' => (float) $discountValue,
                    // 'total'      => (float) $total,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
