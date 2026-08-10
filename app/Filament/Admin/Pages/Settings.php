<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use App\Settings\GeneralSettings;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Group;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Enums\SubNavigationPosition;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class Settings extends SettingsPage
{
    use HasPageShield;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    public function getSubNavigation(): array
    {
        return $this->generateNavigationItems([
            // Settings::class
        ]);
    }

    protected static string $settings = GeneralSettings::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()->schema([
                    Tab::make('General')->schema([
                        Group::make()
                            ->schema([
                                TextInput::make('site_name')
                                    ->required(),
                                Group::make()
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        ToggleButtons::make('spa_mode')
                                            ->required()
                                            ->inline()
                                            ->boolean(),
                                        ToggleButtons::make('spa_prefetching')
                                            ->required()
                                            ->inline()
                                            ->boolean(),
                                    ]),
                                ActionGroup::make([
                                    Action::make('reset_cache')
                                        ->color('gray')
                                        ->icon(Heroicon::ArrowPath)
                                        ->action(function () {
                                            $this->redirect(route('optimize'));
                                        }),
                                    Action::make('clear_cache')
                                        ->color('gray')
                                        ->icon(Heroicon::Trash)
                                        ->action(function () {
                                            $this->redirect(route('optimize:clear'));
                                        }),
                                ])
                                    ->buttonGroup(),
                            ]),
                        Group::make()
                            ->schema([
                                FileUpload::make('site_logo')
                                    ->directory('images/logo')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public')
                                    ->deleteUploadedFileUsing(function ($file, GeneralSettings $generalSettings) {
                                        Storage::disk('public')->delete($file);
                                        $generalSettings->site_logo = null;
                                        $generalSettings->save();
                                    })
                                    ->nullable()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->downloadable()
                                    ->openable(),
                                FileUpload::make('site_logo_dark_mode')
                                    ->directory('images/logo')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public')
                                    ->deleteUploadedFileUsing(function ($file, GeneralSettings $generalSettings) {
                                        Storage::disk('public')->delete($file);
                                        $generalSettings->site_logo_dark_mode = null;
                                        $generalSettings->save();
                                    })
                                    ->nullable()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->downloadable()
                                    ->openable(),
                            ]),
                    ]),
                    Tab::make('Appearance')->schema([
                        Select::make('background_type')
                            ->required()
                            ->searchable(false)
                            ->options([
                                'solid'   => 'Solid',
                                'pattern' => 'Pattern',
                            ]),
                        // Select::make('navigation_type')
                        //     ->required()
                        //     ->searchable(false)
                        //     ->options([
                        //         'sidebar' => 'Sidebar',
                        //         'topbar' => 'Topbar',
                        //     ]),
                        Select::make('content_width')
                            ->required()
                            ->searchable(false)
                            ->options(Width::class),
                    ]),
                    Tab::make('Invoice Settings')->schema([
                        Group::make()
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                FileUpload::make('invoice_watermark_logo')
                                    ->directory('images/logo')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public')
                                    ->deleteUploadedFileUsing(function ($file, GeneralSettings $generalSettings) {
                                        Storage::disk('public')->delete($file);
                                        $generalSettings->site_logo = null;
                                        $generalSettings->save();
                                    })
                                    ->nullable()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->downloadable()
                                    ->openable(),
                                FileUpload::make('invoice_footer_logo')
                                    ->directory('images/logo')
                                    ->disk('public')
                                    ->image()
                                    ->imageEditor()
                                    ->visibility('public')
                                    ->deleteUploadedFileUsing(function ($file, GeneralSettings $generalSettings) {
                                        Storage::disk('public')->delete($file);
                                        $generalSettings->site_logo_dark_mode = null;
                                        $generalSettings->save();
                                    })
                                    ->nullable()
                                    ->removeUploadedFileButtonPosition('right')
                                    ->downloadable()
                                    ->openable(),
                            ]),
                    ]),
                ])->columnSpanFull()
                    ->columns(2)
                    ->persistTabInQueryString()
                    ->persistTab()
                    ->id('general-settings-tabs'),
            ]);
    }

    protected function afterSave(): void
    {
        // This runs after the settings are saved
        $this->redirect(request()->header('Referer') ?? url()->current());
    }
}
