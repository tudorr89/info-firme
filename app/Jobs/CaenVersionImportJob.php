<?php

namespace App\Jobs;

use App\Models\CaenVersion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CaenVersionImportJob implements ShouldQueue
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
        //COD^DESCRIERE
        $fieldMap = [
            'COD'       => 0,
            'DESCRIERE' => 1,
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
            CaenVersion::firstOrCreate(
                [
                    'code'  => $line[$fieldMap['COD']],
                ],
                [
                    'name'  => $line[$fieldMap['DESCRIERE']],

                ]
            );
        }

        fclose($fileStream);

        // Delete the file after import
        //unlink($this->file);
    }
}
