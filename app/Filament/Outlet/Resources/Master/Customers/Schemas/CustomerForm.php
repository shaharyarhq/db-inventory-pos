<?php

namespace App\Filament\Outlet\Resources\Master\Customers\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Filament\Admin\Resources\Master\Areas\Schemas\AreaForm;
use App\Filament\Admin\Resources\Master\Cities\Schemas\CityForm;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Section::make()
                            ->columnSpan(fn($operation) => $operation === 'edit' ?  2 : 'full')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->unique()
                                    ->columnSpanFull()
                                    ->required(),

                                TextInput::make('contact')
                                    ->nullable()
                                    ->tel(),

                                Select::make('city_id')
                                    ->relationship('city', 'name')
                                    ->manageOptionForm(CityForm::configure($schema)->getComponents())
                                    ->reactive()
                                    ->afterStateUpdated(fn(Set $set) => $set('area_id', null)),

                                Select::make('area_id')
                                    ->relationship(
                                        'area',
                                        'name',
                                        fn($query, Get $get) => $query->where('city_id', $get('city_id'))
                                    )
                                    ->manageOptionForm(AreaForm::configure($schema)->getComponents()),

                                Select::make('referred_by')
                                    ->relationship('referredBy', 'name')
                                    ->nullable(),
                            ]),
                        Section::make()
                            ->columnSpan(1)
                            ->visibleOn('edit')
                            ->schema([
                                // TextInput::make('opening_balance')
                                //     ->numeric()
                                //     ->default(0)
                                //     ->required()
                                //     ->hintIcon(
                                //         'heroicon-m-question-mark-circle',
                                //         tooltip: 'Enter 0 if there is no opening balance'
                                //     ),
                                TextInput::make('current_balance')
                                    ->currency()
                                    ->disabled()
                                    ->visibleOn('edit')
                                // ->dehydrated(false),
                            ]),
                    ]),
                Group::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Section::make()
                            ->columnSpan(2)
                            ->schema([
                                Textarea::make('address')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),
                        Section::make()
                            ->columnSpan(1)
                            ->schema([
                                FileUpload::make('photo')
                                    ->label('Photo')
                                    ->directory('images/customers/photo')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public')
                                    ->deleteUploadedFileUsing(function ($file) {
                                        Storage::disk('public')->delete($file);
                                    })
                                    ->nullable()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->downloadable()
                                    ->columnSpanFull()
                                    ->openable(),
                                // FileUpload::make('attachments')
                                //     ->label('Attachments')
                                //     ->multiple()
                                //     ->reorderable()
                                //     ->removeUploadedFileButtonPosition('right')
                                //     ->directory('images/customer/attachments')
                                //     ->disk('public')
                                //     ->image()
                                //     ->imageEditor()
                                //     ->visibility('public')
                                //     ->deleteUploadedFileUsing(function ($file) {
                                //         Storage::disk('public')->delete($file);
                                //     })
                                //     ->nullable()
                                //     ->downloadable()
                                //     ->columnSpanFull()
                                //     ->openable(),
                            ]),
                    ]),
            ]);
    }
}
