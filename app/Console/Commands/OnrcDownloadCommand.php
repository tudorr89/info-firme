<?php

namespace App\Console\Commands;

use App\Jobs\OnrcDownloadJob;
use App\Services\OnrcDownloadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OnrcDownloadCommand extends Command
{
    protected $signature = 'onrc:download
        {--sync : Download inline instead of dispatching queued jobs}
        {--force : Re-download files that already exist}';

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

        foreach ($resources as $resource) {
            $this->dispatchDownload($service, $resource['url'], $resource['target']);
        }

        $this->output->success('ONRC download queue filled.');
        $this->output->note('Monitor progress on the Horizon dashboard at: /admin/horizon');

        return self::SUCCESS;
    }

    private function dispatchDownload(OnrcDownloadService $service, string $url, string $target): void
    {
        if ($this->option('sync')) {
            $this->line("  - downloading {$target}");
            $service->download($url, $target);

            return;
        }

        if (! $this->option('force') && $this->fileExists($target)) {
            $this->line("  - {$target} already present, skipping");

            return;
        }

        OnrcDownloadJob::dispatch($url, $target);
        $this->line("  - queued {$target}");
    }

    private function fileExists(string $target): bool
    {
        return Storage::disk(config('import.disk'))
            ->exists(config('import.directory').'/'.$target);
    }
}
