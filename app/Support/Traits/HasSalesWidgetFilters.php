<?php

namespace App\Support\Traits;

use App\Enums\ReceiptStatus;
use App\Models\Accounting\Expense;
use App\Models\Accounting\Receipt;
use App\Models\Inventory\InventoryLedger;
use App\Models\Sale\Sale;
use App\Models\Sale\SaleItem;
use App\Models\Sale\SaleReturn;
use App\Models\Sale\SaleReturnItem;
use Illuminate\Database\Eloquent\Builder;

trait HasSalesWidgetFilters
{
    use DefaultPageFIlters;

    protected function getCustomerId(): ?int
    {
        return isset($this->pageFilters['customerId'])
            ? (int) $this->pageFilters['customerId']
            : null;
    }

    protected function getAreaId(): ?int
    {
        return isset($this->pageFilters['areaId'])
            ? (int) $this->pageFilters['areaId']
            : null;
    }

    protected function getCityId(): ?int
    {
        return isset($this->pageFilters['cityId'])
            ? (int) $this->pageFilters['cityId']
            : null;
    }

    protected function getProductId(): ?int
    {
        return isset($this->pageFilters['productId'])
            ? (int) $this->pageFilters['productId']
            : null;
    }

    protected function getCategoryId(): ?int
    {
        return isset($this->pageFilters['categoryId'])
            ? (int) $this->pageFilters['categoryId']
            : null;
    }

    protected function getBrandId(): ?int
    {
        return isset($this->pageFilters['brandId'])
            ? (int) $this->pageFilters['brandId']
            : null;
    }

    protected function applyFilters(Builder $query): Builder
    {
        if ($this->getOutletId()) {
            $query->where('outlet_id', $this->getOutletId());
        }

        if ($this->getStartDate() && $this->getEndDate()) {
            $query->whereBetween('created_at', [
                $this->getStartDate(),
                $this->getEndDate(),
            ]);
        }

        // if ($this->getCustomerId()) {
        //     // Check if this is a SaleReturn query
        //     if ($query->getModel() instanceof SaleReturn) {
        //         $query->whereHas('sale', function ($q) {
        //             $q->where('customer_id', $this->getCustomerId());
        //         });
        //     } else {
        //         $query->where('customer_id', $this->getCustomerId());
        //     }
        // }

        // SaleReturn doesn't have customer_id directly — goes through sale
        if ($query->getModel() instanceof SaleReturn) {
            if ($this->getCustomerId()) {
                $query->whereHas('sale', fn($q) => $q->where('customer_id', $this->getCustomerId()));
            }

            if ($this->getAreaId()) {
                $query->whereHas('sale.customer', fn($q) => $q->where('area_id', $this->getAreaId()));
            }

            if ($this->getCityId()) {
                $query->whereHas('sale.customer', fn($q) => $q->where('city_id', $this->getCityId()));
            }
        } else {
            $this->applyCustomerFilters($query);
        }

        return $query;
    }

    protected function applyCustomerFilters(Builder $query, string $customerRelation = 'customer'): Builder
    {
        if ($this->getCustomerId()) {
            $query->where('customer_id', $this->getCustomerId());
        }

        if ($this->getAreaId()) {
            $query->whereHas($customerRelation, fn($q) => $q->where('area_id', $this->getAreaId()));
        }

        if ($this->getCityId()) {
            $query->whereHas($customerRelation, fn($q) => $q->where('city_id', $this->getCityId()));
        }

        return $query;
    }

    protected function applyLedgerFilters(Builder $query, ?array $classes = null): Builder
    {
        $classes = $classes ?? [SaleItem::class, SaleReturnItem::class];

        $query->whereIn('source_type', $classes);

        if ($this->getOutletId()) {
            $query->where('outlet_id', $this->getOutletId());
        }

        if ($this->getStartDate() && $this->getEndDate()) {
            $query->whereBetween('created_at', [
                $this->getStartDate(),
                $this->getEndDate(),
            ]);
        }

        // if ($this->getCustomerId()) {
        //     $query->where(function ($query) use ($classes) {
        //         $hasSale = in_array(SaleItem::class, $classes);
        //         $hasReturn = in_array(SaleReturnItem::class, $classes);

        //         if ($hasSale) {
        //             $query->whereHasMorph(
        //                 'source',
        //                 SaleItem::class,
        //                 fn($q) => $q->whereHas(
        //                     'sale',
        //                     fn($q) => $q->where('customer_id', $this->getCustomerId())
        //                 )
        //             );
        //         }

        //         if ($hasReturn) {
        //             $method = $hasSale ? 'orWhereHasMorph' : 'whereHasMorph';
        //             $query->$method(
        //                 'source',
        //                 SaleReturnItem::class,
        //                 fn($q) => $q->whereHas(
        //                     'saleReturn',
        //                     fn($q) => $q->whereHas(
        //                         'sale',
        //                         fn($q) => $q->where('customer_id', $this->getCustomerId())
        //                     )
        //                 )
        //             );
        //         }
        //     });
        // }


        $hasCustomerFilter = $this->getCustomerId() || $this->getAreaId() || $this->getCityId();

        if ($hasCustomerFilter) {
            $hasSale   = in_array(SaleItem::class, $classes);
            $hasReturn = in_array(SaleReturnItem::class, $classes);

            $query->where(function ($query) use ($hasSale, $hasReturn) {
                if ($hasSale) {
                    $query->whereHasMorph(
                        'source',
                        SaleItem::class,
                        fn($q) => $q->whereHas('sale', fn($q) => $this->applyCustomerFilters($q))
                    );
                }

                if ($hasReturn) {
                    $method = $hasSale ? 'orWhereHasMorph' : 'whereHasMorph';
                    $query->$method(
                        'source',
                        SaleReturnItem::class,
                        fn($q) => $q->whereHas('saleReturn.sale', fn($q) => $this->applyCustomerFilters($q))
                    );
                }
            });
        }

        return $query;
    }

    protected function getFilteredReceiptsQuery(): Builder
    {
        $query = Receipt::query()->where('status', ReceiptStatus::APPROVED);

        if ($this->getOutletId()) {
            $query->where('outlet_id', $this->getOutletId());
        }

        if ($this->getStartDate() && $this->getEndDate()) {
            $query->whereBetween('created_at', [
                $this->getStartDate(),
                $this->getEndDate(),
            ]);
        }

        // Receipt has customer_id directly, so applyCustomerFilters works as-is
        $this->applyCustomerFilters($query);

        return $query;
    }

    protected function getFilteredExpensesQuery(): Builder
    {
        $query = Expense::query();

        if ($this->getOutletId()) {
            $query->where('outlet_id', $this->getOutletId());
        }

        // if ($this->getStartDate() && $this->getEndDate()) {
        //     $query->whereBetween('created_at', [
        //         $this->getStartDate(),
        //         $this->getEndDate(),
        //     ]);
        // }

            if ($this->getStartDate() && $this->getEndDate()) {
            $query->whereBetween('date', [
                $this->getStartDate(),
                $this->getEndDate(),
            ]);
        }

        return $query;
    }

    protected function getFilteredSalesQuery(): Builder
    {
        return $this->applyFilters(Sale::query());
    }

    protected function getFilteredSalesReturnQuery(): Builder
    {
        return $this->applyFilters(SaleReturn::query());
    }

    protected function getFilteredInventoryQuery(): Builder
    {
        return $this->applyLedgerFilters(InventoryLedger::query());
    }

    protected function getFilteredInventoryQueryForSale(): Builder
    {
        return $this->applyLedgerFilters(InventoryLedger::query(), [SaleItem::class]);
    }

    protected function getFilteredInventoryQueryForSaleReturn(): Builder
    {
        return $this->applyLedgerFilters(InventoryLedger::query(), [SaleReturnItem::class]);
    }
}
