<?php

namespace App\Console\Commands;

use App\Jobs\AnafEnrichmentJob;
use Illuminate\Console\Command;
use App\Models\Company;
use App\Services\AnafRequestService;

class AnafEnrichmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anaf:enrich';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enrich companies with data from ANAF.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(env('ENRICH') == 'true') {
            Company::whereRelation('info', 'registrationDate', null)->chunkById(2, function ($companies) {
                $anaf = new \Itrack\Anaf\Client();
                foreach ($companies as $company) {
                    $date = date('Y-m-d');
                    $anaf->addCif($company->cui, $date);
                }
                $results = $anaf->get();
                if($results) {
                    foreach($results as $result) {
                        foreach($companies as $company) {
                            if($company->cui == $result['date_generale']['cui']) {
                                //dispatch(new AnafEnrichmentJob($company, $result));
                                $company->info->update([
                                    'phone'                 => $result['date_generale']['telefon'],
                                    'fax'                   => $result['date_generale']['fax'],
                                    'postalCode'            => $result['date_generale']['codPostal'],
                                    'document'              => $result['date_generale']['act'],
                                    'registrationDate'      => $result['date_generale']['data_inregistrare'],
                                    'registrationStatus'    => $result['date_generale']['stare_inregistrare'],
                                    'activityCode'          => $result['date_generale']['cod_CAEN'],
                                    'bankAccount'           => $result['date_generale']['iban'],
                                    'roInvoiceStatus'       => $result['date_generale']['statusRO_e_Factura'],
                                    'authorityName'         => $result['date_generale']['organFiscalCompetent'],
                                    'formOfOwnership'       => $result['date_generale']['forma_de_proprietate'],
                                    'organizationalForm'    => $result['date_generale']['forma_organizare'],
                                    'legalForm'             => $result['date_generale']['forma_juridica'],
                                ]);
                            }
                        }
                    }
                    die;
                }
                sleep(env('ENRICH_TIMEOUT', '60'));
            });
        }
    }
}
