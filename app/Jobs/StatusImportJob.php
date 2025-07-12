<?php

namespace App\Jobs;

use App\Jobs\CompaniesImport\ProcessStatusImportJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class StatusImportJob implements ShouldQueue
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
        //COD_INMATRICULARE^COD
        $fieldMap = [
            'COD_INMATRICULARE'         => 0,
            'COD'                       => 1,
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
                dispatch(new ProcessStatusImportJob($line, $fieldMap));
            } catch (\Exception $e) {
                Log::error('Error processing Status line: ' . json_encode($line));
            }
        }

        fclose($fileStream);

        // Delete the file after import
        unlink($this->file);
    }
}
