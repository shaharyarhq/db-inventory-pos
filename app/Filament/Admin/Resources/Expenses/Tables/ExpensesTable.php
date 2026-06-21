<?php

namespace App\Filament\Admin\Resources\Expenses\Tables;

use App\Filament\Outlet\Resources\Accounting\Expenses\ExpenseResource;
use App\Filament\Outlet\Resources\Accounting\Expenses\Tables\ExpensesTable as O;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...O::configure($table)->getColumns(),
                TextColumn::make('outlet.name')
                    ->searchable(),
            ])
            ->moreFilters([], [
                SelectFilter::make('expenseCategory')
                    ->relationship('expenseCategory', 'name')
                    ->preload()
                    ->optionsLimit(10)
                    ->searchable(),
                SelectFilter::make('paymentMethod')
                    ->relationship('paymentMethod', 'name')
                    ->preload()
                    ->optionsLimit(10)
                    ->searchable(),
                SelectFilter::make('account')
                    ->relationship('account', 'name')
                    ->preload()
                    ->optionsLimit(10)
                    ->searchable(),
                SelectFilter::make('outlet')
                    ->relationship('outlet', 'name'),
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make()
                    ->url(function (Model $record) {
                        return ExpenseResource::getUrl(
                            'index',
                            panel: 'outlet',
                            tenant: $record->outlet,
                        ) . "?" . http_build_query([
                            'tableAction' => 'edit',             // The name of your table action
                            'tableActionRecord' => $record->id,  // The ID of the record to load into the modal
                        ]);
                    }, true)
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
