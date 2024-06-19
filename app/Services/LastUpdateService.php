<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;
use App\Settings\GeneralSettings;

class LastUpdateService
{
    public static function getDate()
    {
        return Carbon::parse(app(GeneralSettings::class)->lastUpdate);
    }

    public static function needsUpdate()
    {
        $lastUpdate = self::getDate();
        $now = Carbon::now();

        return $lastUpdate->diffInMonths($now) >= 4;
    }

    public static function setDate()
    {
        $settings = new GeneralSettings();
        $settings->lastUpdate = Carbon::now();
        $settings->save();
    }
}