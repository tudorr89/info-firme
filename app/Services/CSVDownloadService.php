<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CSVDownloadService
{
    public static function download(): void
    {
        $months = [
          1 =>  'ianuarie',
          4 =>  'aprilie',
          7 =>  'iulie',
          10 => 'octombrie'
        ];

        $now = Carbon::now();

        if(!in_array($now->month, array_keys($months))) {
            return;
        }

        if(count(Storage::files('csv')) > 0) {
            return;
        }

        $request = HTTP::get('https://data.gov.ro/api/3/action/package_show?id=firme-inregistrate-la-registrul-comertului-pana-la-data-de-07-'.$months[$now->month].'-'.$now->year);

        $info = $request->json();

        if($info['success'] == false) {
            return;
        }

        foreach($info['result']['resources'] as $resource) {
            if($resource['format'] == 'CSV') {
                $csv = HTTP::timeout(300)->get($resource['url']);
                $csv = $csv->body();
                Storage::put('csv/'.basename($resource['url']), $csv);
            }
        }

        return;
    }
}
