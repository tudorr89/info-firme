<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessCaenCompanyImportChunkJob;
use App\Jobs\Batches\ProcessCaenCompanyImportJob;
use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CaenCompanyImportJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 7200; // 2 hours
    public $tries = 3;

    private const CHUNK_SIZE = 100000; // Increased to 100k rows per chunk

    public function __construct(private string $file)
    {
        ini_set('memory_limit', '2G'); // Increased memory
        set_time_limit(0);
    }

    public function handle(): void
    {
        $fieldMap = [
            'COD_INMATRICULARE'  => 0,
            'COD_CAEN_AUTORIZAT' => 1,
            'VER_CAEN_AUTORIZAT' => 2,
        ];

        try {
            $this->createChunkJobs($fieldMap);
        } catch (\Exception $e) {
            Log::error('Fatal error in CAEN chunked import: ' . $e->getMessage());
            throw $e;
        }
    }

    private function createChunkJobs(array $fieldMap): void
    {
        // Use SplFileObject for better memory management with large files
        $file = new \SplFileObject($this->file, 'r');
        $file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl('^'); // Set delimiter

        // Skip header
        $file->current();
        $file->next();

        $chunkNumber = 1;
        $currentChunk = [];
        $totalRows = 0;

        while (!$file->eof()) {
            $line = $file->current();
            $file->next();

            if (empty($line) || count($line) < 3) {
                continue;
            }

            $currentChunk[] = $line;
            $totalRows++;

            if (count($currentChunk) >= self::CHUNK_SIZE) {
                $this->dispatchChunk($currentChunk, $fieldMap, $chunkNumber++);
                $currentChunk = [];

                // Force garbage collection to manage memory
                if ($chunkNumber % 10 === 0) {
                    gc_collect_cycles();
                }
            }
        }

        // Process remaining chunk
        if (!empty($currentChunk)) {
            $this->dispatchChunk($currentChunk, $fieldMap, $chunkNumber);
        }

        Log::info("Created {$chunkNumber} chunk jobs for {$totalRows} total rows");
    }

    private function dispatchChunk(array $lines, array $fieldMap, int $chunkNumber): void
    {
        // Create a temporary file for this chunk
        $chunkFile = storage_path("app/temp/caen_chunk_{$chunkNumber}.csv");

        // Ensure directory exists
        $dir = dirname($chunkFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $handle = fopen($chunkFile, 'w');
        foreach ($lines as $line) {
            fputcsv($handle, $line, '^');
        }
        fclose($handle);

        // Dispatch chunk processing job with delay to prevent overwhelming the queue
        dispatch(new ProcessCaenCompanyImportChunkJob($chunkFile, $fieldMap, $chunkNumber))
            ->onQueue('caen-import')
            ->delay(now()->addSeconds($chunkNumber * 2)); // Stagger job execution
    }
}
