<?php

namespace App\Filament\Admin\Resources\Master\Products\Schemas;

use App\Filament\Admin\Resources\Master\Brands\Schemas\BrandForm;
use App\Filament\Admin\Resources\Master\Categories\Schemas\CategoryForm;
use App\Filament\Admin\Resources\Master\Units\Schemas\UnitForm;
use App\Models\Master\Brand;
use App\Models\Master\Category;
use App\Models\Master\Unit;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->contained(false)
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('General')
                            ->schema([
                                Group::make()
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        Section::make()
                                            ->columnSpan(2)
                                            ->columns(2)
                                            ->schema([
                                                TextInput::make('name')
                                                    // ->unique()
                                                    ->columnSpanFull()
                                                    ->required(),
                                                // TextInput::make('code')
                                                //     ->unique()
                                                //     ->suffixAction(Action::make('Generate Code')
                                                //         ->tooltip('Generate a random product code')
                                                //         ->iconButton()
                                                //         ->icon('heroicon-o-arrow-path')
                                                //         ->actionJs(<<<'JS'
                                                //             $set('code',Math.floor(Math.random() * 1000) + 1)
                                                //         JS)
                                                //     )
                                                //     ->nullable(),
                                                TextInput::make('cost_price')
                                                    ->required()
                                                    ->numeric()
                                                    ->currency()
                                                    ->minValue(0.0001),
                                                TextInput::make('selling_price')
                                                    ->required()
                                                    ->numeric()
                                                    ->currency()
                                                    ->minValue(0),
                                                // TagsInput::make('tags')
                                                //     ->placeholder('Tags')
                                                //     ->columnSpanFull(),
                                                Group::make()
                                                    ->columnSpanFull()
                                                    ->columns(2)
                                                    ->schema([
                                                        Select::make('category_id')
                                                            ->relationship('category', 'name')
                                                            ->options(Category::options())
                                                            ->manageOptionForm(CategoryForm::configure($schema)->getComponents())
                                                            ->searchable()
                                                            // ->columnSpanFull()
                                                            ->preload(false)
                                                        // ->required()
                                                        ,
                                                        Select::make('brand_id')
                                                            ->relationship('brand', 'name')
                                                            ->options(Brand::options())
                                                            ->manageOptionForm(BrandForm::configure($schema)->getComponents())
                                                            ->searchable()
                                                            // ->columnSpanFull()
                                                            ->preload(false)
                                                        // ->required()
                                                        ,
                                                    ])
                                            ]),
                                        Section::make()
                                            ->columnSpan(1)
                                            ->schema([
                                                FileUpload::make('thumbnail')
                                                    ->label('Thumbnail Image')
                                                    ->directory('images/products/thumbnails')
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
                                            ]),
                                    ]),
                                Group::make()
                                    ->columnSpanFull()
                                    ->columns(3)
                                    ->schema([
                                        Section::make()
                                            ->columnSpanFull()
                                            ->schema([
                                                Textarea::make('description')
                                                    ->default(null)
                                                    ->columnSpanFull(),
                                                // FileUpload::make('attachments')
                                                //     ->label('Attachments')
                                                //     ->multiple()
                                                //     ->directory('attachments/products')
                                                //     ->disk('public')
                                                //     ->visibility('public')
                                                //     ->deleteUploadedFileUsing(function ($file) {
                                                //         Storage::disk('public')->delete($file);
                                                //     })
                                                //     ->nullable()
                                                //     ->downloadable()
                                                //     ->columnSpanFull()
                                                //     ->openable(),
                                            ]),
                                        // Section::make()
                                        //     ->columnSpan(1)
                                        //     ->schema([
                                        //     FileUpload::make('additional_images')
                                        //         ->label('Additional Images')
                                        //         ->multiple()
                                        //         ->reorderable()
                                        //         ->removeUploadedFileButtonPosition('right')
                                        //         ->directory('images/products/additional-images')
                                        //         ->disk('public')
                                        //         ->image()
                                        //         ->imageEditor()
                                        //         ->visibility('public')
                                        //         ->deleteUploadedFileUsing(function ($file) {
                                        //             Storage::disk('public')->delete($file);
                                        //         })
                                        //         ->nullable()
                                        //         ->downloadable()
                                        //         ->columnSpanFull()
                                        //         ->openable(),
                                    ]),
                            ]),
                        Tab::make('Unit')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('unit_id')
                                            ->relationship('unit', 'name')
                                            ->options(Unit::options())
                                            ->manageOptionForm(UnitForm::configure($schema)->getComponents())
                                            ->required()
                                            ->searchable()
                                            ->columnSpanFull()
                                            ->preload(false)
                                            ->required(),
                                        Select::make('sub_unit_id')
                                            ->relationship('subUnit', 'name')
                                            ->options(Unit::options())
                                            ->manageOptionForm(UnitForm::configure($schema)->getComponents())
                                            ->nullable()
                                            ->searchable()
                                            // ->columnSpanFull()
                                            ->preload(false)
                                        // ->required()
                                        ,
                                        TextInput::make('sub_unit_conversion')
                                            ->numeric()
                                            ->step(1)
                                            ->required(function (Get $get) {
                                                $subUnitId = $get('sub_unit_id');

                                                if (filled($subUnitId)) {
                                                    return true;
                                                }
                                                return false;
                                            })
                                            ->minValue(function (Get $get) {
                                                $subUnitId = $get('sub_unit_id');

                                                if (filled($subUnitId)) {
                                                    return 1;
                                                }
                                                return null;
                                            })
                                            ->default(0)
                                            ->nullable(false),

                                        TextInput::make('sub_unit_selling_price')
                                            ->numeric()
                                            ->step(1)
                                            ->required(function (Get $get) {
                                                $subUnitId = $get('sub_unit_id');

                                                if (filled($subUnitId)) {
                                                    return true;
                                                }
                                                return false;
                                            })
                                            ->minValue(function (Get $get) {
                                                $subUnitId = $get('sub_unit_id');

                                                if (filled($subUnitId)) {
                                                    return 1;
                                                }
                                                return null;
                                            })
                                            ->default(0)
                                            ->nullable(false),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
