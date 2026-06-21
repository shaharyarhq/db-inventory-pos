<?php

namespace App\Filament\Admin\Resources\Receipts\Schemas;

use Filament\Schemas\Schema;
use App\Filament\Outlet\Resources\Accounting\Receipts\Schemas\ReceiptForm as OReceiptForm;

class ReceiptInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(OReceiptForm::configure($schema)->getComponents());
    }
}
