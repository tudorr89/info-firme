<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessEUBranchBatchImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EUBranchImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    private const BATCH_SIZE = 1000;

    public function __construct(private string $file)
    {
        //
    }

    public function handle(): void
    {
        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'TIP_UNITATE' => 1,
            'DENUMIRE_SUCURSALA' => 2,
            'EUID' => 3,
            'COD_FISCAL' => 4,
            'TARA' => 5,
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
                dispatch(new ProcessEUBranchBatchImport($batch, $fieldMap, ++$batchCount));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessEUBranchBatchImport($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
    }
}
