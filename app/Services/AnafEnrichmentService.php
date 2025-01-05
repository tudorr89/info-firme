<?php

namespace App\Services;

use App\Processors\AnafProcessor;
use App\Models\Company;
use App\Jobs\AnafEnrichmentJob;

class AnafEnrichmentService
{
    protected $batchSize;

    public function __construct()
    {
        $this->batchSize = env('ANAF_BATCH_SIZE', 100);
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
            sleep(env('ENRICH_TIMEOUT', 60));
        });
    }

    private function updateCompaniesFromAnafResults($companies, $results)
    {
        $companyCuiMap = $companies->keyBy('cui');
        foreach ($results as $result) {
            $cui = $result['date_generale']['cui'];
            if ($companyCuiMap->has($cui)) {
                $company = Company::where('cui', $cui)->first();
                dispatch(new AnafEnrichmentJob($company, $result));
            }
        }
    }
}
