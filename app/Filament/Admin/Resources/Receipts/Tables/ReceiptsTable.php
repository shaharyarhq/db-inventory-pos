<?php

namespace App\Filament\Admin\Resources\Receipts\Tables;

use App\Filament\Outlet\Resources\Accounting\Receipts\ReceiptResource;
use App\Filament\Outlet\Resources\Accounting\Receipts\Tables\ReceiptsTable as O;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReceiptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...O::configure($table)->getColumns(),
                TextColumn::make('outlet.name')
                    ->searchable(),
            ])
            ->moreFilters([
                ...O::configure($table)->getFilters(),
                SelectFilter::make('outlet')
                    ->relationship('outlet', 'name'),
            ])
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make()
                    ->url(function (Model $record) {
                        return ReceiptResource::getUrl(
                            'edit',
                            ['record' => $record],
                            panel: 'outlet',      // <-- force the correct panel
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
