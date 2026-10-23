<?php

namespace App\Jobs;

use App\Models\Caen;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class CaenDefinitionImportJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 900;

    public int $tries = 3;

    public function __construct(private string $file)
    {
        //
    }

    /**
     * CSV structure: SECTIUNEA^SUBSECTIUNEA^DIVIZIUNEA^GRUPA^CLASA^DENUMIRE^VERSIUNE_CAEN
     */
    public function handle(): void
    {
        $fileStream = fopen($this->file, 'r');

        if ($fileStream === false) {
            Log::error("Cannot open CAEN definition file: {$this->file}");

            return;
        }

        $skipHeader = true;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $class = trim($line[4] ?? '');
            $name = trim($line[5] ?? '');

            if ($class === '' || $name === '') {
                continue;
            }

            Caen::updateOrCreate(
                [
                    'class' => (int) $class,
                    'version' => (int) trim($line[6] ?? ''),
                ],
                [
                    'section' => $line[0] ?? null,
                    'subsection' => $line[1] ?? null,
                    'division' => (int) trim($line[2] ?? '') ?: null,
                    'group' => (int) trim($line[3] ?? '') ?: null,
                    'name' => $name,
                ]
            );
        }

        fclose($fileStream);
    }
}
