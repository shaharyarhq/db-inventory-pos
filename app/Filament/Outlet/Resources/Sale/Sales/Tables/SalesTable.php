<?php

namespace App\Filament\Outlet\Resources\Sale\Sales\Tables;

use App\Enums\DiscountType;
use App\Enums\PanelId;
use App\Filament\Outlet\Resources\Master\Customers\CustomerResource;
use App\Filament\Outlet\Resources\Sale\SaleReturns\SaleReturnResource;
use App\Support\Actions\PdfDownloadAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')
                    ->copyable(),
                TextColumn::make('customer.name')
                    ->url(
                        filament()->auth()->user()->isSuperAdmin() ? fn($state) => CustomerResource::getUrl('index', [
                            'search' => $state,
                        ], panel: PanelId::ADMIN->value) : '',
                        true
                    )
                    ->copyable(! filament()->auth()->user()->isSuperAdmin()),
                TextColumn::make('customer.area.name')
                    ->copyable(),
                TextColumn::make('customer.city.name')
                    ->copyable(),
                TextColumn::make('total')
                    ->summarize(Sum::make()->formatStateUsing(fn($state) => currency_format($state)))
                    ->currency(),
                TextColumn::make('discount_type')
                    ->copyable()
                    ->color('warning')
                    ->badge(),
                TextColumn::make('discount_value')
                    ->numeric()
                    ->prefix(fn($record) => $record->discount_type === DiscountType::FIXED ? app_currency_symbol() : '')
                    ->suffix(fn($record) => $record->discount_type === DiscountType::PERCENT ? ' %' : '')
                    ->copyable(),
                TextColumn::make('discount_amount')
                    ->copyable()
                    ->currency()
                    ->sumCurrency()
                    ->badge()
                    ->color('info'),
                TextColumn::make('grand_total')
                    ->sumCurrency()
                    ->currency(),
                IconColumn::make('is_pos')
                    ->boolean(),
                TextColumn::make('pos_receipt_number')
                    ->copyable(),
                TextColumn::make('rider.name'),
                TextColumn::make('description')
                    ->desc(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->summaries(
                // allTableCondition: false
            )
            ->groupedRecordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('create_sale_return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(function (Model $record) {
                        return SaleReturnResource::getUrl('create', ['sale_id' => $record->id]);
                    }, true),
                Action::make('view_sale_return')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->url(function (Model $record) {
                        return SaleReturnResource::getUrl('index', ['filters' => [
                            'sale' => [
                                'value' => $record?->id,
                            ],
                        ]]);
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
                    DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
