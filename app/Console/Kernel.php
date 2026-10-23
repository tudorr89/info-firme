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
        $schedule->command('onrc:download')->monthlyOn(15, '02:00');
        $schedule->command('import:all')->monthlyOn(15, '03:00')->withoutOverlapping();
        $schedule->command('companies:link')->dailyAt('04:00')->withoutOverlapping();
        $schedule->command('companies:link --prune')->monthlyOn(16, '04:00')->withoutOverlapping();
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
