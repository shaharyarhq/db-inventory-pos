<?php

namespace App\Filament\Admin\Resources\Outlet\Outlets\Tables;

use App\Enums\Status;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OutletsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Panel::make([
                Stack::make([
                    // ImageColumn::make('thumbnail')
                    //     ->label('Thumbnail')
                    //     ->disk('public')
                    //     ->imageSize(120)
                    //     ->toggleable(false)
                    //     ->defaultImageUrl(url('/images/thumbnails/shop.png'))
                    //     ->extraAttributes(['class' => 'table-song-card']),
                    Panel::make([
                        TextColumn::make('name')
                            ->label('Name')
                            ->copyable()
                            ->weight('bold')
                            ->toggleable(false)
                            ->extraAttributes(['class' => 'text-center text-lg mt-2']),

                        TextColumn::make('phone_number')
                            ->label('Phone Number')
                            ->disableNumericFormatting()
                            ->copyable()
                            ->toggleable(false)
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500 mt-1']),

                        TextColumn::make('address')
                            ->label('Address')
                            ->copyable()
                            ->limit('60')
                            ->tooltip(fn($state) => $state)
                            ->toggleable(false)
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500 mt-1']),
                        IconColumn::make('status')
                            ->label('Status')
                            ->icon(fn(Status $state): Heroicon => match ($state) {
                                Status::ACTIVE => Heroicon::CheckCircle,
                                Status::IN_ACTIVE => Heroicon::XCircle,
                            })
                            ->color(fn(Status $state): string => match ($state) {
                                Status::ACTIVE => 'success',
                                Status::IN_ACTIVE => 'danger',
                            })
                            ->sortable()
                            ->action(function ($record, $livewire) {
                                $record->update(['status' =>  $record->status === Status::ACTIVE ? Status::IN_ACTIVE : Status::ACTIVE]);
                                $livewire->resetTable();
                            })
                            ->toggleable(false)
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500 mt-1 icon-column']),
                        TextColumn::make('created_at')
                            ->extraAttributes(['class' => 'text-center text-sm text-gray-500 mt-1'])
                            ->dateTime(),
                    ])
                        ->collapsible(),
                ])
                    ->alignCenter()
                    ->extraAttributes(['class' => 'text-center']), // center everything
                // ])
                // ->columnSpanFull(),
            ])
            ->filters([
                // TrashedFilter::make(),
            ])
            ->contentGrid([
                'xl' => 2,
                '2xl' => 3,
            ])
            ->recordActions([
                // ViewAction::make(),
                ActionGroup::make([
                    EditAction::make()->button(),
                    Action::make('enter_outlet')
                        ->button()
                        ->color('warning')
                        ->icon('heroicon-o-arrow-left-end-on-rectangle')
                        ->hidden(fn($record) => $record->deleted_at || ! $record->status === Status::ACTIVE->value)
                        ->url(function ($record) {
                            return Filament::getPanel('outlet')->getUrl($record);
                        }, true),
                    DeleteAction::make()->button(),
                    // RestoreAction::make(),
                    // ForceDeleteAction::make(),
                ])
                    ->buttonGroup(),
            ])
            ->columnManager(false)
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    // ForceDeleteBulkAction::make(),
                    // RestoreBulkAction::make(),
                ]),
            ]);
    }
}
