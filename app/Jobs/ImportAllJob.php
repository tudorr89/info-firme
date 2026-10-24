<?php

namespace App\Jobs;

use App\Services\Import\ImportPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use RuntimeException;
use Throwable;

/**
 * Runs the import on the worker.
 *
 * Dispatched after the ONRC downloads finish (the import reads the CSV files
 * from disk) and, in its "related data" variant, after the company import
 * finished, because every other file links to `companies`.
 */
class ImportAllJob implements ShouldQueue, Silenced
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 1;

    public function handle(ImportPipeline $pipeline): void
    {
        $dispatched = $pipeline->dispatchAll();

        Log::info('ONRC import dispatched', ['files' => $dispatched]);

        if ($dispatched === []) {
            throw new RuntimeException('No ONRC files available to import.');
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('ONRC import failed: '.$exception?->getMessage());
    }
}
