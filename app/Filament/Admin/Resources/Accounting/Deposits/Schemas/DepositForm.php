<?php

namespace App\Filament\Admin\Resources\Accounting\Deposits\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use App\Filament\Admin\Resources\Accounting\Accounts\Schemas\AccountForm;

class DepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('account_id')
                            ->relationship('account', 'name')
                            ->manageOptionForm(AccountForm::configure($schema)->getComponents())
                            ->required(),
                        DatePicker::make('date')
                            ->required()
                            ->default(now()),
                        TextInput::make('amount')
                            ->required()
                            ->calculator()
                            ->columnSpanFull()
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
