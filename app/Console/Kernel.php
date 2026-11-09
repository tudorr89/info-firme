<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        // `--force` refreshes every snapshot: the target filenames are fixed,
        // so without it the scheduled download skips everything after the first
        // run. The import is chained from the download batch's `then` callback.
        $schedule->command('onrc:download --force --import')
            ->monthlyOn(15, '02:00')
            ->withoutOverlapping();
        $schedule->command('companies:link')->dailyAt('04:00')->withoutOverlapping();
        // Half an hour after `companies:link`: pruning re-checks the links, and
        // the two must not fight over the same rows at the same minute.
        $schedule->command('companies:link --prune')->monthlyOn(16, '04:30')->withoutOverlapping();
        $schedule->command('sitemap:generate')->monthlyOn(15, '05:00')->withoutOverlapping();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
