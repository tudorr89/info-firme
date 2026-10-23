<?php

namespace App\Jobs;

use App\Services\CompanyLinkService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use Throwable;

/**
 * Backfills `company_id` on the related import tables once the company data is
 * available. Dispatched at the end of a full import and by the scheduler.
 */
class LinkCompanyDataJob implements ShouldBeUnique, ShouldQueue, Silenced
{
    use Queueable;

    public int $timeout = 1200;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900];

    public int $uniqueFor = 3600;

    /**
     * When enabled, related rows of companies that were not imported (banned
     * or removed) are deleted instead of being left behind as orphans.
     */
    public bool $pruneOrphans = false;

    /**
     * Also delete related rows of companies that were never imported.
     */
    public static function withPruning(): self
    {
        $job = new self;
        $job->pruneOrphans = true;

        return $job;
    }

    public function handle(CompanyLinkService $service): void
    {
        $linked = $service->linkAll();

        Log::info('Company data linked', $linked);

        if ($this->pruneOrphans) {
            $pruned = $service->pruneOrphans();

            Log::info('Orphan company data pruned', $pruned);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Linking company data failed: '.$exception?->getMessage());
    }
}
