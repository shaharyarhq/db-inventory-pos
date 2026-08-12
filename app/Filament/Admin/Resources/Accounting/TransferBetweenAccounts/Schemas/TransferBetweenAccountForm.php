<?php

namespace App\Filament\Admin\Resources\Accounting\TransferBetweenAccounts\Schemas;

use Closure;
use Filament\Schemas\Schema;
use App\Models\Accounting\Account;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Admin\Resources\Accounting\Accounts\Schemas\AccountForm;

class TransferBetweenAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('from_account_id')
                            ->relationship('fromAccount', 'name')
                            ->manageOptionForm(AccountForm::configure(new Schema())->getComponents())
                            ->required(),
                        Select::make('to_account_id')
                            ->relationship(
                                'toAccount',
                                'name',
                                modifyQueryUsing: function (Get $get, $query) {
                                    return $query->whereNot('id', $get('from_account_id'));
                                }
                            )
                            ->manageOptionForm(AccountForm::configure(new Schema())->getComponents())
                            ->required(),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        TextInput::make('amount')
                            ->required()
                            ->calculator()
                            // ->columnSpanFull()
                            ->rules(function (Get $get) {
                                return [
                                    function ($attribute, $value, Closure $fail) use ($get) {
                                        $fromAccountId = $get('from_account_id');

                                        $account = Account::find($fromAccountId);

                                        $availableBalance = $account->ledgers()->sum('amount');

                                        if ($value > $availableBalance) {
                                            $fail("Amount exceed balance at {$account->name}");
                                        }
                                    },
                                ];
                            })
                            ->currency(),
                    ]),
                Textarea::make('remarks')
                    ->nullable()
                    ->columnSpanFull(),
                FileUpload::make('attachments')
                    ->label('Attachments')
                    ->multiple()
                    ->directory('attachments/deposits')
                    ->disk('public')
                    ->visibility('public')
                    ->deleteUploadedFileUsing(function ($file) {
                        Storage::disk('public')->delete($file);
                    })
                    ->nullable()
                    ->downloadable()
                    ->columnSpanFull()
                    ->openable(),
            ]);
    }
}
