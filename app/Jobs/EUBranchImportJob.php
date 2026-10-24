<?php

namespace App\Jobs;

use App\Jobs\Batches\ProcessEUBranchBatchImport;
use App\Models\EUBranch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EUBranchImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    private const BATCH_SIZE = 4000;

    public function __construct(private string $file)
    {
        //
    }

    public function handle(): void
    {
        $fieldMap = [
            'COD_INMATRICULARE' => 0,
            'TIP_UNITATE' => 1,
            'DENUMIRE_SUCURSALA' => 2,
            'EUID' => 3,
            'COD_FISCAL' => 4,
            'TARA' => 5,
        ];

        // A full snapshot replaces the whole table: the ONRC files are not
        // sorted by registration, so rows of the same company are spread over
        // several batches and a per-batch delete would drop them again.
        EUBranch::truncate();

        $fileStream = fopen($this->file, 'r');
        $batch = [];
        $skipHeader = true;
        $batchCount = 0;

        while (($line = fgetcsv($fileStream, 0, '^')) !== false) {
            if ($skipHeader) {
                $skipHeader = false;

                continue;
            }

            $batch[] = $line;

            if (count($batch) >= self::BATCH_SIZE) {
                dispatch(new ProcessEUBranchBatchImport($batch, $fieldMap, ++$batchCount));
                $batch = [];
            }
        }

        if (! empty($batch)) {
            dispatch(new ProcessEUBranchBatchImport($batch, $fieldMap, ++$batchCount));
        }

        fclose($fileStream);
    }
}
