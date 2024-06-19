<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;
use DateTime;

class GeneralSettings extends Settings
{
    public DateTime $lastUpdate;

    public static function group(): string
    {
        return 'general';
    }
}