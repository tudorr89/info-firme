<?php

namespace App\Jobs;

use App\Jobs\CompaniesImport\ProcessCaenCompanyImportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CaenCompanyImportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private string $file)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //COD_INMATRICULARE^COD_CAEN_AUTORIZAT^VER_CAEN_AUTORIZAT
        $fieldMap = [
            'COD_INMATRICULARE'         => 0,
            'COD_CAEN_AUTORIZAT'        => 1,
            'VER_CAEN_AUTORIZAT'        => 2,
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
                dispatch(new ProcessCaenCompanyImportJob($line, $fieldMap));
            } catch (\Exception $e) {
                Log::error('Error processing CAEN line: ' . json_encode($line));
            }
        }

        fclose($fileStream);

        // Delete the file after import
        unlink($this->file);
    }
}
