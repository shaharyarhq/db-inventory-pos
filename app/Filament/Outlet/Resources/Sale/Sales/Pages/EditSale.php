<?php

namespace App\Filament\Outlet\Resources\Sale\Sales\Pages;

use App\Filament\Outlet\Resources\Sale\Sales\SaleResource;
use App\Support\Actions\PdfDownloadAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
            ActionGroup::make([
                PdfDownloadAction::make('partials.pdf.sale', fn(Model $record) => $record->sale_number)
                    ->download()
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true)
                    ]),
                PdfDownloadAction::make('partials.pdf.sale', fn(Model $record) => $record->sale_number)
                    ->print()
                    ->modalWidth(Width::Medium)
                    ->schema([
                        Toggle::make('group_variants')->default(true)
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
            ]),
        ];
    }
}
