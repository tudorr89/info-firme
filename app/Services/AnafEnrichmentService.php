<?php

namespace App\Services;

use App\Models\Company;

class AnafEnrichmentService
{
    public static function enrich(): void
    {
        $companies = Company::whereRelation('info', 'registrationDate', null)
                            ->chunk(3, function ($companies) {
                                foreach ($companies as $company) {
                                    $cifs[] = [
                                        'cui' => $company->cui,
                                        'data' => date('Y-m-d'),
                                    ];
                                }

                                $results = AnafRequestService::getCompaniesData($cifs);
                                if($results) {
                                   foreach($companies as $company) {
                                        if($company->cui == $results['date_generale'][$company->cui])
                                        $company->info->update([
                                            'registrationDate' => $results[$company->cui]['dataInceputTva'],
                                            'deregistrationDate' => $results[$company->cui]['dataSfarsitTva'],
                                        ]);
                                   }
                                }
                                dd(AnafRequestService::getCompaniesData($cifs));
                            });
    }
}