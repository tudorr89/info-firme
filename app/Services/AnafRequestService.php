<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;

class AnafRequestService
{
    public static function getCompaniesData($cifs)
    {
        $response = Http::withBody(json_encode($cifs))
                        ->post('https://webservicesp.anaf.ro/PlatitorTvaRest/api/v8/ws/tva');
        if($response->failed() || $response->serverError() || $response->json()['found'] == false){
            return false;
        }

        return $response->json()['found'];
    }
}