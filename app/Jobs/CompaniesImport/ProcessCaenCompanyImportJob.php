<?php

namespace App\Jobs\CompaniesImport;

use App\Models\CaenCompany;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Horizon\Contracts\Silenced;

class ProcessCaenCompanyImportJob implements ShouldQueue, Silenced
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
        CaenCompany::firstOrCreate(
            [
                'registration'      => $this->dataLine[$this->fieldMap['COD_INMATRICULARE']],
            ],
            [
                'registration'      => $this->dataLine[$this->fieldMap['COD_INMATRICULARE']],
                'code'              => $this->dataLine[$this->fieldMap['COD_CAEN_AUTORIZAT']],
                'version'           => $this->dataLine[$this->fieldMap['VER_CAEN_AUTORIZAT']],
            ]
        );
    }
}
