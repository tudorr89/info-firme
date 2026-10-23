<?php

namespace App\Services;

use App\Jobs\AnafEnrichmentJob;
use App\Models\Company;
use App\Processors\AnafProcessor;

class AnafEnrichmentService
{
    protected $batchSize;

    public function __construct()
    {
        $this->batchSize = (int) config('services.anaf.batch_size');
    }

    public function enrich()
    {
        Company::whereRelation('info', 'registrationDate', null)->chunkById($this->batchSize, function ($companies) {
            $cuis = [];
            foreach ($companies as $company) {
                $cuis[] = $company->cui;
            }
            $results = AnafProcessor::getCompanyInfo($cuis);
            if ($results) {
                $this->updateCompaniesFromAnafResults($companies, $results);
            }
            sleep((int) config('services.anaf.timeout'));
        });
    }

    private function updateCompaniesFromAnafResults($companies, $results)
    {
        $companyCuiMap = $companies->keyBy('cui');
        foreach ($results as $result) {
            $cui = $result['date_generale']['cui'];
            if ($companyCuiMap->has($cui)) {
                $company = Company::where('cui', $cui)->orderBy('registration_date', 'desc')->first();
                dispatch(new AnafEnrichmentJob($company, $result));
            }
        }
    }
}
