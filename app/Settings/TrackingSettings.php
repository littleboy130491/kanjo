<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class TrackingSettings extends Settings
{
    public ?string $head_code = null;

    public ?string $body_code = null;

    public static function group(): string
    {
        return 'tracking';
    }
}
