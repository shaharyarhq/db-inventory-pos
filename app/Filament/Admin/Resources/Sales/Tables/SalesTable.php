<?php

namespace App\Filament\Admin\Resources\Sales\Tables;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
use App\Support\Actions\PdfDownloadAction;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Outlet\Resources\Sale\Sales\SaleResource;
use App\Filament\Outlet\Resources\Sale\Sales\Tables\SalesTable as S;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...S::configure($table)->getColumns(),
                TextColumn::make('outlet.name')
                    ->searchable(),
            ])
            ->moreFilters([], [
                SelectFilter::make('customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('customer_referred_by')
                    ->relationship('customer.referredBy', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('area')
                    ->relationship('customer.area', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('city')
                    ->relationship('customer.city', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                SelectFilter::make('product')
                    ->relationship('items.product', 'name')
                    ->preload()
                    ->optionsLimit(10)
                    ->searchable(),
                SelectFilter::make('category')
                    ->relationship('items.product.category', 'name')
                    ->searchable()
                    ->optionsLimit(10)
                    ->preload(),
                SelectFilter::make('brand')
                    ->relationship('items.product.brand', 'name')
                    ->preload()
                    ->searchable()
                    ->optionsLimit(10),
                SelectFilter::make('rider')
                    ->relationship('rider', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(10),
                TernaryFilter::make('is_pos')
                    ->label('POS')
                    ->trueLabel('POS Sales Only')
                    ->falseLabel('Normal Sales Only'),
                SelectFilter::make('outlet')
                    ->relationship('outlet', 'name'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->url(function (Model $record) {
                        return SaleResource::getUrl(
                            'index',
                            panel: 'outlet',
                            tenant: $record->outlet,
                        );
                    }, true),
                EditAction::make()
                    ->url(function (Model $record) {
                        return SaleResource::getUrl(
                            'index',
                            panel: 'outlet',
                            tenant: $record->outlet,
                        );
                    }, true),
                PdfDownloadAction::make('partials.pdf.sale', fn(Model $record) => $record->sale_number)
                    ->download()
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true),
                    ]),
                PdfDownloadAction::make('partials.pdf.sale', fn(Model $record) => $record->sale_number)
                    ->print()
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true),
                    ]),
                Action::make('open_pdf_in_new_tab')
                    ->icon(Heroicon::ArrowUpRight)
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true),
                    ])
                    ->action(function (Model $record, array $data, $livewire) {
                        $url = route('print.pdf', [
                            'model' => $record::class,
                            'id' => $record->id,
                            'view' => 'partials.pdf.sale',
                            'params' => $data,
                        ]);

                        $livewire->js("window.open('{$url}', '_blank')");
                    }),
                Action::make('open_pdf_popup')
                    ->icon(Heroicon::ArrowUpRight)
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true),
                    ])
                    ->action(function (Model $record, array $data, $livewire) {
                        $url = route('print.pdf', [
                            'model' => $record::class,
                            'id' => $record->id,
                            'view' => 'partials.pdf.sale',
                            'params' => $data,
                        ]);

                        $livewire->js("window.open('{$url}', '_blank', 'width=900,height=700,scrollbars=yes')");
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
