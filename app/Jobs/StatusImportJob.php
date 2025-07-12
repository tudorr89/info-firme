<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessStatusBatchImportJob;
use App\Jobs\Batches\ProcessStatusImportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StatusImportJob implements ShouldQueue
{
    use Queueable;

    private const BATCH_SIZE = 1000; // Adjust based on your system

    public function __construct(private string $file)
    {
        // Set longer timeout for large files
        $this->timeout = 3600; // 1 hour
    }

    public function handle(): void
    {
        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'COD' => 1,
        ];

        $fileStream = fopen($this->file, 'r');
        $batch = [];
        $skipHeader = true;
        $batchCount = 0;

        while (($line = fgetcsv($fileStream, 1000, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;
                continue;
            }

            $batch[] = $line;

            if (count($batch) >= self::BATCH_SIZE) {
                dispatch(new ProcessStatusBatchImportJob($batch, $fieldMap, ++$batchCount));
                $batch = [];
            }
        }

        // Process remaining records
        if (!empty($batch)) {
            dispatch(new ProcessStatusBatchImportJob($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
        unlink($this->file);
    }
}
