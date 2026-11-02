<?php

namespace App\Filament\Outlet\Resources\Master\Products\Tables;

use App\Exports\InventoryLedgerExport;
use App\Models\Master\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Facades\Filament;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('thumbnail')
                    ->circular()
                    ->imageSize(80)
                    ->placeholder('---')
                    ->disk('public')
                    ->visibility('public'),
                TextColumn::make('name')
                    ->extraAttributes(['data-disable-numeric' => true])
                    ->copyable(),
                // TextColumn::make('code')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->copyable(),
                TextColumn::make('category.name')
                    ->copyable(),
                TextColumn::make('brand.name')
                    // ->visible(fn($record) => dd($record))
                    ->copyable(),
                TextColumn::make('unit.name')
                    ->copyable(),
                TextColumn::make('cost_price')
                    ->prefix(app_currency_symbol())
                    ->visible(fn() => filament()->auth()->user()->can('ViewFinancials:Product'))
                    ->copyable(),
                TextColumn::make('selling_price')
                    ->prefix(app_currency_symbol())
                    ->copyable(),
                TextColumn::make('current_outlet_stock')
                    ->searchable(false)
                    ->quantity()
                    ->suffix(fn($record) => ' ' . ($record->unit?->symbol ?? '')),
                TextColumn::make('current_outlet_stock_value')
                    ->searchable(false)
                    ->visible(fn() => filament()->auth()->user()->can('ViewFinancials:Product'))
                    // ->visible(fn(Model $record) => filament()->auth()->user()->can('ViewFinancials', $record))
                    ->currency()
                    ->sumCurrency(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->moreFilters([
                // TrashedFilter::make(),
            ], [
                SelectFilter::make('category')
                    ->relationship('category', 'name'),
                SelectFilter::make('brand')
                    ->relationship('brand', 'name'),
                SelectFilter::make('unit')
                    ->relationship('unit', 'name'),
            ])
            ->recordActions([
                // ViewAction::make(),
                // EditAction::make(),
                Action::make('export_ledger')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->action(function ($record) {
                        $tenantName = Filament::getTenant()->name;
                        $fileName = 'inventory_ledger_' . $record->name . '-' . $tenantName . '.xlsx';
                        dd($record);
                        return Excel::download(new InventoryLedgerExport(
                            filteredTableQuery: null,
                            recordId: $record->id,
                            outletId: Filament::getTenant()->id,
                        ), $fileName);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
