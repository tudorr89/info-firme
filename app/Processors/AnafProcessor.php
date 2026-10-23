<?php

namespace App\Processors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnafProcessor
{
    const URL = 'https://webservicesp.anaf.ro/api/PlatitorTvaRest/v9/tva';

    public static function getCompanyInfo($data)
    {
        $arr = [];
        foreach ($data as $cui) {
            $arr[] = [
                'cui' => $cui,
                'data' => date('Y-m-d'),
            ];
        }
        try {
            $response = Http::withHeaders(['Content-Type: application/json'])->withBody(json_encode($arr))->post(self::URL);
            $body = json_decode($response->body(), true);
            if ($body['message'] == 'SUCCESS' && $body['cod'] == 200) {
                return $body['found'];
            }

            return false;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return false;
        }

    }
}
