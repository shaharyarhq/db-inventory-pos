<?php

namespace App\Filament\Admin\Resources\Accounting\Deposits\Tables;

use App\Filament\Admin\Resources\Accounting\Accounts\AccountResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DepositsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('deposit_number')
                    ->copyable(),
                TextColumn::make('account.name')
                    ->url(fn($record) => AccountResource::getUrl('index', [
                        'search' => $record->account->name,
                    ]), true)
                    ->copyable(false),
                TextColumn::make('amount')
                    ->currency()
                    ->copyable(),
                TextColumn::make('date')
                    ->dateTime()
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
                SelectFilter::make('account')
                    ->relationship('account', 'name'),
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                // ForceDeleteAction::make(),
                // RestoreAction::make(),
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
