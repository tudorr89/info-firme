<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use RuntimeException;
use Throwable;

/**
 * Runs the full import on the worker.
 *
 * Dispatched after the ONRC downloads finish, because the import reads the CSV
 * files from disk and must not start before they are complete.
 */
class ImportAllJob implements ShouldQueue, Silenced
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 1;

    public function handle(): void
    {
        Log::info('Full ONRC import starting');

        $exitCode = Artisan::call('import:all');

        $output = Artisan::output();

        Log::info('Full ONRC import dispatched', [
            'exit_code' => $exitCode,
            'output' => $output,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('import:all failed with exit code '.$exitCode.': '.$output);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Full ONRC import failed: '.$exception?->getMessage());
    }
}
