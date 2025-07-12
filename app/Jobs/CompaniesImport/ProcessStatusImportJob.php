<?php

namespace App\Jobs\CompaniesImport;

use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Horizon\Contracts\Silenced;

class ProcessStatusImportJob implements ShouldQueue, Silenced
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private array $dataLine, private array $fieldMap)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Status::firstOrCreate(
            [
                'registration'      => $this->dataLine[$this->fieldMap['COD_INMATRICULARE']],
            ],
            [
                'registration'      => $this->dataLine[$this->fieldMap['COD_INMATRICULARE']],
                'status'            => $this->dataLine[$this->fieldMap['COD']],
            ]
        );
    }
}
