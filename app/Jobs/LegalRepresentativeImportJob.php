<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessLegalRepresentativeBatchImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LegalRepresentativeImportJob implements ShouldQueue
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
            'PERSOANA_IMPUTERNICITA' => 1,
            'CALITATE' => 2,
            'DATA_NASTERE' => 3,
            'LOCALITATE_NASTERE' => 4,
            'JUDET_NASTERE' => 5,
            'TARA_NASTERE' => 6,
            'LOCALITATE' => 7,
            'JUDET' => 8,
            'TARA' => 9,
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
                dispatch(new ProcessLegalRepresentativeBatchImport($batch, $fieldMap, ++$batchCount));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessLegalRepresentativeBatchImport($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
        unlink($this->file);
    }
}
