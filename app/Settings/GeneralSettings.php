<?php

namespace App\Settings;

use DateTime;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public ?DateTime $lastUpdate = null;

    public static function group(): string
    {
        return 'general';
    }
}
