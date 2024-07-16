<?php

namespace App\Services;

use Carbon\Carbon;
use App\Settings\GeneralSettings;

class LastUpdateService
{
    public static function needsUpdate()
    {
        return Carbon::parse(Carbon::now())->diffInMonths(app(GeneralSettings::class)->lastUpdate) >= 3;
    }

    public static function setDate()
    {
        $settings = new GeneralSettings();
        $settings->lastUpdate = Carbon::now();
        $settings->save();
    }
}
