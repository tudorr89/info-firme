<?php

namespace App\Jobs;

use App\Services\Import\ImportPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use Throwable;

/**
 * Imports the files that link to companies (statuses, CAEN, representatives,
 * branches).
 *
 * Dispatched from the company import batch's `then` callback: every related
 * file joins `companies`, so they may only run once the companies exist.
 */
class ImportRelatedDataJob implements ShouldQueue, Silenced
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 1;

    public function handle(ImportPipeline $pipeline): void
    {
        $dispatched = $pipeline->dispatchRelated();

        Log::info('Related ONRC import dispatched', ['files' => $dispatched]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Related ONRC import failed: '.$exception?->getMessage());
    }
}
