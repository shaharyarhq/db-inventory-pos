<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use Filament\Support\Enums\Width;

class GeneralSettings extends Settings
{
    public string $site_name;

    public ?string $site_logo;

    public ?string $site_logo_dark_mode;

    public string $navigation_type;

    public string $background_type;

    public bool $spa_mode;

    // public bool $spa_prefetching;

    public ?string $address;

    public ?string $contact;

    public string|Width|null $content_width;

    public ?string $invoice_watermark_logo;

    public ?string $invoice_footer_logo;

    public static function group(): string
    {
        return 'general';
    }
}
