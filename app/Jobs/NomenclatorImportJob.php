<?php

namespace App\Jobs;

use App\Models\Nomenclator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NomenclatorImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    public array $backoff = [1, 5, 10];

    public function __construct(private string $file)
    {
        //
    }

    public function handle(): void
    {
        $fileStream = fopen($this->file, 'r');

        if ($fileStream === false) {
            Log::error("Cannot open nomenclator file: {$this->file}");

            return;
        }

        $skipHeader = true;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $code = trim($line[0] ?? '');
            $description = trim($line[1] ?? '');

            if ($code === '' || $description === '') {
                continue;
            }

            Nomenclator::updateOrCreate(
                ['code' => (int) $code],
                ['description' => $description]
            );
        }

        fclose($fileStream);
    }
}
