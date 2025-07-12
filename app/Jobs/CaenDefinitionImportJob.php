<?php

namespace App\Jobs;

use App\Models\Caen;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CaenDefinitionImportJob implements ShouldQueue
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
        //SECTIUNEA^SUBSECTIUNEA^DIVIZIUNEA^GRUPA^CLASA^DENUMIRE^VERSIUNE_CAEN
        $fieldMap = [
            'SECTIUNEA'         => 0,
            'SUBSECTIUNEA'      => 1,
            'DIVIZIUNEA'        => 2,
            'GRUPA'             => 3,
            'CLASA'             => 4,
            'DENUMIRE'          => 5,
            'VERSIUNE_CAEN'     => 6,
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
            Caen::firstOrCreate(
                [
                    'name'              => $line[$fieldMap['DENUMIRE']],
                ],
                [
                    'section'           => $line[$fieldMap['SECTIUNEA']],
                    'subsection'        => $line[$fieldMap['SUBSECTIUNEA']],
                    'division'          => $line[$fieldMap['DIVIZIUNEA']],
                    'group'             => $line[$fieldMap['GRUPA']],
                    'class'             => $line[$fieldMap['CLASA']],
                    'name'              => $line[$fieldMap['DENUMIRE']],
                    'version'           => $line[$fieldMap['VERSIUNE_CAEN']],
                ]
            );
        }

        fclose($fileStream);

        // Delete the file after import
        unlink($this->file);
    }
}
