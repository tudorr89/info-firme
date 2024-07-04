<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnafEnrichmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Company $company, public array $results)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->company->info->update([
            'phone'                 => $this->results['date_generale']['telefon'],
            'fax'                   => $this->results['date_generale']['fax'],
            'postalCode'            => $this->results['date_generale']['codPostal'],
            'document'              => $this->results['date_generale']['act'],
            'registrationDate'      => $this->results['date_generale']['data_inregistrare'],
            'registrationStatus'    => $this->results['date_generale']['stare_inregistrare'],
            'activityCode'          => $this->results['date_generale']['cod_CAEN'],
            'bankAccount'           => $this->results['date_generale']['iban'],
            'roInvoiceStatus'       => $this->results['date_generale']['statusRO_e_Factura'],
            'authorityName'         => $this->results['date_generale']['organFiscalCompetent'],
            'formOfOwnership'       => $this->results['date_generale']['forma_de_proprietate'],
            'organizationalForm'    => $this->results['date_generale']['forma_organizare'],
            'legalForm'             => $this->results['date_generale']['forma_juridica'],
        ]);
    }
}
