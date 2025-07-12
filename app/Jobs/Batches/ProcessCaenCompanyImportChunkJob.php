<?php

namespace App\Jobs\Batches;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessCaenCompanyImportChunkJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 1800; // 30 minutes
    public $tries = 3;

    public function __construct(
        private string $chunkFile,
        private array $fieldMap,
        private int $chunkNumber
    ) {
        ini_set('memory_limit', '1G'); // Increased memory
    }

    public function handle(): void
    {
        $fileStream = fopen($this->chunkFile, 'r');

        if (!$fileStream) {
            throw new \Exception("Cannot open chunk file: {$this->chunkFile}");
        }

        $jobs = [];
        $processedCount = 0;
        $batchSize = 500; // Process 500 records per job (reduced from individual records)

        $currentBatch = [];

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            $currentBatch[] = $line;

            if (count($currentBatch) >= $batchSize) {
                $jobs[] = new ProcessCaenCompanyImportJob($currentBatch, $this->fieldMap);
                $processedCount += count($currentBatch);
                $currentBatch = [];

                // Dispatch in smaller job batches to avoid memory issues
                if (count($jobs) >= 50) { // 50 jobs per batch (25k records)
                    Bus::batch($jobs)
                        ->name("CAEN Chunk #{$this->chunkNumber} Batch")
                        ->allowFailures()
                        ->onQueue('caen-process')
                        ->dispatch();

                    $jobs = [];
                }
            }
        }

        // Process remaining records
        if (!empty($currentBatch)) {
            $jobs[] = new ProcessCaenCompanyImportJob($currentBatch, $this->fieldMap);
            $processedCount += count($currentBatch);
        }

        // Process remaining jobs
        if (!empty($jobs)) {
            Bus::batch($jobs)
                ->name("CAEN Chunk #{$this->chunkNumber} Final Batch")
                ->allowFailures()
                ->onQueue('caen-process')
                ->dispatch();
        }

        fclose($fileStream);

        // Clean up chunk file
        unlink($this->chunkFile);

        Log::info("Processed chunk #{$this->chunkNumber} with {$processedCount} rows");
    }
}
