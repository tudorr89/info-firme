<?php

namespace App\Services;

use \Itrack\Anaf\Client as AnafClient;
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
            $anaf = new AnafClient();
            foreach ($companies as $company) {
                $date = date('Y-m-d');
                $anaf->addCif($company->cui, $date);
            }
            $results = $anaf->get();
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
            $result = $result->getParserData();
            $cui = $result['date_generale']['cui'];
            if ($companyCuiMap->has($cui)) {
                $company = Company::where('cui', $cui)->first();
                dispatch(new AnafEnrichmentJob($company, $result));
            }
        }
    }
}