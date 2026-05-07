<?php

namespace App\Filament\Outlet\Resources\Accounting\Expenses\Schemas;

use App\Models\Accounting\AccountLedger;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Group::make()
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                Select::make('account_id')
                                    ->relationship('account', 'name')
                                    ->live()
                                    ->partiallyRenderComponentsAfterStateUpdated(['amount'])
                                    ->required(),
                                Select::make('expense_category_id')
                                    ->relationship('expenseCategory', 'name')
                                    ->manageOptionForm([
                                        Section::make()
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->required(),
                                Select::make('payment_method_id')
                                    ->relationship('paymentMethod', 'name')
                                    ->manageOptionForm([
                                        Section::make()
                                            ->columnSpanFull()
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    ->required()
                                                    ->columnSpanFull(),
                                            ]),
                                    ])
                                    ->nullable(),
                                DatePicker::make('date')
                                    ->nullable(),
                            ]),
                        TextInput::make('amount')
                            ->columnSpanFull()
                            ->required()
                            ->helperText(function (Get $get) {
                                $accountId = $get('account_id');
                                if (! $accountId) {
                                    return;
                                }
                                $balance = AccountLedger::getBalanceForAccountId($accountId);

                                return 'Account balance: '.currency_format($balance);
                            })
                            ->rules(fn (Get $get, ?Model $record) => [
                                'min:0',
                                function (string $attribute, $value, Closure $fail) use ($get, $record) {
                                    $accountId = $get('account_id');

                                    if ($accountId) {
                                        $accountBalance = AccountLedger::getBalanceForAccountId($accountId);

                                        if ($record) {
                                            $accountBalance += $record->amount;
                                        }

                                        if ($value > $accountBalance) {
                                            $fail('Insufficient account balance to make this expense.');
                                        }
                                    }
                                },
                            ])
                            ->calculator()
                            ->currency(),
                        Textarea::make('description')
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
