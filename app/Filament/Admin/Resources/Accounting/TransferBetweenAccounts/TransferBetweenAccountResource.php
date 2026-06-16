<?php

namespace App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts;

use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Pages\CreateTransferBetweenAccount;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Pages\EditTransferBetweenAccount;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Pages\ListTransferBetweenAccounts;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Pages\ViewTransferBetweenAccount;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Schemas\TransferBetweenAccountForm;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Schemas\TransferBetweenAccountInfolist;
use App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Tables\TransferBetweenAccountsTable;
use App\Models\Accounting\TransferBetweenAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TransferBetweenAccountResource extends Resource
{
    protected static ?string $model = TransferBetweenAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowPath;

    protected static ?string $recordTitleAttribute = 'transfer_number';

    public static function form(Schema $schema): Schema
    {
        return TransferBetweenAccountForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TransferBetweenAccountInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TransferBetweenAccountsTable::configure($table);
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
            'index' => ListTransferBetweenAccounts::route('/'),
            // 'create' => CreateTransferBetweenAccount::route('/create'),
            // 'view' => ViewTransferBetweenAccount::route('/{record}'),
            // 'edit' => EditTransferBetweenAccount::route('/{record}/edit'),
        ];
    }
}
