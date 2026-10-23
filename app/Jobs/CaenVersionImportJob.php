<?php

namespace App\Jobs;

use App\Models\CaenVersion;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CaenVersionImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 3;

    public function __construct(private string $file)
    {
        //
    }

    /**
     * CSV structure: COD^DESCRIERE
     */
    public function handle(): void
    {
        $fileStream = fopen($this->file, 'r');

        if ($fileStream === false) {
            Log::error("Cannot open CAEN version file: {$this->file}");

            return;
        }

        $skipHeader = true;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $code = trim($line[0] ?? '');
            $name = trim($line[1] ?? '');

            if ($code === '' || $name === '') {
                continue;
            }

            CaenVersion::updateOrCreate(
                ['code' => (int) $code],
                ['name' => $name]
            );
        }

        fclose($fileStream);
    }
}
