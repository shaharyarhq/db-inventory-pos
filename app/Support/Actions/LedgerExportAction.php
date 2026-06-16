<?php

namespace App\Support\Actions;

use Closure;
use Filament\Actions\Action;
use App\Models\Outlet\Outlet;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;

class LedgerExportAction
{
    protected string|Closure $fileName = 'export';

    public function __construct(
        protected string $exportClass,
        protected bool $isOutletRequired = true,
        protected bool $hasOutletSelectionSchema = true
    ) {}

    public function getExportClass(): string
    {
        return $this->exportClass;
    }

    public function fileName(string|Closure $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileName(): string|Closure
    {
        return $this->fileName;
    }

    public function isOutletRequired(bool $isOutletRequired)
    {
        $this->isOutletRequired = $isOutletRequired;
        return $this;
    }

    public function getIsOutletRequired(): string|Closure
    {
        return $this->isOutletRequired;
    }


    public function hasOutletSelectionSchema(bool $hasOutletSelectionSchema)
    {
        $this->hasOutletSelectionSchema = $hasOutletSelectionSchema;
        return $this;
    }

    public function getHasOutletSelectionSchema(): string|Closure
    {
        return $this->hasOutletSelectionSchema;
    }

    public static function configure(string $exportClass)
    {
        return (new static($exportClass));
    }

    public function make(?string $name = null): Action
    {
        return $this->getAction($name);
    }



    public function getAction(?string $name): Action
    {
        $exportClass = $this->getExportClass();
        $fileName = $this->getFileName();

        return Action::make($name ?? 'export_ledger')
            ->icon('heroicon-o-document-text')
            ->color('info')
            ->schema($this->getHasOutletSelectionSchema() ? [
                Select::make('outlet_id')
                    ->label('Outlet')
                    ->required($this->getIsOutletRequired())
                    ->options(Outlet::options()),
            ] : [])
            ->action(function (?Model $record, array $data, $livewire) use ($exportClass, $fileName) {
                $outletId = $data['outlet_id'] ?? null;
                $outlet = Outlet::find($outletId);

                $resolvedFileName = $fileName instanceof Closure
                    ? $fileName($record, $outlet)
                    : $fileName;

                // Sanitize: strip characters invalid in Content-Disposition filenames
                $resolvedFileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $resolvedFileName);

                $filteredTableQuery = $livewire->getFilteredTableQuery();

                return Excel::download(
                    new $exportClass($record?->id, $outletId, $filteredTableQuery),
                    "{$resolvedFileName}.xlsx"
                );
            });
    }
}
