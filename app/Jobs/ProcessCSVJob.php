<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\CompaniesImport\ProcessCompanyImportJob;
use Log;

class ProcessCSVJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

    public $timeout = 1200;

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
            'EUID' => 3,
            'STARE_FIRMA' => 4,
            'ADRESA_COMPLETA' => 5,
            'ADR_TARA' => 6,
            'ADR_LOCALITATE' => 7,
            'ADR_JUDET' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_DEN_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
        ];

        // Open the file for reading
        $fileStream = fopen($this->file, 'r');

        $skipHeader = true;
        while (($line = fgetcsv($fileStream, 1000, '^')) !== false) {
            if ($skipHeader) {
                // Skip the header
                $skipHeader = false;
                continue;
            }
            try {
                // For each line, we dispatch a job to process the line
                dispatch(new ProcessCompanyImportJob($line, $fieldMap));
            } catch (\Exception $e) {
                Log::error('Error processing line: ' . json_encode($line));
            }
        }

        // Close the file
        fclose($fileStream);

        // Delete the file after import
        unlink($this->file);
    }
}
