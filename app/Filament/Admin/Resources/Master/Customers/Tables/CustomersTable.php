<?php

namespace App\Filament\Admin\Resources\Master\Customers\Tables;

use App\Filament\Admin\Resources\Master\Customers\Actions\CustomerLedgerExportAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo')
                    ->circular()
                    ->imageSize(80)
                    ->placeholder('---')
                    ->disk('public')
                    ->visibility('public'),
                TextColumn::make('name')
                    ->copyable(),
                TextColumn::make('referredBy.name')
                    ->copyable(),
                TextColumn::make('contact')
                    ->disableNumericFormatting()
                    ->copyable(),
                TextColumn::make('city.name')
                    ->copyable(),
                TextColumn::make('area.name')
                    ->copyable(),
                TextColumn::make('opening_balance')
                    ->currency()
                    ->copyable(),
                TextColumn::make('current_balance')
                    ->currency()
                    ->sumCurrency()
                    ->tooltip(function ($state) {
                        if ($state < 0) {
                            return " Credit";
                        }

                        if ($state > 0) {
                            return " Debit";
                        }

                        return null;
                    })
                    ->searchable(false)
                    ->copyable(),
                TextColumn::make('address')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('business_to_date')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderByRaw("
            (
                SELECT
                    COALESCE((
                        SELECT SUM(si.qty * si.rate)
                        FROM sales s
                        JOIN sale_items si ON si.sale_id = s.id
                        WHERE s.customer_id = customers.id
                    ), 0)
                    -
                    COALESCE((
                        SELECT SUM(sri.qty * sri.rate)
                        FROM sales s
                        JOIN sale_returns sr ON sr.sale_id = s.id
                        JOIN sale_return_items sri ON sri.sale_return_id = sr.id
                        WHERE s.customer_id = customers.id
                    ), 0)
            ) $direction
        ");
                    })
                    ->searchable(false)
                    ->currency(),
                TextColumn::make('last_sale_date')
                    ->searchable(false)
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderByRaw("
            (SELECT MAX(created_at) FROM sales
             WHERE sales.customer_id = customers.id) $direction
        ");
                    })
                    ->searchable(false)
                    ->dateTime(),
                TextColumn::make('last_receipt_date')
                    ->searchable(false)
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderByRaw("
            (SELECT MAX(created_at) FROM receipts
             WHERE receipts.customer_id = customers.id) $direction
        ");
                    })
                    ->searchable(false)
                    ->dateTime(),
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
                SelectFilter::make('city')
                    ->relationship('city', 'name')
                    ->searchable()
                    ->preload(10)
                    ->optionsLimit(10),
                SelectFilter::make('area')
                    ->relationship('area', 'name')
                    ->searchable()
                    ->preload(10)
                    ->optionsLimit(10),
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('delete_opening_balance_entry')
                    ->action(function ($record) {
                        $record->ledger?->delete();
                    })
                    ->visible(fn(Model $record) => $record->ledger)
                    ->color('danger')
                    ->icon(Heroicon::XCircle)
                    ->tooltip('This will delete the opening balance entry for this customer. use if you accidentally create a customer and want to delete it.')
                    ->label('Delete Opening Balance Entry')
                    ->requiresConfirmation(),
                // RestoreAction::make(),
                // ForceDeleteAction::make(),
                CustomerLedgerExportAction::make(),

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
