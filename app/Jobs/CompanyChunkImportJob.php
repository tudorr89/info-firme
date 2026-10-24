<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessCompanyBatchImportJobUpsert;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Laravel\Horizon\Contracts\Silenced;
use RuntimeException;
use Throwable;

/**
 * Imports one chunk file produced by {@see CompanyImportJob}.
 *
 * The parent job slices the (very large) company CSV into chunk files so it can
 * dispatch them as a Laravel batch without holding millions of rows in memory.
 */
class CompanyChunkImportJob implements ShouldQueue, Silenced
{
    use Batchable, Queueable;

    public int $timeout = 1200;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 60, 300, 900];

    /**
     * @param  array<string, int>  $fieldMap
     */
    public function __construct(
        private string $chunkFile,
        private array $fieldMap,
        private int $rowsPerBatch = 4000
    ) {}

    public function handle(): void
    {
        $fileStream = fopen($this->chunkFile, 'r');

        if ($fileStream === false) {
            throw new RuntimeException("Cannot open company chunk file: {$this->chunkFile}");
        }

        $batch = [];
        $imported = 0;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($line === [null]) {
                continue;
            }

            $batch[] = $line;

            if (count($batch) >= $this->rowsPerBatch) {
                (new ProcessCompanyBatchImportJobUpsert($batch, $this->fieldMap))->handle();
                $imported += count($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            (new ProcessCompanyBatchImportJobUpsert($batch, $this->fieldMap))->handle();
            $imported += count($batch);
        }

        fclose($fileStream);
        if (is_file($this->chunkFile)) {
            unlink($this->chunkFile);
        }

        Log::info("Imported company chunk {$this->chunkFile}", ['rows' => $imported]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Company chunk import failed', [
            'chunk' => $this->chunkFile,
            'error' => $exception?->getMessage(),
        ]);
    }
}
