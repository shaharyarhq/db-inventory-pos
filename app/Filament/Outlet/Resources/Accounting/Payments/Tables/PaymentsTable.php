<?php

namespace App\Filament\Outlet\Resources\Accounting\Payments\Tables;

use App\Enums\PanelId;
use App\Filament\Admin\Resources\Master\Suppliers\SupplierResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->copyable(),
                TextColumn::make('supplier.name')
                    ->url(
                        filament()->auth()->user()->isSuperAdmin() ? fn($state) => SupplierResource::getUrl('index', [
                            'search' => $state
                        ], panel: PanelId::ADMIN->value) : '',
                        true
                    )
                    ->copyable(!filament()->auth()->user()->isSuperAdmin()),
                TextColumn::make('account.name')
                    ->copyable(),
                TextColumn::make('amount')
                    ->sumCurrency()
                    ->copyable()
                    ->currency(),
                TextColumn::make('remarks')
                    ->desc(),
                // TextColumn::make('outlet.name')
                //     ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
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
            ->moreFilters([
                // TrashedFilter::make(),
            ], [
                SelectFilter::make('supplier')
                    ->relationship('supplier', 'name'),
                SelectFilter::make('account')
                    ->relationship('account', 'name')
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                // ForceDeleteAction::make(),
                // RestoreAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
