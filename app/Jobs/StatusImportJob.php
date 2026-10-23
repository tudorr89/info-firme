<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessStatusBatchImportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StatusImportJob implements ShouldQueue
{
    use Queueable;

    private const BATCH_SIZE = 4000;

    /** 1 hour for large files. */
    public int $timeout = 1200;

    public function __construct(private string $file) {}

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

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
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
        if (! empty($batch)) {
            dispatch(new ProcessStatusBatchImportJob($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
    }
}
