<?php

namespace App\Filament\Outlet\Resources\Sale\Sales\Schemas;

use App\Filament\Outlet\Resources\Master\Customers\Schemas\CustomerForm;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountAmountInput;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountTypeSelect;
use App\Filament\Outlet\Resources\Sale\Sales\Components\DiscountValueInput;
use App\Filament\Outlet\Resources\Sale\Sales\Components\GrandTotalInput;
use App\Filament\Outlet\Resources\Sale\Sales\Components\SaleItemsRepeater;
use App\Filament\Outlet\Resources\Sale\Sales\Components\TotalAmountInput;
use App\Models\Accounting\CustomerLedger;
use App\Models\Master\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SaleForm
{
    public static function configure(Schema $schema): Schema
    {
        // filament()
        // ->getCurrentPanel()
        // ->maxContentWidth(Width::Full);

        $products = Product::with('unit', 'subUnit', 'customerRates')
            ->withOutletStock()
            ->get(['id', 'name', 'selling_price', 'unit_id', 'sub_unit_id', 'sub_unit_conversion']);

        $productsForState = $products->map(fn($p) => [
            'id'             => $p->id,
            'selling_price'  => $p->selling_price,
            'unit_id'        => $p->unit_id,
            'customer_rates' => $p->customerRates->map(fn($r) => [
                'customer_id'   => $r->customer_id,
                'selling_price' => $r->selling_price,
            ])->toArray(),
        ])->keyBy('id')->toArray();

        $productsKeyedArray = $products->keyBy('id')->toArray();

        return $schema
            ->components([
                Hidden::make('products')
                    ->afterStateHydrated(fn(Set $set) => $set('products', $productsForState))
                    ->dehydrated(false),

                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name)
                            ->manageOptionForm(CustomerForm::configure($schema)->getComponents())
                            ->helperText(function (Get $get) {
                                $customerId = $get('customer_id');
                                if (! $customerId) {
                                    return null;
                                }

                                $balance = CustomerLedger::getBalanceForCustomerId($customerId);

                                return 'Customer balance: ' . currency_format($balance);
                            })
                            ->columnSpanFull()
                            ->required(),
                        Select::make('rider_id')
                            ->relationship('rider', 'name'),
                        // DateTimePicker::make('created_at')->default(now())->required(),
                    ]),

                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([

                        Group::make()
                            ->columnSpanFull()
                            ->columns(4)
                            ->schema([
                                TotalAmountInput::make(),
                                DiscountTypeSelect::make(),
                                DiscountValueInput::make(),
                                DiscountAmountInput::make(),
                            ]),

                        TextInput::make('delivery_charges')
                            ->currency()
                            ->minValue(0)
                            ->rules('min:0')
                            ->calculator()
                            ->afterStateUpdatedJs(self::calculateGrandTotal())
                            ->required(),

                        TextInput::make('tax_charges')
                            ->currency()
                            ->minValue(0)
                            ->rules('min:0')
                            ->calculator()
                            ->afterStateUpdatedJs(self::calculateGrandTotal())
                            ->required(),

                        GrandTotalInput::make(),

                    ]),

                SaleItemsRepeater::make($productsKeyedArray),

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

        let discountAmount = 0;

        if (saleDiscountType === 'percent') {
            discountAmount = (grandTotal * saleDiscountValue / 100);
        } else if (saleDiscountType === 'fixed') {
            discountAmount = saleDiscountValue;
        }

        saleGrandTotal = grandTotal - discountAmount;
        saleGrandTotal += deliveryCharges;
        saleGrandTotal += taxCharges;

        saleGrandTotal = Math.max(saleGrandTotal, 0);

        // set totals
        $set('../../total', grandTotal);
        $set('../../discount_amount', discountAmount);
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

            let discountAmount = 0;

            if (saleDiscountType === 'percent') {
                discountAmount = (grandTotal * saleDiscountValue / 100);
            } else if (saleDiscountType === 'fixed') {
                discountAmount = saleDiscountValue;
            }

            saleGrandTotal = grandTotal - discountAmount;
            saleGrandTotal += deliveryCharges;
            saleGrandTotal += taxCharges;

            saleGrandTotal = Math.max(saleGrandTotal, 0);

            $set('total', grandTotal);
            $set('discount_amount', discountAmount);
            $set('grand_total', saleGrandTotal);
        JS;
    }
}
