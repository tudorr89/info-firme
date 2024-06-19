<?php

namespace App\Jobs;

use App\Models\Nomenclator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NomenclatorImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $fieldMap = [
            'COD'       => 0,
            'DENUMIRE'  => 1,
        ];

        // Open the file for reading
        $fileStream = fopen($this->file, 'r');

        $skipHeader = true;
        while (($line = fgetcsv($fileStream, 1000, '|')) !== false) {
            if ($skipHeader) {
                // Skip the header
                $skipHeader = false;
                continue;
            }
            //$line = str_getcsv($line[0],'|');
            Nomenclator::firstOrCreate(
                [
                    'code'          => $line[$fieldMap['COD']],
                ],
                [
                    'code'          => $line[$fieldMap['COD']],
                    'description'   => $line[$fieldMap['DENUMIRE']],
                ]
            );
        }

        fclose($fileStream);

        // Delete the file after import
        unlink($this->file);
    }
}
