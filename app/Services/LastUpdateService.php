<?php

namespace App\Services;

use App\Settings\GeneralSettings;
use Carbon\Carbon;

class LastUpdateService
{
    public static function needsUpdate(): bool
    {
        $lastUpdate = app(GeneralSettings::class)->lastUpdate ?? null;

        if ($lastUpdate === null) {
            return true;
        }

        return Carbon::parse($lastUpdate)->diffInMonths(Carbon::now()) >= 3;
    }

    public static function setDate(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->lastUpdate = Carbon::now();
        $settings->save();
    }
}
