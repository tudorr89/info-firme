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

    public int $timeout = 2400;

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

        // Track unique identifiers to avoid duplicates within the file
        $seenRegComs = [];
        $seenEuids = [];

        while (($line = fgetcsv($fileStream, 1000, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $regCom = $line[$fieldMap['COD_INMATRICULARE']];
            $euid = $line[$fieldMap['EUID']];

            // Skip duplicates within the same file
            if (isset($seenRegComs[$regCom]) || isset($seenEuids[$euid])) {
                Log::info("Skipping duplicate in file - reg_com: {$regCom}, euid: {$euid}");

                continue;
            }

            $seenRegComs[$regCom] = true;
            $seenEuids[$euid] = true;

            $batch[] = $line;
            $batchCount++;

            if ($batchCount >= self::BATCH_SIZE) {
                dispatch(new ProcessCompanyBatchImportJobUpsert($batch, $fieldMap));
                $batch = [];
                $batchCount = 0;
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessCompanyBatchImportJobUpsert($batch, $fieldMap));
        }

        fclose($fileStream);
    }
}
