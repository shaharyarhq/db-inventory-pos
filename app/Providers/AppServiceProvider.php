<?php

namespace App\Providers;

use App\Enums\DiscountType;
use App\Enums\Status;
use App\Filament\Support\View\LucideLoadingIndicator;
use App\Support\Actions\CalculatorAction;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Contracts\LoadingIndicator;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Action::configureUsing(function(Action $action){
        //     if($action->getDefaultName() === 'create'){
        //           return $action->extraAttributes(['style' => 'color:white']);
        //     }
        // });

        Filament::serving(function () {
            $width = request()->route()->getName() === 'filament.outlet.pages.pos' ?  Width::Full : null;
            $topbar = request()->route()->getName() === 'filament.outlet.pages.pos' ?  true : false;

            request()->route()->getName() === 'filament.outlet.pages.pos' ? filament()
                ->getCurrentPanel()
                ->sidebarCollapsibleOnDesktop(false)
                ->sidebarFullyCollapsibleOnDesktop()
                // ->topbar($topbar)
                ->maxContentWidth($width) : null;
        });

         $this->app->bind(LoadingIndicator::class, LucideLoadingIndicator::class);

        TextEntry::configureUsing(function (TextEntry $entry) {
            // $entry->size(TextSize::Large);
        });

        TextColumn::macro('disableNumericFormatting', function (): static {
            $this->extraAttributes(['data-disable-numeric' => true]);

            return $this;
        });

        TextColumn::macro('quantity', function () {
            $this->prefix(app_currency_symbol())
                ->default(0)
                ->numeric(2);

            return $this;
        });

        TextColumn::macro('currency', function () {
            $this->prefix(app_currency_symbol())
                ->color(function ($state) {
                    if ($state < 0) {
                        return 'danger';
                    }

                    if ($state > 0) {
                        return 'success';
                    }

                    return 'gray';
                })
                ->default(0);

            return $this;
        });

        TextEntry::macro('currency', function () {
            $this->prefix(app_currency_symbol())
                ->numeric(2)
                ->color(function ($state) {
                    if ($state < 0) {
                        return 'danger';
                    }

                    if ($state > 0) {
                        return 'success';
                    }

                    return 'gray';
                })
                ->default(0);

            return $this;
        });


        // TextColumn::macro('balanceTooltip', function () {
        //     $this->tooltip(function ($state) {
        //         if ($state < 0) {
        //             return " Debit";
        //         }

        //         if ($state > 0) {
        //             return " Credit";
        //         }

        //         return null;
        //     });

        //     return $this;
        // });

        TextInput::macro('calculator', function(){
            return $this->suffixAction(CalculatorAction::make()
                    ->overlayParentActions()->disabled(fn($operation)=>$operation==='view'));
        });

        TextInput::macro('currency', function () {
            $this->prefix(app_currency_symbol())
                ->numeric()
                ->formatStateUsing(fn($state) => blank($state) ? 0 : $state)
                ->default(0);

            return $this;
        });


        TextColumn::macro('desc', function () {
            $this->copyable()
                ->limit('60')
                ->tooltip(fn($state) => $state);

            return $this;
        });

        TextColumn::macro('sumCurrency', function () {
            $this->summarize(Sum::make()->formatStateUsing(fn($state) => currency_format($state)));

            return $this;
        });

        TextColumn::configureUsing(function (TextColumn $column): void {
            $column->toggleable()
                ->sortable()
                ->searchable();

            $column->formatStateUsing(function ($state) use ($column) {
                if ($column->getExtraAttributes()['data-disable-numeric'] ?? false) {
                    return $state;
                }

                return is_numeric($state)
                    ? default_number_format((float) $state)
                    : $state;
            });
        });

        Column::configureUsing(function (Column $column) {
            $column->placeholder('---');
        });

        Table::configureUsing(function (Table $table): void {
            $table
                // ->deferFilters(false)
                ->persistFiltersInSession()
                ->reorderableColumns()
                ->defaultDateDisplayFormat(app_date_format())
                ->defaultDateTimeDisplayFormat(app_date_time_format())
                ->defaultSort('created_at', 'desc')
                ->filtersFormColumns(2)
                ->paginated([5, 10, 25, 50, 100, 'all'])
                ->extremePaginationLinks();
                // ->deferColumnManager(false)
                // ->striped()
                // ->filters([], layout: FiltersLayout::AboveContentCollapsible)
                // ->filtersTriggerAction(
                //     fn(Action $action) => $action
                //         ->slideOver() // This makes the filter panel a slide-over
                // )
            ;
        });

        Table::macro('moreFilters', function (array|null $beforeDefault = [], array|null $afterDefault = []) {
            return $this->filters([
                ...$beforeDefault,
                Filter::make('created_at')
                    ->schema([
                        Fieldset::make()
                            ->label('Created')
                            ->columnSpanFull()
                            ->columns(1)
                            ->schema([
                                DatePicker::make('from')
                                    ->displayFormat(app_date_format())
                                    ->maxDate(fn(Get $get, Set $set) => $get('until') ?: now())
                                    ->label('From'),
                                DatePicker::make('until')
                                    ->displayFormat(app_date_format())
                                    ->minDate(fn(Get $get) => $get('from'))
                                    ->maxDate(now())
                                    ->label('Until'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['from'] && !$data['until']) {
                            return null;
                        }

                        $from = $data['from']
                            ? Carbon::parse($data['from'])->format(app_date_format())
                            : null;

                        $until = $data['until']
                            ? Carbon::parse($data['until'])->format(app_date_format())
                            : null;

                        if ($from && $until) {
                            return "From {$from} to {$until}";
                        }

                        if ($from) {
                            return "From {$from}";
                        }

                        return "Until {$until}";
                    }),
                Filter::make('updated_at')
                    ->schema([
                        Fieldset::make()
                            ->label('Updated')
                            ->columnSpanFull()
                            ->columns(1)
                            ->schema([
                                DatePicker::make('updated_from')
                                    ->displayFormat(app_date_format())
                                    ->maxDate(fn(Get $get) => $get('updated_until') ?: now())
                                    ->label('From'),
                                DatePicker::make('updated_until')
                                    ->displayFormat(app_date_format())
                                    ->minDate(fn(Get $get) => $get('updated_from'))
                                    ->maxDate(now())
                                    ->label('Until'),
                            ])
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['updated_from'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('updated_at', '>=', $date),
                            )
                            ->when(
                                $data['updated_until'],
                                fn(Builder $query, $date): Builder =>
                                $query->whereDate('updated_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!$data['updated_from'] && !$data['updated_until']) {
                            return null;
                        }

                        $from = $data['updated_from']
                            ? Carbon::parse($data['updated_from'])->format(app_date_format())
                            : null;

                        $until = $data['updated_until']
                            ? Carbon::parse($data['updated_until'])->format(app_date_format())
                            : null;

                        if ($from && $until) {
                            return "Updated: {$from} to {$until}";
                        }

                        if ($from) {
                            return "Updated from {$from}";
                        }

                        return "Updated until {$until}";
                    }),
                SelectFilter::make('created_by')
                    ->relationship('creator', 'name'),
                ...$afterDefault
            ]);
        });

        Table::macro('groupedRecordActions', function (array $actions) {
            return $this->recordActions([
                ActionGroup::make([
                    ...$actions,
                ]),
            ], RecordActionsPosition::BeforeColumns);
        });

        Select::configureUsing(function (Select $select) {
            $select->searchable()
                ->preload()
                ->optionsLimit(10);
        });

        ForceDeleteAction::configureUsing(function (ForceDeleteAction $action) {
            $action->action(function () use ($action): void {
                try {
                    $result = $action->process(static fn(Model $record): ?bool => $record->forceDelete());

                    if (! $result) {
                        $action->failure();

                        return;
                    }

                    $action->success();
                } catch (\Throwable $e) {
                    $errorRef = strtoupper(Str::random(8));

                    $isForeignKey = isset($e->errorInfo) &&
                        $e->errorInfo[0] === '23000' &&
                        ($e->errorInfo[1] ?? null) === 1451;

                    $errorMessage = match (true) {
                        $isForeignKey => 'Cannot delete this record because it has linked ledger entries or related data.',
                        default => $e->getMessage() ?: "An unknown error occurred while deleting the record. Error Ref: {$e->getMessage()}",
                    };

                    Notification::make('record_deletion_error')
                        ->danger()
                        ->title('Error While Deleting Record')
                        ->body($errorMessage)
                        ->send();

                    logger($e);

                    $action->failure();
                }
            });
        });

        SelectFilter::configureUsing(function (SelectFilter $selectFilter) {
            $selectFilter->preload()
                ->optionsLimit(10)
                ->searchable();
        });

        DeleteAction::configureUsing(function (DeleteAction $action) {
            $action->action(function () use ($action): void {
                try {
                    $result = $action->process(static fn(Model $record): ?bool => $record->delete());

                    if (! $result) {
                        $action->failure();

                        return;
                    }

                    $action->success();
                } catch (\Throwable $e) {

                    $errorRef = strtoupper(Str::random(8));

                    $isForeignKey = isset($e->errorInfo) &&
                        $e->errorInfo[0] === '23000' &&
                        ($e->errorInfo[1] ?? null) === 1451;

                    $errorMessage = match (true) {
                        $isForeignKey => 'Cannot delete this record because it has linked ledger entries or related data.',
                        default => $e->getMessage() ?: "An unknown error occurred while deleting the record.",
                    };

                    Notification::make('record_deletion_error')
                        ->danger()
                        ->title('Error While Deleting Record')
                        ->body($errorMessage)
                        ->send();

                    $action->failure();

                    logger($e);
                }
            });
        });

        Blueprint::macro('belongsToOutlet', function (bool $nullable = true) {
            $column = $this->foreignId('outlet_id');

            if ($nullable) {
                $column->nullable();
            }

            return $column->constrained('outlets')->restrictOnDelete();
        });

        Blueprint::macro('money', function (string $column) {
            return $this->decimal($column, 19, 4)->default(0);
        });

        Blueprint::macro('quantity', function (string $column) {
            return $this->decimal($column, 15, 3)->default(0);
        });

        Blueprint::macro('status', function () {
            return $this->string('status')->default(Status::ACTIVE);
        });

        Blueprint::macro('discountType', function () {
            return $this->enum('discount_type', array_map(fn($case) => $case->value, DiscountType::cases()));
        });

        Blueprint::macro('discountValue', function () {
            return $this->decimal('discount_value', 15, 4)->default(0);
        });

        Blueprint::macro('auditUsers', function () {
            $this->foreignId('creator_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $this->foreignId('updater_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();
        });

        FilamentAsset::registerCssVariables([
            'background-image' => 'url(' . asset('images/background/header.png') . ')',
        ]);
    }
}
