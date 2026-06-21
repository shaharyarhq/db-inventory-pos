<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use App\Filament\Outlet\Resources\Sale\Sales\SaleResource;
use App\Filament\Outlet\Resources\Sale\Sales\Tables\SalesTable as S;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...S::configure($table)->getColumns(),
                TextColumn::make('outlet.name')
                    ->searchable(),
            ])
            ->moreFilters([], [
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('customer_referred_by')
                    ->relationship('customer.referredBy', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('area')
                    ->relationship('customer.area', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('city')
                    ->relationship('customer.city', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('product')
                    ->relationship('items.product', 'name')
                    ->preload()
                    ->optionsLimit(10)
                    ->searchable(),
                SelectFilter::make('category')
                    ->relationship('items.product.category', 'name')
                    ->searchable()
                    ->optionsLimit(10)
                    ->preload(),
                SelectFilter::make('brand')
                    ->relationship('items.product.brand', 'name')
                    ->preload()
                    ->searchable()
                    ->optionsLimit(10),
                SelectFilter::make('rider')
                    ->relationship('rider', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                TernaryFilter::make('is_pos')
                    ->label('POS')
                    ->trueLabel('POS Sales Only')
                    ->falseLabel('Normal Sales Only'),
                SelectFilter::make('outlet')
                    ->relationship('outlet', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(function (Model $record) {
                        return SaleResource::getUrl(
                            'index',
                            panel: 'outlet',
                            tenant: $record->outlet,
                        );
                    }, true),
                EditAction::make()
                    ->url(function (Model $record) {
                        return SaleResource::getUrl(
                            'index',
                            panel: 'outlet',
                            tenant: $record->outlet,
                        );
                    }, true)
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
