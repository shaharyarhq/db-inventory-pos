<?php

namespace App\Filament\Admin\Resources\Master\Customers\Schemas;

use App\Filament\Admin\Resources\Master\Areas\Schemas\AreaForm;
use App\Filament\Admin\Resources\Master\Cities\Schemas\CityForm;
use App\Models\Master\Area;
use App\Support\Components\StatusToggleButtons;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

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
                            ->columnSpan(2)
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->unique()
                                    ->required(),

                                Select::make('referred_by')
                                    ->relationship('referredBy', 'name')
                                    ->nullable(),

                                TextInput::make('contact')
                                    ->nullable()
                                    ->tel(),

                                Fieldset::make('Location')
                                    ->schema([
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
                                            ->manageOptionForm(AreaForm::configure($schema)->getComponents())
                                            ->createOptionUsing(function ($data) {
                                                Area::create($data);
                                            }),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),
                            ]),
                        Section::make()
                            ->columnSpan(1)
                            ->schema([
                                // StatusToggleButtons::make(),
                                TextInput::make('opening_balance')
                                    ->calculator()
                                    ->currency()
                                    ->required()
                                    ->hintIcon(
                                        'heroicon-m-question-mark-circle',
                                        tooltip: 'Enter 0 if there is no opening balance'
                                    ),
                                TextInput::make('current_balance')
                                    ->currency()
                                    ->disabled()
                                    ->visibleOn('edit')
                                    ->dehydrated(false),
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
