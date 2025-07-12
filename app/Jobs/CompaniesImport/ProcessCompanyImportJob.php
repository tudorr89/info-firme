<?php

namespace App\Jobs\CompaniesImport;

use App\Models\Address;
use App\Models\Company;
use App\Models\Info;
use App\Models\Status;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;
use Laravel\Horizon\Contracts\Silenced;

class ProcessCompanyImportJob implements ShouldQueue, Silenced
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $company = Company::firstOrCreate(
            [
                'cui'       => $this->dataLine[$this->fieldMap['CUI']],
            ],
            [
                'name'      => $this->dataLine[$this->fieldMap['DENUMIRE']],
                'cui'       => $this->dataLine[$this->fieldMap['CUI']],
                'reg_com'   => $this->dataLine[$this->fieldMap['COD_INMATRICULARE']],
                'euid'      => $this->dataLine[$this->fieldMap['EUID']],
                'type'      => $this->dataLine[$this->fieldMap['FORMA_JURIDICA']],
                'registration_date' => date('Y-m-d h:i:s', strtotime($this->dataLine[$this->fieldMap['DATA_INMATRICULARE']])),
            ]
        );

        try {
            Address::updateOrCreate(
                [
                    'company_id'    => $company->id,
                ],
                [
                    'company_id'    => $company->id,
                    'country'       => $this->dataLine[$this->fieldMap['ADR_TARA']],
                    'city'          => $this->dataLine[$this->fieldMap['ADR_LOCALITATE']],
                    'county'        => $this->dataLine[$this->fieldMap['ADR_JUDET']],
                    'street'        => $this->dataLine[$this->fieldMap['ADR_DEN_STRADA']],
                    'number'        => $this->dataLine[$this->fieldMap['ADR_DEN_NR_STRADA']],
                    'block'         => $this->dataLine[$this->fieldMap['ADR_BLOC']],
                    'scara'         => $this->dataLine[$this->fieldMap['ADR_SCARA']],
                    'floor'         => $this->dataLine[$this->fieldMap['ADR_ETAJ']],
                    'apartment'     => $this->dataLine[$this->fieldMap['ADR_APARTAMENT']],
                    'postalCode'    => $this->dataLine[$this->fieldMap['ADR_COD_POSTAL']],
                    'sector'        => $this->dataLine[$this->fieldMap['ADR_SECTOR']],
                    'additional'    => $this->dataLine[$this->fieldMap['ADR_COMPLETARE']],
                ]
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
    }
}
