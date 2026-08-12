<?php

namespace App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Tables;

use App\Filament\Admin\Resources\Accounting\Accounts\AccountResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TransferBetweenAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_number')
                    ->copyable(),
                TextColumn::make('fromAccount.name')
                    ->url(fn($record) => AccountResource::getUrl('index', [
                        'search' => $record->fromAccount->name,
                    ]), true)
                    ->copyable(false),
                TextColumn::make('toAccount.name')
                    ->url(fn($record) => AccountResource::getUrl('index', [
                        'search' => $record->toAccount->name,
                    ]), true)
                    ->copyable(false),
                TextColumn::make('amount')
                    ->currency()
                    ->copyable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->moreFilters([
                // TrashedFilter::make(),
            ], [
                SelectFilter::make('fromAccount')
                    ->relationship('fromAccount', 'name'),
                SelectFilter::make('toAccount')
                    ->relationship('toAccount', 'name'),
            ])
            ->recordActions([
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
