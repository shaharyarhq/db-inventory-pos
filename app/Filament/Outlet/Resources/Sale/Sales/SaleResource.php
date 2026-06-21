<?php

namespace App\Filament\Outlet\Resources\Sale\Sales;

use App\Filament\Outlet\Resources\Sale\Sales\Pages\CreateSale;
use App\Filament\Outlet\Resources\Sale\Sales\Pages\EditSale;
use App\Filament\Outlet\Resources\Sale\Sales\Pages\ListSales;
use App\Filament\Outlet\Resources\Sale\Sales\Pages\ViewSale;
use App\Filament\Outlet\Resources\Sale\Sales\RelationManagers\ReceiptSalesRelationManager;
use App\Filament\Outlet\Resources\Sale\Sales\Schemas\SaleForm;
use App\Filament\Outlet\Resources\Sale\Sales\Schemas\SaleInfolist;
use App\Filament\Outlet\Resources\Sale\Sales\Tables\SalesTable;
use App\Filament\Outlet\Resources\Sale\Sales\Widgets\SalesOverviewWidget;
use App\Models\Sale\Sale;
use App\Support\Traits\HasTimestampColumns;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    // use IgnoresSoftDeleteRouteBinding;

    use HasTimestampColumns;

    protected static ?string $model = Sale::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CurrencyDollar;

    protected static ?string $recordTitleAttribute = 'sale_number';

    public static function form(Schema $schema): Schema
    {
        return SaleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SaleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ReceiptSalesRelationManager::class
        ];
    }

    public static function getWidgets(): array
    {
        return [
            SalesOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSales::route('/'),
            'create' => CreateSale::route('/create'),
            'view'   => ViewSale::route('/{record}'),
            'edit'   => EditSale::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
