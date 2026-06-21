<?php

namespace App\Filament\Admin\Resources\Receipts;

use App\Filament\Admin\Resources\Receipts\Pages\CreateReceipt;
use App\Filament\Admin\Resources\Receipts\Pages\EditReceipt;
use App\Filament\Admin\Resources\Receipts\Pages\ListReceipts;
use App\Filament\Admin\Resources\Receipts\Pages\ViewReceipt;
use App\Filament\Admin\Resources\Receipts\Schemas\ReceiptForm;
use App\Filament\Admin\Resources\Receipts\Schemas\ReceiptInfolist;
use App\Filament\Admin\Resources\Receipts\Tables\ReceiptsTable;
use App\Models\Accounting\Receipt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReceiptResource extends Resource
{
    protected static ?string $model = Receipt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Document;

    protected static ?string $recordTitleAttribute = 'receipt_number';

    public static function form(Schema $schema): Schema
    {
        return ReceiptForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReceiptInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReceiptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReceipts::route('/'),
            // 'create' => CreateReceipt::route('/create'),
            // 'view' => ViewReceipt::route('/{record}'),
            // 'edit' => EditReceipt::route('/{record}/edit'),
        ];
    }
}
