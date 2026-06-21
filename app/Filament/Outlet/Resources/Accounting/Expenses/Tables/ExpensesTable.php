<?php

namespace App\Filament\Outlet\Resources\Accounting\Expenses\Tables;

use App\Enums\PanelId;
use App\Filament\Admin\Resources\Accounting\Accounts\AccountResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ExpensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('expense_number')
                    ->copyable(),
                TextColumn::make('account.name')
                    ->url(
                        filament()->auth()->user()->isSuperAdmin() ? fn($state) => AccountResource::getUrl('index', [
                            'search' => $state
                        ], panel: PanelId::ADMIN->value) : '',
                        true
                    )
                    ->copyable(!filament()->auth()->user()->isSuperAdmin()),
                TextColumn::make('expenseCategory.name')
                    ->copyable(),
                TextColumn::make('paymentMethod.name')
                    ->copyable(),
                TextColumn::make('amount')
                    ->sumCurrency()
                    ->currency(),
                TextColumn::make('description')
                    ->desc(),
                TextColumn::make('date')
                    ->dateTime(app_date_format())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->moreFilters([
                //
            ], [
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
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
