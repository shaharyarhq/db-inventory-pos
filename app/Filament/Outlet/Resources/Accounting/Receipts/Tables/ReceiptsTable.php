<?php

namespace App\Filament\Outlet\Resources\Accounting\Receipts\Tables;

use App\Enums\PanelId;
use App\Enums\ReceiptStatus;
use App\Filament\Outlet\Resources\Master\Customers\CustomerResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('receipt_number')
                    ->copyable(),
                TextColumn::make('customer.name')
                    ->url(
                        filament()->auth()->user()->isSuperAdmin() ? fn($state) => CustomerResource::getUrl('index', [
                            'search' => $state
                        ], panel: PanelId::ADMIN->value) : '',
                        true
                    )
                    ->copyable(!filament()->auth()->user()->isSuperAdmin()),
                TextColumn::make('account.name')
                    ->copyable(),
                TextColumn::make('amount')
                    ->sumCurrency()
                    ->currency(),
                SelectColumn::make('status')
                    ->options(ReceiptStatus::class)
                    ->sortable()
                    ->searchable()
                    ->disabled(fn() => !filament()->auth()->user()->can('UpdateStatus:Receipt')),
                TextColumn::make('remarks')
                    ->desc(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->moreFilters([], [
                SelectFilter::make('account')
                    ->relationship('account', 'name'),
                SelectFilter::make('customer')
                    ->relationship('customer', 'name'),
                SelectFilter::make('status')
                    ->options(ReceiptStatus::class)
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
