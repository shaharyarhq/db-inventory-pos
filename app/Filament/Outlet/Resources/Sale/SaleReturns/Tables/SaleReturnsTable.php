<?php

namespace App\Filament\Outlet\Resources\Sale\SaleReturns\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SaleReturnsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('return_number')
                    ->searchable(),
                TextColumn::make('sale.sale_number')
                    ->searchable(),
                TextColumn::make('total')
                    ->numeric()
                    ->currency()
                    ->sumCurrency()
                    ->sortable(),
                TextColumn::make('discount_type')
                    ->badge(),
                TextColumn::make('discount_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('grand_total')
                    ->currency()
                    ->sumCurrency()
                    ->numeric()
                    ->sortable(),
                // TextColumn::make('outlet.name')
                // ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->moreFilters([], [
                SelectFilter::make('customer')
                    ->relationship('sale.customer', 'name'),
                SelectFilter::make('product')
                    ->relationship('items.product', 'name'),
                SelectFilter::make('category')
                    ->relationship('items.product.category', 'name'),
                SelectFilter::make('category')
                    ->relationship('items.product.brand', 'name'),
                SelectFilter::make('sale')
                    ->relationship('sale', 'sale_number'),
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
