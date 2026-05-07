<?php

namespace App\Filament\Outlet\Resources\Sale\Sales\Widgets;

use App\Enums\ReceiptStatus;
use App\Models\Accounting\Receipt;
use App\Models\Sale\SaleItem;
use App\Models\Sale\SaleReturnItem;
use App\Support\Traits\HasSalesWidgetFilters;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class OutletSaleStats extends StatsOverviewWidget
{
    use HasWidgetShield, InteractsWithPageFilters, HasSalesWidgetFilters;

    protected static bool $isLazy = true;
    public ?string $pollingInterval = null;
    protected int | string | array $columnSpan = 2;
    protected int | array | null $columns = 4;

    protected ?object $salesAggregates = null;
    protected ?object $salesReturnsAggregates = null;
    protected ?object $salesItemAggregates = null;
    protected ?object $salesReturnItemAggregates = null;
    protected ?object $topSellingProductData = null;
    protected ?object $mostSoldProductData = null;
    protected ?object $mostReturnedProductData = null;
    protected ?object $receiptAggregates = null;
    protected ?object $expenseAggregates = null;

    // ═══════════════════════════════════════════
    // Product Filter Helpers
    // ═══════════════════════════════════════════

    protected function isProductFiltered(): bool
    {
        return (bool) ($this->getProductId() || $this->getCategoryId() || $this->getBrandId());
    }

    protected function applyProductFilter(mixed $query): mixed
    {
        return $query
            ->when($this->getProductId(), fn($q) => $q->where('sale_items.product_id', $this->getProductId()))
            ->when($this->getCategoryId(), fn($q) => $q->whereHas('product', fn($q) => $q->where('category_id', $this->getCategoryId())))
            ->when($this->getBrandId(), fn($q) => $q->whereHas('product', fn($q) => $q->where('brand_id', $this->getBrandId())));
    }

    protected function applyReturnProductFilter(mixed $query): mixed
    {
        return $query
            ->when($this->getProductId(), fn($q) => $q->where('sale_return_items.product_id', $this->getProductId()))
            ->when($this->getCategoryId(), fn($q) => $q->whereHas('product', fn($q) => $q->where('category_id', $this->getCategoryId())))
            ->when($this->getBrandId(), fn($q) => $q->whereHas('product', fn($q) => $q->where('brand_id', $this->getBrandId())));
    }

    // ═══════════════════════════════════════════
    // Aggregates
    // ═══════════════════════════════════════════

    protected function getSalesAggregates(): object
    {
        if ($this->salesAggregates) {
            return $this->salesAggregates;
        }

        return $this->salesAggregates = $this->getFilteredSalesQuery()
            ->selectRaw('
                COUNT(*) as total_count,
                COALESCE(SUM(total), 0) as total_amount,
                COALESCE(SUM(grand_total), 0) as grand_total_amount,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COALESCE(SUM(delivery_charges), 0) as total_delivery,
                COALESCE(SUM(tax_charges), 0) as total_tax
            ')
            ->first();
    }

    protected function getSalesReturnAggregates(): object
    {
        if ($this->salesReturnsAggregates) {
            return $this->salesReturnsAggregates;
        }

        return $this->salesReturnsAggregates = $this->getFilteredSalesReturnQuery()
            ->selectRaw('
                COALESCE(SUM(grand_total), 0) as grand_total_amount,
                COALESCE(SUM(delivery_charges), 0) as total_delivery,
                COALESCE(SUM(discount_amount), 0) as total_discount,
                COALESCE(SUM(tax_charges), 0) as total_tax,
                COALESCE(COUNT(*), 0) as total_count
            ')
            ->first();
    }

    protected function getSalesItemAggregates(): object
    {
        if ($this->salesItemAggregates) {
            return $this->salesItemAggregates;
        }

        $query = SaleItem::query()
            ->joinSub(
                $this->getFilteredSalesQuery()->select('id'),
                'filtered_sales',
                fn($join) => $join->on('sale_items.sale_id', '=', 'filtered_sales.id')
            );

        $query = $this->applyProductFilter($query);

        return $this->salesItemAggregates = $query->selectRaw('
                COUNT(DISTINCT sale_items.sale_id) as total_count,
                COALESCE(SUM(sale_items.qty), 0) as total_qty,
                COALESCE(SUM(sale_items.cost * sale_items.qty), 0) as total_cost,
                COALESCE(SUM(sale_items.rate * sale_items.qty), 0) as total_price
            ')
            ->first();
    }

    protected function getSalesReturnItemAggregates(): object
    {
        if ($this->salesReturnItemAggregates) {
            return $this->salesReturnItemAggregates;
        }

        $query = SaleReturnItem::query()
            ->joinSub(
                $this->getFilteredSalesReturnQuery()->select('id'),
                'filtered_sale_returns',
                fn($join) => $join->on('sale_return_items.sale_return_id', '=', 'filtered_sale_returns.id')
            );

        $query = $this->applyReturnProductFilter($query);

        return $this->salesReturnItemAggregates = $query->selectRaw('
                COUNT(DISTINCT sale_return_items.sale_return_id) as total_count,
                COALESCE(SUM(sale_return_items.qty), 0) as total_qty,
                COALESCE(SUM(sale_return_items.cost * sale_return_items.qty), 0) as total_cost,
                COALESCE(SUM(sale_return_items.rate * sale_return_items.qty), 0) as total_price
            ')
            ->first();
    }

    protected function getReceiptAggregates(): object
    {
        if ($this->receiptAggregates) {
            return $this->receiptAggregates;
        }

        return $this->receiptAggregates = Receipt::query()
            ->where('status', ReceiptStatus::APPROVED)
            ->whereHas('receiptSales', fn($q) => $q->whereIn('sale_id', $this->getFilteredSalesQuery()->select('id')))
            ->selectRaw('COALESCE(SUM(amount), 0) as total_received')
            ->first();
    }


    protected function getExpenseAggregates(): object
    {
        if ($this->expenseAggregates) {
            return $this->expenseAggregates;
        }

        return $this->expenseAggregates = $this->getFilteredExpensesQuery()
            ->selectRaw('COALESCE(SUM(amount), 0) as total_expenses')
            ->first();
    }

    // ═══════════════════════════════════════════
    // Inventory / Product Data
    // ═══════════════════════════════════════════

    protected function buildInventoryQuery(Builder $query): mixed
    {
        return $query
            ->with(['product' => fn($q) => $q->with(['category', 'brand', 'unit'])])
            ->select(['product_id'])
            ->selectRaw('ABS(SUM(qty)) as qty_sold')
            ->groupBy('product_id')
            ->orderByDesc('qty_sold')
            ->get();
    }

    protected function getTopSellingProductsData(): mixed
    {
        return $this->topSellingProductData ??= $this->buildInventoryQuery($this->getFilteredInventoryQuery());
    }

    protected function mostSoldProductsData(): mixed
    {
        return $this->mostSoldProductData ??= $this->buildInventoryQuery($this->getFilteredInventoryQueryForSale());
    }

    protected function mostReturnedProductsData(): mixed
    {
        return $this->mostReturnedProductData ??= $this->buildInventoryQuery($this->getFilteredInventoryQueryForSaleReturn());
    }

    // ═══════════════════════════════════════════
    // Accessors — branch on isProductFiltered()
    // ═══════════════════════════════════════════

    protected function getSalesCount(): int
    {
        return $this->isProductFiltered()
            ? (int) $this->getSalesItemAggregates()->total_count
            : (int) $this->getSalesAggregates()->total_count;
    }

    protected function getSalesReturnCount(): int
    {
        return $this->isProductFiltered()
            ? (int) $this->getSalesReturnItemAggregates()->total_count
            : (int) $this->getSalesReturnAggregates()->total_count;
    }

    protected function getSalesGrandTotalAmount(): float
    {
        return (float) $this->getSalesAggregates()->grand_total_amount;
    }

    protected function getSalesReturnGrandTotalAmount(): float
    {
        return (float) $this->getSalesReturnAggregates()->grand_total_amount;
    }

    protected function getSalesDiscount(): float
    {
        return (float) $this->getSalesAggregates()->total_discount - $this->getSalesReturnsDiscount();
    }

    protected function getSalesReturnsDiscount(): float
    {
        return (float) $this->getSalesReturnAggregates()->total_discount;
    }

    protected function getSalesDeliveryCharges(): float
    {
        return (float) $this->getSalesAggregates()->total_delivery - $this->getSalesReturnsDeliveryCharges();
    }

    protected function getSalesReturnsDeliveryCharges(): float
    {
        return (float) $this->getSalesReturnAggregates()->total_delivery;
    }

    protected function getSalesTaxCharges(): float
    {
        return (float) $this->getSalesAggregates()->total_tax - $this->getSalesReturnsTaxCharges();
    }

    protected function getSalesReturnsTaxCharges(): float
    {
        return (float) $this->getSalesReturnAggregates()->total_tax;
    }

    public function getSalesCost(): float
    {
        return (float) ($this->getSalesItemAggregates()->total_cost - $this->getSalesReturnItemAggregates()->total_cost);
    }

    public function getSalesPrice(): float
    {
        return (float) ($this->getSalesItemAggregates()->total_price - $this->getSalesReturnItemAggregates()->total_price);
    }

    public function getSalesGrossProfit(): float
    {
        return $this->getSalesPrice() - $this->getSalesCost();
    }

    public function getSalesNetProfit(): float
    {
        return $this->getSalesGrossProfit() - $this->getSalesDiscount();
    }

    public function getTotalExpenses(): float
    {
        return (float) $this->getExpenseAggregates()->total_expenses;
    }

    public function getSalesNetProfitAfterExpenses(): float
    {
        return $this->getSalesNetProfit() - $this->getTotalExpenses();
    }

    public function getTotalReceived(): float
    {
        return (float) $this->getReceiptAggregates()->total_received;
    }

    public function getOutstandingBalance(): float
    {
        return ($this->getSalesGrandTotalAmount() - $this->getSalesReturnGrandTotalAmount()) - $this->getTotalReceived();
    }

    public function getTotalQtySold(): float
    {
        return (float) $this->getSalesItemAggregates()->total_qty - (float) $this->getSalesReturnItemAggregates()->total_qty;
    }

    // ═══════════════════════════════════════════
    // Stats
    // ═══════════════════════════════════════════

    protected function getStats(): array
    {
        $productFiltered = $this->isProductFiltered();

        // Item-level stats — always accurate whether filtered or not
        $itemStats = [
            Stat::make('Total Cost of Products Sold (COGS)', currency_format($this->getSalesCost()))
                ->icon(Heroicon::ArchiveBox)
                ->color('warning')
                ->description('Across all sales'),

            Stat::make('Total Value of Products Sold', currency_format($this->getSalesPrice()))
                ->icon(Heroicon::Banknotes)
                ->color('primary')
                ->description('Across all sales'),

            Stat::make('Gross Profit From Sales', currency_format($this->getSalesGrossProfit()))
                ->icon(Heroicon::Banknotes)
                ->color('success')
                ->descriptionIcon(Heroicon::InformationCircle)
                ->description('Selling Price - Cost Price'),
        ];

        // Sale-header stats — only meaningful without product filter
        $headerStats = [
            Stat::make('Total Sales Amount', currency_format($this->getSalesGrandTotalAmount()))
                ->icon(Heroicon::CurrencyDollar)
                ->color('success')
                ->description('Grand total of all sales'),

            Stat::make('Total Sale Returns Amount', currency_format($this->getSalesReturnGrandTotalAmount()))
                ->icon(Heroicon::ArrowUturnRight)
                ->color('success')
                ->description('Total sales returned'),

            Stat::make('Total Receivable', currency_format($this->getSalesGrandTotalAmount() - $this->getSalesReturnGrandTotalAmount()))
                ->icon(Heroicon::CurrencyDollar)
                ->color('success')
                ->description('Sales - Returns'),

            Stat::make('Total Received', currency_format($this->getTotalReceived()))
                ->icon(Heroicon::Banknotes)
                ->color('success')
                ->description('Payments collected from customers'),

            Stat::make('Outstanding Balance', currency_format($this->getOutstandingBalance()))
                ->icon(Heroicon::ExclamationCircle)
                ->color($this->getOutstandingBalance() > 0 ? 'danger' : 'success')
                ->description('Receivable - Received'),

            Stat::make('Total Discount', currency_format($this->getSalesDiscount()))
                ->icon(Heroicon::Tag)
                ->color('danger')
                ->description('Discounts applied to sales'),

            Stat::make('Delivery Charges', currency_format($this->getSalesDeliveryCharges()))
                ->icon(Heroicon::Document)
                ->color('info')
                ->description('Delivery charges for sales'),

            Stat::make('Tax Charges', currency_format($this->getSalesTaxCharges()))
                ->icon(Heroicon::DocumentText)
                ->color('warning')
                ->description('Tax applied on sales'),

            Stat::make('Net Profit From Sales', currency_format($this->getSalesNetProfit()))
                ->icon(Heroicon::Banknotes)
                ->color('success')
                ->descriptionIcon(Heroicon::InformationCircle)
                ->description('(Selling Price - Cost Price) - Discounts'),

            Stat::make('Total Expenses', currency_format($this->getTotalExpenses()))
                ->icon(Heroicon::MinusCircle)
                ->color('danger')
                ->description('Expenses in this period'),

            Stat::make('Net Profit (After Expenses)', currency_format($this->getSalesNetProfitAfterExpenses()))
                ->icon(Heroicon::Banknotes)
                ->color($this->getSalesNetProfitAfterExpenses() > 0 ? 'success' : 'danger')
                ->descriptionIcon(Heroicon::InformationCircle)
                ->description('Net Profit - Expenses'),
        ];

        $countDescription = $productFiltered ? 'Orders containing this product' : 'Number of sales';
        $returnCountDescription = $productFiltered ? 'Return orders containing this product' : 'Number of sale returns';

        return array_filter([
            Stat::make('Total Sales', $this->getSalesCount())
                ->icon(Heroicon::ShoppingCart)
                ->color('primary')
                ->description($countDescription),

            Stat::make('Total Units Sold', number_format($this->getTotalQtySold()))
                ->icon(Heroicon::RectangleStack)
                ->color('primary')
                ->description($productFiltered ? 'Units of this product sold (net of returns)' : 'Total units sold across all products (net of returns)'),

            Stat::make('Total Sales Returns', $this->getSalesReturnCount())
                ->icon(Heroicon::ShoppingCart)
                ->color('primary')
                ->description($returnCountDescription),

            // Spread header stats only when not product filtered
            ...($productFiltered ? [] : $headerStats),

            // Item stats always shown
            ...$itemStats,

            // Best/most sold only shown when not filtering to a single product
            ...($this->getProductId() ? [] : [
                Stat::make('Best Selling Product', $this->getTopSellingProductsData()->first()?->product?->name)
                    ->description("Net qty: {$this->getTopSellingProductsData()->first()?->qty_sold} {$this->getTopSellingProductsData()->first()?->product?->unit?->symbol}"),

                Stat::make('Most Returned Product', $this->mostReturnedProductsData()->first()?->product?->name)
                    ->description("Total returned: {$this->mostReturnedProductsData()->first()?->qty_sold} {$this->mostReturnedProductsData()->first()?->product?->unit?->symbol}"),
            ]),
        ]);
    }
}
