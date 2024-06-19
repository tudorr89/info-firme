<?php

namespace App\Services;

use Carbon\Carbon;
use App\Settings\GeneralSettings;

class LastUpdateService
{
    public static function needsUpdate()
    {
        return Carbon::parse(app(GeneralSettings::class)->lastUpdate)->diffInMonths(Carbon::now()) >= 4;
    }

    public static function setDate()
    {
        $settings = new GeneralSettings();
        $settings->lastUpdate = Carbon::now();
        $settings->save();
    }
}