<?php

namespace App\Filament\Admin\Resources\Receipts\Pages;

use App\Filament\Admin\Resources\Receipts\ReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;
}
