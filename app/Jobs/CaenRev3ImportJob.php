<?php

namespace App\Jobs;

use App\Models\CaenVersion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CaenRev3ImportJob implements ShouldQueue
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
        // CSV structure: CAEN Rev.2 Code, Rev.2 Name, CAEN Rev.3 Code, Rev.3 Name
        $fieldMap = [
            'CAEN_REV2_CODE' => 0,
            'CAEN_REV2_NAME' => 1,
            'CAEN_REV3_CODE' => 2,
            'CAEN_REV3_NAME' => 3,
        ];

        $fileStream = fopen($this->file, 'r');
        $skipRows = 6; // Skip header and empty rows
        $currentRow = 0;

        while (($line = fgetcsv($fileStream, 2000, ',')) !== false) {
            $currentRow++;
            if ($currentRow <= $skipRows) {
                continue; // Skip header rows
            }

            // Only process if we have the minimum required data
            if (! isset($line[$fieldMap['CAEN_REV3_CODE']]) || empty(trim($line[$fieldMap['CAEN_REV3_CODE']]))) {
                continue;
            }

            $caenCode = trim($line[$fieldMap['CAEN_REV3_CODE']]);
            $caenName = trim($line[$fieldMap['CAEN_REV3_NAME']] ?? '');

            if (! empty($caenCode)) {
                CaenVersion::firstOrCreate(
                    [
                        'code' => $caenCode,
                    ],
                    [
                        'name' => $caenName,
                    ]
                );
            }
        }

        fclose($fileStream);
    }
}
