<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessCompanyBatchImportJobUpsert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CompanyImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 14400;

    private const BATCH_SIZE = 1000; // Process 1000 records per batch

    public function __construct(private string $file)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        $fileStream = fopen($this->file, 'r');
        $batch = [];
        $batchCount = 0;
        $skipHeader = true;
        $lineNumber = 0;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            $lineNumber++;

            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $batch[] = $line;
            $batchCount++;

            if ($batchCount >= self::BATCH_SIZE) {
                dispatch(new ProcessCompanyBatchImportJobUpsert($batch, $fieldMap));
                Log::info('Dispatched batch with '.count($batch)." records (lines up to $lineNumber)");
                $batch = [];
                $batchCount = 0;
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessCompanyBatchImportJobUpsert($batch, $fieldMap));
            Log::info('Dispatched final batch with '.count($batch)." records (lines up to $lineNumber)");
        }

        Log::info("CompanyImportJob completed. Processed $lineNumber lines from CSV file");
        fclose($fileStream);
    }
}
