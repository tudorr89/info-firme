<?php

namespace App\Console\Commands;

use App\Jobs\ImportAllJob;
use App\Jobs\OnrcDownloadJob;
use App\Services\OnrcDownloadService;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OnrcDownloadCommand extends Command
{
    protected $signature = 'onrc:download
        {--sync : Download inline instead of dispatching queued jobs}
        {--force : Re-download files that already exist}
        {--import : Run the full import once every download finished}';

    protected $description = 'Download the latest ONRC CSV snapshots from data.gov.ro.';

    public function handle(OnrcDownloadService $service): int
    {
        $this->output->title('Downloading latest ONRC snapshots');

        try {
            $resources = $service->latestResources();
        } catch (Throwable $exception) {
            Log::error('Unable to discover ONRC datasets: '.$exception->getMessage());
            $this->output->error('Unable to discover ONRC datasets: '.$exception->getMessage());

            return self::FAILURE;
        }

        if ($resources === []) {
            $this->output->warning('No CSV resources found for the latest ONRC snapshots.');

            return self::FAILURE;
        }

        $this->output->info('Resolved '.count($resources).' CSV resource(s).');

        if ($this->option('sync')) {
            $this->downloadInline($service, $resources);
            $this->queueImportWhenDone();

            return self::SUCCESS;
        }

        $this->queueDownloads($resources);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array{url: string, target: string, package: string}>  $resources
     */
    private function downloadInline(OnrcDownloadService $service, array $resources): void
    {
        foreach ($resources as $resource) {
            $this->line("  - downloading {$resource['target']}");
            $service->download($resource['url'], $resource['target']);
        }

        $this->output->success('Downloaded '.count($resources).' file(s).');
    }

    /**
     * @param  array<int, array{url: string, target: string, package: string}>  $resources
     */
    private function queueDownloads(array $resources): void
    {
        $jobs = [];

        foreach ($resources as $resource) {
            if (! $this->option('force') && $this->fileExists($resource['target'])) {
                $this->line("  - {$resource['target']} already present, skipping");

                continue;
            }

            $jobs[] = new OnrcDownloadJob($resource['url'], $resource['target']);
            $this->line("  - queued {$resource['target']}");
        }

        if ($jobs === []) {
            $this->output->info('Everything is up to date.');
            $this->queueImportWhenDone();

            return;
        }

        $batch = Bus::batch($jobs)
            ->name('ONRC download')
            ->allowFailures();

        if ($this->option('import')) {
            $batch->then(function (Batch $finished): void {
                Log::info('ONRC downloads finished', ['failed' => $finished->failedJobs]);
                ImportAllJob::dispatch();
            });
        }

        $batch->dispatch();

        $this->output->success('ONRC download queue filled.');
        $this->output->note('Monitor progress on the Horizon dashboard at: /admin/horizon');
    }

    private function queueImportWhenDone(): void
    {
        if (! $this->option('import')) {
            return;
        }

        $this->output->info('Queueing the full import');

        ImportAllJob::dispatch();
    }

    private function fileExists(string $target): bool
    {
        return Storage::disk(config('import.disk'))
            ->exists(config('import.directory').'/'.$target);
    }
}
