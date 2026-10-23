<?php

namespace App\Jobs;

use App\Services\OnrcDownloadService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Horizon\Contracts\Silenced;
use Throwable;

/**
 * Downloads a single ONRC CSV resource.
 *
 * ONRC file URLs can be very slow, so this job uses a long timeout (kept below
 * the queue `retry_after` of 1250s) and a long, escalating backoff instead of
 * failing fast. Each attempt streams to a `.part` file, so a retry restarts
 * from a clean slate.
 */
class OnrcDownloadJob implements ShouldQueue, Silenced
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 5;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60, 300, 900, 1800];

    public int $maxExceptions = 5;

    public function __construct(public string $url, public string $target)
    {
        //
    }

    public function handle(OnrcDownloadService $service): void
    {
        Log::info('Downloading ONRC resource', [
            'target' => $this->target,
            'url' => $this->url,
            'attempt' => $this->attempts(),
        ]);

        $service->download($this->url, $this->target);

        Log::info('Downloaded ONRC resource', ['target' => $this->target]);
    }

    public function failed(?Throwable $exception): void
    {
        $this->cleanupPartialFile();

        Log::error('ONRC resource download failed', [
            'target' => $this->target,
            'url' => $this->url,
            'error' => $exception?->getMessage(),
        ]);
    }

    private function cleanupPartialFile(): void
    {
        $disk = Storage::disk(config('import.disk'));
        $path = config('import.directory').'/'.$this->target.'.part';

        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
