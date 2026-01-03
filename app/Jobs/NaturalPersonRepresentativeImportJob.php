<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessNaturalPersonRepresentativeBatchImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NaturalPersonRepresentativeImportJob implements ShouldQueue
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
            'NUME' => 1,
            'DATA_NASTERE' => 2,
            'LOCALITATE_NASTERE' => 3,
            'JUDET_NASTERE' => 4,
            'TARA_NASTERE' => 5,
            'CALITATE' => 6,
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
                dispatch(new ProcessNaturalPersonRepresentativeBatchImport($batch, $fieldMap, ++$batchCount));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessNaturalPersonRepresentativeBatchImport($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
    }
}
