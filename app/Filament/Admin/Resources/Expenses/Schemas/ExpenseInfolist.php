<?php

namespace App\Filament\Admin\Resources\Expenses\Schemas;

use App\Filament\Outlet\Resources\Accounting\Expenses\Schemas\ExpenseForm;
use Filament\Schemas\Schema;

class ExpenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components(ExpenseForm::configure($schema)->getComponents());
    }
}
